<?php
session_start();
require_once '../connection.php';
require_once '../functions.php';

// Verificar login y obtener datos del paciente
$user_data = check_login($con);
$paciente_id = $_SESSION['user_id'];

// Manejar solicitud AJAX para horarios ocupados
if (isset($_GET['get_occupied_hours'])) {
    $fecha = $_GET['fecha'];
    $occupiedHours = [];
    
    try {
        $query = "SELECT TIME(fecha) as hora, TIME(fechaFin) as horaFin FROM Citas 
                 WHERE DATE(fecha) = ? AND estado != 'Cancelada'";
        $stmt = $con->prepare($query);
        $stmt->bind_param("s", $fecha);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $occupiedHours[] = [
                'start' => $row['hora'],
                'end' => $row['horaFin']
            ];
        }
        
        header('Content-Type: application/json');
        echo json_encode($occupiedHours);
        exit();
        
    } catch (Exception $e) {
        error_log("Error al obtener horarios ocupados: " . $e->getMessage());
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
}

// Procesar cancelación de cita
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_cita'])) {
    $cita_id = $_POST['cita_id'];
    
    $update_query = "UPDATE Citas SET estado = 'Cancelada' WHERE IDcita = ? AND IDpaciente = ?";
    $stmt = $con->prepare($update_query);
    $stmt->bind_param("ii", $cita_id, $paciente_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $mensaje = "Cita cancelada correctamente";
    } else {
        $mensaje = "Error al cancelar la cita";
    }
    $stmt->close();
    
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Procesar edición de cita
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_cita'])) {
    $cita_id = $_POST['cita_id'];
    $nueva_fecha = $_POST['fecha'];
    $nueva_hora = $_POST['hora'];
    
    $nueva_fecha_hora = new DateTime($nueva_fecha . ' ' . $nueva_hora . ':00');
    $ahora = new DateTime();
    
    if ($nueva_fecha_hora <= $ahora) {
        $mensaje = "Error: No puedes reagendar una cita para una fecha/hora pasada";
    } else {
        $duracion = $_POST['duracion'];
        $nueva_fecha_fin = clone $nueva_fecha_hora;
        $nueva_fecha_fin->modify("+$duracion hours");
        
        $query = "SELECT * FROM Citas WHERE 
                 (fecha < ? AND fechaFin > ?) AND IDcita != ? and estado = 'Pendiente'";
        $stmt = $con->prepare($query);
        $fecha_fin_str = $nueva_fecha_fin->format('Y-m-d H:i:s');
        $fecha_str = $nueva_fecha_hora->format('Y-m-d H:i:s');
        $stmt->bind_param("ssi", $fecha_fin_str, $fecha_str, $cita_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $mensaje = "Error: Ya existe una cita en ese horario";
        } else {
            $update_query = "UPDATE Citas SET fecha = ?, fechaFin = ? WHERE IDcita = ? AND IDpaciente = ?";
            $stmt = $con->prepare($update_query);
            $stmt->bind_param("ssii", $fecha_str, $fecha_fin_str, $cita_id, $paciente_id);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                $mensaje = "Cita reagendada correctamente";
            } else {
                $mensaje = "Error al reagendar la cita";
            }
        }
        $stmt->close();
    }
    
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Actualizar automáticamente citas pasadas pendientes a Canceladas
$ahora = new DateTime();
$query = "UPDATE Citas SET estado = 'Cancelada' 
          WHERE IDpaciente = ? AND estado = 'Pendiente' AND fecha < ?";
