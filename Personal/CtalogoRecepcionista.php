<?php
    session_start();
    
    include("../connection.php");
    include("../functions.php");    

    $user_data = check_login($con);

    // Fetch data from the "Tratamientos" table
    $query = "SELECT IDtratamiento, nombre, imagenURL FROM Tratamientos";
    $result = mysqli_query($con, $query);

    if (!$result) {
        die("Error al obtener los tratamientos: " . mysqli_error($con));
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pantalla Recepcionista</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Bienvenido, Recepcionista</h1>
        
        <div class="options">
            <button class="btn">Agendar nueva cita</button>
            <button class="btn">Ver citas agendadas</button>
        </div>
            <!-- Tratamientos con imágenes -->
        <div class="treatments">
            <?php
                // Loop through the fetched data and display each treatment
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<div class='treatment'>";
                    echo "<h3>" . htmlspecialchars($row['nombre']) . "</h3>";
                    echo "<img src='" . htmlspecialchars($row['imagenURL']) . "' alt='" . htmlspecialchars($row['nombre']) . "'>";
                    echo "<form action='detalleTratamiento.php' method='GET'>";
                    echo "<input type='hidden' name='IDtratamiento' value='" . htmlspecialchars($row['IDtratamiento']) . "'>";
                    echo "<button type='submit'>Ver tratamiento</button>";
                    echo "</form>";
                    echo "</div>";
                }
            ?>
        </div>
    </div>
</body>
</html>
