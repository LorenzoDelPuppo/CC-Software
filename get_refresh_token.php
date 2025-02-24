<?php
// get_refresh_token.php

// Assicurati di aver installato via Composer la libreria "league/oauth2-client"
// E includi l'autoload
require_once __DIR__ . '/../vendor/autoload.php';

use League\OAuth2\Client\Provider\Google;

session_start();

// Inserisci qui i tuoi dati ottenuti dalla Google Developer Console
$clientId     = 'IL_TUO_CLIENT_ID';
$clientSecret = 'IL_TUO_CLIENT_SECRET';
$redirectUri  = 'http://localhost/get_refresh_token.php'; // Assicurati che questo URI sia autorizzato

// Crea l'oggetto provider
$provider = new Google([
    'clientId'     => $clientId,
    'clientSecret' => $clientSecret,
    'redirectUri'  => $redirectUri,
]);

// Se non abbiamo ancora il parametro "code" in GET, genera l'URL di autorizzazione
if (!isset($_GET['code'])) {
    $authUrl = $provider->getAuthorizationUrl([
        'scope'       => ['https://mail.google.com/'],  // Puoi usare anche uno scope più ristretto come "https://www.googleapis.com/auth/gmail.send"
        'access_type' => 'offline',  // Necessario per ottenere il refresh token
        'prompt'      => 'consent'   // Forza la richiesta del refresh token anche se l'utente ha già autorizzato
    ]);
    
    // Salva lo stato per sicurezza
    $_SESSION['oauth2state'] = $provider->getState();

    echo "Visita questo URL per autorizzare l'applicazione:<br>";
    echo "<a href='" . htmlspecialchars($authUrl) . "'>" . htmlspecialchars($authUrl) . "</a>";
    exit;
}

// Verifica che lo stato corrisponda per prevenire attacchi CSRF
if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
    unset($_SESSION['oauth2state']);
    exit('Stato non valido');
}

// Con il parametro "code" presente, scambia il codice per ottenere il token
try {
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    // Mostra i dati ottenuti: access token, refresh token e tempo di scadenza
    echo "<h3>Token ottenuti</h3>";
    echo "<p><strong>Access Token:</strong> " . $token->getToken() . "</p>";
    echo "<p><strong>Refresh Token:</strong> " . $token->getRefreshToken() . "</p>";
    echo "<p><strong>Scadenza Token:</strong> " . date('Y-m-d H:i:s', $token->getExpires()) . "</p>";
} catch (Exception $e) {
    exit('Errore ottenendo il token: ' . $e->getMessage());
}
