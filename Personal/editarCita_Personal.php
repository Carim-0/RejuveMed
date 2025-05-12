<?php
session_start();
require_once '../connection.php';
require_once '../functions.php';

// Verificar login y permisos
$user_data = check_login($con);
if ($_SESSION['user_type'] !== 'Personal') {
    header("Location: ../unauthorized.php");
    exit();
}

// Obtener datos de la cita a editar
$cita = [];
$paciente = [];
$tratamientos = [];
$mensaje = '';

if (isset($_GET['id'])) {
    $cita_id = $_GET['id'];
    
    // Obtener información de la cita
    $query = "SELECT c.*, t.nombre as tratamiento_nombre, p.nombre as paciente_nombre, p.IDpaciente
              FROM Citas c
              JOIN Tratamientos t ON c.IDtratamiento = t.IDtratamiento
              JOIN Pacientes p ON c.IDpaciente = p.IDpaciente
              WHERE c.IDcita = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $cita_id);
    $stmt->execute();
    $cita = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$cita) {
        $mensaje = "Cita no encontrada";
    }
}

// Obtener lista de tratamientos para el select
$query = "SELECT IDtratamiento, nombre, duracion FROM Tratamientos ORDER BY nombre";
$result = $con->query($query);
$tratamientos = $result->fetch_all(MYSQLI_ASSOC);

// Obtener pacientes para autocomplete
$pacientes_query = "SELECT IDpaciente, nombre FROM Pacientes";
$pacientes_result = $con->query($pacientes_query);
$pacientes = [];
while ($row = $pacientes_result->fetch_assoc()) {
    $pacientes[] = [
        'id' => $row['IDpaciente'],
        'nombre_completo' => $row['nombre']
    ];
}

// Obtener horarios ocupados si se solicita via AJAX
if (isset($_GET['get_occupied_hours'])) {
    $fecha = $_GET['fecha'];
    $occupiedHours = [];
    
    $query = "SELECT TIME(fecha) as hora, TIME(fechaFin) as horaFin 
          FROM Citas 
          WHERE DATE(fecha) = ? AND estado != 'Cancelada' AND IDcita != ?";
    
    $stmt = $con->prepare($query);
    $stmt->bind_param("si", $fecha, $cita_id);
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
    exit;
}

