<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WebsiteController;

use App\Http\Controllers\PublicStorefrontController;
use App\Http\Controllers\PublicCustomerAuthController;
use App\Http\Controllers\PublicCustomerAccountController;
use App\Http\Controllers\PublicServiceOrderController;
use App\Http\Controllers\PublicTrackingController;
use App\Http\Controllers\PublicCheckoutController;

use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\KanbanController;
use App\Http\Controllers\Tenant\ServiceOrderController;
use App\Http\Controllers\Tenant\TimeTrackingController;
use App\Http\Controllers\Tenant\ItemController;
use App\Http\Controllers\Tenant\ScheduleController;
use App\Http\Controllers\Tenant\CustomizationController;
use App\Http\Controllers\Tenant\CheckoutOrderController;

use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\TenantManagementController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\ConsumerPrivacyController;

Route::get('/', [WebsiteController::class, 'index'])->name('public.store.index');
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::group(['prefix' => 'painel', 'middleware' => ['auth', 'tenant_context']], function () {
    Route::get('/cobranca', [DashboardController::class, 'billing'])->name('tenant.billing.index');

    // RF-10: Dashboard de métricas gerenciais, faturamento e alertas de estoque[cite: 31].
    Route::get('/', [DashboardController::class, 'index'])->name('tenant.dashboard');
    Route::get('/plano/bloqueado', [DashboardController::class, 'blocked'])->name('tenant.plan.blocked');

    // RF-06: Quadro Kanban Operacional[cite: 21].
    Route::get('/kanban', [KanbanController::class, 'index'])->middleware('feature:kanban')->name('tenant.kanban.index');
    // Movimentação de cartões entre colunas[cite: 22].
    Route::patch('/kanban/mover', [KanbanController::class, 'updateCardPosition'])->middleware('feature:kanban')->name('tenant.kanban.move');
    Route::post('/kanban/colunas', [KanbanController::class, 'storeColumn'])->middleware('feature:kanban')->name('tenant.kanban.columns.store');
    Route::patch('/kanban/colunas/{column}', [KanbanController::class, 'updateColumn'])->middleware('feature:kanban')->name('tenant.kanban.columns.update');
    Route::delete('/kanban/colunas/{column}', [KanbanController::class, 'destroyColumn'])->middleware('feature:kanban')->name('tenant.kanban.columns.destroy');

    // Gerenciamento interno de Ordens de Serviço (CRUD)
    Route::resource('/ordens-servico', ServiceOrderController::class)->middleware('feature:kanban');
    // Vinculação de insumos na OS e cálculo de margem de lucro[cite: 28, 29].
    Route::post('/ordens-servico/{id}/itens', [ServiceOrderController::class, 'attachItems'])->middleware('feature:inventory')->name('tenant.os.attach_items');

    // RF-07: Time Tracking (Play/Pause no cronômetro do técnico)[cite: 23].
    Route::post('/ordens-servico/{id}/time-tracking/start', [TimeTrackingController::class, 'start'])->middleware('feature:time_tracking')->name('tenant.tracking.start');
    Route::post('/ordens-servico/{id}/time-tracking/stop', [TimeTrackingController::class, 'stop'])->middleware('feature:time_tracking')->name('tenant.tracking.stop');
    Route::post('/ordens-servico/{id}/time-tracking/manual', [TimeTrackingController::class, 'storeManual'])->middleware('feature:time_tracking')->name('tenant.tracking.manual');

    // RF-08: Gestão de Insumos e Produtos[cite: 24].
    // CRUD completo para itens, definição de uso interno ou flag de venda pública[cite: 25, 26, 27].
    Route::resource('/estoque', ItemController::class)->middleware('feature:inventory');

    Route::get('/pedidos', [CheckoutOrderController::class, 'index'])->middleware('feature:catalog')->name('tenant.checkout-orders.index');
    Route::get('/pedidos/{order}', [CheckoutOrderController::class, 'show'])->middleware('feature:catalog')->name('tenant.checkout-orders.show');
    Route::patch('/pedidos/{order}/status', [CheckoutOrderController::class, 'updateStatus'])->middleware('feature:catalog')->name('tenant.checkout-orders.status');

    // RF-09: Agenda e Cronograma de entregas[cite: 30].
    Route::get('/agenda', [ScheduleController::class, 'index'])->middleware('feature:schedule')->name('tenant.schedule.index');
    Route::patch('/agenda/ordens-servico/{id}', [ScheduleController::class, 'updateServiceOrderSchedule'])->middleware('feature:schedule')->name('tenant.schedule.service_orders.update');
    Route::post('/agenda/eventos', [ScheduleController::class, 'storeEvent'])->middleware('feature:schedule')->name('tenant.schedule.events.store');
    Route::patch('/agenda/eventos/{event}', [ScheduleController::class, 'updateEvent'])->middleware('feature:schedule')->name('tenant.schedule.events.update');
    Route::delete('/agenda/eventos/{event}', [ScheduleController::class, 'destroyEvent'])->middleware('feature:schedule')->name('tenant.schedule.events.destroy');

    // RF-13: Painel de Customização de Layout do microssite[cite: 32].
    Route::get('/customizacao', [CustomizationController::class, 'edit'])->middleware('feature:customization_basic')->name('tenant.customization.edit');
    // Atualização de banners, textos, Instagram e iFrame do Google Maps[cite: 33].
    Route::put('/customizacao', [CustomizationController::class, 'update'])->middleware('feature:customization_basic')->name('tenant.customization.update');
});

Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'is_superadmin']], function () {

    // RF-11.1: CRUD de Planos (Bronze, Prata, Ouro) e definição de valores e limites[cite: 36].
    Route::resource('/planos', PlanController::class);

    // RF-11.3: Listagem de todas as assistências cadastradas e status de assinatura[cite: 43].
    Route::get('/assistencias', [TenantManagementController::class, 'index'])->name('admin.tenants.index');
    Route::get('/assistencias/create', [TenantManagementController::class, 'create'])->name('admin.tenants.create');
    Route::post('/assistencias', [TenantManagementController::class, 'store'])->name('admin.tenants.store');
    Route::get('/assistencias/{id}', [TenantManagementController::class, 'show'])->name('admin.tenants.show');

    // RF-17: LGPD e gestão de contas de consumidores.
    Route::get('/consumidores', [ConsumerPrivacyController::class, 'index'])->name('admin.consumers.index');
    Route::get('/consumidores/{consumer}', [ConsumerPrivacyController::class, 'show'])->name('admin.consumers.show');
    Route::get('/consumidores/{consumer}/exportar', [ConsumerPrivacyController::class, 'export'])->name('admin.consumers.export');
    Route::delete('/consumidores/{consumer}', [ConsumerPrivacyController::class, 'destroy'])->name('admin.consumers.destroy');

    // Modulo de suspensão de acesso por atraso no gateway[cite: 44].
    Route::post('/assistencias/{id}/suspender', [SubscriptionController::class, 'suspend'])->name('admin.subscriptions.suspend');
    Route::post('/assistencias/{id}/reativar', [SubscriptionController::class, 'reactivate'])->name('admin.subscriptions.reactivate');
    Route::post('/assistencias/{id}/cancelar', [SubscriptionController::class, 'cancel'])->name('admin.subscriptions.cancel');
    Route::patch('/assistencias/{id}/cobranca', [SubscriptionController::class, 'updateBilling'])->name('admin.subscriptions.billing');

    // RF-11.4: Configuração de dias de Trial (Período de testes)[cite: 45].
    Route::patch('/assistencias/{id}/trial', [SubscriptionController::class, 'updateTrialDays'])->name('admin.subscriptions.trial');
});

