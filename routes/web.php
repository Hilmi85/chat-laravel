<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\TypingController;
use Illuminate\Support\Facades\Route;

Route::pattern('room', '[A-Z0-9]{8}');

Route::get('/', HomeController::class)->name('home');
Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');

Route::middleware('room.active')->group(function () {
    Route::get('/room/{room:code}', [RoomController::class, 'show'])->name('rooms.show');
    Route::post('/room/{room:code}/join', [RoomController::class, 'join'])->name('rooms.join');
    Route::post('/room/{room:code}/leave', [RoomController::class, 'leave'])->name('rooms.leave');

    Route::get('/room/{room:code}/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::post('/room/{room:code}/messages', [MessageController::class, 'store'])->name('messages.store');

    Route::post('/room/{room:code}/typing', [TypingController::class, 'store'])->name('typing.store');
    Route::get('/room/{room:code}/typing/status', [TypingController::class, 'status'])->name('typing.status');
});
