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

// Configuración de paginación
$por_pagina = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($pagina - 1) * $por_pagina;

// Búsqueda
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$condicion_busqueda = '';
if(!empty($busqueda)) {
    $condicion_busqueda = "AND (p.nombre LIKE '%".mysqli_real_escape_string($con, $busqueda)."%' 
                          OR a.detalles LIKE '%".mysqli_real_escape_string($con, $busqueda)."%')";
}

// Consulta principal con paginación
$query = "SELECT a.IDpaciente, a.detalles, p.nombre as nombre_paciente 
          FROM Archivo a
          JOIN Pacientes p ON a.IDpaciente = p.IDpaciente
          WHERE 1=1 $condicion_busqueda
          ORDER BY a.IDpaciente
          LIMIT $inicio, $por_pagina";
$result = mysqli_query($con, $query);

// Total de registros para paginación
$query_total = "SELECT COUNT(*) as total 
                FROM Archivo a
                JOIN Pacientes p ON a.IDpaciente = p.IDpaciente
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
    </style>
</head>
<body>
    <div class="header-container">
        <div class="container">
            <h1 class="text-center"><i class="fas fa-archive"></i> Historiales Médicos Archivados</h1>
        </div>
    </div>

    <div class="container">
        <!-- Barra de búsqueda -->
        <div class="row search-box">
            <div class="col-md-6">
                <form method="GET" class="input-group">
                    <input type="text" class="form-control" name="busqueda" value="<?= htmlspecialchars($busqueda) ?>" 
                           placeholder="Buscar por nombre o detalles...">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <?php if(!empty($busqueda)): ?>
                        <a href="HistorialArchivo.php" class="btn btn-secondary">
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
                                        <?= nl2br(htmlspecialchars(substr($row['detalles'], 0, 200))) ?>
                                        <?= strlen($row['detalles']) > 200 ? '...' : '' ?>
                                    </div>
                                </td>
                                <td>
                                    <a href="#" class="action-link" data-bs-toggle="modal" data-bs-target="#detalleModal<?= $row['IDpaciente'] ?>">
                                        <i class="fas fa-eye"></i> Ver completo
                                    </a>
                                    | <a href="restaurar_paciente.php?id=<?= $row['IDpaciente'] ?>" class="action-link text-success">
                                        <i class="fas fa-undo"></i> Restaurar
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
                                                        <span class="badge bg-warning text-dark ms-2">Archivado</span>
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
                                            <a href="restaurar_paciente.php?id=<?= $row['IDpaciente'] ?>" class="btn btn-success">
                                                <i class="fas fa-undo"></i> Restaurar Historial
                                            </a>
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