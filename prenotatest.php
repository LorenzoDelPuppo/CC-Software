<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkbox Relazioni Multiple</title>
    <script>
        // Oggetto che definisce tutte le relazioni
        const relations = {
            1: [1],
            2: [2],
            3: [3, 5, 6, 7, 8],
            4: [3, 4, 5, 6, 7, 8],
            5: [3, 4, 5, 6, 7],
            6: [3, 4, 5, 6, 7, 8],
            7: [3, 4, 5, 6, 7, 8],
            8: [3, 4, 5, 6, 7, 8],
            9: [9],
            10: [10]
        };

        // Funzione per aggiornare lo stato delle checkbox
        function updateCheckboxStates(checkbox) {
            const selectedValue = parseInt(checkbox.value); // Valore della checkbox selezionata

            // Scorri tutte le checkbox
            document.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                const cbValue = parseInt(cb.value); // Ottieni il valore della checkbox corrente
                
                // Se il valore della checkbox corrente è correlato alla checkbox selezionata
                if (relations[selectedValue] && relations[selectedValue].includes(cbValue)) {
                    // Disabilita o abilita in base allo stato della checkbox selezionata
                    cb.disabled = checkbox.checked && cb !== checkbox;
                }
            });
        }
    </script>
</head>
<body>
    <h1>Checkbox con Relazioni</h1>
    
    <!-- Modulo con le checkbox -->
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
    // Controlla se il modulo è stato inviato
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        // Controlla se sono state selezionate checkbox
        if (isset($_POST['checkboxes']) && is_array($_POST['checkboxes'])) {
            // Ottieni i valori delle checkbox selezionate
            $selectedCheckboxes = $_POST['checkboxes'];

            // Converte l'array in una stringa separata da virgole
            $result = implode(", ", $selectedCheckboxes);

            // Mostra il risultato
            echo "<h2>Checkbox selezionate:</h2>";
            echo "<p>$result</p>";
        } else {
            // Nessuna checkbox selezionata
            echo "<h2>Nessuna checkbox selezionata.</h2>";
        }
    }
    ?>
</body>
</html>
