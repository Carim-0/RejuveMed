<?php
session_start();
include("../connection.php");
include("../functions.php");

// Verificar que el usuario esté logueado como paciente
$user_data = check_login($con);
if($_SESSION['user_type'] !== 'Paciente') {
    header("Location: ../unauthorized.php");
    exit();
}

// Obtener ID del paciente desde la sesión
$id_paciente = $_SESSION['user_id'];

// Consultar datos del paciente
$query_paciente = "SELECT * FROM Pacientes WHERE IDpaciente = ?";
$stmt_paciente = $con->prepare($query_paciente);
$stmt_paciente->bind_param("i", $id_paciente);
$stmt_paciente->execute();
$paciente = $stmt_paciente->get_result()->fetch_assoc();
$stmt_paciente->close();

// Consultar historial médico
$query_historial = "SELECT detalles FROM `Historial Medico` WHERE IDpaciente = ?";
$stmt_historial = $con->prepare($query_historial);
$stmt_historial->bind_param("i", $id_paciente);
$stmt_historial->execute();
$result_historial = $stmt_historial->get_result();
$historial = $result_historial->fetch_assoc();
$stmt_historial->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Historial Médico - RejuveMed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --color-primario: #1a37b5;
            --color-secundario: #f8f9fa;
            --color-terciario: #e9ecef;
            --color-texto: #212529;
        }
        
        body {
            background-color: var(--color-secundario);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .header-container {
            background-color: var(--color-primario);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .card-header {
            background-color: var(--color-primario);
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        
        .historial-content {
            white-space: pre-wrap;
            padding: 20px;
            background-color: white;
            border-radius: 0 0 10px 10px;
        }
        
        .btn-volver {
            background-color: var(--color-primario);
            color: white;
            margin-top: 20px;
        }
        
        .btn-volver:hover {
            background-color: #142a8a;
            color: white;
        }
    </style>
</head>
<body>
    <div class="header-container">
        <div class="container">
            <h1 class="text-center"><i class="fas fa-file-medical"></i> Mi Historial Médico</h1>
        </div>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h2 class="h4 mb-0"><i class="fas fa-user"></i> Información del Paciente</h2>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Nombre:</strong> <?= htmlspecialchars($paciente['nombre'] ?? '') ?></p>
                                <p><strong>Edad:</strong> <?= htmlspecialchars($paciente['edad'] ?? '') ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Teléfono:</strong> <?= htmlspecialchars($paciente['telefono'] ?? '') ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2 class="h4 mb-0"><i class="fas fa-file-medical-alt"></i> Historial Médico</h2>
                    </div>
                    <div class="historial-content">
                        <?php if($historial && !empty($historial['detalles'])): ?>
                            <?= nl2br(htmlspecialchars($historial['detalles'])) ?>
                        <?php else: ?>
                            <p class="text-muted">No se encontró historial médico registrado.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="text-center">
                    <a href="../Paciente/catalogoTratamientos.php" class="btn btn-volver">
                        <i class="fas fa-arrow-left"></i> Volver al panel
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $con->close(); ?>