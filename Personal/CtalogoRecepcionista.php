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
        /* Estilos Generales */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f6f9;
            color: #333;
        }

        /* Mensaje de bienvenida */
        h1 {
            background-color: #28a745; /* Color verde suave */
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 12px;
            margin-top: 40px;
            font-size: 26px;
            font-weight: bold;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        h1:hover {
            transform: translateY(-6px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }

        /* Estilo para los botones en el header */
        .header-buttons {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 15px;
        }

        .header-button {
            padding: 14px 28px;
            background-color: #28a745; /* Color verde suave */
            color: white;
            border: none;
            border-radius: 30px; /* Bordes redondeados */
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .header-button:hover {
            background-color: #218838; /* Verde más oscuro */
            transform: translateY(-6px);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.2);
        }

        /* Botón para agendar cita y ver citas */
        .btn {
            background-color: #007bff; /* Color azul para destacar */
            color: white;
            padding: 14px 28px;
            border-radius: 30px; /* Bordes redondeados */
            cursor: pointer;
            font-size: 18px;
            display: inline-block;
            transition: all 0.3s ease;
            margin-top: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn:hover {
            background-color: #0056b3; /* Azul más oscuro al hacer hover */
            transform: translateY(-6px);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.2);
        }

        /* Contenedor de tratamientos */
        .treatments {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            margin-top: 40px;
        }

        /* Estilo para las cajas de tratamiento */
        .treatment {
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 12px;
            padding: 20px;
            width: 250px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            background-color: #ffffff;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .treatment:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.2);
        }

        .treatment img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 15px;
        }

        .treatment h3 {
            font-size: 20px;
            color: #333;
            font-weight: bold;
            margin-bottom: 12px;
        }

        /* Estilo para los botones de tratamiento */
        .treatment button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 30px;
            cursor: pointer;
            font-size: 16px;
            text-transform: uppercase;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .treatment button:hover {
            background-color: #0056b3;
            transform: translateY(-6px);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.2);
        }

        /* Sección calendario con íconos */
        .calendar-section {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .calendar-icon img {
            width: 35px;
            height: 35px;
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
                <img src="../IMG/calendar-icon.png" alt="Calendario" width="35" height="35">
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
