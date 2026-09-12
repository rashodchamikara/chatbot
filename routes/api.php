<?PHP 
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\ChatController;
use App\Http\Middleware\ValidateEmbedToken;
use App\Http\Controllers\Webhooks\WhatsAppWebhookController;

Route::middleware('auth')->post('/websites', [WebsiteController::class, 'store']);
Route::middleware([ValidateEmbedToken::class])->group(function () {
    Route::get('/widget/config', [ChatController::class, 'config']);
    Route::post('/chat', [ChatController::class, 'message']);
    Route::get('/chat/history', [ChatController::class, 'history']);
    Route::post('/live/request', [ChatController::class, 'requestLiveAgent']);
});

Route::get(
    '/webhooks/meta/whatsapp',
    [
        WhatsAppWebhookController::class,
        'verify',
    ]
)->name(
    'webhooks.meta.whatsapp.verify'
);

Route::post(
    '/webhooks/meta/whatsapp',
    [
        WhatsAppWebhookController::class,
        'receive',
    ]
)->name(
    'webhooks.meta.whatsapp.receive'
);