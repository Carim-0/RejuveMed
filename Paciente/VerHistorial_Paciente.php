<?php
session_start();
include("../connection.php");
include("../functions.php");

// Verificación de usuario
$user_data = check_login($con);
if ($_SESSION['user_type'] !== 'Paciente') {
    header("Location: ../login.php");
    die();
}

$id_paciente = $user_data['IDpaciente'];
$fecha_actual = date('Y-m-d H:i:s');

// Obtener datos del paciente (nombre y edad)
$paciente_query = "SELECT nombre, edad FROM Pacientes WHERE IDpaciente = ?";
$paciente_stmt = $con->prepare($paciente_query);
$paciente_stmt->bind_param("i", $id_paciente);
$paciente_stmt->execute();
$paciente_result = $paciente_stmt->get_result();
$paciente_data = $paciente_result->fetch_assoc();

// Obtener historial médico del paciente
$historial_query = "SELECT * FROM `Historial Medico` WHERE idpaciente = ?";
$historial_stmt = $con->prepare($historial_query);
$historial_stmt->bind_param("i", $id_paciente);
$historial_stmt->execute();
$historial_result = $historial_stmt->get_result();
$historial_data = $historial_result->fetch_assoc();

// Procesar actualización del historial
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['actualizar_historial'])) {
    $nuevos_detalles = $_POST['detalles'];
    
    if ($historial_data) {
        // Actualizar historial existente
        $update_query = "UPDATE `Historial Medico` SET detalles = ? WHERE idpaciente = ?";
        $update_stmt = $con->prepare($update_query);
        $update_stmt->bind_param("si", $nuevos_detalles, $id_paciente);
        $update_stmt->execute();
    } else {
        // Crear nuevo registro de historial
        $insert_query = "INSERT INTO `Historial Medico` (idpaciente, detalles) VALUES (?, ?)";
        $insert_stmt = $con->prepare($insert_query);
        $insert_stmt->bind_param("is", $id_paciente, $nuevos_detalles);
        $insert_stmt->execute();
    }
    
    // Recargar los datos actualizados
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Consulta de procedimientos realizados
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
    <title>Mi Historial Médico | VisionClinic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-primario: #1a37b5;
            --color-primario-hover: #142a8a;
            --color-secundario: #ffffff;
            --color-fondo: #f8fafc;
            --color-texto: #2d3748;
            --color-texto-claro: #718096;
            --color-borde: #e2e8f0;
            --color-exito: #10b981;
            --sombra: 0 4px 6px rgba(0, 0, 0, 0.05);
            --sombra-elevada: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--color-fondo);
            color: var(--color-texto);
            line-height: 1.6;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: var(--sombra-elevada);
        }

        .header {
            background: linear-gradient(135deg, var(--color-primario) 0%, #0d1b3e 100%);
            color: white;
            padding: 2rem 0;
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiPjxkZWZzPjxwYXR0ZXJuIGlkPSJwYXR0ZXJuIiB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHBhdHRlcm5Vbml0cz0idXNlclNwYWNlT25Vc2UiIHBhdHRlcm5UcmFuc2Zvcm09InJvdGF0ZSg0NSkiPjxyZWN0IHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgZmlsbD0icmdiYSgyNTUsMjU1LDI1NSwwLjA1KSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3QgZmlsbD0idXJsKCNwYXR0ZXJuKSIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIvPjwvc3ZnPg==');
            opacity: 0.3;
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
        }

        .header p {
            font-size: 1rem;
            opacity: 0.9;
            position: relative;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin: 1.5rem 0;
            background-color: var(--color-primario);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: var(--sombra);
            border: none;
            cursor: pointer;
            font-weight: 500;
        }

        .back-button:hover {
            background-color: var(--color-primario-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .historial-container {
            padding: 2rem;
            margin-bottom: 3rem;
        }

        .historial-section {
            margin-bottom: 2.5rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--color-primario);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            font-size: 1.25rem;
        }

        .historial-form {
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--color-texto);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--color-borde);
            border-radius: 8px;
            font-family: inherit;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.7);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--color-primario);
            box-shadow: 0 0 0 3px rgba(26, 55, 181, 0.1);
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-primary {
            background-color: var(--color-primario);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--color-primario-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-success {
            background-color: var(--color-exito);
            color: white;
        }

        .btn-success:hover {
            background-color: #0d9f6e;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-warning {
            background-color: #f59e0b;
            color: white;
        }

        .btn-warning:hover {
            background-color: #d97706;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .historial-item {
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .historial-item::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--color-primario);
            transition: all 0.3s ease;
        }

        .historial-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .historial-item:hover::before {
            width: 6px;
            background: linear-gradient(to bottom, var(--color-primario), #3b82f6);
        }

        .historial-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .tratamiento-nombre {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--color-primario);
            position: relative;
            padding-left: 1.5rem;
        }

        .tratamiento-nombre::before {
            content: "•";
            position: absolute;
            left: 0;
            color: var(--color-primario);
            font-size: 1.5rem;
        }

        .tratamiento-fecha {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: var(--color-texto-claro);
            background: rgba(226, 232, 240, 0.5);
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
        }

        .no-historial {
            text-align: center;
            padding: 3rem;
            color: var(--color-texto-claro);
        }

        .no-historial i {
            font-size: 3rem;
            color: var(--color-borde);
            margin-bottom: 1rem;
        }

        .no-historial h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--color-texto);
        }

        .edit-mode {
            display: none;
        }

        .view-mode {
            display: block;
        }

        .editable-text {
            padding: 1rem;
            background-color: rgba(255, 255, 255, 0.7);
            border-radius: 8px;
            margin-bottom: 1rem;
            white-space: pre-wrap;
        }

        .info-paciente {
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .info-paciente p {
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .info-paciente strong {
            color: var(--color-primario);
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.5rem;
            }
            
            .historial-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .tratamiento-fecha {
                align-self: flex-start;
            }
            
            .historial-container {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1><i class="fas fa-file-medical"></i> Historial Médico</h1>
            <p>Registro completo de tus procedimientos y detalles médicos</p>
        </div>
    </div>

    <div class="container">
        <button onclick="window.location.href='catalogoTratamientos.php'" class="back-button glass-card">
            <i class="fas fa-arrow-left"></i> Volver al Panel
        </button>

        <div class="historial-container glass-card">
            <!-- Sección de Información del Paciente -->
            <div class="historial-section">
                <h2 class="section-title">
                    <i class="fas fa-user"></i> Información del Paciente
                </h2>
                <div class="info-paciente glass-card">
                    <p><strong>Nombre:</strong> <?php echo htmlspecialchars($paciente_data['nombre']); ?></p>
                    <p><strong>Edad:</strong> <?php echo htmlspecialchars($paciente_data['edad']); ?> años</p>
                </div>
            </div>

            <!-- Sección de Edición del Historial Médico -->
            <div class="historial-section">
                <h2 class="section-title">
                    <i class="fas fa-edit"></i> Mis Datos Médicos
                </h2>
                
                <div id="viewMode" class="view-mode">
                    <div class="editable-text glass-card">
                        <?php echo !empty($historial_data['detalles']) ? nl2br(htmlspecialchars($historial_data['detalles'])) : 'No hay información médica registrada.'; ?>
                    </div>
                    <button id="editButton" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Editar Historial
                    </button>
                </div>
                
                <form id="editMode" method="POST" class="historial-form edit-mode">
                    <div class="form-group">
                        <label for="detalles">Detalles de mi historial médico:</label>
                        <textarea id="detalles" name="detalles" class="form-control glass-card" 
                                  placeholder="Describe cualquier condición médica, alergias, medicamentos, etc."><?php 
                                  echo htmlspecialchars($historial_data['detalles'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" name="actualizar_historial" class="btn btn-success">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <button type="button" id="cancelEdit" class="btn btn-primary">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </form>
            </div>

            <!-- Sección de Procedimientos Realizados -->
            <div class="historial-section">
                <h2 class="section-title">
                    <i class="fas fa-procedures"></i> Procedimientos Realizados
                </h2>
                
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="historial-item glass-card">
                            <div class="historial-header">
                                <span class="tratamiento-nombre"><?php echo htmlspecialchars($row['tratamiento']); ?></span>
                                <span class="tratamiento-fecha">
                                    <i class="far fa-calendar-alt"></i> 
                                    <?php echo date('d/m/Y H:i', strtotime($row['fecha'])); ?>
                                </span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-historial glass-card">
                        <i class="fas fa-file-import"></i>
                        <h3>No hay procedimientos realizados</h3>
                        <p>No se encontraron tratamientos completados en tu historial</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Efecto de carga futurista
        document.addEventListener('DOMContentLoaded', () => {
            const items = document.querySelectorAll('.historial-item');
            items.forEach((item, index) => {
                setTimeout(() => {
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Efecto para el textarea
            const textarea = document.getElementById('detalles');
            if (textarea) {
                textarea.addEventListener('focus', function() {
                    this.style.boxShadow = '0 0 0 3px rgba(26, 55, 181, 0.2)';
                    this.style.backgroundColor = 'rgba(255, 255, 255, 0.95)';
                });
                
                textarea.addEventListener('blur', function() {
                    this.style.boxShadow = 'none';
                    this.style.backgroundColor = 'rgba(255, 255, 255, 0.7)';
                });
            }
            
            // Control de edición del historial
            const editButton = document.getElementById('editButton');
            const cancelEdit = document.getElementById('cancelEdit');
            const viewMode = document.getElementById('viewMode');
            const editMode = document.getElementById('editMode');
            
            if (editButton && cancelEdit && viewMode && editMode) {
                editButton.addEventListener('click', function() {
                    viewMode.classList.remove('view-mode');
                    viewMode.classList.add('edit-mode');
                    editMode.classList.remove('edit-mode');
                    editMode.classList.add('view-mode');
                });
                
                cancelEdit.addEventListener('click', function() {
                    editMode.classList.remove('view-mode');
                    editMode.classList.add('edit-mode');
                    viewMode.classList.remove('edit-mode');
                    viewMode.classList.add('view-mode');
                });
            }
        });
    </script>
</body>
</html>