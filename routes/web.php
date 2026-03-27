<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PhotoController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

Route::resource('photos', PhotoController::class);

Route::delete('/photos/{photo}', [PhotoController::class, 'destroy'])
    ->middleware('auth')
    ->name('photos.destroy');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/photos/{photo}', [PhotoController::class, 'show'])
    ->middleware('auth')
    ->name('photos.show');
    
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';

Route::get('/test-cpp', function () {
    $cmd = '/var/www/html/cpp/removebg input.jpg output.png';

    // 実行結果を受け取る
    $output = [];
    $return_var = 0;

    exec($cmd, $output, $return_var);

    return [
        'command' => $cmd,
        'output' => $output,
        'return_code' => $return_var,
    ];
});

Route::get('/run-cpp', function () {
    return view('run_cpp');
});

Route::post('/run-cpp/exec', function () {
    $cmd = '/var/www/html/cpp/removebg input.jpg output.png';

    $output = [];
    $return_var = 0;

    exec($cmd, $output, $return_var);

    return view('run_cpp', [
        'command' => $cmd,
        'output' => $output,
        'return_code' => $return_var,
    ]);
});

Route::get('/debug', function () {
    \Log::info('Debug route accessed');
    return 'OK';
});
