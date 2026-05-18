<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapport - {{ $report->titre }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #333; line-height: 1.6; margin: 40px; }
        .header { text-align: center; border-bottom: 2px solid #0F2544; padding-bottom: 20px; margin-bottom: 30px; }
        .logo { font-size: 28px; font-weight: 800; color: #0F2544; }
        .logo span { color: #6366f1; }
        .meta { margin-bottom: 40px; }
        .meta-item { margin-bottom: 10px; }
        .label { font-weight: bold; color: #666; width: 150px; display: inline-block; }
        .title { font-size: 24px; font-weight: 700; color: #0F2544; margin-bottom: 20px; }
        .section { margin-bottom: 30px; }
        .section-title { font-size: 18px; font-weight: 700; color: #0F2544; border-left: 4px solid #6366f1; padding-left: 10px; margin-bottom: 15px; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-info { background: #dbeafe; color: #1e40af; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">UP<span>PRO</span></div>
        <div style="font-size: 14px; color: #666; margin-top: 5px;">Plateforme de Gestion de Projets Universitaires</div>
    </div>

    <div class="title">{{ $report->titre }}</div>

    <div class="meta">
        <div class="meta-item"><span class="label">Groupe :</span> {{ $report->group->nom }}</div>
        <div class="meta-item"><span class="label">Projet :</span> {{ $report->group->project->titre }}</div>
        <div class="meta-item"><span class="label">Déposé par :</span> {{ $report->deposant->prenom }} {{ $report->deposant->name }}</div>
        <div class="meta-item"><span class="label">Date :</span> {{ $report->created_at->format('d/m/Y H:i') }}</div>
        <div class="meta-item"><span class="label">Type :</span> {{ ucfirst($report->type) }}</div>
        <div class="meta-item"><span class="label">Statut :</span> 
            <span class="badge {{ $report->statut === 'valide' ? 'badge-success' : 'badge-info' }}">
                {{ str_replace('_', ' ', $report->statut) }}
            </span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Analyse IA & Feedback</div>
        <div class="content">
            {!! nl2br(e($report->feedback_ia ?? 'Aucun feedback IA disponible pour ce rapport.')) !!}
        </div>
    </div>

    @if($report->score_plagiat !== null)
    <div class="section">
        <div class="section-title">Score de Plagiat</div>
        <div class="content">
            Le score de plagiat détecté pour ce rapport est de **{{ $report->score_plagiat }}%**.
        </div>
    </div>
    @endif

    <div class="section">
        <div class="section-title">Commentaires & Annotations</div>
        @if($report->comments && $report->comments->count() > 0)
            @foreach($report->comments as $comment)
                <div style="margin-bottom: 15px; padding: 10px; background: #f9fafb; border-radius: 6px;">
                    <div style="font-size: 12px; font-weight: bold; color: #4b5563;">{{ $comment->user->prenom }} {{ $comment->user->name }}</div>
                    <div style="font-size: 14px;">{{ $comment->commentaire }}</div>
                </div>
            @endforeach
        @else
            <p style="color: #999; font-style: italic;">Aucun commentaire.</p>
        @endif
    </div>

    <div class="footer">
        Document généré automatiquement par UPPRO - {{ date('Y') }} &copy;
    </div>
</body>
</html>
