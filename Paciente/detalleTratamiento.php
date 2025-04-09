<?php
    include("../connection.php");

    // Verifica si se recibió el ID del tratamiento
    if (isset($_GET['IDtratamiento']) && is_numeric($_GET['IDtratamiento'])) {
        $id = $_GET['IDtratamiento'];

        // Consulta que incluye la duración
        $query = "SELECT nombre, detalles, precio, imagenURL, duracion FROM Tratamientos WHERE IDtratamiento = $id LIMIT 1";
        $result = mysqli_query($con, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            $treatment = mysqli_fetch_assoc($result);
        } else {
            die("Tratamiento no encontrado.");
        }
    } else {
        die("ID de tratamiento no válido.");
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Tratamiento</title>
    <style>
        .container {
            max-width: 600px;
            margin: 50px auto;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .container img {
            width: 100%;
            height: auto;
            border-radius: 8px;
        }

        .container h1 {
            font-size: 24px;
            margin: 20px 0;
        }

        .container p {
            font-size: 18px;
            margin: 10px 0;
        }

        .container .btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }

        .container .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($treatment['nombre']); ?></h1>
        <img src="<?php echo htmlspecialchars($treatment['imagenURL']); ?>" alt="<?php echo htmlspecialchars($treatment['nombre']); ?>">
        <p><strong>Detalles:</strong> <?php echo htmlspecialchars($treatment['detalles']); ?></p>
        <p><strong>Precio:</strong> $<?php echo htmlspecialchars($treatment['precio']); ?></p>
        <p><strong>Duración:</strong> <?php echo htmlspecialchars($treatment['duracion']); ?> minuto</p>
        <a href="catalogoTratamientos.php" class="btn">Regresar</a>
    </div>
</body>
</html>
