<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Loan;
use App\Http\Controllers\PartPayment;
use App\Http\Middleware\LoanExists;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get("/", function() {
    return "Welcome to soul society!";
});

Route::prefix("/loan")->group(function() {
    Route::post('/', [Loan::class, 'create']);
    Route::prefix("/{loan_id}")
    ->middleware(LoanExists::class)
    ->group(function() {
        Route::get("/", [Loan::class, 'read']);
    });    
});

Route::prefix("/part-payment/{loan_id}")
->middleware(LoanExists::class)
->group(function() {
    Route::post("/", [PartPayment::class, 'create']);
    Route::get("/", [PartPayment::class, 'read']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
