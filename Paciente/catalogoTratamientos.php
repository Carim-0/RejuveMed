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
    <title>Bienvenido </title>
    <link rel="stylesheet" href="tratamientos_style.css">
    <style>
        .treatments {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }

        .treatment {
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            width: 200px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .treatment img {
            width: 100%;
            height: auto;
            border-radius: 8px;
        }

        .treatment h3 {
            font-size: 18px;
            margin: 10px 0;
        }

        .treatment button {
            background-color: rgb(45, 153, 165);
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        .treatment button:hover {
            background-color: rgb(45, 153, 165);
        }

        .header-buttons {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
        }

        .header-button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        .header-button:hover {
            background-color: #0056b3;
        }

        .header-button.historial {
            background-color: #007bff;
        }

        .header-button.historial:hover {
            background-color: #007bff;
        }
    </style>
</head>
<body>
    <div class="header-buttons">
        <button class="header-button historial" onclick="window.location.href='VerHistorial_Paciente.php'">
            <i class="fas fa-history"></i> Ver Historial
        </button>
        <button class="header-button" onclick="window.location.href='../verPerfil.php'">
            <i class="fas fa-user"></i> Ver Perfil
        </button>
    </div>

    <div class="container">
        <!-- Mensaje de bienvenida -->
        <h1><br>
        Hola, <?php echo $user_data['nombre']; ?></h1>

        <!-- Opciones Agendar cita o ver citas -->
        <div class="options">
        <button class="btn" onclick="window.location.href='pacienteAgendarCita.php'">Agendar una cita</button>
            
        </div>

        <!-- Calendario e Icono -->
        <div class="calendar-section">
            <div class="calendar-icon">
                <img src="../IMG/calendar-icon.png" alt="Calendario" width="30" height="30">
            </div>
            <button class="btn" onclick="window.location.href='verCitas_Paciente.php'">Ver citas agendadas</button>
        </div>

        <!-- Ver catálogo de tratamientos -->
        

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