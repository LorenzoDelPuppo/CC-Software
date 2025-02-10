
    <?php
    require_once 'connect.php';
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        if (isset($_POST['checkboxes']) && is_array($_POST['checkboxes'])) {
            $selectedCheckboxes = implode(", ", $_POST['checkboxes']);

            $sql = "INSERT INTO servicesOfAppointment (appointment_id, service_id, sPera) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
    
            // Ciclo su tutti i servizi selezionati
            foreach ($_POST['checkboxes'] as $service_id) {
                $service_id = intval($service_id);
                $stmt->bind_param("iis", $appointment_id, $service_id, $sPera);
                $stmt->execute();
            }
    
            $stmt->close();
        } else {
            echo "<h2>Nessuna checkbox selezionata.</h2>";
        }
    
    // Chiudi la connessione al database
    $conn->close();
        } 

    ?>



