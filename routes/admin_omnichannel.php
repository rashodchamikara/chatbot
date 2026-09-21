<?php

use App\Http\Controllers\Admin\AiAgentController;
use App\Http\Controllers\Admin\ChannelController;
use App\Http\Controllers\Admin\KnowledgeHubController;
use App\Http\Controllers\Admin\WhatsAppConnectionController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\WhatsAppEmbeddedSignupController;

/*
|--------------------------------------------------------------------------
| Channel-first admin routes
|--------------------------------------------------------------------------
|
| Add this line near the bottom of routes/web.php:
|
|     require __DIR__ . '/admin_omnichannel.php';
|
*/
Route::middleware([
    'auth',
    EnsureUserIsActive::class,
])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/channels', [ChannelController::class, 'index'])
            ->name('channels.index');

        Route::prefix('channels/whatsapp')
            ->name('channels.whatsapp.')
            ->group(function (): void {
                Route::get('/create', [WhatsAppConnectionController::class, 'create'])
                    ->name('create');

                Route::post('/', [WhatsAppConnectionController::class, 'store'])
                    ->name('store');

                Route::get('/{channelConnection}/edit', [WhatsAppConnectionController::class, 'edit'])
                    ->name('edit');

                Route::put('/{channelConnection}', [WhatsAppConnectionController::class, 'update'])
                    ->name('update');

                Route::post('/{channelConnection}/health', [WhatsAppConnectionController::class, 'health'])
                    ->name('health');

                Route::delete('/{channelConnection}', [WhatsAppConnectionController::class, 'destroy'])
                    ->name('destroy');

                Route::post(
                        '/embedded-signup/complete',
                        [WhatsAppEmbeddedSignupController::class, 'complete']
                    )->name('embedded.complete');

                //fix merge issue
            });

        Route::get('/knowledge-hub', [KnowledgeHubController::class, 'index'])
            ->name('knowledge-hub.index');

        Route::get('/knowledge-hub/manual/create', [KnowledgeHubController::class, 'create'])
            ->name('knowledge-hub.create');

        Route::post('/knowledge-hub/manual', [KnowledgeHubController::class, 'store'])
            ->name('knowledge-hub.store');

        Route::post('/knowledge-hub/files', [KnowledgeHubController::class, 'storeFiles'])
            ->name('knowledge-hub.files.store');

        Route::post('/knowledge-hub/pages/{knowledgePage}/index', [KnowledgeHubController::class, 'indexPage'])
            ->name('knowledge-hub.pages.index');

        Route::patch('/knowledge-hub/pages/{knowledgePage}/toggle', [KnowledgeHubController::class, 'togglePage'])
            ->name('knowledge-hub.pages.toggle');

        Route::delete('/knowledge-hub/pages/{knowledgePage}', [KnowledgeHubController::class, 'destroyPage'])
            ->name('knowledge-hub.pages.destroy');

        Route::delete('/knowledge-hub/sources/{knowledgeSource}', [KnowledgeHubController::class, 'destroySource'])
            ->name('knowledge-hub.sources.destroy');

        Route::get('/ai-agents', [AiAgentController::class, 'index'])
            ->name('ai-agents.index');

        Route::get('/ai-agents/{aiAgent}/edit', [AiAgentController::class, 'edit'])
            ->name('ai-agents.edit');

        Route::put('/ai-agents/{aiAgent}', [AiAgentController::class, 'update'])
            ->name('ai-agents.update');
    });
