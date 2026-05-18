<x-mail::message>
# Bienvenue sur UP-PRO !

Bonjour {{ $user->prenom }},

Vous avez été invité(e) à rejoindre la plateforme **UP-PRO** pour la gestion de vos projets universitaires.

Un compte a été créé pour vous. Voici vos identifiants temporaires de connexion :

**Email :** {{ $user->email }}
**Mot de passe temporaire :** {{ $password }}

> **⚠️ Attention :** Ce mot de passe est temporaire. Vous avez **72 heures** pour vous connecter et le modifier.

<x-mail::button :url="config('app.frontend_url') . '/login'">
Se connecter à UP-PRO
</x-mail::button>

Si vous rencontrez des problèmes pour vous connecter, veuillez contacter votre administration.

Cordialement,<br>
L'équipe {{ config('app.name') }}
</x-mail::message>
