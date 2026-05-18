<?php

namespace App\Services;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Spatie\PdfToText\Pdf;
use Exception;
use Illuminate\Support\Facades\Log;

class TextExtractorService
{
    public function extract(string $filePath): string
    {
        $fullPath = storage_path('app/public/' . $filePath);
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        Log::info('=== DÉBUT EXTRACTION ===', [
            'path' => $fullPath,
            'ext' => $extension,
            'exists' => file_exists($fullPath),
            'size' => file_exists($fullPath) ? filesize($fullPath) : 0,
        ]);

        if (!file_exists($fullPath)) {
            Log::error("Fichier non trouvé : {$fullPath}");
            return '';
        }

        try {
            $content = match ($extension) {
                'pdf'  => $this->extractPdf($fullPath),
                'docx' => $this->extractDocx($fullPath),
                'doc'  => $this->extractDoc($fullPath),
                default => '',
            };

            $cleaned = $this->deepClean($content);

            Log::info('=== EXTRACTION RÉUSSIE ===', [
                'raw_length' => strlen($content),
                'cleaned_length' => strlen($cleaned),
                'preview' => substr($cleaned, 0, 300),
            ]);

            return $cleaned;
        } catch (Exception $e) {
            Log::error("Exception extraction : " . $e->getMessage());
            return '';
        }
    }

    private function extractPdf(string $path): string
    {
        try {
            $text = Pdf::getText($path);
            return $text ?: '';
        } catch (Exception $e) {
            Log::error('PDF extraction error: ' . $e->getMessage());
            return '';
        }
    }

    private function extractDocx(string $path): string
    {
        try {
            $phpWord = IOFactory::load($path);
            Log::info('DOCX chargé avec PhpWord');
            return $this->extractFromPhpWord($phpWord);
        } catch (Exception $e) {
            Log::error('PhpWord failed: ' . $e->getMessage());
            return $this->extractDocxXmlFallback($path);
        }
    }

    private function extractDocxXmlFallback(string $path): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return '';
        }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if (!$xml) return '';

        // Nettoyer le XML avant extraction
        $xml = preg_replace('/<w:del[^>]*>.*?<\/w:del>/s', '', $xml);
        $xml = preg_replace('/<w:ins[^>]*>.*?<\/w:ins>/s', '', $xml);
        $xml = preg_replace('/<w:rPr[^>]*>.*?<\/w:rPr>/s', '', $xml);
        $xml = preg_replace('/<w:pPr[^>]*>.*?<\/w:pPr>/s', '', $xml);

        // Extraire uniquement le texte des balises <w:t>
        preg_match_all('/<w:t[^>]*>(.*?)<\/w:t>/s', $xml, $matches);
        $text = isset($matches[1]) ? implode('', $matches[1]) : '';

        $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
        return trim($text);
    }

    private function extractDoc(string $path): string
    {
        return $this->extractDocx($path);
    }

    /**
     * Extraction propre du texte depuis PhpWord (sans duplication)
     */
    private function extractFromPhpWord(PhpWord $phpWord): string
    {
        $allText = [];
        foreach ($phpWord->getSections() as $section) {
            $this->collectTextFromElements($section->getElements(), $allText);
        }
        return implode("\n", $allText);
    }

    /**
     * Parcourt les éléments et collecte le texte SANS duplication
     */
    private function collectTextFromElements(iterable $elements, array &$textArray): void
    {
        foreach ($elements as $element) {
            // Élément texte simple (Text, Link, etc.)
            if (method_exists($element, 'getText') && !$element instanceof \PhpOffice\PhpWord\Element\TextRun && !$element instanceof \PhpOffice\PhpWord\Element\Table && !$element instanceof \PhpOffice\PhpWord\Element\Cell) {
                $text = trim($element->getText());
                if ($text !== '') {
                    $textArray[] = $text;
                }
            }

            // TextRun (paragraphe avec mise en forme) - ne pas extraire getText(), seulement les enfants
            if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                foreach ($element->getElements() as $child) {
                    if (method_exists($child, 'getText') && !$child instanceof \PhpOffice\PhpWord\Element\TextRun) {
                        $text = trim($child->getText());
                        if ($text !== '') {
                            $textArray[] = $text;
                        }
                    }
                }
                $textArray[] = ''; // Fin de paragraphe
            }

            // Tableau
            if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                foreach ($element->getRows() as $row) {
                    $rowText = [];
                    foreach ($row->getCells() as $cell) {
                        $cellText = [];
                        $this->collectTextFromElements($cell->getElements(), $cellText);
                        $rowText[] = implode(' ', $cellText);
                    }
                    $textArray[] = implode("\t", $rowText);
                }
                $textArray[] = ''; // Fin de tableau
            }

            // Éléments enfants (sections, conteneurs)
            if (method_exists($element, 'getElements') && !$element instanceof \PhpOffice\PhpWord\Element\TextRun && !$element instanceof \PhpOffice\PhpWord\Element\Table && !$element instanceof \PhpOffice\PhpWord\Element\Cell) {
                $this->collectTextFromElements($element->getElements(), $textArray);
            }

            // Saut de ligne
            if ($element instanceof \PhpOffice\PhpWord\Element\TextBreak) {
                $textArray[] = '';
            }

            // Liste
            if ($element instanceof \PhpOffice\PhpWord\Element\ListItem) {
                $text = trim($element->getText());
                if ($text !== '') {
                    $textArray[] = '• ' . $text;
                }
            }
        }
    }

    private function deepClean(string $text): string
    {
        if (empty($text)) return '';

        // Supprimer les balises XML résiduelles
        $text = strip_tags($text);

        // Décoder les entités HTML
        $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');

        // Supprimer les caractères de contrôle
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);

        // Normaliser les espaces
        $text = preg_replace('/[ \t]+/', ' ', $text);

        // Réduire les sauts de ligne multiples
        $text = preg_replace('/\n\s*\n\s*\n+/', "\n\n", $text);

        return trim($text);
    }
}