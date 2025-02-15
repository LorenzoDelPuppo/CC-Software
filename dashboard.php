<?php
// Blocco di controllo accessi
session_start();
require_once 'connect.php';

// Verifica se l'utente è loggato
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

$email = $_SESSION['email'];

// Recupera il ruolo dell'utente dal database
$sql = "SELECT user_tipe FROM customer WHERE email = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Errore nella preparazione della query: " . $conn->error);
}
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($userType);
$stmt->fetch();
$stmt->close();

// Controllo: solo amministratore e operatrice possono accedere
if ($userType !== 'amministratore' && $userType !== 'operatrice') {
    header("Location: access_denied.php");
    exit;
}
?>
<!DOCTYPE html> 
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="top-bar">
  <div class="left-section">
  </div>
  <div class="center-section">
    <a href="menu.php">
      <img src="style/rullino/logo.png" alt="Logo" class="logo" />
    </a>
  </div>

  <div class="right-section">
  <div class="user-menu">
  <!-- Icona utente (o un'immagine) -->
  <span class="user-icon">&#128100;</span>
  
  <!-- Dropdown -->
  <div class="dropdown-menu">
    <a href="profilo.php" class="dropdown-item">Profilo</a>
    <a href="impostazioni.php" class="dropdown-item">Impostazioni</a>
    <hr class="dropdown-separator">
    <a href="logout.php" class="dropdown-item logout-item">Logout</a>
  </div>
</div>
</div>

</div>

    <!-- Barra di ricerca per la data -->
    <form action="" method="POST">
        <input type="date" name="search_date" value="<?php echo $selectedDate ? $selectedDate : ''; ?>" placeholder="Seleziona una data">
        <button type="submit">Cerca Appuntamenti</button>
    </form>
    <form action="" method="POST">
        <button type="submit" name="search_date" value="">Mostra tutti gli appuntamenti</button>
    </form>

    <h2>Appuntamenti per il giorno: <?php echo $selectedDate ? date("d-m-Y", strtotime($selectedDate)) : "Tutti i Giorni"; ?></h2>

    <!-- Colonne per le preferenze -->
    <div class="column" style="background-color: #f8d7da;">
        <h3>Preferenza: Barbara</h3>
        <?php
        if (!empty($appointmentsByPreference['Barbara'])) {
            foreach ($appointmentsByPreference['Barbara'] as $appointmentKey => $appointments) {
                echo "<div class='appointment-box'>";
                // Stampa il nome cliente solo una volta per ogni appuntamento
                echo "<strong>Cliente: " . $appointments[0]['cliente'] . "</strong><br>";
                echo "<strong>Data: " . date("d-m-Y H:i", strtotime($appointmentKey)) . "</strong><br>";
                foreach ($appointments as $appt) {
                    echo $appt['nameS'] . " (" . $appt['engageTime'] . " min)<br>"; // Stampa solo il servizio
                }
                // Aggiungi il form di modifica della preferenza una sola volta
                echo "<form action='' method='POST'>
                        <input type='hidden' name='appointment_id' value='" . $appointments[0]['appointment_id'] . "'>
                        <select name='preference'>
                            <option value='Barbara' " . ($preference == 'Barbara' ? 'selected' : '') . ">Barbara</option>
                            <option value='Giulia' " . ($preference == 'Giulia' ? 'selected' : '') . ">Giulia</option>
                            <option value='Casuale' " . ($preference == 'Casuale' ? 'selected' : '') . ">Casuale</option>
                        </select>
                        <button type='submit' name='update_preference'>Modifica Preferenza</button>
                      </form>";
                echo "</div>";
            }
        } else {
            echo "<p>Nessun appuntamento.</p>";
        }
        ?>
    </div>

    <div class="column" style="background-color: #d1ecf1;">
        <h3>Preferenza: Giulia</h3>
        <?php
        if (!empty($appointmentsByPreference['Giulia'])) {
            foreach ($appointmentsByPreference['Giulia'] as $appointmentKey => $appointments) {
                echo "<div class='appointment-box'>";
                // Stampa il nome cliente solo una volta per ogni appuntamento
                echo "<strong>Cliente: " . $appointments[0]['cliente'] . "</strong><br>";
                echo "<strong>Data: " . date("d-m-Y H:i", strtotime($appointmentKey)) . "</strong><br>";
                foreach ($appointments as $appt) {
                    echo $appt['nameS'] . " (" . $appt['engageTime'] . " min)<br>"; // Stampa solo il servizio
                }
                // Aggiungi il form di modifica della preferenza una sola volta
                echo "<form action='' method='POST'>
                        <input type='hidden' name='appointment_id' value='" . $appointments[0]['appointment_id'] . "'>
                        <select name='preference'>
                            <option value='Barbara' " . ($preference == 'Barbara' ? 'selected' : '') . ">Barbara</option>
                            <option value='Giulia' " . ($preference == 'Giulia' ? 'selected' : '') . ">Giulia</option>
                            <option value='Casuale' " . ($preference == 'Casuale' ? 'selected' : '') . ">Casuale</option>
                        </select>
                        <button type='submit' name='update_preference'>Modifica Preferenza</button>
                      </form>";
                echo "</div>";
            }
        } else {
            echo "<p>Nessun appuntamento.</p>";
        }
        ?>
    </div>

    <div class="column" style="background-color: #d4edda;">
        <h3>Preferenza: Casuale</h3>
        <?php
        if (!empty($appointmentsByPreference['Casuale'])) {
            foreach ($appointmentsByPreference['Casuale'] as $appointmentKey => $appointments) {
                echo "<div class='appointment-box'>";
                // Stampa il nome cliente solo una volta per ogni appuntamento
                echo "<strong>Cliente: " . $appointments[0]['cliente'] . "</strong><br>";
                echo "<strong>Data: " . date("d-m-Y H:i", strtotime($appointmentKey)) . "</strong><br>";
                foreach ($appointments as $appt) {
                    echo $appt['nameS'] . " (" . $appt['engageTime'] . " min)<br>"; // Stampa solo il servizio
                }
                // Aggiungi il form di modifica della preferenza una sola volta
                echo "<form action='' method='POST'>
                        <input type='hidden' name='appointment_id' value='" . $appointments[0]['appointment_id'] . "'>
                        <select name='preference'>
                            <option value='Barbara' " . ($preference == 'Barbara' ? 'selected' : '') . ">Barbara</option>
                            <option value='Giulia' " . ($preference == 'Giulia' ? 'selected' : '') . ">Giulia</option>
                            <option value='Casuale' " . ($preference == 'Casuale' ? 'selected' : '') . ">Casuale</option>
                        </select>
                        <button type='submit' name='update_preference'>Modifica Preferenza</button>
                      </form>";
                echo "</div>";
            }
        } else {
            echo "<p>Nessun appuntamento.</p>";
        }
        ?>
    </div>

    <div class="clearfix"></div>

    <!-- Sezione pulsanti -->
    <div class="button-container" style="display: flex; gap: 10px;">
        <form action="prenota.php" method="post">
            <button type="submit">Aggiungi Appuntamento</button>
        </form>
        <form action="calendario.php" method="get">
            <button type="submit">Calendario</button>
        </form>
        <form action="visualizza_clienti.php" method="get">
            <button type="submit">Gestione Clienti</button>
        </form>
        <form action="aggiungi_utente.php" method="get">
            <button type="submit">Aggiungi Cliente</button>
        </form>
        <form action="cerca_appuntamento.php" method="get">
            <button type="submit">Modifica Appuntamento</button>
        </form>
    </div>

</body>
</html>
