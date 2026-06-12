<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function (Request $request) {
    if ($request->user()) {
        if ($request->user()->two_factor_enabled && !session(config('google2fa.session_var'))) {
            return redirect()->route('two-factor.challenge');
        }

        return redirect()->route('dashboard');
    }

    return Inertia::render('Auth/Login', [
        'canResetPassword' => Route::has('password.request'),
        'status' => session('status'),
    ]);
});

Route::middleware([Authenticate::class, EnsureEmailIsVerified::class])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    /* Módulo Entidades/Empresas */
    Route::get('/entities', [App\Http\Controllers\EntityController::class, 'index'])->name('entities.index');
    Route::post('/entities', [App\Http\Controllers\EntityController::class, 'store'])->name('entities.store');
    Route::put('/entities/{entity}', [App\Http\Controllers\EntityController::class, 'update'])->name('entities.update');
    Route::delete('/entities/{entity}', [App\Http\Controllers\EntityController::class, 'destroy'])->name('entities.destroy');
    Route::get('/entities/{entity}', [App\Http\Controllers\EntityController::class, 'show'])->name('entities.show');

    /* Módulo Pessoas */
    Route::get('/people', [App\Http\Controllers\PersonController::class, 'index'])->name('people.index');
    Route::post('/people', [App\Http\Controllers\PersonController::class, 'store'])->name('people.store');
    Route::put('/people/{person}', [App\Http\Controllers\PersonController::class, 'update'])->name('people.update');
    Route::delete('/people/{person}', [App\Http\Controllers\PersonController::class, 'destroy'])->name('people.destroy');
    Route::get('/people/{person}', [App\Http\Controllers\PersonController::class, 'show'])->name('people.show');

    /* Módulo Negócios */
    Route::get('/deals', [App\Http\Controllers\DealController::class, 'index'])->name('deals.index');
    Route::post('/deals', [App\Http\Controllers\DealController::class, 'store'])->name('deals.store');
    Route::put('/deals/{deal}', [App\Http\Controllers\DealController::class, 'update'])->name('deals.update');
    Route::patch('/deals/{deal}/stage', [App\Http\Controllers\DealController::class, 'updateStage'])->name('deals.updateStage');
    Route::delete('/deals/{deal}', [App\Http\Controllers\DealController::class, 'destroy'])->name('deals.destroy');
    
    /* Detalhe do negócio */
    Route::get('/deals/{deal}', [App\Http\Controllers\DealController::class, 'show'])->name('deals.show');
    Route::post('/deals/{deal}/activities', [App\Http\Controllers\DealController::class, 'storeActivity'])->name('deals.activities.store');
    Route::patch('/deals/{deal}/activities/{activity}/complete', [App\Http\Controllers\DealController::class, 'completeActivity'])->name('deals.activities.complete');
    Route::post('/deals/{deal}/proposals', [App\Http\Controllers\DealController::class, 'uploadProposal'])->name('deals.proposals.upload');
    Route::post('/deals/{deal}/proposals/{proposal}/send', [App\Http\Controllers\DealController::class, 'sendProposal'])->name('deals.proposals.send');

    /* Cancelar follow-up automático */
    Route::patch('/deals/{deal}/followup/cancel', [App\Http\Controllers\DealController::class, 'cancelFollowUp'])->name('deals.followup.cancel');

    /* Módulo Calendário */
    Route::get('/calendar', [App\Http\Controllers\CalendarEventController::class, 'index'])->name('calendar.index');
    Route::post('/calendar', [App\Http\Controllers\CalendarEventController::class, 'store'])->name('calendar.store');
    Route::put('/calendar/{calendarEvent}', [App\Http\Controllers\CalendarEventController::class, 'update'])->name('calendar.update');
    Route::delete('/calendar/{calendarEvent}', [App\Http\Controllers\CalendarEventController::class, 'destroy'])->name('calendar.destroy');

    /* Relatórios */
    Route::get('/reports', [App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [App\Http\Controllers\ReportController::class, 'exportProducts'])->name('reports.export');

    /* Produtos */
    Route::get('/products', [App\Http\Controllers\ProductController::class, 'index'])->name('products.index');
    Route::post('/products', [App\Http\Controllers\ProductController::class, 'store'])->name('products.store');
    Route::put('/products/{product}', [App\Http\Controllers\ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [App\Http\Controllers\ProductController::class, 'destroy'])->name('products.destroy');

    /* Produtos nos negócios */
    Route::post('/deals/{deal}/products', [App\Http\Controllers\DealController::class, 'addProduct'])->name('deals.addProduct');
    Route::delete('/deals/{deal}/products/{product}', [App\Http\Controllers\DealController::class, 'removeProduct'])->name('deals.removeProduct');

    /* Automações */
    Route::get('/automations', [App\Http\Controllers\AutomationController::class, 'index'])->name('automations.index');
    Route::post('/automations', [App\Http\Controllers\AutomationController::class, 'store'])->name('automations.store');
    Route::put('/automations/{automationRule}', [App\Http\Controllers\AutomationController::class, 'update'])->name('automations.update');
    Route::delete('/automations/{automationRule}', [App\Http\Controllers\AutomationController::class, 'destroy'])->name('automations.destroy');
    Route::patch('/automations/{automationRule}/toggle', [App\Http\Controllers\AutomationController::class, 'toggle'])->name('automations.toggle');
    Route::post('/automations/{automationRule}/run', [App\Http\Controllers\AutomationController::class, 'run'])->name('automations.run');

    /* Formulários públicos */
    Route::get('/lead-forms', [App\Http\Controllers\LeadFormController::class, 'index'])->name('lead-forms.index');
    Route::post('/lead-forms', [App\Http\Controllers\LeadFormController::class, 'store'])->name('lead-forms.store');
    Route::put('/lead-forms/{leadForm}', [App\Http\Controllers\LeadFormController::class, 'update'])->name('lead-forms.update');
    Route::delete('/lead-forms/{leadForm}', [App\Http\Controllers\LeadFormController::class, 'destroy'])->name('lead-forms.destroy');
    Route::get('/lead-forms/{leadForm}/submissions', [App\Http\Controllers\LeadFormController::class, 'submissions'])->name('lead-forms.submissions');

    /* AI Chat */
    Route::get('/chat', [App\Http\Controllers\AiChatController::class, 'index'])->name('chat.index');
    Route::post('/chat/send', [App\Http\Controllers\AiChatController::class, 'send'])->name('chat.send');
    Route::delete('/chat/history', [App\Http\Controllers\AiChatController::class, 'clearHistory'])->name('chat.clearHistory');

    /* Notificações */
    Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [App\Http\Controllers\NotificationController::class, 'markRead'])->name('notifications.read');
    Route::patch('/notifications/read-all', [App\Http\Controllers\NotificationController::class, 'markAllRead'])->name('notifications.readAll');

    Route::get('/settings', [App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
    
    /* Perfil do utilizador */
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/password', [App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profile', [App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::delete('/profile/photo', [App\Http\Controllers\ProfileController::class, 'destroyPhoto'])->name('profile.photo.destroy');

    /* 2FA */
    Route::get('/profile/2fa/setup', [App\Http\Controllers\ProfileController::class, 'setupTwoFactor'])->name('profile.2fa.setup');
    Route::post('/profile/2fa/enable', [App\Http\Controllers\ProfileController::class, 'enableTwoFactor'])->name('profile.2fa.enable');
    Route::post('/profile/2fa/disable', [App\Http\Controllers\ProfileController::class, 'disableTwoFactor'])->name('profile.2fa.disable');
});

/* Formulário público — sem autenticação */
Route::get('/f/{slug}', [App\Http\Controllers\LeadFormController::class, 'show'])->name('lead-forms.public');
Route::post('/f/{slug}', [App\Http\Controllers\LeadFormController::class, 'submit'])->name('lead-forms.submit');

require __DIR__.'/auth.php';
