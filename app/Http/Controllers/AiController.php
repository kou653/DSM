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
        $prompt .= "Réponds en français, avec bienveillance, de manière structurée en utilisant le Markdown (titres, listes à puces) et sois concis mais utile.";

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey;

        $response = Http::post($url, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ]);

        if ($response->successful()) {
            $responseData = $response->json();
            $text = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? 'Aucune réponse générée.';
            return response()->json(['result' => $text]);
        }

        return response()->json([
            'message' => 'Erreur lors de la communication avec l\'API Gemini.',
            'error' => $response->json(),
            'status' => $response->status(),
        ], 500);
    }
}
