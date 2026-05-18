<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Rappels de réunions (toutes les heures pour vérifier s'il y a un RDV dans 24h)
Schedule::command('app:send-meeting-reminders')->hourly();

// Rappels de deadlines (tous les jours à 8h)
Schedule::command('app:send-deadline-reminders')->dailyAt('08:00');

// Relances pour les professeurs (tous les jours à 9h)
Schedule::command('app:send-professor-reminders')->dailyAt('09:00');

// Newsletter hebdomadaire (tous les lundis à 8h)
Schedule::command('app:send-weekly-newsletter')->weeklyOn(1, '08:00');

// Rapport mensuel pour RUP (le 1er du mois à 8h)
Schedule::command('app:send-monthly-rup-report')->monthlyOn(1, '08:00');
