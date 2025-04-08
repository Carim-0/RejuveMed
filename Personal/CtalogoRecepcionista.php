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
    <!-- Agregar los archivos CSS de Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .carousel-inner img {
            width: 100%; /* Asegura que las imágenes ocupen el 100% del ancho */
            height: 300px; /* Limita la altura para que no sean tan grandes */
            object-fit: cover; /* Asegura que las imágenes se recorten bien */
        }

        /* Estilo para el botón "Ver citas agendadas" */
        .btn.ver-citas {
            background-color: #007bff; /* Azul */
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        .btn.ver-citas:hover {
            background-color: #0056b3; /* Azul oscuro */
        }

        /* Estilo para el botón "Ver tratamiento" */
        .treatment button.ver-tratamiento {
            background-color: #28a745; /* Verde */
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }

        .treatment button.ver-tratamiento:hover {
            background-color: #218838; /* Verde oscuro */
        }

        /* Estilo para el saludo con cuadro azul y letras blancas */
        h1 {
            background-color: #007bff; /* Azul */
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            margin-top: 40px;
            font-size: 24px;
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
        <!-- Mensaje de bienvenida con estilo -->
        <h1>Hola, <?php echo $user_data['nombre']; ?></h1>

        <!-- Carrusel de imágenes -->
        <div id="carouselExample" class="carousel slide my-4" data-bs-ride="carousel">
            <div class="carousel-inner">
                <?php
                    $first = true;
                    // Loop through the fetched data and display each treatment image in the carousel
                    while ($row = mysqli_fetch_assoc($result)) {
                        $activeClass = $first ? " active" : "";
                        echo "<div class='carousel-item$activeClass'>";
                        echo "<img src='" . htmlspecialchars($row['imagenURL']) . "' class='d-block w-100' alt='" . htmlspecialchars($row['nombre']) . "'>";
                        echo "</div>";
                        $first = false;
                    }
                ?>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#carouselExample" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#carouselExample" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>

        <!-- Opciones Agendar cita o ver citas -->
        <div class="options">
            <button class="btn" onclick="window.location.href='pacienteAgendarCita.php'">Agendar una cita</button>
        </div>

        <!-- Calendario e Icono -->
        <div class="calendar-section">
            <div class="calendar-icon">
                <img src="../IMG/calendar-icon.png" alt="Calendario" width="30" height="30">
            </div>
            <button class="btn ver-citas" onclick="window.location.href='verCitas_Paciente.php'">Ver citas agendadas</button>
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
                    echo "<button class='ver-tratamiento' type='submit'>Ver tratamiento</button>";
                    echo "</form>";
                    echo "</div>";
                }
            ?>
        </div>
    </div>

    <!-- Agregar los archivos JS de Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
