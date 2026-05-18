<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Report;
use App\Services\GeminiService;
use App\Services\TextExtractorService;
use Illuminate\Support\Facades\Log;

class AnalyzeReportCommand extends Command
{
    protected $signature = 'uppro:analyze-report {report_id}';
    protected $description = 'Analyze a student report using Gemini AI in the background';

    public function handle(GeminiService $gemini, TextExtractorService $extractor)
    {
        $reportId = $this->argument('report_id');
        $report = Report::find($reportId);

        if (!$report) {
            $this->error("Rapport #{$reportId} introuvable.");
            return 1;
        }

        $this->info("Analyse du rapport #{$reportId} lancée...");

        try {
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
                Log::info("Command: Envoi direct du PDF à Gemini pour le rapport #{$report->id}");
            } else {
                $content = $extractor->extract($filePath);
            }
            
            if (empty($content) && empty($fileData)) {
                Log::warning("Command: Impossible d'extraire le contenu ou de lire le fichier pour le rapport #{$report->id}");
                $content = "Titre: " . $report->titre;
            }
            
            // Analyser avec IA
            $analysis = $gemini->analyzeReport($content, $fileData);
            
            // Mettre à jour le rapport
            $report->update([
                'feedback_ia' => $analysis,
            ]);
            
            $this->info("✅ Analyse IA terminée avec succès !");
            Log::info("Command: Analyse IA terminée pour le rapport #{$report->id}");
            
        } catch (\Exception $e) {
            $this->error("Erreur lors de l'analyse : " . $e->getMessage());
            Log::error("Command: Erreur lors de l'analyse du rapport #{$report->id}: " . $e->getMessage());
            
            $report->update([
                'feedback_ia' => "Erreur lors de l'analyse automatique. Veuillez réessayer plus tard.",
            ]);
        }

        return 0;
    }
}
