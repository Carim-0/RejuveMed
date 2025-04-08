<?php
    include("../connection.php");

    // Check if the ID is provided in the URL
    if (isset($_GET['IDtratamiento']) && is_numeric($_GET['IDtratamiento'])) {
        $id = $_GET['IDtratamiento'];

        // Fetch the treatment details from the database
        $query = "SELECT nombre, detalles, precio, imagenURL FROM Tratamientos WHERE IDtratamiento = $id LIMIT 1";
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

        .container {
            max-width: 600px;
            margin: 50px auto;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            background-color: #ffffff;
            transition: all 0.3s ease;
        }

        .container:hover {
            transform: translateY(-6px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }

        .container img {
            width: 100%;
            height: auto;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .container h1 {
            font-size: 28px;
            color: #007bff;
            margin: 20px 0;
            font-weight: bold;
        }

        .container p {
            font-size: 18px;
            margin: 10px 0;
        }

        .container .btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 30px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
            display: inline-block;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .container .btn:hover {
            background-color: #218838;
            transform: translateY(-6px);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.2);
        }

        /* Diseño del header similar al de la doctora */
        .header-buttons {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 15px;
        }

        .header-button {
            padding: 14px 26px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .header-button:hover {
            background-color: #0056b3;
            transform: translateY(-6px);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.2);
        }

        /* Estilo del contenedor de los botones */
        .button-container {
            margin-top: 40px;
            display: flex;
            justify-content: center;
            gap: 20px;
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
        <h1><?php echo htmlspecialchars($treatment['nombre']); ?></h1>
        <img src="<?php echo htmlspecialchars($treatment['imagenURL']); ?>" alt="<?php echo htmlspecialchars($treatment['nombre']); ?>">
        <p><strong>Detalles:</strong> <?php echo htmlspecialchars($treatment['detalles']); ?></p>
        <p><strong>Precio:</strong> $<?php echo htmlspecialchars($treatment['precio']); ?></p>
        <a href="catalogoTratamientos.php" class="btn">Regresar</a>
    </div>
</body>
</html>
