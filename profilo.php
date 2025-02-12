<?php
session_start();
require_once 'connect.php'; // Includi qui il file di connessione al database

// Verifica che l'utente sia loggato
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php"); // reindirizza alla pagina di login se non è loggato
    exit;
}

$customer_id = $_SESSION['customer_id'];

// Recupera i dati del profilo (escludendo customer_id, password e wants_notification)
$query = "SELECT fName, lName, hair, phoneN, gender, preference, email 
          FROM Customer 
          WHERE customer_id = ?";
if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $profile = $result->fetch_assoc();
    } else {
        die("Errore: Cliente non trovato.");
    }
    $stmt->close();
} else {
    die("Errore nella preparazione della query: " . $conn->error);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profilo Cliente</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 2em;
      background-color: #f5f5f5;
    }
    .profile-container {
      max-width: 600px;
      margin: 0 auto;
      background-color: #fff;
      border: 1px solid #ccc;
      padding: 1.5em;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    h1 {
      text-align: center;
      color: #333;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 1em;
    }
    th, td {
      padding: 0.8em;
      text-align: left;
      border: 1px solid #ddd;
    }
    th {
      background-color: #eee;
    }
  </style>
</head>
<body>
  <div class="profile-container">
    <h1>Il Mio Profilo</h1>
    <table>
      <tr>
        <th>Nome</th>
        <td><?php echo htmlspecialchars($profile['fName']); ?></td>
      </tr>
      <tr>
        <th>Cognome</th>
        <td><?php echo htmlspecialchars($profile['lName']); ?></td>
      </tr>
      <tr>
        <th>Tipo di Capelli</th>
        <td><?php echo htmlspecialchars($profile['hair']); ?></td>
      </tr>
      <tr>
        <th>Telefono</th>
        <td><?php echo htmlspecialchars($profile['phoneN']); ?></td>
      </tr>
      <tr>
        <th>Genere</th>
        <td><?php echo htmlspecialchars($profile['gender']); ?></td>
      </tr>
      <tr>
        <th>Preferenza</th>
        <td><?php echo htmlspecialchars($profile['preference']); ?></td>
      </tr>
      <tr>
        <th>Email</th>
        <td><?php echo htmlspecialchars($profile['email']); ?></td>
      </tr>
    </table>
  </div>
</body>
</html>
