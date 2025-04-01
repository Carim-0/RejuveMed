<?php
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

        <div class="treatments">
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<div class='treatment'>";
                    echo "<h3>" . htmlspecialchars($row['nombre']) . "</h3>";
                    echo "<img src='images/" . htmlspecialchars($row['imagen']) . "' alt='" . htmlspecialchars($row['nombre']) . "'>";
                    echo "<button class='btn'>Ver tratamiento</button>";
                    echo "</div>";
                }
            } else {
                echo "<p>No hay tratamientos disponibles.</p>";
            }
            ?>
        </div>
    </div>
</body>
</html>

<?php
$con->close();
?>