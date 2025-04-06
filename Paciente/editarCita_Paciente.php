<?php
session_start();
require_once '../connection.php';
require_once '../functions.php';

// Get the current logged-in user's ID
$IDpaciente = $_SESSION['user_id']; // Ensure the user is logged in

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
              WHERE c.IDcita = ? AND c.IDpaciente = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("ii", $cita_id, $IDpaciente);
    $stmt->execute();
    $cita = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$cita) {
        $mensaje = "Cita no encontrada o no pertenece al paciente actual.";
    }
}

// Fetch available treatments
$query = "SELECT IDtratamiento, nombre, duracion FROM Tratamientos ORDER BY IDtratamiento";
$result = mysqli_query($con, $query);
$tratamientos = $result->fetch_all(MYSQLI_ASSOC);

// Procesar actualización de cita
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_cita'])) {
    $cita_id = $_POST['cita_id'];
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $estado = $_POST['estado'];
    $tratamiento_id = $_POST['tratamiento'];
    $duracion = (int)$_POST['duracion']; // Ensure duracion is cast to an integer
    
    // Validar datos
    if (empty($fecha) || empty($hora) || empty($tratamiento_id)) {
        $mensaje = "Por favor complete todos los campos requeridos";
    } else {
        // Get the current date
        $currentDate = date('Y-m-d');

        // Validate that the selected date is not in the past and not the same as today
        if ($fecha <= $currentDate) {
            $mensaje = "La fecha debe ser un día futuro. No puede ser hoy ni una fecha pasada.";
        } elseif ($hora < "10:00:00" || $hora > "18:00:00") {
            // Validate that the hour is within the allowed range
            $mensaje = "La hora debe estar entre las 10:00 AM y las 6:00 PM.";
        } else {
            // Combine date and time into a single datetime value
            $datetime = $fecha . ' ' . $hora;

            // Calculate fechaFin by adding the duration to the start time
            $startDateTime = new DateTime($datetime);
            $startDateTime->modify("+$duracion hours"); // Add the duration as hours
            $fechaFin = $startDateTime->format('Y-m-d H:i:s');

            // Check for overlapping appointments
            $query = "SELECT * FROM Citas WHERE 
                      (fecha <= ? AND fechaFin >= ?) AND IDpaciente != ?";
            $stmt = $con->prepare($query);
            $stmt->bind_param("ssi", $fechaFin, $datetime, $IDpaciente);
            $stmt->execute();
            $overlapping = $stmt->get_result();
            $stmt->close();

            if ($overlapping->num_rows > 0) {
                $mensaje = "Ya existe una cita en ese horario. Por favor, elija otro horario.";
            } else {
                // Actualizar en base de datos
                $query = "UPDATE Citas SET 
                          fecha = ?, 
                          estado = ?, 
                          IDtratamiento = ? 
                          WHERE IDcita = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param("ssii", $datetime, $estado, $tratamiento_id, $cita_id);

                if ($stmt->execute()) {
                    $mensaje = "Cita actualizada correctamente";
                    // Actualizar datos mostrados
                    $cita['fecha'] = $datetime;
                    $cita['estado'] = $estado;
                    $cita['IDtratamiento'] = $tratamiento_id;
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
    <style>
        :root {
            --color-primario: #1a37b5;
            --color-secundario: #FFF9EB;
            --color-terciario: #C4C4C4;
            --color-button: #fe652b;
            --color-button-hover: #501801;
            --color-text: #444444;
            --color-fondo: #e2dfdf;
            --color-white: #FFFFFF;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: var(--color-fondo);
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: var(--color-primario);
            margin-bottom: 20px;
            text-align: center;
        }

        .patient-info {
            background-color: var(--color-secundario);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .patient-info h2 {
            color: var(--color-primario);
            margin-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--color-text);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--color-terciario);
            border-radius: 4px;
            font-size: 16px;
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.3s;
        }

        .btn-primary {
            background-color: var(--color-primario);
            color: white;
        }

        .btn-primary:hover {
            background-color: #0d2b9a;
        }

        .btn-secondary {
            background-color: var(--color-terciario);
            color: var(--color-text);
        }

        .btn-secondary:hover {
            background-color: #b0b0b0;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: flex-end;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }

        .status-pendiente {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-cancelada {
            background-color: #f8d7da;
            color: #721c24;
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Editar Cita</h1>
        
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?= strpos($mensaje, 'Error') !== false ? 'danger' : 'success' ?>">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($cita)): ?>
            <p>Cita no encontrada</p>
        <?php else: ?>
            <div class="patient-info">
                <h2>Paciente: <?= htmlspecialchars($cita['paciente_nombre']) ?></h2>
                <p>Tratamiento actual: <?= htmlspecialchars($cita['tratamiento_nombre']) ?></p>
                <p>Estado: 
                    <span class="status-badge status-<?= strtolower($cita['estado']) ?>">
                        <?= htmlspecialchars($cita['estado']) ?>
                    </span>
                </p>
            </div>
            
            <form method="POST">
                <input type="hidden" name="cita_id" value="<?= $cita['IDcita'] ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha">Fecha*</label>
                        <input type="date" id="fecha" name="fecha" required 
                               value="<?= htmlspecialchars(date('Y-m-d', strtotime($cita['fecha']))) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="hora">Hora*</label>
                        <input type="time" id="hora" name="hora" required 
                               value="<?= htmlspecialchars(date('H:i', strtotime($cita['fecha']))) ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="tratamiento">Tratamiento*</label>
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
                        <label for="estado">Estado*</label>
                        <select id="estado" name="estado" required>
                            <option value="Pendiente" <?= $cita['estado'] == 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="Cancelada" <?= $cita['estado'] == 'Cancelada' ? 'selected' : '' ?>>Cancelada</option>
                            <option value="Completada" <?= $cita['estado'] == 'Completada' ? 'selected' : '' ?>>Completada</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="duracion">Duración (horas)</label>
                        <input type="text" id="duracion" name="duracion" readonly 
                               value="<?= htmlspecialchars($tratamientos[array_search($cita['IDtratamiento'], array_column($tratamientos, 'IDtratamiento'))]['duracion'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="fechaFin">Hora de Fin</label>
                        <input type="text" id="fechaFin" name="fechaFin" readonly>
                    </div>
                </div>
                
                <div class="button-group">
                    <button href="verCitas_Paciente.php" type="submit" name="actualizar_cita" class="btn btn-primary">Guardar Cambios</button>
                    <a href="verCitas_Paciente.php" class="btn btn-secondary">Cancelar</a>
                    
                </div>
            </form>
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
