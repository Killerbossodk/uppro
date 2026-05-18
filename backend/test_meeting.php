<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::find(4);
$m = App\Models\Meeting::with(['project.specialite', 'organisateur', 'groupes'])
    ->where('statut_soutenance', '!=', 'termine')
    ->where(function ($q) use ($user) {
        $q->where('organisateur_id', $user->id)
            ->orWhereHas('jury', function ($jq) use ($user) {
                $jq->where('president_id', $user->id)
                ->orWhereHas('membres', function($mq) use ($user) {
                    $mq->where('user_id', $user->id);
                });
            });
    })
    ->get();

echo "Total: " . count($m) . "\n";
foreach($m as $meeting) {
    echo "Meeting ID: " . $meeting->id . " Status: " . $meeting->statut_soutenance . "\n";
}