$stmt = $con->prepare($query);
$ahora_str = $ahora->format('Y-m-d H:i:s');
$stmt->bind_param("is", $paciente_id, $ahora_str);
$stmt->execute();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RejuveMed - Mis Citas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root {
            --primary-color: #4a6fa5;
            --secondary-color: #6b8cae;
            --accent-color: #4a6fa5;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --disabled-color: #cccccc;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f0f2f5;
            padding: 20px;
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .page-title {
            color: var(--primary-color);
            font-size: 28px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: var(--border-radius);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background-color: #3a5a8a;
            transform: translateY(-2px);
        }
        
        .btn-outline {
            background-color: transparent;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }
        
        .btn-outline:hover {
            background-color: rgba(74, 111, 165, 0.1);
        }
        
        .content-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
            height: fit-content;
        }
        
        .card-title {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--secondary-color);
            font-size: 14px;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 15px;
        }
        
        .form-group input[readonly],
        .form-group textarea[readonly] {
            background-color: var(--light-color);
            color: #777;
            border-color: #eee;
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .inline-fields {
            display: flex;
            gap: 15px;
        }
        
        .appointment-item {
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        
        .appointment-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .appointment-card {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 15px;
        }
        
        .appointment-pendiente {
            background-color: rgba(74, 111, 165, 0.1);
            border-left: 4px solid var(--primary-color);
        }
        
        .appointment-cancelada {
            background-color: rgba(220, 53, 69, 0.1);
            border-left: 4px solid var(--danger-color);
        }
        
        .appointment-completada {
            background-color: rgba(40, 167, 69, 0.1);
            border-left: 4px solid var(--success-color);
        }
        
        .appointment-field {
            font-weight: 500;
            color: var(--secondary-color);
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .appointment-value {
            color: var(--dark-color);
            margin-bottom: 10px;
        }
        
        .appointment-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 15px;
        }
        
        .status-pendiente {
            background-color: rgba(255, 193, 7, 0.2);
            color: blue;
        }
        
        .status-cancelada {
            background-color: rgba(220, 53, 69, 0.2);
            color: var(--danger-color);
        }
        
        .status-completada {
            background-color: rgba(40, 167, 69, 0.2);
            color: var(--success-color);
        }
        
        .appointment-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-sm {
            padding: 8px 15px;
            font-size: 13px;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: white;
            border: none;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .btn-success {
            background-color: var(--success-color);
            color: white;
            border: none;
        }
        
        .btn-success:hover {
            background-color: #218838;
        }
        
        .no-appointments {
            text-align: center;
            color: #777;
            font-style: italic;
            padding: 30px 0;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.2);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.2);
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }
        
        .btn-disabled {
            background-color: var(--disabled-color);
            color: #666;
            border: none;
            cursor: not-allowed;
        }
        
        .btn-disabled:hover {
            background-color: var(--disabled-color);
            transform: none;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow: auto;
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            width: 90%;
            max-width: 600px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-title {
            color: var(--primary-color);
            font-size: 24px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .close-modal {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close-modal:hover {
            color: var(--dark-color);
        }
        
        .modal-form {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .duration-display {
            background-color: #f5f5f5;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 15px;
        }
        
        @media (max-width: 1024px) {
            .content-container {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
                width: 100%;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            
            .modal-content {
                margin: 10% auto;
                width: 95%;
            }
        }
       

        #modalHora option[disabled] {
            color: #999 !important;
            background-color: #f5f5f5 !important;
        }

        #modalHora:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 111, 165, 0.2);
        }

        #modalHora {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
        }
        
        #modalHora option:disabled,
        #modalHora option.disabled {
            color: #999 !important;
            background-color: #f5f5f5 !important;
            cursor: not-allowed;
        }
        
        #modalHora option:hover:not(:disabled) {
            background-color: #e9f3ff;
        }
        
        .loading-hours {
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header-container">
        <h1 class="page-title"><i class="fas fa-file-medical"></i> Historial Clínico</h1>
        
        <div class="action-buttons">
            <a href="VerHistorial_Paciente.php" class="btn btn-primary">
                <i class="fas fa-history"></i> Ver Historial
            </a>
            <a href="../verPerfil.php" class="btn btn-outline">
                <i class="fas fa-user"></i> Ver Perfil
            </a>
            <a href="catalogoTratamientos.php" class="btn btn-outline">
                <i class="fas fa-spa"></i> Catálogo
            </a>
        </div>
    </div>

    <!-- Mostrar mensajes -->
    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?php echo strpos($mensaje, 'Error') !== false ? 'danger' : 'success'; ?>">
            <i class="fas <?php echo strpos($mensaje, 'Error') !== false ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i>
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
    <?php endif; ?>

    <!-- Contenedor principal -->
    <div class="content-container">
        <!-- Sección izquierda (datos del usuario) -->
        <div class="card">
            <h2 class="card-title"><i class="fas fa-user-circle"></i> Datos del Paciente</h2>
            
            <div class="form-group">
                <label for="nombre">Nombre completo</label>
                <input type="text" id="nombre" name="nombre" readonly 
                       value="<?php echo htmlspecialchars($user_data['nombre'] ?? ''); ?>">
            </div>
            
            <div class="inline-fields">
                <div class="form-group" style="flex: 1;">
                    <label for="edad">Edad</label>
                    <input type="text" id="edad" name="edad" readonly 
                           value="<?php echo htmlspecialchars($user_data['edad'] ?? ''); ?>">
                </div>
                
                <div class="form-group" style="flex: 2;">
                    <label for="telefono">Teléfono</label>
                    <input type="text" id="telefono" name="telefono" readonly
                           value="<?php echo htmlspecialchars($user_data['telefono'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="detalles">Detalles médicos</label>
                <textarea id="detalles" name="detalles" readonly><?php 
                    echo htmlspecialchars($user_data['detalles'] ?? ''); 
                ?></textarea>
            </div>
        </div>
        
        <!-- Sección derecha (citas agendadas) -->
        <div class="card">
            <h2 class="card-title"><i class="fas fa-calendar-check"></i> Citas Agendadas</h2>
            
            <?php
            // Consulta para obtener las citas con información completa
            $query = "SELECT 
                        c.IDcita,
                        c.fecha, 
                        t.nombre as tratamiento, 
                        t.detalles as descripcion,
                        t.precio,
                        t.duracion,
                        c.estado
                      FROM Citas c
                      JOIN Tratamientos t ON c.IDtratamiento = t.IDtratamiento
                      WHERE c.IDpaciente = ?
                      ORDER BY c.fecha";
            
            $stmt = $con->prepare($query);
            $stmt->bind_param("i", $paciente_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Formatear fecha y hora
                    $fecha_obj = new DateTime($row['fecha']);
                    $fecha_formateada = $fecha_obj->format('d/m/Y');
                    $hora_formateada = $fecha_obj->format('H:i a');
                    $fecha_iso = $fecha_obj->format('Y-m-d');
                    $hora_iso = $fecha_obj->format('H:i');

                    // Calcular si faltan menos de 24 horas para la cita
                    $ahora = new DateTime();
                    $diferencia = $ahora->diff($fecha_obj);
                    $horas_restantes = ($diferencia->days * 24) + $diferencia->h;
                    $puede_editar = ($horas_restantes >= 24 && strtolower($row['estado']) == 'pendiente');

                    // Formatear precio
                    $precio_formateado = '$' . number_format($row['precio'], 2);

                    // Determinar clases CSS según el estado
                    $clase_estado = 'appointment-' . strtolower($row['estado']);
                    $clase_status = 'status-' . strtolower($row['estado']);

                    echo '<div class="appointment-item">';
                    echo '<div class="appointment-card '.$clase_estado.'">';
                    echo '<span class="appointment-status '.$clase_status.'">';
                    $icono = (strtolower($row['estado']) == 'pendiente') ? 'fa-clock' : 
                            ((strtolower($row['estado']) == 'cancelada') ? 'fa-times-circle' : 'fa-check-circle');
                    echo '<i class="fas '.$icono.'"></i> ' . htmlspecialchars($row['estado']);
                    echo '</span>';
                    
                    echo '<div class="appointment-field">Fecha y hora</div>';
                    echo '<div class="appointment-value">'.htmlspecialchars($fecha_formateada).' a las '.htmlspecialchars($hora_formateada).'</div>';
                    
                    echo '<div class="appointment-field">Tratamiento</div>';
                    echo '<div class="appointment-value">'.htmlspecialchars($row['tratamiento']).'</div>';
                    
                    echo '<div class="appointment-field">Precio</div>';
                    echo '<div class="appointment-value">'.htmlspecialchars($precio_formateado).'</div>';
                    
                    echo '<div class="appointment-field">Descripción</div>';
                    echo '<div class="appointment-value">'.nl2br(htmlspecialchars($row['descripcion'])).'</div>';
                    echo '</div>';

                    echo '<div class="appointment-actions">';
                    
                    // Botón de editar (solo para citas pendientes y con más de 24 horas)
                    if (strtolower($row['estado']) == 'pendiente') {
                        if ($puede_editar) {
                            echo '<button type="button" class="btn btn-outline btn-sm editar-cita-btn" 
                                  data-id="'.$row['IDcita'].'"
                                  data-fecha="'.$fecha_iso.'"
                                  data-hora="'.$hora_iso.'"
                                  data-duracion="'.$row['duracion'].'">
                                <i class="fas fa-edit"></i> Editar
                            </button>';
                        } else {
                            echo '<button type="button" class="btn btn-disabled btn-sm" title="No se puede editar con menos de 24 horas de anticipación">
                                <i class="fas fa-edit"></i> Editar
                            </button>';
                        }
                    }

                    // Botón de cancelar (solo para citas pendientes)
                    if (strtolower($row['estado']) == 'pendiente') {
                        echo '<form method="POST" style="display: inline;">';
                        echo '<input type="hidden" name="cita_id" value="'.$row['IDcita'].'">';
                        echo '<button type="submit" name="cancelar_cita" class="btn btn-danger btn-sm">';
                        echo '<i class="fas fa-times"></i> Cancelar';
                        echo '</button>';
                        echo '</form>';
                    }
                    
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<div class="no-appointments">';
                echo '<i class="far fa-calendar-times" style="font-size: 24px; margin-bottom: 10px;"></i>';
                echo '<p>No tienes citas agendadas actualmente</p>';
                echo '</div>';
            }
            
            $stmt->close();
            $con->close();
            ?>
        </div>
    </div>

    <!-- Modal para editar cita -->
    <div id="editarCitaModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title"><i class="fas fa-calendar-edit"></i> Reagendar Cita</h2>
                <span class="close-modal">&times;</span>
            </div>
            
            <form id="editarCitaForm" method="POST" class="modal-form">
                <input type="hidden" name="cita_id" id="modalCitaId">
                <input type="hidden" name="duracion" id="modalDuracion">
                <input type="hidden" name="editar_cita" value="1">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="modalFecha"><i class="far fa-calendar-alt"></i> Fecha*</label>
                        <input type="date" id="modalFecha" name="fecha" required min="<?php 
                            $minDate = new DateTime('now');
                            $minDate->modify('+1 day');
                            echo $minDate->format('Y-m-d'); 
                        ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="modalHora"><i class="far fa-clock"></i> Hora*</label>
                        <select id="modalHora" name="hora" required>
                            <option value="">Seleccione una hora</option>
                            <?php 
                            for ($h = 10; $h <= 17; $h++) {
                                echo '<option value="'.str_pad($h, 2, '0', STR_PAD_LEFT).':00">'.str_pad($h, 2, '0', STR_PAD_LEFT).':00</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-hourglass-half"></i> Duración</label>
                        <div class="duration-display" id="modalDuracionDisplay">Seleccione un tratamiento</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="modalFechaFin"><i class="fas fa-stopwatch"></i> Hora de Fin</label>
                        <input type="text" id="modalFechaFin" name="fechaFin" readonly>
                    </div>
                </div>
                
                <div class="alert alert-danger" id="modalError" style="display: none;">
                    <i class="fas fa-exclamation-circle"></i>
                    <span id="modalErrorText"></span>
                </div>
                
                <div class="button-group">
                    <button type="button" class="btn btn-secondary close-modal-btn">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('editarCitaModal');
        const closeBtns = document.querySelectorAll('.close-modal, .close-modal-btn');
        const editButtons = document.querySelectorAll('.editar-cita-btn');
        
        // Abrir modal al hacer clic en editar
        editButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const citaId = this.getAttribute('data-id');
                const fecha = this.getAttribute('data-fecha');
                const hora = this.getAttribute('data-hora');
                const duracion = this.getAttribute('data-duracion');
                
                document.getElementById('modalCitaId').value = citaId;
                document.getElementById('modalFecha').value = fecha;
                document.getElementById('modalDuracion').value = duracion;
                document.getElementById('modalDuracionDisplay').textContent = 
                    duracion + ' hora' + (duracion > 1 ? 's' : '');
                
                loadAvailableHours(fecha, hora);
                modal.style.display = 'block';
            });
        });
        
        // Cerrar modal
        closeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                modal.style.display = 'none';
            });
        });
        
        // Cerrar al hacer clic fuera del modal
        window.addEventListener('click', function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        });
        
        // Event listener para cambio de fecha
        document.getElementById('modalFecha').addEventListener('change', function() {
            const fecha = this.value;
            if (!fecha) return;
            loadAvailableHours(fecha);
        });
        
        // Event listener para cambio de hora
        document.getElementById('modalHora').addEventListener('change', updateHoraFinModal);
        
        // Validar formulario antes de enviar
        document.getElementById('editarCitaForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const fechaInput = document.getElementById('modalFecha');
            const horaSelect = document.getElementById('modalHora');
            const duracion = document.getElementById('modalDuracion').value;
            const fechaFinInput = document.getElementById('modalFechaFin');
            
            if (!validateAppointmentForm(fechaInput, horaSelect, duracion, fechaFinInput)) {
                return;
            }
            
            this.submit();
        });
        
        // Función para cargar horas disponibles
        async function loadAvailableHours(fecha, horaSeleccionada = null) {
            const horaSelect = document.getElementById('modalHora');
            
            try {
                // Mostrar estado de carga
                horaSelect.disabled = true;
                horaSelect.innerHTML = '<option value="">Cargando horarios...</option>';
                
                const occupiedHours = await fetchHorariosOcupados(fecha);
                
                // Generar opciones de hora
                horaSelect.innerHTML = '<option value="">Seleccione una hora</option>';
                
                for (let h = 10; h <= 17; h++) {
                    const hora = h.toString().padStart(2, '0') + ':00';
                    const option = document.createElement('option');
                    option.value = hora;
                    option.textContent = hora;
                    
                    // Verificar disponibilidad
                    const isAvailable = isHourAvailable(hora, fecha, occupiedHours);
                    
                    if (!isAvailable.available) {
                        option.disabled = true;
                        option.classList.add('disabled-option');
                        option.title = isAvailable.reason;
                    }
                    
                    horaSelect.appendChild(option);
                }
                
                // Restaurar selección si está disponible
                if (horaSeleccionada && horaSelect.querySelector(`option[value="${horaSeleccionada}"]:not(:disabled)`)) {
                    horaSelect.value = horaSeleccionada;
                }
                
                horaSelect.disabled = false;
                updateHoraFinModal();
                
            } catch (error) {
                console.error('Error en loadAvailableHours:', error);
                horaSelect.innerHTML = '<option value="">Error al cargar horarios</option>';
                
                let errorMessage = 'No se pudieron cargar los horarios.';
                if (error.message.includes('Failed to fetch')) {
                    errorMessage += ' Problema de conexión.';
                } else if (error.message.includes('Formato de datos')) {
                    errorMessage += ' Error en el servidor.';
                }
                
                showModalError(errorMessage);
            }
        }
        
        // Función para obtener horarios ocupados del servidor
        function fetchHorariosOcupados(fecha) {
            return new Promise((resolve, reject) => {
                fetch(`verCitas_Paciente.php?get_occupied_hours=1&fecha=${fecha}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`Error HTTP: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (!Array.isArray(data)) {
                            throw new Error('Formato de datos inválido');
                        }
                        resolve(data);
                    })
                    .catch(error => {
                        console.error('Error en fetchHorariosOcupados:', error);
                        reject(error);
                    });
            });
        }
        
        // Función para verificar disponibilidad de hora
        function isHourAvailable(hora, fecha, occupiedHours) {
            // Verificar si la hora está ocupada
            const horaOcupada = occupiedHours.some(range => {
                return hora >= range.start.substring(0, 5) && hora < range.end.substring(0, 5);
            });
            
            if (horaOcupada) {
                return {
                    available: false,
                    reason: "Horario ocupado"
                };
            }
            
            // Verificar límites de horario
            const duracion = document.getElementById('modalDuracion').value;
            if (!duracion) return { available: true };
            
            const horaInicio = new Date(`${fecha}T${hora}:00`);
            const horaFin = new Date(horaInicio);
            horaFin.setHours(horaFin.getHours() + parseInt(duracion));
            
            // Validar horario de cierre (18:00)
            const hora18 = new Date(`${fecha}T18:00:00`);
            if (horaFin > hora18) {
                return {
                    available: false,
                    reason: "Excede horario de cierre (18:00)"
                };
            }
            
            // Validar citas después de 17:00
            const hora17 = new Date(`${fecha}T17:00:00`);
            if (horaInicio >= hora17 && duracion > 1) {
                return {
                    available: false,
                    reason: "Solo 1 hora después de las 17:00"
                };
            }
            
            return { available: true };
        }
        
        // Función para actualizar hora de fin
        function updateHoraFinModal() {
            const duracion = document.getElementById('modalDuracion').value;
            const fechaFinInput = document.getElementById('modalFechaFin');
            const horaInicioSelect = document.getElementById('modalHora');
            const fechaInicioInput = document.getElementById('modalFecha');
            
            if (duracion && horaInicioSelect.value && fechaInicioInput.value) {
                const [hours, minutes] = horaInicioSelect.value.split(":").map(Number);
                const fechaFin = new Date(`${fechaInicioInput.value}T${horaInicioSelect.value}:00`);
                fechaFin.setHours(fechaFin.getHours() + parseInt(duracion));
                
                const horaFin = fechaFin.getHours();
                const minutoFin = fechaFin.getMinutes();
                
                if (horaFin > 18 || (horaFin === 18 && minutoFin > 0)) {
                    fechaFinInput.value = `${fechaFin.getHours().toString().padStart(2, '0')}:${fechaFin.getMinutes().toString().padStart(2, '0')} (Fuera de horario)`;
                    fechaFinInput.style.color = 'red';
                } else {
                    fechaFinInput.value = `${fechaFin.getHours().toString().padStart(2, '0')}:${fechaFin.getMinutes().toString().padStart(2, '0')}`;
                    fechaFinInput.style.color = '';
                }
            } else {
                fechaFinInput.value = "";
            }
        }
        
        // Función para validar el formulario
        function validateAppointmentForm(fechaInput, horaSelect, duracion, fechaFinInput) {
            // Validar que no sea en el pasado
            const ahora = new Date();
            const fechaCita = new Date(`${fechaInput.value}T${horaSelect.value}:00`);
            
            if (fechaCita <= ahora) {
                showModalError('No puedes reagendar una cita para una fecha/hora pasada');
                return false;
            }
            
            // Validar anticipación mínima de 1 día completo
            const hoy = new Date();
            hoy.setHours(0, 0, 0, 0);
            
            const fechaCitaDia = new Date(fechaInput.value);
            const diferenciaDias = Math.floor((fechaCitaDia - hoy) / (1000 * 60 * 60 * 24));
            
            if (diferenciaDias < 1) {
                const minAvailableDate = new Date(hoy);
                minAvailableDate.setDate(hoy.getDate() + 1);
                
                showModalError(`Debes reagendar con al menos 1 día completo de anticipación. La primera fecha disponible es ${minAvailableDate.toLocaleDateString()}`);
                return false;
            }
            
            // Validar campos requeridos
            if (!horaSelect.value || !duracion) {
                showModalError('Por favor complete todos los campos requeridos.');
                return false;
            }
            
            // Validar si la hora seleccionada está ocupada
            if (horaSelect.options[horaSelect.selectedIndex].disabled) {
                showModalError('La hora seleccionada no está disponible. Por favor elija otra.');
                return false;
            }
            
            // Validar que no se pase de las 18:00 horas
            if (fechaFinInput.value.includes("(Fuera de horario)")) {
                showModalError('El tratamiento debe terminar a las 18:00 como máximo. Por favor, elija una hora más temprana.');
                return false;
            }
            
            return true;
        }
        
        // Función para mostrar errores en el modal
        function showModalError(message) {
            const errorDiv = document.getElementById('modalError');
            const errorText = document.getElementById('modalErrorText');
            
            if (errorDiv && errorText) {
                errorText.textContent = message;
                errorDiv.style.display = 'flex';
                
                errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                setTimeout(() => {
                    errorDiv.style.display = 'none';
                }, 5000);
            } else {
                console.error('Error:', message);
                alert(message);
            }
        }
    });
    </script>
</body>
</html>