// Procesar actualización de cita
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_cita'])) {
    $cita_id = $_POST['cita_id'];
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $estado = $_POST['estado'];
    $tratamiento_id = $_POST['tratamiento'];
    $IDpaciente = $_POST['IDpaciente'];

    // Obtener la duración del tratamiento seleccionado
    $query = "SELECT duracion FROM Tratamientos WHERE IDtratamiento = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $tratamiento_id);
    $stmt->execute();
    $stmt->bind_result($duracion);
    $stmt->fetch();
    $stmt->close();

    // Combine date and time into a single datetime value
    $horaCompleta = $hora . ':00';
    $datetime = $fecha . ' ' . $horaCompleta;

    // Definir horario laboral
    $horaMin = '10:00:00';
    $horaMax = '18:00:00';
    
    // Validar tiempo mínimo de anticipación (1 día completo)
    $now = new DateTime('now');
    $now->setTime(0, 0, 0); // Considerar el día completo
    
    $datetimeLocal = new DateTime($datetime);
    $diferencia = $now->diff($datetimeLocal);
    
    if ($diferencia->days < 1 || $datetimeLocal <= $now) {
        $mensaje = "Debes agendar con al menos 1 día completo de anticipación.";
    }
    // Validar horario laboral
    elseif ($horaCompleta < $horaMin || $horaCompleta > $horaMax) {
        $mensaje = "La hora debe estar entre las 10:00 AM y 6:00 PM.";
    } else {
        // Calcular fecha de fin
        $datetimeFinLocal = clone $datetimeLocal;
        $datetimeFinLocal->modify("+$duracion hours");
        
        // Validar que no pase de las 18:00 horas
        $horaFin = $datetimeFinLocal->format('H:i:s');
        if ($horaFin > $horaMax) {
            $mensaje = "El tratamiento no puede terminar después de las 18:00. Por favor, elija una hora más temprana o un tratamiento más corto.";
        }
        // Validar citas después de 17:00 (incluyendo las 17:00 exactas)
        elseif ($datetimeLocal->format('H:i:s') >= '17:00:00' && $duracion > 1) {
            $mensaje = "Solo se permiten citas de 1 hora después de las 17:00.";
        } else {
            // Formatear para consulta SQL
            $fechaBD = $datetimeLocal->format('Y-m-d H:i:s');
            $fechaFinBD = $datetimeFinLocal->format('Y-m-d H:i:s');

            // Check for overlapping appointments
            $query = "SELECT * FROM Citas WHERE 
                (fecha < ? AND fechaFin > ?) AND IDcita != ? AND estado != 'Cancelada'";
            $stmt = $con->prepare($query);
            $stmt->bind_param("ssi", $fechaFinBD, $fechaBD, $cita_id);
            $stmt->execute();
            $overlapping = $stmt->get_result();
            $stmt->close();

            if ($overlapping->num_rows > 0) {
                $mensaje = "Ya existe una cita en ese horario. Por favor, elija otro horario.";
            } else {
                // Update the cita in the database
                $query = "UPDATE Citas SET fecha = ?, fechaFin = ?, estado = ?, IDtratamiento = ?, IDpaciente = ? WHERE IDcita = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param("sssiii", $fechaBD, $fechaFinBD, $estado, $tratamiento_id, $IDpaciente, $cita_id);

                if ($stmt->execute()) {
                    $mensaje = "Cita actualizada correctamente.";
                } else {
                    $mensaje = "Error al actualizar la cita: " . $con->error;
                }
                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cita - RejuveMed</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
        }
        
        .container {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 900px;
            padding: 30px;
            margin: 20px 0;
        }
        
        h1 {
            color: var(--primary-color);
            margin-bottom: 25px;
            text-align: center;
            font-size: 28px;
            font-weight: 600;
        }
        
        .patient-card {
            background-color: var(--light-color);
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            box-shadow: var(--box-shadow);
        }
        
        .patient-card h2 {
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .patient-card h2 i {
            color: var(--secondary-color);
        }
        
        .patient-info {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 14px;
            color: var(--secondary-color);
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .info-value {
            font-size: 16px;
            color: var(--dark-color);
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            gap: 5px;
        }
        
        .status-pendiente {
            background-color: rgba(var(--warning-color), 0.2);
            color: blue;
        }
        
        .status-cancelada {
            background-color: rgba(var(--danger-color), 0.2);
            color: var(--danger-color);
        }
        
        .status-completada {
            background-color: rgba(var(--success-color), 0.2);
            color: var(--success-color);
        }
        
        .form-container {
            margin-top: 25px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--secondary-color);
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 111, 165, 0.2);
        }
        
        .form-group input[readonly] {
            background-color: #f5f5f5;
            color: #777;
        }
        
        .button-group {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #3a5a8a;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background-color: #e9ecef;
            color: var(--dark-color);
        }
        
        .btn-secondary:hover {
            background-color: #d6d8db;
            transform: translateY(-2px);
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
            background-color: rgba(var(--success-color), 0.2);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }
        
        .alert-danger {
            background-color: rgba(var(--danger-color), 0.2);
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }
        
        .alert i {
            font-size: 20px;
        }
        
        /* Autocomplete styles */
        .ui-autocomplete {
            max-height: 200px;
            overflow-y: auto;
            overflow-x: hidden;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            z-index: 1000 !important;
        }
        
        .ui-menu-item {
            padding: 8px 12px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .ui-menu-item:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .ui-helper-hidden-accessible {
            display: none;
        }
        
        /* Estilo para opciones deshabilitadas */
        option:disabled, option.disabled-option {
            color: #999 !important;
            background-color: #f5f5f5 !important;
            cursor: not-allowed;
        }

        .duration-display {
            background-color: #f5f5f5;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 15px;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .patient-info {
                grid-template-columns: 1fr;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
            
            .container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-calendar-edit"></i> Editar Cita</h1>
        
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?= strpos($mensaje, 'Error') !== false ? 'danger' : 'success' ?>">
                <i class="fas <?= strpos($mensaje, 'Error') !== false ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i>
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($cita)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                Cita no encontrada
            </div>
        <?php else: ?>
            <div class="patient-card">
                <h2><i class="fas fa-user-injured"></i> Información del Paciente</h2>
                <div class="patient-info">
                    <div class="info-item">
                        <span class="info-label">Nombre completo</span>
                        <span class="info-value"><?= htmlspecialchars($cita['paciente_nombre']) ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Tratamiento actual</span>
                        <span class="info-value"><?= htmlspecialchars($cita['tratamiento_nombre']) ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Estado de la cita</span>
                        <span class="status-badge status-<?= strtolower($cita['estado']) ?>">
                            <i class="fas <?= 
                                $cita['estado'] == 'Pendiente' ? 'fa-clock' : 
                                ($cita['estado'] == 'Cancelada' ? 'fa-times-circle' : 'fa-check-circle') 
                            ?>"></i>
                            <?= htmlspecialchars($cita['estado']) ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="form-container">
                <form method="POST" id="citaForm">
                    <input type="hidden" name="cita_id" value="<?= $cita['IDcita'] ?>">
                    
                    <div class="form-group">
                        <label for="paciente"><i class="fas fa-user"></i> Paciente*</label>
                        <input type="text" id="paciente" name="paciente" placeholder="Buscar paciente..." 
                               value="<?= htmlspecialchars($cita['paciente_nombre']) ?>" autocomplete="off">
                        <input type="hidden" id="IDpaciente" name="IDpaciente" value="<?= $cita['IDpaciente'] ?>">
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="fecha"><i class="far fa-calendar-alt"></i> Fecha*</label>
                            <input type="date" id="fecha" name="fecha" required 
                                   min="<?php 
                                        $minDate = new DateTime('now');
                                        $minDate->modify('+1 day');
                                        echo $minDate->format('Y-m-d'); 
                                   ?>"
                                   value="<?= htmlspecialchars(date('Y-m-d', strtotime($cita['fecha']))) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="hora"><i class="far fa-clock"></i> Hora*</label>
                            <select id="hora" name="hora" required>
                                <option value="">Seleccione una hora</option>
                                <?php 
                                // Generar opciones de hora de 10:00 a 17:00
                                for ($h = 10; $h <= 17; $h++) {
                                    $horaFormato = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
                                    $selected = date('H:i', strtotime($cita['fecha'])) == $horaFormato ? 'selected' : '';
                                    echo "<option value='$horaFormato' $selected>$horaFormato</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="tratamiento"><i class="fas fa-pills"></i> Tratamiento*</label>
                            <select id="tratamiento" name="tratamiento" required onchange="updateDuration()">
                                <option value="">Seleccione un tratamiento</option>
                                <?php foreach ($tratamientos as $tratamiento): ?>
                                    <option value="<?= $tratamiento['IDtratamiento'] ?>" 
                                            data-duracion="<?= $tratamiento['duracion'] ?>"
                                            <?= $tratamiento['IDtratamiento'] == $cita['IDtratamiento'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($tratamiento['nombre']) ?> (Duración: <?= $tratamiento['duracion'] ?> hora<?= $tratamiento['duracion'] > 1 ? 's' : '' ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="estado"><i class="fas fa-info-circle"></i> Estado*</label>
                            <select id="estado" name="estado" required>
                                <option value="Pendiente" <?= $cita['estado'] == 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                <option value="Cancelada" <?= $cita['estado'] == 'Cancelada' ? 'selected' : '' ?>>Cancelada</option>
                                <option value="Completada" <?= $cita['estado'] == 'Completada' ? 'selected' : '' ?>>Completada</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-hourglass-half"></i> Duración</label>
                            <div class="duration-display" id="duracion-display">
                                <?php 
                                    $duracionActual = $tratamientos[array_search($cita['IDtratamiento'], array_column($tratamientos, 'IDtratamiento'))]['duracion'] ?? 0;
                                    echo $duracionActual . ' hora' . ($duracionActual > 1 ? 's' : '');
                                ?>
                            </div>
                            <input type="hidden" id="duracion" name="duracion" value="<?= $duracionActual ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="fechaFin"><i class="fas fa-stopwatch"></i> Hora de Fin</label>
                            <input type="text" id="fechaFin" name="fechaFin" readonly
                                   value="<?= date('H:i', strtotime($cita['fechaFin'])) ?>">
                        </div>
                    </div>
                    
                    <div class="button-group">
                        <a href="verCitasPacientes_Personal.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Cancelar
                        </a>
                        <button type="submit" name="actualizar_cita" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script>
        $(function() {
            var pacientes = <?php echo json_encode($pacientes); ?>;
            
            // Prepare data for autocomplete
            var pacienteData = pacientes.map(function(paciente) {
                return {
                    label: paciente.nombre_completo,
                    value: paciente.nombre_completo,
                    id: paciente.id
                };
            });
            
            // Initialize autocomplete
            $("#paciente").autocomplete({
                source: pacienteData,
                minLength: 2,
                select: function(event, ui) {
                    $("#IDpaciente").val(ui.item.id);
                    $("#paciente").val(ui.item.label);
                    return false;
                },
                focus: function(event, ui) {
                    $("#paciente").val(ui.item.label);
                    return false;
                }
            }).autocomplete("instance")._renderItem = function(ul, item) {
                return $("<li>")
                    .append("<div>" + item.label + "</div>")
                    .appendTo(ul);
            };
        });

        document.addEventListener("DOMContentLoaded", function() {
            // Escuchar cambios en la fecha para cargar horarios ocupados
            document.getElementById("fecha").addEventListener("change", function() {
                const fecha = this.value;
                if (!fecha) return;
                
                fetch(`editar_cita.php?get_occupied_hours=1&fecha=${fecha}&id=<?= $cita['IDcita'] ?? '' ?>`)
                    .then(response => response.json())
                    .then(occupiedHours => updateTimePicker(occupiedHours));
            });

            // Inicializar campos
            updateDuration();
        });

        function updateDuration() {
            const tratamientoSelect = document.getElementById("tratamiento");
            const duracionDisplay = document.getElementById("duracion-display");
            const duracionInput = document.getElementById("duracion");
            const fechaFinInput = document.getElementById("fechaFin");
            const horaInicioSelect = document.getElementById("hora");
            const fechaInicioInput = document.getElementById("fecha");

            const selectedOption = tratamientoSelect.options[tratamientoSelect.selectedIndex];
            const duracion = selectedOption.getAttribute("data-duracion");

            if (duracion) {
                duracionDisplay.textContent = `${duracion} hora${duracion > 1 ? 's' : ''}`;
                duracionInput.value = duracion;
                
                // Actualizar hora de fin automáticamente
                updateHoraFin();
                
                // Actualizar disponibilidad de horarios
                const fecha = document.getElementById("fecha").value;
                if (fecha) {
                    fetch(`editar_cita.php?get_occupied_hours=1&fecha=${fecha}&id=<?= $cita['IDcita'] ?? '' ?>`)
                        .then(response => response.json())
                        .then(occupiedHours => updateTimePicker(occupiedHours));
                }
            } else {
                duracionDisplay.textContent = "Seleccione un tratamiento";
                duracionInput.value = "";
                fechaFinInput.value = "";
            }
        }

        function updateHoraFin() {
            const duracionInput = document.getElementById("duracion");
            const fechaFinInput = document.getElementById("fechaFin");
            const horaInicioSelect = document.getElementById("hora");
            const fechaInicioInput = document.getElementById("fecha");

            const duracion = parseInt(duracionInput.value, 10);
            const horaInicio = horaInicioSelect.value;
            const fechaInicio = fechaInicioInput.value;

            if (duracion && horaInicio && fechaInicio) {
                const [hours, minutes] = horaInicio.split(":").map(Number);
                const fechaFin = new Date(`${fechaInicio}T${horaInicio}:00`);
                fechaFin.setHours(fechaFin.getHours() + duracion);

                const horaFin = fechaFin.getHours();
                const minutoFin = fechaFin.getMinutes();
                
                // Permitir hasta exactamente las 18:00
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

        function updateTimePicker(occupiedHours) {
            const timeSelect = document.getElementById("hora");
            const options = timeSelect.querySelectorAll("option");
            const tratamientoSelect = document.getElementById("tratamiento");
            const duracion = tratamientoSelect.options[tratamientoSelect.selectedIndex]?.getAttribute("data-duracion") || 0;
            const fecha = document.getElementById("fecha").value;
            
            // Reset all options
            options.forEach(option => {
                if (option.value !== "") {
                    option.disabled = false;
                    option.style.color = '';
                    option.title = '';
                    option.classList.remove('disabled-option');
                }
            });
            
            // Deshabilitar horarios ocupados
            if (occupiedHours && occupiedHours.length > 0) {
                occupiedHours.forEach(range => {
                    const startHour = range.start.substring(0, 5); // Formato HH:MM
                    const endHour = range.end.substring(0, 5);
                    
                    options.forEach(option => {
                        if (option.value && option.value >= startHour && option.value < endHour) {
                            option.disabled = true;
                            option.style.color = '#999';
                            option.title = "Horario ocupado";
                            option.classList.add('disabled-option');
                        }
                    });
                });
            }
            
            // Validar horarios que excedan el límite
            if (fecha && duracion) {
                options.forEach(option => {
                    if (option.value && !option.disabled) {
                        const horaInicio = new Date(`${fecha}T${option.value}:00`);
                        const horaFin = new Date(horaInicio);
                        horaFin.setHours(horaFin.getHours() + parseInt(duracion));
                        
                        // Horario de cierre a las 18:00
                        const hora18 = new Date(`${fecha}T18:00:00`);
                        
                        // Validar que no termine después de las 18:00
                        if (horaFin > hora18) {
                            option.disabled = true;
                            option.style.color = '#999';
                            option.title = "Esta cita excedería el horario de cierre (18:00)";
                            option.classList.add('disabled-option');
                        }
                        
                        // Validar citas después de 17:00
                        const hora17 = new Date(`${fecha}T17:00:00`);
                        if (horaInicio >= hora17 && duracion > 1) {
                            option.disabled = true;
                            option.style.color = '#999';
                            option.title = "Solo se permiten citas de 1 hora después de las 17:00";
                            option.classList.add('disabled-option');
                        }
                    }
                });
            }
        }

        // Validación del formulario antes de enviar
        document.getElementById('citaForm').addEventListener('submit', function(e) {
            // Validar que no se pase de las 18:00 horas
            const fechaFinInput = document.getElementById('fechaFin');
            if (fechaFinInput.value.includes("(Fuera de horario)")) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Horario no válido',
                    text: 'El tratamiento debe terminar a las 18:00 como máximo. Por favor, elija una hora más temprana o un tratamiento más corto.',
                    confirmButtonColor: '#3085d6'
                });
                return false;
            }
            
            // Validar si la hora seleccionada está ocupada
            const horaSelect = document.getElementById('hora');
            if (horaSelect.options[horaSelect.selectedIndex].disabled) {
                e.preventDefault();
                const motivo = horaSelect.options[horaSelect.selectedIndex].title || 'La hora seleccionada no está disponible';
                Swal.fire({
                    icon: 'error',
                    title: 'Horario no disponible',
                    text: motivo,
                    confirmButtonColor: '#3085d6'
                });
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>
<?php $con->close(); ?>
