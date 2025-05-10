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
    $query = "SELECT c.*, t.nombre as tratamiento_nombre, p.nombre as paciente_nombre 
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

// Procesar actualización de cita
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_cita'])) {
    $cita_id = $_POST['cita_id'];
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $estado = $_POST['estado'];
    $tratamiento_id = $_POST['tratamiento'];

    // Fetch the duration of the selected tratamiento
    $query = "SELECT duracion FROM Tratamientos WHERE IDtratamiento = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $tratamiento_id);
    $stmt->execute();
    $stmt->bind_result($duracion);
    $stmt->fetch();
    $stmt->close();

    // Combine date and time into a single datetime value
    $datetime = $fecha . ' ' . $hora;

    // Calculate fechaFin by adding the duration to the start time
    $startDateTime = new DateTime($datetime);
    $startDateTime->modify("+$duracion hours");
    $fechaFin = $startDateTime->format('Y-m-d H:i:s');

    // Get the current date
    $currentDate = date('Y-m-d');

    // Validate the date
    if ($fecha <= $currentDate) {
        $mensaje = "La fecha tiene que ser después de mañana como mínimo.";
    }

    // Validate the date
    if ($fecha <= $currentDate) {
        $mensaje = "La fecha debe ser un día futuro. No puede ser hoy ni una fecha pasada.";
    }
    // Validate the hour range
    elseif ($hora < "10:00:00" || $hora > "18:00:00" || $startDateTime->format('H:i') > "18:00") {
        $mensaje = "La hora debe estar entre las 10:00 AM y las 6:00 PM.";
    }
    // Check for overlapping appointments
    else {
        $query = "SELECT * FROM Citas WHERE 
                  (fecha <= ? AND fechaFin >= ?) AND IDcita != ? AND estado = 'Pendiente'";
        $stmt = $con->prepare($query);
        $stmt->bind_param("ssi", $fechaFin, $datetime, $cita_id);
        $stmt->execute();
        $overlapping = $stmt->get_result();
        $stmt->close();

        if ($overlapping->num_rows > 0) {
            $mensaje = "Ya existe una cita en ese horario. Por favor, elija otro horario.";
        } else {
            // Update the cita in the database
            $query = "UPDATE Citas SET fecha = ?, fechaFin = ?, estado = ?, IDtratamiento = ? WHERE IDcita = ?";
            $stmt = $con->prepare($query);
            $stmt->bind_param("sssii", $datetime, $fechaFin, $estado, $tratamiento_id, $cita_id);

            if ($stmt->execute()) {
                $mensaje = "Cita actualizada correctamente.";
            } else {
                $mensaje = "Error al actualizar la cita: " . $con->error;
            }
            $stmt->close();
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
                <form method="POST">
                    <input type="hidden" name="cita_id" value="<?= $cita['IDcita'] ?>">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="fecha"><i class="far fa-calendar-alt"></i> Fecha*</label>
                            <input type="date" id="fecha" name="fecha" required 
                                   value="<?= htmlspecialchars(date('Y-m-d', strtotime($cita['fecha']))) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="hora"><i class="far fa-clock"></i> Hora*</label>
                            <input type="time" id="hora" name="hora" required 
                                   value="<?= htmlspecialchars(date('H:i', strtotime($cita['fecha']))) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="tratamiento"><i class="fas fa-pills"></i> Tratamiento*</label>
                            <select id="tratamiento" name="tratamiento" required>
                                <?php foreach ($tratamientos as $tratamiento): ?>
                                    <option value="<?= $tratamiento['IDtratamiento'] ?>" 
                                            data-duracion="<?= $tratamiento['duracion'] ?>"
                                            <?= $tratamiento['IDtratamiento'] == $cita['IDtratamiento'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($tratamiento['nombre']) ?>
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
                            <label for="duracion"><i class="fas fa-hourglass-half"></i> Duración</label>
                            <input type="text" id="duracion" name="duracion" readonly 
                                   value="<?= htmlspecialchars($tratamientos[array_search($cita['IDtratamiento'], array_column($tratamientos, 'IDtratamiento'))]['duracion'] ?? '') ?> horas">
                        </div>
                        
                        <div class="form-group">
                            <label for="fechaFin"><i class="fas fa-stopwatch"></i> Hora de Fin</label>
                            <input type="text" id="fechaFin" name="fechaFin" readonly>
                        </div>
                    </div>
                    
                    <div class="button-group">
                        <a href="verCitasPacientes_Doctora.php" class="btn btn-secondary">
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

    <script>
        // Validación de fecha/hora futura
        document.querySelector('form').addEventListener('submit', function(e) {
            const fecha = document.getElementById('fecha').value;
            const hora = document.getElementById('hora').value;
            const ahora = new Date();
            const fechaCita = new Date(fecha + 'T' + hora);
            
            if (fechaCita < ahora) {
                if (!confirm('La fecha y hora de la cita son en el pasado. ¿Desea continuar?')) {
                    e.preventDefault();
                }
            }
        });

        document.addEventListener("DOMContentLoaded", function () {
            const tratamientoSelect = document.getElementById("tratamiento");
            const horaInput = document.getElementById("hora");
            const fechaInput = document.getElementById("fecha");
            const duracionInput = document.getElementById("duracion");
            const fechaFinInput = document.getElementById("fechaFin");

            // Update duracion and fechaFin when the tratamiento or hora changes
            function updateFields() {
                const selectedOption = tratamientoSelect.options[tratamientoSelect.selectedIndex];
                const duracion = selectedOption.getAttribute("data-duracion");
                const horaInicio = horaInput.value;
                const fechaInicio = fechaInput.value;

                // Update duracion field
                duracionInput.value = duracion ? `${duracion} horas` : "";

                // Calculate and update fechaFin field
                if (duracion && horaInicio && fechaInicio) {
                    const [hours, minutes] = horaInicio.split(":").map(Number);
                    const duracionHoras = parseInt(duracion, 10);
                    const fechaFin = new Date(`${fechaInicio}T${horaInicio}`);
                    fechaFin.setHours(fechaFin.getHours() + duracionHoras);
                    fechaFinInput.value = `${fechaFin.getHours().toString().padStart(2, '0')}:${fechaFin.getMinutes().toString().padStart(2, '0')}`;
                } else {
                    fechaFinInput.value = "";
                }
            }

            // Attach event listeners
            tratamientoSelect.addEventListener("change", updateFields);
            horaInput.addEventListener("input", updateFields);
            fechaInput.addEventListener("input", updateFields);

            // Initialize fields on page load
            updateFields();
        });
    </script>
</body>
</html>
<?php $con->close(); ?>
