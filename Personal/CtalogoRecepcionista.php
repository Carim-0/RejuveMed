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
    
    // Obtener citas del día actual
    $today = date('Y-m-d');
    $citas_query = "SELECT c.IDcita, c.fecha, p.nombre AS paciente_nombre, t.nombre AS tratamiento_nombre 
                    FROM Citas c
                    JOIN Pacientes p ON c.IDpaciente = p.IDpaciente
                    JOIN Tratamientos t ON c.IDtratamiento = t.IDtratamiento
                    WHERE DATE(c.fecha) = '$today'
                    ORDER BY c.fecha ASC";
    $citas_result = mysqli_query($con, $citas_query);
    $citas_hoy = [];
    if ($citas_result) {
        $citas_hoy = mysqli_fetch_all($citas_result, MYSQLI_ASSOC);
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Tratamientos</title>
    <style>
        /* Estilos */
    </style>
</head>
<body>
    <div class="header-container">
        <div class="container">
            <h1 class="text-center mb-0">Hola, <?php echo $user_data['nombre']; ?></h1>
            <button class="profile-button" onclick="window.location.href='../verPerfil.php'">
                <i class="fas fa-user-circle"></i> Hola, <?php echo $user_data['nombre']; ?>
            </button>
        </div>
    </div>

    <!-- Botones principales -->
    <div class="buttons-container">
        <!-- Enlace para agendar una cita -->
        <button class="main-button" onclick="location.href='agendarCitaRecepcionista.php'">
            <i class="fas fa-calendar-plus"></i> Agendar una cita
        </button>
        
        <!-- Enlace para ver todas las citas -->
        <button class="main-button" onclick="location.href='verCitasRecepcionista.php'">
            <i class="fas fa-calendar-check"></i> Ver todas las citas
        </button>

        <!-- Enlace para ver pacientes -->
        <button class="main-button" onclick="location.href='verPacientes.php'">
            <i class="fas fa-users"></i> Ver Pacientes
        </button>
        
        <!-- Enlace para ver el historial -->
        <button class="main-button" onclick="location.href='verHistorial.php'">
            <i class="fas fa-history"></i> Ver Historial Clínico
        </button>
        
        <!-- Enlace para ver citas de hoy -->
        <div class="citas-btn-container">
            <button class="main-button" id="verCitasHoyBtn" onclick="location.href='citasHoy.php'">
                <i class="fas fa-calendar-day"></i> Citas de hoy
            </button>
            <?php if(count($citas_hoy) > 0): ?>
                <span class="citas-badge" id="citasBadge"><?php echo count($citas_hoy); ?></span>
            <?php endif; ?>
        </div>
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
                echo "<button type='submit'><i class='fas fa-eye'></i> Ver tratamiento</button>";
                echo "</form>";
                echo "</div>";
            }
        ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
