<!DOCTYPE html>
<html>
<head>
    <title>Convocation à la soutenance</title>
</head>
<body>
    <h2>Bonjour {{ $student->prenom }},</h2>
    
    <p>Votre soutenance intitulée <strong>{{ $meeting->titre }}</strong> a été officiellement programmée.</p>
    
    <ul>
        <li><strong>Date et Heure :</strong> {{ \Carbon\Carbon::parse($meeting->date_heure)->format('d/m/Y H:i') }}</li>
        <li><strong>Lieu / Salle :</strong> {{ $meeting->lieu ?: 'Non défini' }}</li>
        <li><strong>Jury :</strong> {{ $jury->president->prenom }} {{ $jury->president->nom }} (Président)</li>
    </ul>

    <p>Veuillez trouver en pièce jointe la fiche de convocation et les consignes pour cette soutenance.</p>

    <p>Vous pouvez consulter plus de détails dans l'application.</p>

    <p>Cordialement,<br>L'équipe UPPRO</p>
</body>
</html>
