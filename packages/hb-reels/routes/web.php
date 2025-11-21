<?php

use HbReels\EventReelGenerator\Controllers\ReelController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ReelController::class, 'index'])->name('index');
Route::post('/generate', [ReelController::class, 'generate'])->name('generate');

