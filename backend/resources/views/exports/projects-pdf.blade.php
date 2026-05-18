<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Liste des projets - UP-PRO</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; padding: 20px; font-size: 12px; }
        h1 { color: #4F46E5; margin-bottom: 5px; }
        .subtitle { color: #6B7280; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #4F46E5; color: white; padding: 8px; text-align: left; font-size: 11px; }
        td { padding: 6px; border-bottom: 1px solid #E5E7EB; }
        .statut { 
            display: inline-block; 
            padding: 2px 6px; 
            border-radius: 12px; 
            font-size: 10px; 
            font-weight: bold;
        }
        .statut-brouillon { background: #9CA3AF; color: white; }
        .statut-soumis { background: #FBBF24; color: white; }
        .statut-valide { background: #10B981; color: white; }
        .statut-refuse { background: #EF4444; color: white; }
        .footer { margin-top: 30px; text-align: center; color: #9CA3AF; font-size: 10px; }
    </style>
</head>
<body>
    <h1>UP-PRO - Liste des projets</h1>
    <p class="subtitle">Généré le {{ $date }} par {{ $user->name }} {{ $user->prenom }}</p>
    <p>Total : {{ $projects->count() }} projet(s)</p>
    
    <table>
        <thead>
            <tr>
                <th>Titre</th>
                <th>Niveau</th>
                <th>Statut</th>
                <th>Spécialité</th>
                <th>Superviseur</th>
                <th>Groupes</th>
                <th>Année</th>
            </tr>
        </thead>
        <tbody>
            @foreach($projects as $project)
            <tr>
                <td>{{ $project->titre }}</td>
                <td>{{ $project->niveau }}</td>
                <td>
                    <span class="statut statut-{{ $project->statut }}">
                        {{ ucfirst($project->statut) }}
                    </span>
                </td>
                <td>{{ $project->specialite->nom ?? 'Mélangé' }}</td>
                <td>{{ $project->superviseur->name ?? '' }} {{ $project->superviseur->prenom ?? '' }}</td>
                <td>{{ $project->groupes->count() }}</td>
                <td>{{ $project->annee_universitaire }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="footer">
        UP-PRO - Plateforme de gestion de projets pédagogiques
    </div>
</body>
</html>