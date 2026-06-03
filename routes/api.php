<?PHP 
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\ChatController;

Route::middleware('auth')->post('/websites', [WebsiteController::class, 'store']);
Route::middleware('embed.token')->post('/chat', [ChatController::class, 'message']);