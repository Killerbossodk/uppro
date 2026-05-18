<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GeminiService
{
    protected $apiKey;
    protected $currentModel;

    protected $models = [
        'gemini-2.5-flash',
        'gemini-2.0-flash',
        'gemini-1.5-flash',
        'gemini-pro',
    ];

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->currentModel = Cache::get('gemini_working_model', 'gemini-2.5-flash');
    }

    private function callGemini($prompt, $maxTokens = 2000, $fileData = null)
    {
        if (!$this->apiKey) {
            return "🤖 Service IA non configuré.";
        }

        $modelsToTry = array_merge([$this->currentModel], array_diff($this->models, [$this->currentModel]));

        foreach ($modelsToTry as $model) {
            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

                $parts = [['text' => $prompt]];
                if ($fileData && isset($fileData['data']) && isset($fileData['mime_type'])) {
                    $parts[] = [
                        'inline_data' => [
                            'mime_type' => $fileData['mime_type'],
                            'data' => $fileData['data']
                        ]
                    ];
                }

                $response = Http::timeout(180)
                    ->withOptions(['verify' => false, 'connect_timeout' => 30])
                    ->post($url . '?key=' . $this->apiKey, [
                        'contents' => [['parts' => $parts]],
                        'generationConfig' => [
                            'maxOutputTokens' => $maxTokens,
                            'temperature' => 0.7,
                        ]
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                        Cache::put('gemini_working_model', $model, 3600);
                        $this->currentModel = $model;
                        return trim($data['candidates'][0]['content']['parts'][0]['text']);
                    }
                }

                if (in_array($response->status(), [503, 429])) {
                    Log::warning("Modèle {$model} surchargé ou quota dépassé.");
                    sleep(3);
                    continue;
                }
            } catch (\Exception $e) {
                Log::warning("Exception avec {$model}: " . $e->getMessage());
                continue;
            }
        }

        return "🤖 Le service IA est actuellement très sollicité. Veuillez réessayer dans quelques instants.";
    }

    public function callWithRetry($prompt, $maxTokens = 2000, $maxRetries = 3, $fileData = null)
    {
        for ($i = 0; $i < $maxRetries; $i++) {
            $result = $this->callGemini($prompt, $maxTokens, $fileData);
            if (!str_contains($result, 'surchargé') && !str_contains($result, 'sollicité')) {
                return $result;
            }
            if ($i < $maxRetries - 1) sleep(pow(2, $i + 1));
        }
        return $result;
    }

    public function chatWithContext($message, $role, $history = [], $fileData = null)
    {
        $systemPrompts = [
            // ... (keep existing prompts)
            'etudiant' => "Tu es un assistant pédagogique pour un ÉTUDIANT. Tes capacités : 
- Corriger l'orthographe et la grammaire
- Reformuler des textes en français académique
- Résumer des documents
- Analyser un rapport (points forts/faibles)
- Donner des conseils de rédaction
- Aider sur la gestion de projet

⚠️ Tu ne dois PAS :
- Suggérer une note sur 20
- Valider ou rejeter un rapport
- Gérer des étudiants

Si l'étudiant demande quelque chose d'interdit, réponds poliment : \"Je suis désolé, cette fonction est réservée aux professeurs.\"",

            'professeur' => "Tu es un assistant pour un PROFESSEUR. Tes capacités :
- Générer des idées de projets pédagogiques
- Analyser un rapport étudiant en profondeur
- Suggérer une note sur 20 avec justification
- Rédiger des commentaires d'évaluation
- Proposer des questions de soutenance
- Donner des conseils pédagogiques

⚠️ Tu ne dois PAS reformuler un texte comme si c'était le tien personnel. Si le professeur te demande de reformuler son propre texte, réponds : \"Je peux vous aider à analyser un rapport étudiant, mais pas à reformuler votre texte personnel.\"",

            'rup_projet' => "Tu es un assistant pour le RUP PROJET (responsable pédagogique). Tes capacités :
- Fournir des statistiques globales (projets, groupes, soutenances)
- Générer des rapports de suivi
- Identifier des projets similaires
- Aider à la planification des soutenances

⚠️ Tu ne gères pas les notes ni la constitution des jurys.",

            'rup_specialite' => "Tu es un assistant pour le RUP SPÉCIALITÉ. Tes capacités :
- Aider à constituer des jurys (suggérer une composition)
- Analyser les notes d'une filière
- Générer des synthèses par spécialité
- Suivre les soutenances à venir"
        ];

        $systemPrompt = $systemPrompts[$role] ?? "Tu es un assistant UP-PRO utile et professionnel.";

        $prompt = $systemPrompt . "\n\n";

        if (!empty($history)) {
            $prompt .= "Historique de la conversation :\n";
            foreach ($history as $msg) {
                $prompt .= "- " . ucfirst($msg['role'] ?? 'user') . ": " . ($msg['content'] ?? $msg['message'] ?? '') . "\n";
            }
            $prompt .= "\n";
        }

        $prompt .= "Utilisateur: $message\nAssistant: ";

        return $this->callWithRetry($prompt, 8000, 3, $fileData);
    }

    public function analyzeReport($content, $fileData = null)
    {
        $prompt = "Voici le contenu d'un rapport académique. Analyse-le de façon détaillée :\n\n";
        
        if ($content) {
            $content = substr($content, 0, 15000);
            $prompt .= "CONTENU DU RAPPORT (TEXTE EXTRAIT) :\n----------------------------------------\n{$content}\n----------------------------------------\n\n";
        }
        
        $prompt .= "Analyse le document (si fourni) ou le texte ci-dessus. 
        Structure ta réponse en utilisant du Markdown (gras, listes à puces, traits de séparation) pour une lecture claire et professionnelle.
        
        ### 📊 Résumé
        (2-3 phrases)
        
        ### ✅ Points Forts
        (Utilise une liste à puces)
        
        ### ⚠️ Points Faibles
        (Utilise une liste à puces)
        
        ### 💡 Suggestions d'amélioration
        (Utilise une liste à puces)
        
        ### 📝 Commentaire Général
        (Ton avis global en 2-3 phrases)
        
        Sois précis, constructif et professionnel. Utilise le gras pour mettre en évidence les termes importants.";
        
        return $this->callWithRetry($prompt, 4000, 3, $fileData);
    }

    public function suggestGrade($content, $fileData = null)
    {
        $prompt = "Tu es un professeur expert chargé d'évaluer ce rapport académique. 
        Analyse le contenu en profondeur et suggère une note sur 20 ainsi qu'une justification pédagogique constructive d'environ 2 à 3 phrases.

        Considère les critères suivants :
        - Clarté et structure du document
        - Rigueur technique et pertinence du contenu
        - Qualité de l'argumentation

        Réponds UNIQUEMENT au format JSON : {\"note\": 14.5, \"justification\": \"Ta justification détaillée ici...\"}\n\n";

        if ($content) {
            $content = substr($content, 0, 15000);
            $prompt .= "CONTENU DU RAPPORT :\n----------------------------------------\n{$content}\n----------------------------------------\n\n";
        }
        
        $response = $this->callWithRetry($prompt, 1500, 3, $fileData);
        
        // Nettoyage Markdown si présent
        $cleaned = preg_replace('/```(?:json)?\s*/i', '', $response);
        $cleaned = trim(str_replace('```', '', $cleaned));
        
        if (preg_match('/\{.*\}/s', $cleaned, $matches)) {
            $json = json_decode($matches[0], true);
            if ($json && isset($json['note'])) {
                return [
                    'note' => floatval($json['note']),
                    'justification' => $json['justification'] ?? ''
                ];
            }
        }
        
        // Fallback par regex si le JSON est mal formé mais la note est présente
        if (preg_match('/(\d{1,2}(?:\.\d)?)\s*\/\s*20/', $cleaned, $matches)) {
            return [
                'note' => floatval($matches[1]),
                'justification' => "Note extraite de l'analyse IA."
            ];
        }
        
        return ['note' => 14, 'justification' => "Note par défaut (l'IA n'a pas pu générer une réponse structurée)."];
    }     
      public function recommendProjects($specialite, $niveau, $interets = '')
    {
        $prompt = "Propose 3 projets pour un étudiant en $specialite ($niveau). Réponds en JSON : [{\"titre\":\"...\",\"description\":\"...\",\"competences\":[...]}]\n";
        if ($interets) $prompt .= "Centres d'intérêt : $interets";
        $response = $this->callWithRetry($prompt, 800);
        if (preg_match('/\[.*\]/s', $response, $matches)) {
            $json = json_decode($matches[0], true);
            if ($json) return $json;
        }
        return [];
    }

    public function recommendProjectsChat($message, $history = [], $specialite = 'Informatique')
    {
        $prompt = "Tu es un conseiller pédagogique expert. L'utilisateur est un professeur de $specialite.\n\n";
        foreach ($history as $msg) {
            $prompt .= "- {$msg['role']}: {$msg['content']}\n";
        }
        $prompt .= "\nUtilisateur: $message\n\n";
        $prompt .= "Réponds de manière conversationnelle. Si l'utilisateur demande des idées de projets, génère 2-3 projets avec : titre, description (3-4 phrases), compétences (3-5), niveau (TS1/TS2/TS3/Mélangé).\n";
        $prompt .= "Réponds en JSON : {\"message\": \"ta réponse\", \"projects\": [{\"titre\":\"...\",\"description\":\"...\",\"competences\":[...],\"niveau\":\"...\"}]}";
        $response = $this->callWithRetry($prompt, 1500);
        if (preg_match('/\{.*\}/s', $response, $matches)) {
            $json = json_decode($matches[0], true);
            if ($json) return $json;
        }
        return ['message' => $response, 'projects' => null];
    }

    public function generateMeetingMinutes($meeting)
    {
        $title = $meeting->titre ?? 'Réunion';
        $agenda = $meeting->ordre_du_jour ?? '';
        $date = $meeting->date_heure ? $meeting->date_heure->format('d/m/Y') : '';
        $prompt = "Génère un compte-rendu de réunion :\nTitre : $title\nDate : $date\nOrdre du jour : $agenda\n\n1. Points abordés\n2. Décisions\n3. Actions";
        return $this->callWithRetry($prompt, 800);
    }

    public function generateDefenseQuestions($projectTitle, $description, $count = 3)
    {
        $prompt = "Génère $count questions de soutenance pour le projet '$projectTitle'.\nDescription : " . substr($description, 0, 1500) . "\n\nRéponds en JSON : [{\"question\": \"...\"}]";
        $response = $this->callWithRetry($prompt, 500);
        if (preg_match('/\[.*\]/s', $response, $matches)) {
            $json = json_decode($matches[0], true);
            if ($json) return $json;
        }
        return [];
    }

    public function suggestSectionTitles($content)
    {
        $prompt = "Suggère 5 titres de sections pour structurer ce contenu.\n\n" . substr($content, 0, 2000) . "\n\nRéponds en JSON : [\"Titre 1\", \"Titre 2\", ...]";
        $response = $this->callWithRetry($prompt, 400);
        if (preg_match('/\[.*\]/s', $response, $matches)) {
            $json = json_decode($matches[0], true);
            if ($json) return $json;
        }
        return ['Introduction', 'Développement', 'Analyse', 'Résultats', 'Conclusion'];
    }

    public function analyzeSection($content)
    {
        $prompt = "Analyse cette section de rapport académique. Donne 3 points forts et 3 axes d'amélioration.\n\n" . substr($content, 0, 3000);
        return $this->callWithRetry($prompt, 600);
    }

    public function correctGrammar($content)
    {
        $prompt = "Corrige les fautes d'orthographe et de grammaire de ce texte. Réponds uniquement avec le texte corrigé.\n\n" . $content;
        return $this->callWithRetry($prompt, 1000);
    }

    public function summarize($content)
    {
        $prompt = "Résume ce texte en 3-4 phrases concises.\n\n" . substr($content, 0, 3000);
        return $this->callWithRetry($prompt, 400);
    }

    public function rephrase($content)
    {
        $prompt = "Reformule ce texte dans un style académique professionnel. Réponds uniquement avec le texte reformulé.\n\n" . $content;
        return $this->callWithRetry($prompt, 1000);
    }

    public function suggestImprovements($content)
    {
        $prompt = "Suggère 3-4 améliorations concrètes pour ce contenu.\n\n" . substr($content, 0, 3000);
        return $this->callWithRetry($prompt, 500);
    }

    public function expandContent($content)
    {
        $prompt = "Développe ce texte en ajoutant plus de détails. Réponds uniquement avec le texte développé.\n\n" . $content;
        return $this->callWithRetry($prompt, 1200);
    }

    public function generateComment($content)
    {
        $prompt = "Rédige un commentaire d'évaluation constructif pour ce rapport étudiant.\n\n" . substr($content, 0, 3000);
        return $this->callWithRetry($prompt, 600);
    }

    public function testModels()
    {
        $results = [];
        foreach ($this->models as $model) {
            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
                $response = Http::timeout(10)
                    ->withOptions(['verify' => false])
                    ->post($url . '?key=' . $this->apiKey, [
                        'contents' => [['parts' => [['text' => 'OK']]]]
                    ]);
                $results[$model] = ['status' => $response->status(), 'working' => $response->successful()];
                if ($response->successful()) Cache::put('gemini_working_model', $model, 3600);
            } catch (\Exception $e) {
                $results[$model] = ['status' => 'error', 'working' => false];
            }
        }
        return $results;
    }
    // Dans GeminiService.php
    public function chatWithStats($message, $history = [], $stats = null)
    {
        $context = "";
        if ($stats) {
            $total = $stats['projets_par_statut'] ?? [];
            $totalProjets = array_sum(array_column($total, 'total'));
            $valides = array_values(array_filter($total, fn($s) => $s['statut'] === 'valide'))[0]['total'] ?? 0;
            
            $context = "Voici les VRAIES statistiques actuelles du système :\n";
            $context .= "- Total projets : $totalProjets\n";
            $context .= "- Projets validés : $valides\n";
            $context .= "- Taux de dépôt : " . ($stats['taux_depot'] ?? 0) . "%\n";
            $context .= "- Rapports soumis : " . ($stats['rapports_soumis'] ?? 0) . "\n";
            $context .= "- Activité récente : " . ($stats['activite_recente'] ?? 0) . " actions\n\n";
            $context .= "Utilise ces informations pour répondre.\n\n";
        }
        
        return $this->chatWithContext($context . $message, 'rup_projet', $history);
    }

    public function suggestResources($title, $description)
    {
        $prompt = "En tant qu'expert pédagogique, propose des ressources pour aider un étudiant sur son projet :
Titre : $title
Description : $description

Propose 6 ressources pertinentes réparties en 3 catégories :
1. 📚 Documentations officielles ou techniques
2. 🎥 Tutoriels YouTube (donne des titres de recherche ou des liens probables)
3. 📄 Articles ou concepts académiques clés

Réponds UNIQUEMENT en JSON avec ce format :
[
  {
    \"type\": \"doc|video|article\",
    \"title\": \"Titre de la ressource\",
    \"url\": \"Lien réel ou lien de recherche Google/YouTube correspondant\",
    \"description\": \"Pourquoi cette ressource est utile (1 phrase)\"
  }
]";

        $response = $this->callWithRetry($prompt, 1500);
        if (preg_match('/\[.*\]/s', $response, $matches)) {
            $json = json_decode($matches[0], true);
            if ($json) return $json;
        }
        return [];
    }

    public function generateProjectPhases($title, $description)
    {
        $prompt = "En tant qu'expert en gestion de projet, génère un planning de 4 à 6 phases clés pour le projet suivant :\n";
        $prompt .= "Titre : $title\n";
        $prompt .= "Description : $description\n\n";
        $prompt .= "Pour chaque phase, fournis :\n";
        $prompt .= "1. Un titre court et explicite\n";
        $prompt .= "2. Une description concise des objectifs\n";
        $prompt .= "3. Un pourcentage d'avancement estimé (0 par défaut)\n";
        $prompt .= "4. Si c'est un jalon critique (true/false)\n\n";
        $prompt .= "Réponds UNIQUEMENT sous forme d'un tableau JSON valide, sans texte avant ou après, avec cette structure :\n";
        $prompt .= "[{\"titre\": \"...\", \"description\": \"...\", \"avancement_pct\": 0, \"est_jalon\": false}, ...]";

        $response = $this->callWithRetry($prompt, 2000);
        
        // Nettoyage de la réponse pour extraire le JSON
        $json = trim($response);
        if (str_starts_with($json, '```json')) {
            $json = str_replace(['```json', '```'], '', $json);
        }
        $json = trim($json);

        return json_decode($json, true) ?: [];
    }
}