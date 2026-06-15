<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\ConversationController;
use App\Http\Controllers\Admin\WebsiteController as AdminWebsiteController;
use App\Http\Controllers\Admin\KnowledgePageController;
use App\Http\Controllers\Admin\AgentPresenceController;
use App\Http\Controllers\Admin\LiveChatController;

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

    Route::get('/admin/websites/{website}/knowledge', [KnowledgePageController::class, 'index'])
    ->name('admin.websites.knowledge.index');

    Route::get('/admin/websites/{website}/knowledge/create', [KnowledgePageController::class, 'create'])
        ->name('admin.websites.knowledge.create');

    Route::post('/admin/websites/{website}/knowledge', [KnowledgePageController::class, 'store'])
        ->name('admin.websites.knowledge.store');

    Route::delete('/admin/websites/{website}/knowledge/delete-all', [KnowledgePageController::class, 'deleteAllForWebsite'])
        ->name('admin.websites.knowledge.deleteAll');

    Route::get('/admin/knowledge-pages/{knowledgePage}', [KnowledgePageController::class, 'show'])
        ->name('admin.knowledge.show');

    Route::get('/admin/knowledge-pages/{knowledgePage}/edit', [KnowledgePageController::class, 'edit'])
        ->name('admin.knowledge.edit');

    Route::patch('/admin/knowledge-pages/{knowledgePage}', [KnowledgePageController::class, 'update'])
        ->name('admin.knowledge.update');

    Route::delete('/admin/knowledge-pages/{knowledgePage}', [KnowledgePageController::class, 'destroy'])
        ->name('admin.knowledge.destroy');

    Route::post('/admin/knowledge-pages/{knowledgePage}/index', [KnowledgePageController::class, 'indexPage'])
        ->name('admin.knowledge.indexPage');

    Route::post('/admin/knowledge-pages/{knowledgePage}/toggle-active', [KnowledgePageController::class, 'toggleActive'])
        ->name('admin.knowledge.toggleActive');
    Route::post('/admin/agent/online', [AgentPresenceController::class, 'online'])
    ->name('admin.agent.online');

    Route::post('/admin/agent/offline', [AgentPresenceController::class, 'offline'])
    ->name('admin.agent.offline');

    Route::get('/live-chat', [LiveChatController::class, 'index'])
        ->name('live-chat.index');

    Route::get('/live-chat/{conversation}', [LiveChatController::class, 'show'])
        ->name('live-chat.show');

    Route::post('/live-chat/{conversation}/take', [LiveChatController::class, 'take'])
        ->name('live-chat.take');

    Route::post('/live-chat/{conversation}/message', [LiveChatController::class, 'sendMessage'])
        ->name('live-chat.message');

    Route::post('/live-chat/{conversation}/close', [LiveChatController::class, 'close'])
        ->name('live-chat.close');

});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::middleware('auth')->post('/websites', [WebsiteController::class, 'store']);

require __DIR__.'/auth.php';
