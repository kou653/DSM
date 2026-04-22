<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiController extends Controller
{
    public function analyze(Request $request)
    {
        $request->validate([
            'context' => 'required|string',
            'data' => 'required|array',
        ]);

        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            return response()->json(['message' => 'Clé API Gemini non configurée.'], 500);
        }

        $contextName = $request->input('context');
        $data = $request->input('data');

        $prompt = "Tu es un assistant IA expert en agronomie et gestion de projets de reforestation (Dronek). Ton rôle est d'analyser les données structurées suivantes et de fournir un résumé clair, des insights et des recommandations si possible.\n\n";
        $prompt .= "Contexte de la page : " . $contextName . "\n";
        $prompt .= "Données :\n" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
        $prompt .= "Ne pose jamais de question a la fin.";
        $models = ['gemini-3.1-flash-lite-preview', 'gemini-3-flash-preview', 'gemini-2.5-flash'];
        $response = null;
        $lastError = null;

        foreach ($models as $modelName) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key=" . $apiKey;

            for ($attempt = 1; $attempt <= 2; $attempt++) {
                try {
                    $response = Http::post($url, [
                        'contents' => [
                            [
                                'parts' => [['text' => $prompt]]
                            ]
                        ]
                    ]);

                    if ($response->successful()) {
                        break 2; // Succès ! On sort des deux boucles.
                    }

                    // On ne réessaie que si c'est une surcharge (503)
                    if ($response->status() !== 503) {
                        break;
                    }
                } catch (\Exception $e) {
                    // Erreur de connexion, on laisse le retry faire son travail
                }

                if ($attempt < 2) {
                    sleep(1); // Petite pause avant de réessayer
                }
            }

            $lastError = $response ? $response->json() : null;
        }

        if ($response && $response->successful()) {
            $responseData = $response->json();
            $text = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? 'Aucune réponse générée.';
            return response()->json(['result' => $text]);
        }

        $errorData = $lastError ?? $response?->json() ?? [];
        $errorMessage = $errorData['error']['message'] ?? 'Erreur inconnue de l\'API Gemini.';
        $status = $response?->status() ?? 500;

        $finalStatus = ($status === 405) ? 502 : (($status >= 400 && $status < 600) ? $status : 500);

        return response()->json([
            'message' => 'Erreur lors de la communication avec l\'API Gemini : ' . $errorMessage,
            'details' => $errorData,
            'status' => $status,
        ], $finalStatus);
    }
}
