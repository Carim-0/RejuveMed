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
            --color-editar: #f59e0b;
            --sombra: 0 4px 6px rgba(0, 0, 0, 0.05);
            --sombra-elevada: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        /* [Resto de tus estilos CSS permanecen igual...] */

        .edit-form-container {
            display: none;
            margin-top: 1.5rem;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .btn-editar {
            background-color: var(--color-editar);
            color: white;
        }

        .btn-editar:hover {
            background-color: #d97706;
        }

        .btn-cancelar {
            background-color: var(--color-texto-claro);
            margin-left: 0.5rem;
        }

        .btn-cancelar:hover {
            background-color: #4b5563;
        }

        .historial-content {
            padding: 1rem;
            background-color: rgba(241, 245, 249, 0.5);
            border-radius: 8px;
            white-space: pre-wrap;
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
            <!-- Sección de Visualización del Historial Médico -->
            <div class="historial-section">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2 class="section-title">
                        <i class="fas fa-clipboard-list"></i> Mis Datos Médicos
                    </h2>
                    <button id="btn-editar" class="btn btn-editar">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                </div>
                
                <div class="historial-content">
                    <?php echo $historial_data['detalles'] ?? 'No hay información médica registrada.'; ?>
                </div>
                
                <!-- Formulario de Edición (oculto inicialmente) -->
                <div id="edit-form" class="edit-form-container">
                    <form method="POST" class="historial-form">
                        <div class="form-group">
                            <textarea id="detalles" name="detalles" class="form-control glass-card" 
                                      placeholder="Describe cualquier condición médica, alergias, medicamentos, etc."><?php 
                                      echo htmlspecialchars($historial_data['detalles'] ?? ''); ?></textarea>
                        </div>
                        
                        <div style="display: flex;">
                            <button type="submit" name="actualizar_historial" class="btn btn-success">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                            <button type="button" id="btn-cancelar" class="btn btn-cancelar">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- [Sección de Procedimientos Realizados permanece igual...] -->
        </div>
    </div>

    <script>
        // Mostrar/ocultar formulario de edición
        document.addEventListener('DOMContentLoaded', () => {
            const btnEditar = document.getElementById('btn-editar');
            const btnCancelar = document.getElementById('btn-cancelar');
            const editForm = document.getElementById('edit-form');
            const historialContent = document.querySelector('.historial-content');
            
            btnEditar.addEventListener('click', () => {
                editForm.style.display = 'block';
                historialContent.style.display = 'none';
                btnEditar.style.display = 'none';
            });
            
            btnCancelar.addEventListener('click', () => {
                editForm.style.display = 'none';
                historialContent.style.display = 'block';
                btnEditar.style.display = 'inline-flex';
            });
            
            // Efecto de carga para procedimientos
            const items = document.querySelectorAll('.historial-item');
            items.forEach((item, index) => {
                setTimeout(() => {
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>