<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Http;

class TestGeminiModels extends Command
{
    protected $signature = 'gemini:test';
    protected $description = 'Test Gemini models';

    public function handle()
    {
        $this->info('🧪 Test des modèles Gemini...');
        
        $apiKey = config('services.gemini.api_key');
        
        // ✅ Test simple avec le modèle qui fonctionne
        $this->info('Test avec gemini-2.5-flash...');
        
        try {
            $response = Http::timeout(30)
                ->withOptions(['verify' => false])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}", [
                    'contents' => [
                        ['parts' => [['text' => 'Dis "OK" si tu fonctionnes']]]
                    ]
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                $this->info('✅ gemini-2.5-flash fonctionne !');
                $this->line('Réponse : ' . $text);
            } else {
                $this->error('❌ Status : ' . $response->status());
                $this->line($response->body());
            }
        } catch (\Exception $e) {
            $this->error('❌ Exception : ' . $e->getMessage());
        }
        
        $this->info('✅ Test terminé !');
    }
}