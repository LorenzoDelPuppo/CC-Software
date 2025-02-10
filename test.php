<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <form action="logout.php" method="post">
    <button type="submit">Logout</button>
    </form>
    <title>Checkbox con Relazioni Multiple</title>
    <script>
        // Relazioni obbligatorie (se seleziono una, anche l'altra si seleziona)
        const mandatoryRelations = {
            2: [1],
            3: [1],
            4: [1],
            5: [1],
            7: [1],
            8: [1],
            9: [1]
        };

        // Relazioni di incompatibilità (se seleziono una, l'altra si disabilita)
        const incompatibleRelations = {
            3: [7],
            4: [7, 8],
            5: [6, 7],
            6: [5, 8],
            7: [3, 4, 5, 6],
            8: [3, 4, 6],
        };

        function updateCheckboxStates(checkbox) {
            const selectedValue = parseInt(checkbox.value);
            
            // Se la checkbox è stata selezionata, attiva quelle obbligatorie
            if (checkbox.checked && mandatoryRelations[selectedValue]) {
                mandatoryRelations[selectedValue].forEach(value => {
                    const relatedCheckbox = document.querySelector(`input[value="${value}"]`);
                    if (relatedCheckbox) {
                        relatedCheckbox.checked = true;
                    }
                });
            }

            // Disabilita le checkbox incompatibili
            document.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                const cbValue = parseInt(cb.value);
                
                if (incompatibleRelations[selectedValue] && incompatibleRelations[selectedValue].includes(cbValue)) {
                    cb.disabled = checkbox.checked;  // Disabilita se selezionata
                    if (checkbox.checked) {
                        cb.checked = false;  // Deseleziona la checkbox disabilitata
                    } else {
                        cb.disabled = false;  // Riabilita se deselezionata
                    }
                }
            });
        }
    </script>
</head>
<body>
    <h1>Checkbox con Relazioni</h1>
    
    <form method="post">
        <input type="checkbox" name="checkboxes[]" value="1" onchange="updateCheckboxStates(this)"> Checkbox 1<br>
        <input type="checkbox" name="checkboxes[]" value="2" onchange="updateCheckboxStates(this)"> Checkbox 2<br>
        <input type="checkbox" name="checkboxes[]" value="3" onchange="updateCheckboxStates(this)"> Checkbox 3<br>
        <input type="checkbox" name="checkboxes[]" value="4" onchange="updateCheckboxStates(this)"> Checkbox 4<br>
        <input type="checkbox" name="checkboxes[]" value="5" onchange="updateCheckboxStates(this)"> Checkbox 5<br>
        <input type="checkbox" name="checkboxes[]" value="6" onchange="updateCheckboxStates(this)"> Checkbox 6<br>
        <input type="checkbox" name="checkboxes[]" value="7" onchange="updateCheckboxStates(this)"> Checkbox 7<br>
        <input type="checkbox" name="checkboxes[]" value="8" onchange="updateCheckboxStates(this)"> Checkbox 8<br>
        <input type="checkbox" name="checkboxes[]" value="9" onchange="updateCheckboxStates(this)"> Checkbox 9<br>
        <input type="checkbox" name="checkboxes[]" value="10" onchange="updateCheckboxStates(this)"> Checkbox 10<br>
        <button type="submit">Invia</button>
    </form>

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
</body>
</html>


