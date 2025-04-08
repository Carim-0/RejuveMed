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
    <title>Catálogo de Tratamientos</title>
    <link rel="stylesheet" href="tratamientos_style.css">
    <style>
        /* Estilos base para el diseño */
        :root {
            --color-primario: #4a8cff; /* Azul similar al de doctora */
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

        .catalog-container {
            background-color: var(--color-fondo);
            border-radius: 10px;
            box-shadow: var(--sombra);
            width: 100%;
            max-width: 1000px;
            padding: 20px;
        }

        .catalog-header {
            background-color: var(--color-primario);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .catalog-header h1 {
            font-size: 28px;
            font-weight: 600;
        }

        .catalog-body {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }

        .treatment-card {
            background-color: var(--color-secundario);
            border-radius: 10px;
            width: 300px;
            box-shadow: var(--sombra);
            overflow: hidden;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .treatment-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.2);
        }

        .treatment-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .treatment-card-content {
            padding: 15px;
        }

        .treatment-card h3 {
            font-size: 20px;
            margin-bottom: 10px;
        }

        .treatment-card p {
            font-size: 14px;
            color: var(--color-texto);
            margin-bottom: 10px;
        }

        .treatment-card .price {
            font-size: 18px;
            font-weight: 600;
            color: var(--color-primario);
            margin-bottom: 15px;
        }

        .btn-view {
            display: inline-block;
            padding: 12px 20px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            background-color: var(--color-primario);
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            margin-bottom: 10px;
        }

        .btn-view:hover {
            background-color: #3a7ae8;
            transform: scale(1.1); /* Pulsación al hacer hover */
        }

        /* Estilos para los botones de la parte superior */
        .header-buttons {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
        }

        .header-button {
            padding: 12px 24px;
            background-color: var(--color-primario);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .header-button:hover {
            background-color: #0056b3;
            transform: translateY(-6px);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.2);
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
        <h1>Catálogo de Tratamientos</h1>
        <div class="treatments">
            <?php
                // Loop through the fetched data and display each treatment
                while ($row = mysqli_fetch_assoc($result)):
            ?>
                <div class="treatment">
                    <img src="<?php echo htmlspecialchars($row['imagenURL']); ?>" alt="<?php echo htmlspecialchars($row['nombre']); ?>">
                    <h3><?php echo htmlspecialchars($row['nombre']); ?></h3>
                    <button class="btn-view" onclick="window.location.href='detalleTratamiento.php?IDtratamiento=<?php echo $row['IDtratamiento']; ?>'">
                        Ver detalles
                    </button>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>
