<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $report->titre }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; margin: 2cm; }
        h1 { color: #1e3a8a; }
        .pdf-block { margin: 20px 0; }
        img { max-width: 100%; }
    </style>
</head>
<body>
    <h1>{{ $report->titre }}</h1>
    <p><strong>Projet :</strong> {{ $report->group->project->titre }}</p>
    <hr>
    @foreach($blocks as $block)
        @include('exports.block-' . $block->type, ['block' => $block])
    @endforeach
</body>
</html>