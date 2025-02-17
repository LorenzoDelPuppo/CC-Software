<?php
require __DIR__ . '/vendor/autoload.php';

function getClient()
{
    $client = new Google_Client();
    $client->setApplicationName('Checapelli');
	$client->setScopes(Google\Service\Gmail::MAIL_GOOGLE_COM);
    $client->setAuthConfig('client_secret_648357072665-qtqb4imi06t5ssmcbl7vpm7mkrcfs276.apps.googleusercontent.com.json'); // Il tuo file JSON
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    // Carica il token salvato in precedenza (se esiste)
    $tokenPath = 'token.json';
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
    }

    // Se il token non esiste o è scaduto, procedi con l'autenticazione
    if ($client->isAccessTokenExpired()) {
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        } else {
            // Genera il link per l'autorizzazione
            $authUrl = $client->createAuthUrl();
            printf("Apri il seguente link nel browser:\n%s\n", $authUrl);
            print 'Inserisci il codice di verifica: ';
            $authCode = trim(fgets(STDIN));

            // Scambia il codice di verifica con un token di accesso
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);

            if (array_key_exists('error', $accessToken)) {
                throw new Exception(join(', ', $accessToken));
            }
        }
        // Salva il token su file
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    }
    return $client;
}

// Ottieni il client
$client = getClient();

// Crea il servizio Gmail
$service = new Google\Service\Gmail($client);

// Esempio di chiamata: lista delle etichette
$user = 'me';
$results = $service->users_labels->listUsersLabels($user);

if (count($results->getLabels()) === 0) {
    echo "Nessuna etichetta trovata.\n";
} else {
    echo "Etichette trovate:\n";
    foreach ($results->getLabels() as $label) {
        printf("- %s\n", $label->getName());
    }
}
?>