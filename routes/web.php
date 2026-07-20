<?php

use App\Http\Controllers\AiSettingsController;
use App\Http\Controllers\AiCreditsController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ConnectedAccountController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\KnowledgeBaseController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\MessageAttachmentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('landing');
Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/data-deletion', [LegalController::class, 'dataDeletion'])->name('legal.data-deletion');

Route::middleware(['guest', 'throttle:20,1'])->group(function () {
    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware('auth')->group(function () {
    Route::get('/onboarding/workspace', [WorkspaceController::class, 'create'])->name('onboarding.workspace');
    Route::post('/onboarding/workspace', [WorkspaceController::class, 'store'])->name('onboarding.workspace.store');
    Route::post('/workspace/switch', [WorkspaceController::class, 'switch'])->name('workspace.switch');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/team/invitations/{token}', [TeamController::class, 'accept'])->name('team.invitations.accept');
});

Route::middleware(['auth', 'verified', 'current.business'])
    ->prefix('dashboard')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/inbox', [InboxController::class, 'index'])->name('dashboard.inbox');
        Route::get('/inbox/pulse', [InboxController::class, 'pulse'])->name('dashboard.inbox.pulse');
        Route::post('/inbox/{conversation}/reply', [InboxController::class, 'reply'])->name('dashboard.inbox.reply');
        Route::post('/inbox/{conversation}/take-over', [InboxController::class, 'takeOver'])->name('dashboard.inbox.take-over');
        Route::post('/inbox/{conversation}/resume-ai', [InboxController::class, 'resumeAi'])->name('dashboard.inbox.resume-ai');
        Route::post('/inbox/{conversation}/close', [InboxController::class, 'close'])->name('dashboard.inbox.close');
        Route::delete('/inbox/{conversation}', [InboxController::class, 'destroy'])
            ->middleware('workspace.role:owner,admin')
            ->name('dashboard.inbox.destroy');
        Route::get('/attachments/{attachment}', [MessageAttachmentController::class, 'download'])->name('dashboard.attachments.download');
        Route::middleware('workspace.role:owner,admin')->group(function () {
            Route::get('/accounts', [ConnectedAccountController::class, 'index'])->name('dashboard.accounts');
            Route::post('/accounts/fake-connect', [ConnectedAccountController::class, 'fakeConnect'])->name('dashboard.accounts.fake-connect');
            Route::get('/accounts/gmail/redirect', [ConnectedAccountController::class, 'redirectToGmail'])->name('dashboard.accounts.gmail.redirect');
            Route::get('/accounts/gmail/callback', [ConnectedAccountController::class, 'handleGmailCallback'])->name('dashboard.accounts.gmail.callback');
            Route::post('/accounts/gmail/{account}/sync', [ConnectedAccountController::class, 'syncGmailAccount'])->name('dashboard.accounts.gmail.sync');
            Route::post('/accounts/telegram/connect', [ConnectedAccountController::class, 'connectTelegram'])->name('dashboard.accounts.telegram.connect');
            Route::post('/accounts/whatsapp/embedded-signup', [ConnectedAccountController::class, 'completeWhatsAppEmbeddedSignup'])->name('dashboard.accounts.whatsapp.embedded-signup');
            Route::post('/accounts/meta/development-connect', [ConnectedAccountController::class, 'connectMetaDevelopment'])->name('dashboard.accounts.meta.development-connect');
            Route::patch('/accounts/{account}/disconnect', [ConnectedAccountController::class, 'disconnect'])->name('dashboard.accounts.disconnect');
            Route::get('/ai-settings', [AiSettingsController::class, 'index'])->name('dashboard.ai-settings');
            Route::patch('/ai-settings', [AiSettingsController::class, 'update'])->name('dashboard.ai-settings.update');
            Route::get('/analytics', AnalyticsController::class)->name('dashboard.analytics');
            Route::get('/team', [TeamController::class, 'index'])->name('dashboard.team');
            Route::post('/team/invitations', [TeamController::class, 'invite'])->name('dashboard.team.invite');
            Route::patch('/team/members/{member}/role', [TeamController::class, 'updateRole'])->name('dashboard.team.members.role');
            Route::delete('/team/members/{member}', [TeamController::class, 'remove'])->name('dashboard.team.members.remove');
            Route::delete('/team/invitations/{invite}', [TeamController::class, 'cancelInvite'])->name('dashboard.team.invitations.cancel');
        });
        Route::get('/ai-credits', AiCreditsController::class)->middleware('workspace.role:owner')->name('dashboard.ai-credits');
        Route::middleware('workspace.role:owner,admin')->group(function () {
            Route::get('/knowledge-base', [KnowledgeBaseController::class, 'index'])->name('dashboard.knowledge-base');
            Route::post('/knowledge-base/faqs', [KnowledgeBaseController::class, 'storeFaq'])->name('dashboard.knowledge-base.faqs.store');
            Route::patch('/knowledge-base/faqs/{faq}', [KnowledgeBaseController::class, 'updateFaq'])->name('dashboard.knowledge-base.faqs.update');
            Route::delete('/knowledge-base/faqs/{faq}', [KnowledgeBaseController::class, 'destroyFaq'])->name('dashboard.knowledge-base.faqs.destroy');
            Route::post('/knowledge-base/products', [KnowledgeBaseController::class, 'storeProduct'])->name('dashboard.knowledge-base.products.store');
            Route::patch('/knowledge-base/products/{product}', [KnowledgeBaseController::class, 'updateProduct'])->name('dashboard.knowledge-base.products.update');
            Route::delete('/knowledge-base/products/{product}', [KnowledgeBaseController::class, 'destroyProduct'])->name('dashboard.knowledge-base.products.destroy');
            Route::post('/knowledge-base/rules', [KnowledgeBaseController::class, 'storeRule'])->name('dashboard.knowledge-base.rules.store');
            Route::patch('/knowledge-base/rules/{rule}', [KnowledgeBaseController::class, 'updateRule'])->name('dashboard.knowledge-base.rules.update');
            Route::delete('/knowledge-base/rules/{rule}', [KnowledgeBaseController::class, 'destroyRule'])->name('dashboard.knowledge-base.rules.destroy');
            Route::post('/knowledge-base/saved-replies', [KnowledgeBaseController::class, 'storeSavedReply'])->name('dashboard.knowledge-base.saved-replies.store');
            Route::patch('/knowledge-base/saved-replies/{savedReply}', [KnowledgeBaseController::class, 'updateSavedReply'])->name('dashboard.knowledge-base.saved-replies.update');
            Route::delete('/knowledge-base/saved-replies/{savedReply}', [KnowledgeBaseController::class, 'destroySavedReply'])->name('dashboard.knowledge-base.saved-replies.destroy');
        });
        Route::middleware('workspace.role:owner')->group(function () {
            Route::get('/settings', [SettingsController::class, 'index'])->name('dashboard.settings');
            Route::patch('/settings/business', [SettingsController::class, 'updateBusiness'])->name('dashboard.settings.business.update');
            Route::delete('/settings/workspace', [SettingsController::class, 'destroyWorkspace'])->name('dashboard.settings.workspace.destroy');
        });
    });

require __DIR__.'/auth.php';
