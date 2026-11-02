<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\MessengerChannelController as Messenger;
Route::middleware(['auth', 'verified', 'active.user'])->prefix('admin/channels/facebook')->name('admin.channels.facebook.')->group(function () {
    Route::get('connect', [Messenger::class, 'create'])->name('create');
    Route::post('connect', [Messenger::class, 'start'])->middleware('throttle:10,1')->name('start');
    Route::get('callback', [Messenger::class, 'callback'])->name('callback');
    Route::get('pages/{grant}', [Messenger::class, 'pages'])->name('pages');
    Route::post('pages/{grant}', [Messenger::class, 'connect'])->name('connect');
    Route::get('{channel}/edit', [Messenger::class, 'edit'])->name('edit');
    Route::patch('{channel}', [Messenger::class, 'update'])->name('update');
    Route::post('{channel}/health', [Messenger::class, 'health'])->middleware('throttle:10,1')->name('health');
    Route::delete('{channel}', [Messenger::class, 'disconnect'])->name('disconnect');
});
