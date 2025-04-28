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

// Procesar restauración si se envió el formulario
if(isset($_POST['restaurar'])) {
    $id_paciente_archivado = $_POST['id_paciente'];
    
    // 1. Obtener todos los datos del archivo
    $query_archivo = "SELECT * FROM Archivo WHERE IDpaciente = $id_paciente_archivado";
    $result_archivo = mysqli_query($con, $query_archivo);
    $archivo = mysqli_fetch_assoc($result_archivo);
    
    if($archivo) {
        // Verificar si el paciente existe por nombre y teléfono
        $query_check = "SELECT IDpaciente FROM Pacientes 
                       WHERE nombre = '".mysqli_real_escape_string($con, $archivo['nombre'])."' 
                       AND telefono = '".mysqli_real_escape_string($con, $archivo['telefono'])."'";
        $result_check = mysqli_query($con, $query_check);
        $paciente_existente = mysqli_fetch_assoc($result_check);
        
        if($paciente_existente) {
            // Paciente existe - actualizar historial médico
            $id_paciente = $paciente_existente['IDpaciente'] ?? $paciente_existente['IDPaciente'] ?? null;
            
            if($id_paciente) {
                // Insertar en Historial Medico
                $detalles = mysqli_real_escape_string($con, $archivo['detalles']);
                $query_insert = "INSERT INTO `Historial Medico` (IDpaciente, detalles) 
                                VALUES ($id_paciente, '$detalles')";
                mysqli_query($con, $query_insert);
                
                $mensaje = "Historial médico restaurado y agregado al paciente existente";
            } else {
                $mensaje = "Error: No se pudo obtener el ID del paciente";
            }
        } else {
            // Paciente no existe - crear nuevo
            $query_insert_paciente = "INSERT INTO Pacientes (nombre, telefono, edad) 
                                    VALUES (
                                        '".mysqli_real_escape_string($con, $archivo['nombre'])."',
                                        '".mysqli_real_escape_string($con, $archivo['telefono'])."',
                                        ".(int)$archivo['edad']."
                                    )";
            mysqli_query($con, $query_insert_paciente);
            $id_paciente = mysqli_insert_id($con);
            
            // Insertar en Historial Medico
            $detalles = mysqli_real_escape_string($con, $archivo['detalles']);
            $query_insert = "INSERT INTO `Historial Medico` (IDpaciente, detalles) 
                            VALUES ($id_paciente, '$detalles')";
            mysqli_query($con, $query_insert);
            
            $mensaje = "Paciente recreado con su historial médico";
        }
        
        // Eliminar de Archivo
        $query_delete = "DELETE FROM Archivo WHERE IDpaciente = $id_paciente_archivado";
        mysqli_query($con, $query_delete);
        
        // Mensaje de éxito
        $_SESSION['mensaje'] = $mensaje;
        $_SESSION['tipo_mensaje'] = "success";
        
        // Redirigir para evitar reenvío del formulario
        header("Location: historiales_archivados.php");
        exit();
    }
}

// Configuración de paginación
$por_pagina = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($pagina - 1) * $por_pagina;

// Búsqueda
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$condicion_busqueda = '';
if(!empty($busqueda)) {
    $condicion_busqueda = "AND (a.nombre LIKE '%".mysqli_real_escape_string($con, $busqueda)."%' 
                          OR a.telefono LIKE '%".mysqli_real_escape_string($con, $busqueda)."%'
                          OR a.detalles LIKE '%".mysqli_real_escape_string($con, $busqueda)."%')";
}

// Consulta principal con paginación
$query = "SELECT a.* 
          FROM Archivo a
          WHERE 1=1 $condicion_busqueda
          ORDER BY a.IDpaciente
          LIMIT $inicio, $por_pagina";
$result = mysqli_query($con, $query);

// Total de registros para paginación
$query_total = "SELECT COUNT(*) as total 
                FROM Archivo a
                WHERE 1=1 $condicion_busqueda";
