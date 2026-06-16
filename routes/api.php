<?PHP 
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\ChatController;
use App\Http\Middleware\ValidateEmbedToken;

Route::middleware('auth')->post('/websites', [WebsiteController::class, 'store']);
Route::middleware([ValidateEmbedToken::class])->group(function () {
    Route::get('/widget/config', [ChatController::class, 'config']);
    Route::post('/chat', [ChatController::class, 'message']);
    Route::get('/chat/history', [ChatController::class, 'history']);
    Route::post('/live/request', [ChatController::class, 'requestLiveAgent']);
});