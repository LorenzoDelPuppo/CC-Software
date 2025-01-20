<?php
require_once 'connect.php';

// Recupera i servizi dal database
$sql = "SELECT service_id, nameS FROM serviceCC";
$result = $conn->query($sql);

if (!$result) {
    die("Errore nella query: " . $conn->error);
}

$services = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
} else {
    echo "<p style='color:red; text-align:center;'>Nessun servizio disponibile. Controlla i dati nel database.</p>";
}

// Recupera le incompatibilità
$sql_incompat = "SELECT service_id1, service_id2 FROM incompatible";
$result_incompat = $conn->query($sql_incompat);

if (!$result_incompat) {
    die("Errore nella query delle incompatibilità: " . $conn->error);
}

$incompatibilities = [];
if ($result_incompat->num_rows > 0) {
    while ($row = $result_incompat->fetch_assoc()) {
        $incompatibilities[] = [$row['service_id1'], $row['service_id2']];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Prenotazione Servizi</title>
    <style>
        .service-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            max-width: 600px;
            margin: 20px auto;
            justify-content: center;
        }
        .service-checkbox {
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 150px;
            text-align: center;
        }
        .service-checkbox.disabled {
            background-color: #f0f0f0;
            cursor: not-allowed;
        }
    </style>
    <script>
        const incompatibilities = <?php echo json_encode($incompatibilities); ?>;

        function checkIncompatibilities(serviceId) {
            incompatibilities.forEach(pair => {
                if (pair[0] == serviceId || pair[1] == serviceId) {
                    let toDisable = pair[0] == serviceId ? pair[1] : pair[0];
                    let checkboxDiv = document.getElementById('container-' + toDisable);
                    let checkbox = document.getElementById('service-' + toDisable);

                    if (checkbox.checked) {
                        checkboxDiv.classList.add('disabled');
                        checkbox.disabled = true;
                    } else {
                        checkboxDiv.classList.remove('disabled');
                        checkbox.disabled = false;
                    }
                }
            });
        }
    </script>
</head>
<body>
    <h1 style="text-align:center;">Seleziona i Servizi</h1>
    <form method="POST" action="">
        <div class="service-container">
            <?php if (count($services) > 0): ?>
                <?php foreach ($services as $service): ?>
                    <div class="service-checkbox" id="container-<?php echo $service['service_id']; ?>">
                        <input type="checkbox" id="service-<?php echo $service['service_id']; ?>" 
                               name="services[]" value="<?php echo $service['service_id']; ?>" 
                               onclick="checkIncompatibilities(<?php echo $service['service_id']; ?>)">
                        <label for="service-<?php echo $service['service_id']; ?>">
                            <?php echo htmlspecialchars($service['nameS']); ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align:center; color:red;">Nessun servizio disponibile.</p>
            <?php endif; ?>
        </div>

        <!-- Aggiunta data e ora -->
        <div style="text-align:center; margin-top:20px;">
            <label for="appointmentDate">Seleziona la data e ora dell'appuntamento:</label><br>
            <input type="datetime-local" id="appointmentDate" name="appointmentDate" required><br><br>
        </div>

        <div style="text-align:center; margin-top:20px;">
            <button type="submit">Prenota</button>
        </div>
    </form>

    <?php
    // Se il form viene inviato
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['services']) && count($_POST['services']) > 0 && isset($_POST['appointmentDate'])) {
            // Ottieni l'ID del cliente (per esempio da una sessione o un campo nascosto)
            $customer_id = 1; // Supponiamo che il cliente con ID 1 stia facendo la prenotazione
            $appointment_date = $_POST['appointmentDate'];

            // 1. Inserisci l'appuntamento
            $stmt = $conn->prepare("INSERT INTO appointment (customer_id, dateTime) VALUES (?, ?)");
            $stmt->bind_param("is", $customer_id, $appointment_date);
            $stmt->execute();
            $appointment_id = $stmt->insert_id; // Recupera l'ID dell'appuntamento appena inserito

            // 2. Inserisci i servizi selezionati
            foreach ($_POST['services'] as $service_id) {
                // Inserisci il servizio selezionato nella tabella servicesOfAppointment
                $stmt = $conn->prepare("INSERT INTO servicesOfAppointment (appointment_id, service_id, sPera) VALUES (?, ?, ?)");
                // Usa un nome di servizio o un altro parametro per il campo sPera (facoltativo)
                $selected_services = implode(", ", $_POST['services']); 
                $stmt->bind_param("iis", $appointment_id, $service_id, $selected_services);
                $stmt->execute();
            }

            echo "<p style='text-align:center;'>Prenotazione completata con successo!</p>";
        } else {
            echo "<p style='color:red; text-align:center;'>Nessun servizio selezionato o data mancante.</p>";
        }
    }
    ?>
</body>
</html>
