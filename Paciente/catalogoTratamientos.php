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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --color-primario: #4a8cff; /* Azul para tratamientos */
            --color-secundario: #f8f9fa;
            --color-terciario: #e9ecef;
            --color-texto: #212529;
            --color-borde: #ced4da;
            --color-fondo: #ffffff;
            --sombra: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--color-terciario);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            background-color: var(--color-fondo);
            border-radius: 10px;
            box-shadow: var(--sombra);
            width: 100%;
            max-width: 600px;
            overflow: hidden;
        }

        .header {
            background-color: var(--color-primario);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 600;
        }

        .body {
            padding: 30px;
            text-align: center;
        }

        .body img {
            width: 100%;
            height: auto;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .body p {
            font-size: 18px;
            margin: 10px 0;
            color: var(--color-texto);
        }

        .btn {
            display: inline-block;
            padding: 12px 20px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            border: none;
            background-color: var(--color-primario);
            color: white;
            width: 100%;
        }

        .btn:hover {
            background-color: #3a7ae8;
        }

        .btn-link {
            color: var(--color-primario);
            text-decoration: none;
            display: inline-block;
            margin-top: 15px;
            font-size: 14px;
        }

        .btn-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 576px) {
            .body {
                padding: 20px;
            }

            .header {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-prescription-bottle-alt"></i> Detalles del Tratamiento</h1>
        </div>

        <div class="body">
            <h2><?php echo htmlspecialchars($treatment['nombre']); ?></h2>
            <img src="<?php echo htmlspecialchars($treatment['imagenURL']); ?>" alt="<?php echo htmlspecialchars($treatment['nombre']); ?>">
            <p><strong>Detalles:</strong> <?php echo htmlspecialchars($treatment['detalles']); ?></p>
            <p><strong>Precio:</strong> $<?php echo htmlspecialchars($treatment['precio']); ?></p>
            <a href="catalogoTratamientos.php" class="btn">Regresar</a>
        </div>
    </div>
</body>
</html>
