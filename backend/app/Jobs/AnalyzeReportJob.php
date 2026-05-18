<?php

namespace App\Jobs;

use App\Models\Report;
use App\Services\GeminiService;
use App\Services\TextExtractorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AnalyzeReportJob implements ShouldQueue
{
    use Queueable;

    protected $report;

    public function __construct(Report $report)
    {
        $this->report = $report;
    }

    public function handle(GeminiService $gemini, TextExtractorService $extractor)
    {
        try {
            $filePath = $this->report->fichier_url;
            // Nettoyer le chemin (enlever /storage/ ou storage/ au début si présent)
            $cleanedPath = ltrim($filePath, '/');
            if (str_starts_with($cleanedPath, 'storage/')) {
                $cleanedPath = substr($cleanedPath, 8);
            }
            
            $extension = strtolower(pathinfo($cleanedPath, PATHINFO_EXTENSION));
            $fullPath = storage_path('app/public/' . $cleanedPath);
            
            $content = '';
            $fileData = null;

            if ($extension === 'pdf' && file_exists($fullPath)) {
                // Pour les PDF, on envoie le fichier directement à Gemini
                $fileData = [
                    'mime_type' => 'application/pdf',
                    'data' => base64_encode(file_get_contents($fullPath))
                ];
                Log::info("Envoi direct du PDF à Gemini pour le rapport #{$this->report->id}");
            } else {
                // Pour les autres (Word), on extrait le texte
                $content = $extractor->extract($filePath);
            }
            
            if (empty($content) && empty($fileData)) {
                Log::warning("Impossible d'extraire le contenu ou de lire le fichier pour le rapport #{$this->report->id}");
                $content = "Titre: " . $this->report->titre;
            }
            
            // Analyser avec IA (en passant le fichier si présent)
            $analysis = $gemini->analyzeReport($content, $fileData);
            
            // Mettre à jour le rapport
            $this->report->update([
                'feedback_ia' => $analysis,
            ]);
            
            Log::info("Analyse IA terminée pour le rapport #{$this->report->id}");
            
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'analyse du rapport #{$this->report->id}: " . $e->getMessage());
            
            $this->report->update([
                'feedback_ia' => "Erreur lors de l'analyse automatique. Veuillez réessayer plus tard.",
            ]);
        }
    }
}