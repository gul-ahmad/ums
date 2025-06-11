<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get("/user", function (Request $request) {
    return $request->user();
})->middleware("auth:sanctum");

Route::group(["prefix" => "auth"], function () {
    Route::post("login", [AuthController::class, "login"]);
    Route::post("register", [AuthController::class, "register"]);

    Route::group(["middleware" => "auth:sanctum"], function () {
        Route::get("logout", [AuthController::class, "logout"]);
        Route::get("user", [AuthController::class, "user"]);
    });
});
