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
    <title>Bienvenido</title>
    <link rel="stylesheet" href="tratamientos_style.css">
    <style>
        /* Estilo general para el contenedor de tratamientos */
        .treatments {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            margin-top: 40px;
        }

        /* Estilo para cada caja de tratamiento */
        .treatment {
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            width: 250px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            background-color: #f8f9fa; /* Fondo más suave */
            transition: all 0.3s ease; /* Transición suave al hacer hover */
        }

        .treatment:hover {
            transform: translateY(-10px); /* Efecto de elevarse al hacer hover */
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.2); /* Sombra más fuerte */
        }

        .treatment img {
            width: 100%;
            height: 100px; /* Ajusta la altura */
            object-fit: cover; /* Mantiene la imagen recortada correctamente */
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .treatment h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .treatment button {
            background-color: #28a745; /* Verde */
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-transform: uppercase;
            transition: background-color 0.3s;
            margin-top: 10px;
        }

        .treatment button:hover {
            background-color: #218838; /* Verde oscuro */
        }

        /* Estilo para el mensaje de bienvenida */
        h1 {
            background-color: #007bff; /* Azul */
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            margin-top: 40px;
            font-size: 24px;
            font-weight: bold;
        }

        /* Estilo para los botones de la parte superior */
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
            font-size: 14px;
        }

        .header-button:hover {
            background-color: #0056b3;
        }

        /* Botones específicos para "Ver citas" y "Agendar" */
        .btn {
            background-color: #007bff; /* Azul */
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            display: inline-block;
            margin-top: 20px;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        .calendar-section {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .calendar-icon img {
            width: 30px;
            height: 30px;
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
        <!-- Mensaje de bienvenida con diseño mejorado -->
        <h1>Hola, <?php echo $user_data['nombre']; ?></h1>

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

        <!-- Tratamientos con imágenes y diseño mejorado -->
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
