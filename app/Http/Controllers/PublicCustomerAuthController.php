<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\MagicLoginCode;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\MagicLoginCodeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PublicCustomerAuthController extends Controller
{
    public function showLoginForm(Request $request, string $slug)
    {
        $tenant = $this->publicTenant($slug);

        return view('public.auth.login', [
            'tenant' => $tenant,
            'intended' => $this->safeIntendedPath($tenant, $request->query('intended')),
        ]);
    }

    public function login(Request $request, string $slug)
    {
        $tenant = $this->publicTenant($slug);
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'As credenciais informadas estão incorretas.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = Auth::user();

        if (! $user->isCustomer()) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('public.customer.login', $tenant->slug)
                ->withErrors(['email' => 'Use uma conta de cliente para acessar a área pública.']);
        }

        $this->rememberTenantContext($request, $tenant);
        $this->ensureCustomerForTenant($tenant, $user);

        return redirect()
            ->intended(route('public.account.index', $tenant->slug))
            ->with('success', 'Login realizado. Exibindo o histórico desta assistência.');
    }

    public function showRegisterForm(Request $request, string $slug)
    {
        $tenant = $this->publicTenant($slug);

        return view('public.auth.register', compact('tenant'));
    }

    public function register(Request $request, string $slug)
    {
        $tenant = $this->publicTenant($slug);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', 'min:8'],
            'cpf' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'customer',
            'tenant_id' => null,
        ]);

        $this->ensureCustomerForTenant($tenant, $user, [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'cpf' => $validated['cpf'] ?? null,
            'phone' => $validated['phone'] ?? null,
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $this->rememberTenantContext($request, $tenant);

        return redirect()
            ->route('public.account.index', $tenant->slug)
            ->with('success', 'Conta criada. Este login poderá ser usado em outras assistências.');
    }

    public function requestMagicCode(Request $request, string $slug)
    {
        $tenant = $this->publicTenant($slug);
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'intended' => ['nullable', 'string', 'max:255'],
        ]);

        $email = Str::lower($validated['email']);
        $code = (string) random_int(100000, 999999);
        $intended = $this->safeIntendedPath($tenant, $validated['intended'] ?? null);

        MagicLoginCode::where('tenant_id', $tenant->id)
            ->where('email', $email)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        MagicLoginCode::create([
            'tenant_id' => $tenant->id,
            'email' => $email,
            'code_hash' => Hash::make($code),
            'intended_path' => $intended,
            'expires_at' => now()->addMinutes(10),
            'ip_address' => $request->ip(),
        ]);

        Notification::route('mail', $email)
            ->notify(new MagicLoginCodeNotification($tenant, $code));

        return redirect()
            ->route('public.customer.magic.verify', $tenant->slug)
            ->withInput(['email' => $email])
            ->with('success', 'Enviamos um código de acesso para '.$email.'.');
    }

    public function showMagicCodeForm(Request $request, string $slug)
    {
        $tenant = $this->publicTenant($slug);

        return view('public.auth.magic-code', compact('tenant'));
    }

    public function verifyMagicCode(Request $request, string $slug)
    {
        $tenant = $this->publicTenant($slug);
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'code' => ['required', 'digits:6'],
        ]);

        $email = Str::lower($validated['email']);
        $magicCode = MagicLoginCode::where('tenant_id', $tenant->id)
            ->where('email', $email)
            ->whereNull('consumed_at')
            ->latest()
            ->first();

        if (! $magicCode?->isValid() || ! Hash::check($validated['code'], $magicCode->code_hash)) {
            return back()
                ->withErrors(['code' => 'Código inválido ou expirado. Solicite um novo código.'])
                ->withInput(['email' => $email]);
        }

        $user = User::where('email', $email)->first();

        if ($user && ! $user->isCustomer()) {
            return back()
                ->withErrors(['email' => 'Este e-mail pertence a uma conta interna. Use o login do painel.'])
                ->withInput(['email' => $email]);
        }

        if (! $user) {
            $user = User::create([
                'name' => $this->nameFromEmail($email),
                'email' => $email,
                'password' => Str::password(32),
                'role' => 'customer',
                'tenant_id' => null,
            ]);
        }

        $magicCode->update(['consumed_at' => now()]);

        $this->ensureCustomerForTenant($tenant, $user, [
            'name' => $user->name,
            'email' => $user->email,
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $this->rememberTenantContext($request, $tenant);

        return redirect($magicCode->intended_path ?: route('public.account.index', $tenant->slug))
            ->with('success', 'Acesso rápido confirmado.');
    }

    private function rememberTenantContext(Request $request, Tenant $tenant): void
    {
        $request->session()->put('public_tenant_id', $tenant->id);
        $request->session()->put('public_tenant_slug', $tenant->slug);
    }

    private function ensureCustomerForTenant(Tenant $tenant, User $user, array $attributes = []): Customer
    {
        $customer = Customer::where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $customer && filled($attributes['cpf'] ?? null)) {
            $customer = Customer::where('tenant_id', $tenant->id)
                ->where('cpf', $attributes['cpf'])
                ->first();
        }

        if (! $customer) {
            $customer = Customer::where('tenant_id', $tenant->id)
                ->where('email', $user->email)
                ->first();
        }

        $data = [
            'user_id' => $user->id,
            'name' => $attributes['name'] ?? $user->name,
            'email' => $attributes['email'] ?? $user->email,
            'phone' => $attributes['phone'] ?? null,
        ];

        if (array_key_exists('cpf', $attributes)) {
            $data['cpf'] = $attributes['cpf'];
        }

        if ($customer) {
            $customer->update(array_filter($data, fn ($value) => $value !== null));

            return $customer;
        }

        return Customer::create(array_merge($data, [
            'tenant_id' => $tenant->id,
            'cpf' => $attributes['cpf'] ?? null,
        ]));
    }

    private function publicTenant(string $slug): Tenant
    {
        $tenant = Tenant::where('slug', $slug)->firstOrFail();

        $tenant->enforceSubscriptionLifecycle();

        abort_unless($tenant->isPubliclyAvailable(), 404);

        return $tenant;
    }

    private function safeIntendedPath(Tenant $tenant, ?string $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        if (str_contains($path, "\n") || str_contains($path, "\r")) {
            return null;
        }

        return str_starts_with($path, '/'.$tenant->slug) ? $path : null;
    }

    private function nameFromEmail(string $email): string
    {
        $name = Str::of($email)
            ->before('@')
            ->replace(['.', '_', '-'], ' ')
            ->title()
            ->trim();

        return $name->isNotEmpty() ? (string) $name : 'Cliente';
    }
}
