<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use OpenAI\Laravel\Facades\OpenAI;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::get('/test-openai', function () {

    $response = OpenAI::responses()->create([
        'model' => 'gpt-4.1-mini',
        'input' => 'Say hello in one word'
    ]);

    return $response->output[0]->content[0]->text;
});

require __DIR__.'/auth.php';
