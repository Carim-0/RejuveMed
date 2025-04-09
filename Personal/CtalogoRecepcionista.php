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
    
    // Obtener citas del día actual
    $today = date('Y-m-d');
    $citas_query = "SELECT c.IDcita, c.fecha, p.nombre AS paciente_nombre, t.nombre AS tratamiento_nombre 
                    FROM Citas c
                    JOIN Pacientes p ON c.IDpaciente = p.IDpaciente
                    JOIN Tratamientos t ON c.IDtratamiento = t.IDtratamiento
                    WHERE DATE(c.fecha) = '$today'
                    ORDER BY c.fecha ASC";
    $citas_result = mysqli_query($con, $citas_query);
    $citas_hoy = [];
    if ($citas_result) {
        $citas_hoy = mysqli_fetch_all($citas_result, MYSQLI_ASSOC);
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
            --color-primario: #1a37b5; /* Azul original */
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
            padding: 20px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: relative;
        }

        .profile-button {
            background-color: white;
            color: var(--color-primario);
            border: none;
            border-radius: 50px;
            padding: 8px 20px;
            font-weight: 500;
            font-size: 1rem;
            transition: all 0.3s;
            position: absolute;
            top: 20px;
            right: 20px;
        }

        .profile-button:hover {
            background-color: #f0f0f0;
            transform: translateY(-2px);
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
            padding: 12px 25px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 16px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .main-button:hover {
            background-color: #142a8a;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

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
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }

        .treatment img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 15px;
        }

        .treatment h3 {
            font-size: 20px;
            color: var(--color-texto);
            font-weight: bold;
            margin-bottom: 15px;
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

        .citas-btn-container {
            display: flex;
            justify-content: center;
            position: relative;
        }

        .citas-badge {
            background-color: var(--color-exito);
            color: white;
            font-weight: bold;
            border-radius: 50%;
            padding: 5px 10px;
            position: absolute;
            top: -10px;
            right: -10px;
        }
        /* Estilos para el overlay de citas */
        .citas-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .citas-container {
            background-color: white;
            border-radius: 12px;
            padding: 25px;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .citas-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .citas-header h2 {
            color: var(--color-primario);
            margin: 0;
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--color-error);
        }
        
        .cita-item {
            border-bottom: 1px solid var(--color-borde);
            padding: 15px 0;
        }
        .cita-item:last-child {
            border-bottom: none;
        }
        
        .cita-hora {
            font-weight: bold;
            color: var(--color-primario);
        }
        
        .cita-paciente {
            font-weight: 500;
            margin-top: 5px;
        }
        
        .cita-tratamiento {
            color: #666;
            font-size: 0.9em;
        }
        
        .no-citas {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        .citas-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--color-error);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 12px;
        }
        
        .citas-btn-container {
            position: relative;
            display: inline-block;
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
        <button class="main-button" onclick="location.href='pacienteAgendarCita_Personal.php'">
            <i class="fas fa-calendar-plus"></i> Agendar una cita
        </button>
        
        <button class="main-button" onclick="location.href='verCitasPacientes_Personal.php'">
            <i class="fas fa-calendar-check"></i> Ver todas las citas
        </button>

        <button class="main-button" onclick="location.href='tablaPacientes.php'">
            <i class="fas fa-users"></i> Ver Pacientes
        </button>
        
         <!-- Botón de Citas de Hoy con badge -->
         <div class="citas-btn-container">
            <button class="main-button" id="verCitasHoyBtn">
                <i class="fas fa-calendar-day"></i> Citas de hoy
            </button>
            <?php if(count($citas_hoy) > 0): ?>
                <span class="citas-badge"><?php echo count($citas_hoy); ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tratamientos con imágenes -->
    <div class="treatments">
        <?php
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
    <!-- Overlay de citas de hoy -->
    <div class="citas-overlay" id="citasOverlay">
        <div class="citas-container">
            <div class="citas-header">
                <h2>Citas programadas para hoy (<?php echo date('d/m/Y'); ?>)</h2>
                <button class="close-btn" id="closeCitasOverlay">&times;</button>
            </div>
            <?php if(count($citas_hoy) > 0): ?>
                <?php foreach($citas_hoy as $cita): ?>
                    <div class="cita-item">
                        <div class="cita-hora">
                            <?php echo date('H:i', strtotime($cita['fecha'])); ?>
                        </div>
                        <div class="cita-paciente">
                            <?php echo htmlspecialchars($cita['paciente_nombre']); ?>
                        </div>
                        <div class="cita-tratamiento">
                            <?php echo htmlspecialchars($cita['tratamiento_nombre']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-citas">
                    <i class="fas fa-calendar-check fa-3x" style="color: #ccc; margin-bottom: 15px;"></i>
                    <p>No hay citas programadas para hoy</p>
                </div>
            <?php endif; ?>
            </div>
        </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar/ocultar overlay de citas
    document.getElementById('verCitasHoyBtn').addEventListener('click', function() {
        document.getElementById('citasOverlay').style.display = 'flex';
        // Ocultar el badge cuando se abre el overlay
        var badge = document.getElementById('citasBadge');
        if(badge) {
            badge.style.display = 'none';
        }
    });

    document.getElementById('closeCitasOverlay').addEventListener('click', function() {
        document.getElementById('citasOverlay').style.display = 'none';
    });

    document.getElementById('citasOverlay').addEventListener('click', function(e) {
        if(e.target === this) {
            this.style.display = 'none';
        }
        });
    </script>
</body>
</html>
