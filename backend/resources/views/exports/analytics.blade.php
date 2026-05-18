<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Statistiques UP-PRO</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; padding: 20px; }
        h1 { color: #4F46E5; margin-bottom: 5px; }
        .subtitle { color: #6B7280; margin-bottom: 30px; }
        .stats-grid { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #F9FAFB; border-radius: 8px; padding: 15px; flex: 1; min-width: 150px; border: 1px solid #E5E7EB; }
        .stat-label { color: #6B7280; font-size: 12px; margin-bottom: 5px; }
        .stat-value { font-size: 28px; font-weight: bold; color: #1F2937; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #F3F4F6; padding: 10px; text-align: left; font-size: 12px; color: #6B7280; border-bottom: 1px solid #E5E7EB; }
        td { padding: 10px; border-bottom: 1px solid #E5E7EB; }
        .section-title { font-size: 18px; font-weight: bold; color: #1F2937; margin: 30px 0 15px; }
        .footer { margin-top: 50px; text-align: center; color: #9CA3AF; font-size: 11px; border-top: 1px solid #E5E7EB; padding-top: 20px; }
    </style>
</head>
<body>
    <h1>UP-PRO - Tableau de bord analytique</h1>
    <p class="subtitle">Généré le {{ $date }} par {{ $user->name }} {{ $user->prenom }}</p>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total projets</div>
            <div class="stat-value">{{ $stats->total_projets ?? 0 }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Projets validés</div>
            <div class="stat-value" style="color: #10B981;">{{ $stats->projets_valides ?? 0 }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Taux de dépôt</div>
            <div class="stat-value" style="color: #3B82F6;">{{ $stats->taux_depot ?? 0 }}%</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Activité récente (30j)</div>
            <div class="stat-value" style="color: #8B5CF6;">{{ $stats->activite_recente ?? 0 }}</div>
        </div>
    </div>
    
    <h2 class="section-title">Projets par statut</h2>
    <table>
        <thead>
            <tr>
                <th>Statut</th>
                <th>Nombre</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stats->projets_par_statut ?? [] as $item)
            <tr>
                <td>{{ ucfirst($item->statut) }}</td>
                <td>{{ $item->total }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <h2 class="section-title">Moyenne des notes par spécialité</h2>
    <table>
        <thead>
            <tr>
                <th>Spécialité</th>
                <th>Moyenne</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stats->moyenne_notes ?? [] as $item)
            <tr>
                <td>{{ $item->nom }}</td>
                <td>{{ number_format($item->moyenne, 2) }}/20</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <h2 class="section-title">Top 5 professeurs</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Professeur</th>
                <th>Projets encadrés</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stats->top_professeurs ?? [] as $index => $prof)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $prof->name }} {{ $prof->prenom }}</td>
                <td>{{ $prof->total }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="footer">
        UP-PRO - Plateforme de gestion de projets pédagogiques
    </div>
</body>
</html>