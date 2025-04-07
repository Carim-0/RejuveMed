<?php
session_start();
include("../connection.php");
include("../functions.php");

// Verificar permisos 
$user_data = check_login($con);
if($_SESSION['user_type'] !== 'Personal') { 
    header("Location: ../unauthorized.php");
    exit();
}

// Consulta para obtener todos los historiales archivados
$query = "SELECT a.IDpaciente, a.detalles, p.nombre as nombre_paciente 
          FROM Archivo a
          JOIN Pacientes p ON a.IDpaciente = p.IDpaciente
          ORDER BY a.IDpaciente";
$result = mysqli_query($con, $query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historiales Médicos Archivados - RejuveMed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --color-primario: #1a37b5;
            --color-secundario: #f8f9fa;
            --color-terciario: #e9ecef;
            --color-texto: #212529;
            --color-exito: #28a745;
            --color-advertencia: #ffc107;
        }
        
        body {
            background-color: var(--color-secundario);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-bottom: 50px;
        }
        
        .header-container {
            background-color: var(--color-primario);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: var(--color-primario);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
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
        
        .badge-archivado {
            background-color: var(--color-advertencia);
            color: var(--color-texto);
        }
        
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .table thead {
            background-color: var(--color-primario);
            color: white;
        }
        
        .action-link {
            color: var(--color-primario);
            text-decoration: none;
            margin: 0 5px;
        }
        
        .action-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="header-container">
        <div class="container">
            <h1 class="text-center"><i class="fas fa-archive"></i> Historiales Médicos Archivados</h1>
        </div>
    </div>

    <div class="container">
        <?php if(mysqli_num_rows($result) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID Paciente</th>
                            <th>Nombre del Paciente</th>
                            <th>Detalles del Historial</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['IDpaciente']) ?></td>
                                <td><?= htmlspecialchars($row['nombre_paciente']) ?></td>
                                <td>
                                    <div style="max-height: 100px; overflow-y: auto;">
                                        <?= nl2br(htmlspecialchars($row['detalles'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <a href="#" class="action-link" data-bs-toggle="modal" data-bs-target="#detalleModal<?= $row['IDpaciente'] ?>">
                                        <i class="fas fa-eye"></i> Ver completo
                                    </a>
                                </td>
                            </tr>
                            
                            <!-- Modal para ver el historial completo -->
                            <div class="modal fade" id="detalleModal<?= $row['IDpaciente'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                Historial archivado de <?= htmlspecialchars($row['nombre_paciente']) ?>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="card">
                                                <div class="card-header">
                                                    <h6 class="mb-0">
                                                        <i class="fas fa-user"></i> Paciente: <?= htmlspecialchars($row['nombre_paciente']) ?>
                                                        <span class="badge badge-archivado ms-2">Archivado</span>
                                                    </h6>
                                                </div>
                                                <div class="historial-content">
                                                    <?= nl2br(htmlspecialchars($row['detalles'])) ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                <i class="fas fa-times"></i> Cerrar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle"></i> No hay historiales médicos archivados.
            </div>
        <?php endif; ?>
        
        <div class="text-center mt-4">
            <a href="tablaPacientes.php" class="btn btn-volver">
                <i class="fas fa-arrow-left"></i> Volver a la lista de pacientes
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php mysqli_close($con); ?>