<?PHP 
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\ChatController;
use App\Http\Middleware\ValidateEmbedToken;

Route::middleware('auth')->post('/websites', [WebsiteController::class, 'store']);
Route::middleware('embed.token')->post('/chat', [ChatController::class, 'message']);
Route::middleware([ValidateEmbedToken::class])->group(function () {
    Route::get('/widget/config', [ChatController::class, 'config']);
    Route::post('/chat', [ChatController::class, 'message']);
});