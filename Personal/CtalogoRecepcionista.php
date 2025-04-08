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
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Tratamientos</title>
    <style>
        :root {
            --color-primario: #1a37b5; /*Azul original*/
            --color-secundario: #f8f9fa;
            --color-terciario: #e9ecef;
            --color-exito: #28a745;
            --color-error: #dc3545;
            --color-texto: #212529;
            --color-borde: #e0e0e0;
        }

        body {
            background-color: var(--color-secundario);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-bottom: 20px;
        }

        .header-container {
            background-color: var(--color-primario);
            color: white;
            padding: 15px 0;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: relative;
        }

        .profile-button {
            background-color: white;
            color: var(--color-primario);
            border: none;
            border-radius: 50px;
            padding: 6px 15px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s;
            position: absolute;
            top: 15px;
            right: 15px;
        }

        .profile-button:hover {
            background-color: #f0f0f0;
            transform: translateY(-2px);
        }

        /* Estilos para los tratamientos */
        .treatments {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
            padding: 0 15px;
        }

        .treatment {
            text-align: center;
            border: 1px solid var(--color-borde);
            border-radius: 12px;
            padding: 20px;
            width: 250px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            background-color: white;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .treatment:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 15px rgba(0,0,0,0.2);
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
            color: var(--color-texto);
            font-weight: bold;
            margin-bottom: 12px;
        }

        .treatment button {
            background-color: var(--color-primario);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            width: 100%;
        }

        .treatment button:hover {
            background-color: #142a8a;
            transform: translateY(-2px);
            box-shadow: 0 6px 10px rgba(0,0,0,0.15);
        }

        @media (max-width: 768px) {
            .treatment {
                width: 200px;
            }
        }

        @media (max-width: 576px) {
            .treatment {
                width: 100%;
                max-width: 300px;
            }
            
            .profile-button {
                position: relative;
                top: auto;
                right: auto;
                display: block;
                margin: 10px auto;
                width: fit-content;
            }
        }

        .buttons-container {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }

        .main-button {
            background-color: var(--color-primario);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            cursor: pointer;
            font-size: 16px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .main-button:hover {
            background-color: #142a8a;
            transform: translateY(-2px);
            box-shadow: 0 6px 10px rgba(0,0,0,0.15);
        }

        .main-button i {
            margin-right: 8px;
        }
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
        <button class="main-button" onclick="window.location.href='agendarCitaRecepcionista.php'">
            <i class="fas fa-calendar-plus"></i> Agendar una cita
        </button>
        
        <button class="main-button" onclick="window.location.href='verCitasRecepcionista.php'">
            <i class="fas fa-calendar-check"></i> Ver todas las citas
        </button>

        <!-- Botón de Ver Historial -->
        <button class="main-button" onclick="window.location.href='verHistorial.php'">
            <i class="fas fa-history"></i> Ver Historial Clínico
        </button>
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
