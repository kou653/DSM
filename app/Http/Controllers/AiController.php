<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Models\EvolutionImage;

class AiController extends Controller
{
    public function analyze(Request $request)
    {
        $request->validate([
            'context' => 'required|string',
            'data' => 'required|array',
        ]);

        $apiKey = config('services.gemini.key');
        if (!$apiKey) {
            return response()->json(['message' => 'Clé API Gemini non configurée.'], 500);
        }

        $contextName = $request->input('context');
        $data = $request->input('data');

        $prompt = "Tu es un assistant IA expert en agronomie et gestion de projets de reforestation (Dronek). Ton rôle est d'analyser les données structurées suivantes et de fournir un résumé clair et des insights pertinents.\n\n";
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
            'status'  => $status,
        ], $finalStatus);
    }

    public function diagnosePlant(Request $request)
    {
        $request->validate([
            'image_base64' => 'required|string',
            'mime_type'    => 'required|string|in:image/jpeg,image/png,image/webp,image/gif',
        ]);

        $apiKey = config('services.gemini.key');
        if (!$apiKey) {
            return response()->json(['message' => 'Clé API Gemini non configurée.'], 500);
        }

        $imageBase64 = $request->input('image_base64');
        $mimeType    = $request->input('mime_type');

        $prompt = "Tu es un expert agronome spécialisé en reforestation tropicale. Analyse cette photo d'un plant et évalue son état de santé.\n\n"
            . "Réponds UNIQUEMENT avec un objet JSON valide, sans balises markdown, sans explication supplémentaire, dans ce format exact :\n"
            . "{\"etat\": \"vivant\" | \"risque_eleve\" | \"mort\", \"justification\": \"Explication courte en français (max 2 phrases).\"}\n\n"
            . "Critères :\n"
            . "- \"vivant\" : feuilles vertes, tige droite, plant en bonne santé visible\n"
            . "- \"risque_eleve\" : jaunissement, flétrissement partiel, signes de stress hydrique ou maladie\n"
            . "- \"mort\" : tige sèche, feuilles nécrosées ou absentes, plant visiblement mort\n\n"
            . "Si l'image ne montre pas un plant ou est illisible, réponds : {\"etat\": \"inconnu\", \"justification\": \"Image non reconnaissable ou ne montre pas un plant.\"}";

        $models    = ['gemini-2.5-flash', 'gemini-3-flash-preview', 'gemini-3.1-flash-lite-preview'];
        $response  = null;
        $lastError = null;

        foreach ($models as $modelName) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key={$apiKey}";

            for ($attempt = 1; $attempt <= 2; $attempt++) {
                try {
                    $response = Http::post($url, [
                        'contents' => [
                            [
                                'parts' => [
                                    [
                                        'inline_data' => [
                                            'mime_type' => $mimeType,
                                            'data'      => $imageBase64,
                                        ],
                                    ],
                                    ['text' => $prompt],
                                ],
                            ],
                        ],
                        'generationConfig' => [
                            'temperature'     => 0.1,
                            'maxOutputTokens' => 512,
                        ],
                    ]);

                    if ($response->successful()) break 2;
                    if ($response->status() !== 503) break;
                } catch (\Exception $e) {
                    // réseau — on retente
                }

                if ($attempt < 2) sleep(1);
            }

            $lastError = $response ? $response->json() : null;
        }

        if ($response && $response->successful()) {
            $raw  = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
            
            // Log raw response for debugging
            \Illuminate\Support\Facades\Log::info("Gemini Raw Response: " . $raw);

            // Extract JSON if it's wrapped in markdown
            if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $raw, $matches)) {
                $raw = $matches[1];
            } else {
                $raw = trim($raw);
            }

            $data = json_decode($raw, true);
            \Illuminate\Support\Facades\Log::info("Gemini Parsed Data: ", $data ?? []);

            if (!$data || !isset($data['etat'])) {
                return response()->json([
                    'etat'          => 'inconnu',
                    'justification' => 'La réponse de l\'IA n\'a pas pu être interprétée. Réponse brute : ' . $raw,
                ]);
            }

            return response()->json($data);
        }

        $errorData    = $lastError ?? $response?->json() ?? [];
        $errorMessage = $errorData['error']['message'] ?? 'Erreur inconnue de l\'API Gemini.';
        $status       = $response?->status() ?? 500;

        return response()->json([
            'message' => 'Erreur lors du diagnostic : ' . $errorMessage,
        ], $status >= 400 && $status < 600 ? $status : 500);
    }

    public function diagnoseEvolutionImage(EvolutionImage $image)
    {
        $apiKey = config('services.gemini.key');
        if (!$apiKey) {
            return response()->json(['message' => 'Clé API Gemini non configurée.'], 500);
        }

        // Résoudre le chemin physique selon le système de stockage utilisé
        $rawValue = $image->getRawOriginal('url');
        $fileName = basename($rawValue);

        // Nouveau système : public/uploads/evolutions/
        $path = public_path('uploads/evolutions/' . $fileName);

        // Ancien système fallback : storage/app/public/evolutions/
        if (!file_exists($path)) {
            $path = storage_path('app/public/evolutions/' . $fileName);
        }

        if (!file_exists($path)) {
            return response()->json(['message' => 'Fichier image introuvable sur le serveur.'], 404);
        }

        $imageBase64 = base64_encode(file_get_contents($path));
        $mimeType = mime_content_type($path);

        $prompt = "Tu es un expert agronome spécialisé en reforestation tropicale. Analyse cette photo d'un ou plusieurs plants dans leur parcelle d'évolution.\n\n"
            . "Réponds UNIQUEMENT avec un objet JSON valide, sans balises markdown, sans explication supplémentaire, dans ce format exact :\n"
            . "{\"etat\": \"vivant\" | \"risque_eleve\" | \"mort\" | \"inconnu\", \"justification\": \"Explication courte en français (max 2 phrases).\", \"recommandation\": \"Recommandation ou précaution à prendre (max 2 phrases).\"}\n\n"
            . "Critères :\n"
            . "- \"vivant\" : feuilles vertes, tige droite, plant en bonne santé visible\n"
            . "- \"risque_eleve\" : jaunissement, flétrissement partiel, signes de stress hydrique ou maladie\n"
            . "- \"mort\" : tige sèche, feuilles nécrosées ou absentes, plant visiblement mort\n\n"
            . "Fournis obligatoirement une recommandation utile pour aider la plante ou alerter d'un problème.";

        $models = ['gemini-1.5-flash', 'gemini-1.5-pro', 'gemini-3.1-flash-lite-preview', 'gemini-3-flash-preview', 'gemini-2.5-flash'];
        $response = null;
        $lastError = null;

        foreach ($models as $modelName) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key={$apiKey}";

            for ($attempt = 1; $attempt <= 2; $attempt++) {
                try {
                    $response = Http::post($url, [
                        'contents' => [
                            [
                                'parts' => [
                                    [
                                        'inline_data' => [
                                            'mime_type' => $mimeType,
                                            'data'      => $imageBase64,
                                        ],
                                    ],
                                    ['text' => $prompt],
                                ],
                            ],
                        ],
                        'generationConfig' => [
                            'temperature'     => 0.1,
                            'maxOutputTokens' => 512,
                        ],
                    ]);

                    if ($response->successful()) break 2;
                    if ($response->status() !== 503) break;
                } catch (\Exception $e) {
                    // réseau
                }

                if ($attempt < 2) sleep(1);
            }

            $lastError = $response ? $response->json() : null;
        }

        if ($response && $response->successful()) {
            $raw  = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
            \Illuminate\Support\Facades\Log::info("Gemini Raw Response (Evolution): " . $raw);

            if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $raw, $matches)) {
                $raw = $matches[1];
            } else {
                $raw = trim($raw);
            }

            $data = json_decode($raw, true);

            if (!$data || !isset($data['etat'])) {
                return response()->json([
                    'etat'          => 'inconnu',
                    'justification' => 'La réponse de l\'IA n\'a pas pu être interprétée. Réponse brute : ' . $raw,
                    'recommandation' => '',
                ]);
            }

            return response()->json($data);
        }

        $errorData    = $lastError ?? $response?->json() ?? [];
        $errorMessage = $errorData['error']['message'] ?? 'Erreur inconnue de l\'API Gemini.';
        $status       = $response?->status() ?? 500;

        return response()->json([
            'message' => 'Erreur lors du diagnostic : ' . $errorMessage,
        ], $status >= 400 && $status < 600 ? $status : 500);
    }
}