$result_total = mysqli_query($con, $query_total);
$total_registros = mysqli_fetch_assoc($result_total)['total'];
$total_paginas = ceil($total_registros / $por_pagina);
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--color-texto);
            background-color: var(--color-secundario);
        }
        
        .header-container {
            background-color: var(--color-primario);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .header-container h1 {
            font-weight: 600;
        }
        
        .action-link {
            color: var(--color-primario);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .action-link:hover {
            color: #0d6efd;
            text-decoration: underline;
        }
        
        .btn-restore {
            color: var(--color-exito);
            border-color: var(--color-exito);
        }
        
        .btn-restore:hover {
            background-color: var(--color-exito);
            color: white;
        }
        
        .historial-content {
            padding: 15px;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-line;
        }
        
        .pagination .page-item.active .page-link {
            background-color: var(--color-primario);
            border-color: var(--color-primario);
        }
        
        .pagination .page-link {
            color: var(--color-primario);
        }
        
        .search-box {
            margin-bottom: 20px;
        }
        
        .actions-column {
            white-space: nowrap;
        }
        
        .table-responsive {
            overflow-x: auto;
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
        <!-- Mostrar mensajes -->
        <?php if(isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-<?= $_SESSION['tipo_mensaje'] ?> alert-dismissible fade show" role="alert">
                <?= $_SESSION['mensaje'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['mensaje']); unset($_SESSION['tipo_mensaje']); ?>
        <?php endif; ?>
        
        <!-- Barra de búsqueda -->
        <div class="row search-box">
            <div class="col-md-6">
                <form method="GET" class="input-group">
                    <input type="text" class="form-control" name="busqueda" value="<?= htmlspecialchars($busqueda) ?>" 
                           placeholder="Buscar por nombre, teléfono o detalles...">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <?php if(!empty($busqueda)): ?>
                        <a href="historiales_archivados.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <?php if(mysqli_num_rows($result) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Teléfono</th>
                            <th>Edad</th>
                            <th>Detalles</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['IDpaciente'] ?? $row['IDPaciente'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['nombre']) ?></td>
                                <td><?= htmlspecialchars($row['telefono']) ?></td>
                                <td><?= htmlspecialchars($row['edad']) ?></td>
                                <td>
                                    <div style="max-height: 100px; overflow-y: auto;">
                                        <?= nl2br(htmlspecialchars(substr($row['detalles'], 0, 200))) ?>
                                        <?= strlen($row['detalles']) > 200 ? '...' : '' ?>
                                    </div>
                                </td>
                                <td class="actions-column">
                                    <a href="#" class="action-link me-3" data-bs-toggle="modal" data-bs-target="#detalleModal<?= $row['IDpaciente'] ?? $row['IDPaciente'] ?? '' ?>">
                                        <i class="fas fa-eye"></i> Ver completo
                                    </a>
                                    <form method="POST" style="display: inline-block;">
                                        <input type="hidden" name="id_paciente" value="<?= $row['IDpaciente'] ?? $row['IDPaciente'] ?? '' ?>">
                                        <button type="submit" name="restaurar" class="btn btn-outline-success btn-sm btn-restore">
                                            <i class="fas fa-undo"></i> Restaurar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            
                            <!-- Modal para ver el historial completo -->
                            <div class="modal fade" id="detalleModal<?= $row['IDpaciente'] ?? $row['IDPaciente'] ?? '' ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                Historial archivado de <?= htmlspecialchars($row['nombre']) ?>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="card">
                                                <div class="card-header">
                                                    <h6 class="mb-0">
                                                        <i class="fas fa-user"></i> Paciente: <?= htmlspecialchars($row['nombre']) ?>
                                                        <span class="badge bg-warning text-dark ms-2">Archivado</span>
                                                    </h6>
                                                    <p class="mb-0">
                                                        <i class="fas fa-phone"></i> Teléfono: <?= htmlspecialchars($row['telefono']) ?> | 
                                                        <i class="fas fa-birthday-cake"></i> Edad: <?= htmlspecialchars($row['edad']) ?>
                                                    </p>
                                                </div>
                                                <div class="historial-content">
                                                    <?= nl2br(htmlspecialchars($row['detalles'])) ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <form method="POST">
                                                <input type="hidden" name="id_paciente" value="<?= $row['IDpaciente'] ?? $row['IDPaciente'] ?? '' ?>">
                                                <button type="submit" name="restaurar" class="btn btn-success">
                                                    <i class="fas fa-undo"></i> Restaurar historial
                                                </button>
                                            </form>
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
            
            <!-- Paginación -->
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php if($pagina > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?pagina=1&busqueda=<?= urlencode($busqueda) ?>">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?pagina=<?= $pagina-1 ?>&busqueda=<?= urlencode($busqueda) ?>">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php 
                    $inicio_pagina = max(1, $pagina - 2);
                    $fin_pagina = min($total_paginas, $pagina + 2);
                    
                    for($i = $inicio_pagina; $i <= $fin_pagina; $i++): ?>
                        <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                            <a class="page-link" href="?pagina=<?= $i ?>&busqueda=<?= urlencode($busqueda) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if($pagina < $total_paginas): ?>
                        <li class="page-item">
                            <a class="page-link" href="?pagina=<?= $pagina+1 ?>&busqueda=<?= urlencode($busqueda) ?>">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?pagina=<?= $total_paginas ?>&busqueda=<?= urlencode($busqueda) ?>">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            
            <div class="text-center text-muted mb-4">
                Mostrando <?= ($inicio + 1) ?> a <?= min($inicio + $por_pagina, $total_registros) ?> de <?= $total_registros ?> registros
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle"></i> No se encontraron historiales médicos archivados.
            </div>
        <?php endif; ?>
        
        <div class="text-center mt-4">
            <a href="tablaPacientes.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Volver a la lista de pacientes
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php mysqli_close($con); ?>