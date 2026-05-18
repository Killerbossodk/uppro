<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\GeminiService;
use App\Services\TextExtractorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AiController extends Controller
{
    protected $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    public function chat(Request $request)
    {
        $user = $request->user();
        $message = $request->input('message');
        $history = $request->input('history', []);
        $reportId = $request->input('report_id');

        $reportContext = '';
        $fileData = null;
        if ($reportId) {
            $report = Report::find($reportId);
            if ($report) {
                $reportContext = "📄 L'utilisateur consulte actuellement le rapport : {$report->titre}\n";
                
                // Résoudre le chemin du fichier
                $filePath = $report->fichier_url;
                $cleanedPath = ltrim($filePath, '/');
                if (str_starts_with($cleanedPath, 'storage/')) {
                    $cleanedPath = substr($cleanedPath, 8);
                }
                
                $extension = strtolower(pathinfo($cleanedPath, PATHINFO_EXTENSION));
                $fullPath = storage_path('app/public/' . $cleanedPath);

                if ($extension === 'pdf' && file_exists($fullPath)) {
                    $fileData = [
                        'mime_type' => 'application/pdf',
                        'data' => base64_encode(file_get_contents($fullPath))
                    ];
                    $reportContext .= "Le fichier PDF complet est joint à ce message. Analyse-le pour répondre précisément.\n";
                } else {
                    $extractor = new TextExtractorService();
                    $content = $extractor->extract($cleanedPath);
                    if ($content) {
                        $reportContext .= "Contenu du rapport :\n" . substr($content, 0, 10000) . "\n";
                    }
                }

                if ($report->feedback_ia) {
                    $reportContext .= "🤖 Analyse IA précédente :\n" . substr($report->feedback_ia, 0, 500) . "\n";
                }
                $reportContext .= "\nUtilise ces informations pour répondre aux questions sur ce rapport.\n";
            }
        }

        $response = $this->gemini->chatWithContext($reportContext . $message, $user->role, $history, $fileData);
        return response()->json(['message' => $response]);
    }

    public function recommendProjectsChat(Request $request)
    {
        $user = $request->user();
        $message = $request->input('message');
        $history = $request->input('history', []);
        $specialite = $user->specialite->nom ?? 'Informatique';
        $result = $this->gemini->recommendProjectsChat($message, $history, $specialite);
        return response()->json($result);
    }

    public function analyzeReport(Report $report)
    {
        set_time_limit(300); // 5 minutes max execution time
        try {
            Log::info("Début analyse rapport ID: " . $report->id);
            
            // Nettoyer le chemin (enlever /storage/ ou storage/ au début si présent)
            $filePath = $report->fichier_url;
            $cleanedPath = ltrim($filePath, '/');
            if (str_starts_with($cleanedPath, 'storage/')) {
                $cleanedPath = substr($cleanedPath, 8);
            }
            
            $extension = strtolower(pathinfo($cleanedPath, PATHINFO_EXTENSION));
            $fullPath = storage_path('app/public/' . $cleanedPath);

            $content = '';
            $fileData = null;

            if ($extension === 'pdf' && file_exists($fullPath)) {
                $fileData = [
                    'mime_type' => 'application/pdf',
                    'data' => base64_encode(file_get_contents($fullPath))
                ];
            } else {
                $extractor = new TextExtractorService();
                $content = $extractor->extract($cleanedPath);
            }

            if (empty($content) && empty($fileData)) {
                $content = "Titre: {$report->titre}\nType: {$report->type}\nVersion: {$report->version}";
            }

            $analysis = $this->gemini->analyzeReport($content, $fileData);
            Log::info("Analyse générée, longueur: " . strlen($analysis));
            $report->update(['feedback_ia' => $analysis]);
            return response()->json(['analysis' => $analysis]);
        } catch (\Exception $e) {
            Log::error("Erreur: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function suggestGrade(Report $report)
    {
        set_time_limit(300); // 5 minutes max execution time
        try {
            // Nettoyer le chemin (enlever /storage/ ou storage/ au début si présent)
            $filePath = $report->fichier_url;
            $cleanedPath = ltrim($filePath, '/');
            if (str_starts_with($cleanedPath, 'storage/')) {
                $cleanedPath = substr($cleanedPath, 8);
            }
            
            $extension = strtolower(pathinfo($cleanedPath, PATHINFO_EXTENSION));
            $fullPath = storage_path('app/public/' . $cleanedPath);

            $content = '';
            $fileData = null;

            if ($extension === 'pdf' && file_exists($fullPath)) {
                $fileData = [
                    'mime_type' => 'application/pdf',
                    'data' => base64_encode(file_get_contents($fullPath))
                ];
            } else {
                $extractor = new TextExtractorService();
                $content = $extractor->extract($cleanedPath);
            }

            if (empty($content) && empty($fileData)) {
                return response()->json(['note' => null, 'justification' => 'Le rapport ne contient pas assez de texte ou le fichier est illisible.']);
            }

            $suggestion = $this->gemini->suggestGrade($content, $fileData);
            if (isset($suggestion['note']) && is_numeric($suggestion['note'])) {
                $suggestion['note'] = round($suggestion['note'], 1);
            }
            return response()->json($suggestion);
        } catch (\Exception $e) {
            return response()->json(['note' => null, 'justification' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }

    public function recommendProjects(Request $request)
    {
        $user = $request->user();
        $specialite = $user->specialite->nom ?? 'Informatique';
        $niveau = $user->annee_universitaire ?? 'TS2';
        $recommendations = $this->gemini->recommendProjects($specialite, $niveau);
        return response()->json($recommendations);
    }

    public function analyzeSection(Request $request)
    {
        $content = $request->input('content');
        return response()->json(['result' => $this->gemini->analyzeSection($content)]);
    }

    public function correctGrammar(Request $request)
    {
        $content = $request->input('content');
        return response()->json(['corrected' => $this->gemini->correctGrammar($content)]);
    }

    public function summarize(Request $request)
    {
        $content = $request->input('content');
        return response()->json(['summary' => $this->gemini->summarize($content)]);
    }

    public function rephrase(Request $request)
    {
        $content = $request->input('content');
        return response()->json(['rephrased' => $this->gemini->rephrase($content)]);
    }

    public function suggestImprovements(Request $request)
    {
        $content = $request->input('content');
        return response()->json(['suggestions' => $this->gemini->suggestImprovements($content)]);
    }

    public function expandContent(Request $request)
    {
        $content = $request->input('content');
        return response()->json(['expanded' => $this->gemini->expandContent($content)]);
    }

    public function generateComment(Request $request)
    {
        $content = $request->input('content');
        return response()->json(['comment' => $this->gemini->generateComment($content)]);
    }
    public function chatWithStats(Request $request)
    {
        $user = $request->user();
        $message = $request->input('message');
        $history = $request->input('history', []);
        $stats = $request->input('stats');
        
        $response = $this->gemini->chatWithStats($message, $history, $stats);
        return response()->json(['message' => $response]);
    }

    public function suggestResources(Request $request)
    {
        // Libérer la session immédiatement pour éviter de bloquer les autres requêtes de l'utilisateur !
        if (session()->isStarted()) {
            session()->save();
        }

        $title = $request->input('title');
        $description = $request->input('description');
        
        $resources = $this->gemini->suggestResources($title, $description);
        return response()->json($resources);
    }

    public function generatePhases(Request $request)
    {
        $title = $request->input('title');
        $description = $request->input('description');
        
        $phases = $this->gemini->generateProjectPhases($title, $description);
        return response()->json($phases);
    }
}