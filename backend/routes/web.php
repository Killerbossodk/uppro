<?php

use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    return 'OK';
});

// Route de test API (à supprimer en production)
Route::post('/api/login-test', function() {
    return response()->json(['message' => 'route API fonctionne']);
});

// Note: La route /api/login est déjà définie dans api.php
// Pas besoin de la dupliquer ici