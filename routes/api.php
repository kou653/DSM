<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CooperativeController;
use App\Http\Controllers\EspeceController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\ParcelleController;
use App\Http\Controllers\PlantController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::get('/projects/{project}', [ProjectController::class, 'show']);
    Route::get('/projects/{project}/dashboard', [ProjectController::class, 'dashboard']);
    Route::put('/projects/{project}', [ProjectController::class, 'update']);
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);

    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);

    Route::get('/parcelles', [ParcelleController::class, 'index']);
    Route::post('/parcelles', [ParcelleController::class, 'store']);
    Route::get('/parcelles/{parcelle}', [ParcelleController::class, 'show']);
    Route::put('/parcelles/{parcelle}', [ParcelleController::class, 'update']);
    Route::delete('/parcelles/{parcelle}', [ParcelleController::class, 'destroy']);

    Route::get('/especes', [EspeceController::class, 'index']);
    Route::post('/especes', [EspeceController::class, 'store']);
    Route::get('/especes/{espece}', [EspeceController::class, 'show']);
    Route::put('/especes/{espece}', [EspeceController::class, 'update']);
    Route::delete('/especes/{espece}', [EspeceController::class, 'destroy']);

    Route::get('/plants', [PlantController::class, 'index']);
    Route::post('/plants', [PlantController::class, 'store']);
    Route::get('/plants/{plant}', [PlantController::class, 'show']);
    Route::put('/plants/{plant}', [PlantController::class, 'update']);
    Route::delete('/plants/{plant}', [PlantController::class, 'destroy']);

    Route::get('/cooperatives', [CooperativeController::class, 'index']);
    Route::post('/cooperatives', [CooperativeController::class, 'store']);
    Route::get('/cooperatives/{cooperative}', [CooperativeController::class, 'show']);
    Route::put('/cooperatives/{cooperative}', [CooperativeController::class, 'update']);
    Route::delete('/cooperatives/{cooperative}', [CooperativeController::class, 'destroy']);

    Route::get('/monitorings', [MonitoringController::class, 'index']);
    Route::post('/monitorings', [MonitoringController::class, 'store']);
    Route::get('/monitorings/{monitoring}', [MonitoringController::class, 'show']);
    Route::put('/monitorings/{monitoring}', [MonitoringController::class, 'update']);
    Route::delete('/monitorings/{monitoring}', [MonitoringController::class, 'destroy']);

    Route::get('/projects/{project}/monitoring-summary', [MonitoringController::class, 'projectSummary']);
    Route::get('/projects/{project}/monitoring-map', [MonitoringController::class, 'mapSummary']);
});
