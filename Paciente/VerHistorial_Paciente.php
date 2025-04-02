<?php
session_start();
include("../connection.php");
include("../functions.php");

// Verificar que el usuario está logueado y es paciente
$user_data = check_login($con);
if ($_SESSION['user_type'] !== 'Paciente') {
    header("Location: ../login.php");
    die();
}

$id_paciente = $user_data['IDpaciente']; // Obtener ID del paciente logueado
$fecha_actual = date('Y-m-d H:i:s'); // Fecha actual para comparación

// Consulta para obtener los tratamientos realizados (fechas pasadas)
// Consulta para obtener tratamientos con fecha pasada
$query = "SELECT c.IDcita, c.fecha, t.nombre as tratamiento
          FROM Citas c
          JOIN Tratamientos t ON c.idtratamiento = t.IDtratamiento
          WHERE c.idpaciente = ? AND c.fecha < ?
          ORDER BY c.fecha DESC";
$stmt = $con->prepare($query);
$stmt->bind_param("is", $id_paciente, $fecha_actual);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Historial Médico</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --color-primario: #1a37b5;
            --color-secundario: #f8f9fa;
            --color-texto: #212529;
            --color-borde: #e0e0e0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--color-secundario);
            color: var(--color-texto);
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background-color: var(--color-primario);
            color: white;
            padding: 20px 0;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .header h1 {
            margin: 0;
            font-size: 1.8rem;
        }

        .back-button {
            display: inline-block;
            margin: 20px 0;
            background-color: var(--color-primario);
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .back-button:hover {
            background-color: #142a8a;
            transform: translateY(-2px);
        }

        .historial-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 30px;
        }

        .historial-item {
            border-bottom: 1px solid var(--color-borde);
            padding: 15px 0;
        }

        .historial-item:last-child {
            border-bottom: none;
        }

        .historial-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .tratamiento-nombre {
            font-weight: bold;
            color: var(--color-primario);
            font-size: 1.1rem;
        }

        .tratamiento-fecha {
            color: #666;
            font-size: 0.9rem;
        }

        .tratamiento-desc {
            color: var(--color-texto);
            line-height: 1.5;
        }

        .no-historial {
            text-align: center;
            padding: 40px 0;
            color: #666;
        }

        @media (max-width: 768px) {
            .historial-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .tratamiento-fecha {
                margin-top: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1><i class="fas fa-file-medical"></i> Mi Historial Médico</h1>
        </div>
    </div>

    <div class="container">
        <a href="perfilPaciente.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Volver al Perfil
        </a>

        <div class="historial-container">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="historial-item">
                        <div class="historial-header">
                            <span class="tratamiento-nombre"><?php echo htmlspecialchars($row['tratamiento']); ?></span>
                            <span class="tratamiento-fecha">
                                <i class="far fa-calendar-alt"></i> 
                                <?php echo date('d/m/Y H:i', strtotime($row['fecha'])); ?>
                            </span>
                        </div>
                        <div class="tratamiento-desc">
                            <?php echo htmlspecialchars($row['descripcion']); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-historial">
                    <i class="fas fa-info-circle" style="font-size: 2rem; color: #ccc; margin-bottom: 15px;"></i>
                    <h3>No hay tratamientos realizados</h3>
                    <p>No se encontraron tratamientos completados en tu historial.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>