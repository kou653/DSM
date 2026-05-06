<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->validator->errors()->all();
            return response()->json([
                'message' => 'Certains champs sont mal renseignés : ' . implode(' ', $errors),
                'errors' => $e->validator->errors(),
            ], 422);
        });

        $exceptions->render(function (\Illuminate\Database\QueryException $e) {
            // Détection des erreurs de contrainte d'intégrité (clés étrangères, doublons, etc.)
            if ($e->getCode() == '23000' || $e->getCode() == '23503') {
                return response()->json([
                    'message' => 'Impossible d\'effectuer cette action car cette donnée est liée à d\'autres éléments (contrainte de sécurité).',
                ], 400);
            }

            // Pour les autres erreurs SQL en production, on reste vague mais poli
            return response()->json([
                "message" => "Il y a un champ qui n'a pas été rempli. Veuillez vérifier les informations saisies.",
            ], 500);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return response()->json([
                'message' => 'La ressource demandée n\'existe pas.',
            ], 404);
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'message' => 'Vous n\'avez pas les droits nécessaires pour effectuer cette action.',
            ], 403);
        });
    })->create();