Route::group(['prefix' => '{slug}'], function () {

    // RF-01, RF-02, RF-12: Renderização da página pública, banners, mapas e catálogo[cite: 3, 4, 13, 14, 18].
    Route::get('/', [PublicStorefrontController::class, 'index'])->name('public.store.index');

    // RF-14: autenticação unificada do cliente com contexto da assistência acessada.
    Route::get('/login', [PublicCustomerAuthController::class, 'showLoginForm'])->name('public.customer.login');
    Route::post('/login', [PublicCustomerAuthController::class, 'login'])->name('public.customer.login.submit');
    Route::post('/login/rapido', [PublicCustomerAuthController::class, 'requestMagicCode'])->name('public.customer.magic.request');
    Route::get('/login/codigo', [PublicCustomerAuthController::class, 'showMagicCodeForm'])->name('public.customer.magic.verify');
    Route::post('/login/codigo', [PublicCustomerAuthController::class, 'verifyMagicCode'])->name('public.customer.magic.verify.submit');
    Route::get('/cadastro', [PublicCustomerAuthController::class, 'showRegisterForm'])->name('public.customer.register');
    Route::post('/cadastro', [PublicCustomerAuthController::class, 'register'])->name('public.customer.register.submit');

    // RF-16: painel restrito do cliente com dados, equipamentos, pedidos e endereços.
    Route::get('/minha-conta', [PublicCustomerAccountController::class, 'index'])->name('public.account.index');
    Route::patch('/minha-conta/perfil', [PublicCustomerAccountController::class, 'updateProfile'])->name('public.account.profile.update');
    Route::post('/minha-conta/enderecos', [PublicCustomerAccountController::class, 'storeAddress'])->name('public.account.addresses.store');
    Route::patch('/minha-conta/enderecos/{address}', [PublicCustomerAccountController::class, 'updateAddress'])->name('public.account.addresses.update');
    Route::delete('/minha-conta/enderecos/{address}', [PublicCustomerAccountController::class, 'destroyAddress'])->name('public.account.addresses.destroy');

    // RF-03: Portal de abertura de chamados pelo cliente[cite: 6].
    Route::get('/chamados/novo', [PublicServiceOrderController::class, 'create'])->name('public.os.create');
    // Envio dos dados do aparelho, sintomas e fotos[cite: 7].
    Route::post('/chamados', [PublicServiceOrderController::class, 'store'])->name('public.os.store');

    // RF-04: Área de acompanhamento por CPF ou número do chamado[cite: 8].
    Route::get('/acompanhamento', [PublicTrackingController::class, 'index'])->name('public.tracking.index');
    // Visualização da coluna do Kanban em tempo real[cite: 9].
    Route::post('/acompanhamento/buscar', [PublicTrackingController::class, 'show'])->name('public.tracking.show');
    // Aprovação ou recusa de orçamentos enviados[cite: 9].
    Route::patch('/acompanhamento/{os_id}/orcamento', [PublicTrackingController::class, 'updateBudgetStatus'])->name('public.tracking.budget');
    Route::post('/acompanhamento/{os_id}/anexos', [PublicTrackingController::class, 'storeAttachment'])->name('public.tracking.attachments.store');

    // RF-05: Checkout e Split de Pagamento automatizado[cite: 10, 11].
    Route::get('/checkout', [PublicCheckoutController::class, 'cart'])->name('public.checkout.cart');
    Route::post('/checkout/carrinho', [PublicCheckoutController::class, 'addProduct'])->name('public.checkout.cart.add');
    Route::post('/checkout', [PublicCheckoutController::class, 'process'])->name('public.checkout.process');
    Route::post('/checkout/orcamento/{os_id}', [PublicCheckoutController::class, 'createBudgetOrder'])->name('public.checkout.budget');
    Route::get('/checkout/pedidos/{order}', [PublicCheckoutController::class, 'showOrder'])->name('public.checkout.order.show');
    Route::patch('/checkout/pedidos/{order}/cancelar', [PublicCheckoutController::class, 'cancelOrder'])->name('public.checkout.order.cancel');
});
