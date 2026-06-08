<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\ConversationController;
use App\Http\Controllers\Admin\WebsiteController as AdminWebsiteController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/admin/leads', [LeadController::class, 'index'])
        ->name('admin.leads.index');

    Route::get('/admin/leads/{lead}', [LeadController::class, 'show'])
        ->name('admin.leads.show');

    Route::patch('/admin/leads/{lead}/status', [LeadController::class, 'updateStatus'])
        ->name('admin.leads.updateStatus');

    Route::get('/admin/conversations', [ConversationController::class, 'index'])
        ->name('admin.conversations.index');

    Route::get('/admin/conversations/{conversation}', [ConversationController::class, 'show'])
        ->name('admin.conversations.show');

    Route::get('/admin/websites', [AdminWebsiteController::class, 'index'])
    ->name('admin.websites.index');

    Route::get('/admin/websites/create', [AdminWebsiteController::class, 'create'])
        ->name('admin.websites.create');

    Route::post('/admin/websites', [AdminWebsiteController::class, 'store'])
        ->name('admin.websites.store');

    Route::get('/admin/websites/{website}', [AdminWebsiteController::class, 'show'])
        ->name('admin.websites.show');

    Route::get('/admin/websites/{website}/edit', [AdminWebsiteController::class, 'edit'])
        ->name('admin.websites.edit');

    Route::patch('/admin/websites/{website}', [AdminWebsiteController::class, 'update'])
        ->name('admin.websites.update');

    Route::post('/admin/websites/{website}/regenerate-token', [AdminWebsiteController::class, 'regenerateToken'])
        ->name('admin.websites.regenerateToken');

    Route::post('/admin/websites/{website}/index-knowledge', [AdminWebsiteController::class, 'indexKnowledge'])
        ->name('admin.websites.indexKnowledge');

});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::middleware('auth')->post('/websites', [WebsiteController::class, 'store']);

require __DIR__.'/auth.php';
