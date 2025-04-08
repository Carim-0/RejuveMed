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
        /* Estilo general para el contenedor del carrusel */
        .carousel-container {
            position: relative;
            width: 500px;
            height: 500px;
            margin: 0 auto;
            border-radius: 50%;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* El contenedor que rodea todos los elementos del carrusel */
        .carousel {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: nowrap;
            animation: rotate 15s infinite linear;
        }

        /* Animación para el carrusel circular */
        @keyframes rotate {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        /* Estilo para cada tratamiento en el carrusel */
        .treatment {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f8f9fa;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            position: absolute;
        }

        /* Estilo para las imágenes dentro del tratamiento */
        .treatment img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        /* Estilo para el texto de nombre del tratamiento */
        .treatment h3 {
            position: absolute;
            bottom: 10px;
            color: white;
            font-weight: bold;
            font-size: 14px;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.7);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Mensaje de bienvenida -->
        <h1>Hola, <?php echo $user_data['nombre']; ?></h1>

        <!-- Carrusel de tratamientos -->
        <div class="carousel-container">
            <div class="carousel">
                <?php
                    // Contador para los ángulos de los tratamientos
                    $angleStep = 360 / mysqli_num_rows($result);
                    $i = 0;
                    
                    // Loop through the fetched data and display each treatment in the circular carousel
                    while ($row = mysqli_fetch_assoc($result)) {
                        // Calcula el ángulo para cada elemento
                        $angle = $i * $angleStep;
                        echo "<div class='treatment' style='transform: rotate(" . $angle . "deg) translateX(200px);'>";
                        echo "<img src='" . htmlspecialchars($row['imagenURL']) . "' alt='" . htmlspecialchars($row['nombre']) . "'>";
                        echo "<h3>" . htmlspecialchars($row['nombre']) . "</h3>";
                        echo "</div>";
                        $i++;
                    }
                ?>
            </div>
        </div>
    </div>
</body>
</html>
