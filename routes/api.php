<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CooperativeController;
use App\Http\Controllers\EspeceController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\ParcelleController;
use App\Http\Controllers\PlantController;
use App\Http\Controllers\ProjetController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EvolutionImageController;
use App\Http\Controllers\ObjectifController;
use Illuminate\Support\Facades\Route;

// Authentification
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);

    // Projets
    Route::get('/projets', [ProjetController::class, 'index']);
    Route::post('/projets', [ProjetController::class, 'store']);
    Route::get('/projets/{projet}', [ProjetController::class, 'show']);
    Route::put('/projets/{projet}', [ProjetController::class, 'update']);
    Route::delete('/projets/{projet}', [ProjetController::class, 'destroy']);

    // Parcelles
    Route::get('/projets/{projet}/parcelles', [ParcelleController::class, 'index']); // Filtered by project via logic in Controller
    Route::get('/parcelles', [ParcelleController::class, 'index']);
    Route::get('/parcelles/{parcelle}', [ParcelleController::class, 'show']);
    Route::post('/projets/{projet}/parcelles', [ParcelleController::class, 'store']);
    Route::put('/parcelles/{parcelle}', [ParcelleController::class, 'update']);
    Route::delete('/parcelles/{parcelle}', [ParcelleController::class, 'destroy']);

    // Plants & Monitoring
    Route::get('/projets/{projet}/monitoring', [MonitoringController::class, 'projectMonitoring']);
    Route::get('/parcelles/{parcelle}/monitoring', [MonitoringController::class, 'parcelleMonitoring']);
    Route::get('/parcelles/{parcelle}/plants', [PlantController::class, 'index']);
    Route::post('/plants', [PlantController::class, 'store']);
    Route::patch('/plants/{plant}/status', [PlantController::class, 'updateStatus']);

    // Objectifs
    Route::get('/projets/{projet}/objectifs', [ObjectifController::class, 'index']);
    Route::put('/objectifs/{objectif}', [ObjectifController::class, 'update']);

    // Evolution Visuelle
    Route::get('/parcelles/{parcelle}/evolution', [EvolutionImageController::class, 'index']);
    Route::post('/parcelles/{parcelle}/evolution', [EvolutionImageController::class, 'store']);
    Route::delete('/evolution/{image}', [EvolutionImageController::class, 'destroy']);

    // Référentiels
    Route::get('/especes', [EspeceController::class, 'index']);
    Route::get('/cooperatives', [CooperativeController::class, 'index']);

    // Administration
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{user}', [UserController::class, 'update']);
});
