<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;

Route::post('/signup', [ApiController::class,'signup']);
Route::post('/login', [ApiController::class,'login']);
Route::post('/check-googleid/{id}', [ApiController::class,'googleid_check']);
Route::get('/account-deactivate', [ApiController::class,'account_deactivate']);