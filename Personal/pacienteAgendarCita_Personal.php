<?php
session_start();

include("../connection.php");

// Ensure the user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Personal') {
    die("Acceso denegado. Por favor, inicie sesión como personal.");
}

$IDpersonal = $_SESSION['user_id'];

function showSweetAlert($icon, $title, $text, $redirect = null, $preserveFormData = false) {
    echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: '$icon',
            title: '$title',
            text: '$text',
            confirmButtonColor: '#3085d6'
        })";
    if ($redirect) {
        echo ".then((result) => { if (result.isConfirmed) { window.location.href = '$redirect'; } })";
    } elseif ($preserveFormData) {
        echo ".then((result) => { if (result.isConfirmed) { 
            document.getElementById('fecha').value = localStorage.getItem('tempFecha');
            document.getElementById('hora').value = localStorage.getItem('tempHora');
            document.getElementById('IDtratamiento').value = localStorage.getItem('tempTratamiento');
            updateDuration();
            
            const fecha = localStorage.getItem('tempFecha');
            if (fecha) {
                fetch(`pacienteAgendarCita_Personal.php?get_occupied_hours=1&fecha=\${fecha}`)
                    .then(response => response.json())
                    .then(occupiedHours => updateTimePicker(occupiedHours));
            }
        } })";
    }
    echo ";});
    </script>";
    
    return false; // Evita que se ejecute código adicional
}

// Fetch available treatments with duration
$query = "SELECT IDtratamiento, nombre, duracion FROM Tratamientos";
$result = mysqli_query($con, $query);
if (!$result) {
    die("Error al cargar tratamientos: " . mysqli_error($con));
}

$tratamientos_data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $tratamientos_data[] = $row;
}

// Fetch patients for autocomplete
$pacientes_query = "SELECT IDpaciente, nombre FROM Pacientes";
$pacientes_result = mysqli_query($con, $pacientes_query);
if (!$pacientes_result) {
    die("Error al cargar pacientes: " . mysqli_error($con));
}

$pacientes = [];
while ($row = mysqli_fetch_assoc($pacientes_result)) {
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
          WHERE DATE(fecha) = '$fecha' AND estado != 'Cancelada'";
    
    $result = mysqli_query($con, $query);
    if (!$result) {
        die(json_encode(['error' => mysqli_error($con)]));
    }
    
    while ($row = mysqli_fetch_assoc($result)) {
        $occupiedHours[] = [
            'start' => $row['hora'],
            'end' => $row['horaFin']
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($occupiedHours);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $fecha = $_POST['fecha'] ?? '';
    $hora = $_POST['hora'] ?? '';
    $IDtratamiento = $_POST['IDtratamiento'] ?? '';
    $IDpaciente = $_POST['IDpaciente'] ?? '';
    
    // Guardar temporalmente los datos del formulario
    echo "<script>
        localStorage.setItem('tempFecha', '" . addslashes($fecha) . "');
        localStorage.setItem('tempHora', '" . addslashes($hora) . "');
        localStorage.setItem('tempTratamiento', '" . addslashes($IDtratamiento) . "');
    </script>";

    if (!empty($fecha) && !empty($hora) && !empty($IDtratamiento) && !empty($IDpaciente)) {
        // Obtener la duración del tratamiento seleccionado
        $query = "SELECT duracion FROM Tratamientos WHERE IDtratamiento = '$IDtratamiento'";
        $duracion_result = mysqli_query($con, $query);
        if (!$duracion_result || mysqli_num_rows($duracion_result) == 0) {
            showSweetAlert('error', 'Error', 'Tratamiento no válido o no encontrado.', null, true);
            exit;
        }
        
        $row = mysqli_fetch_assoc($duracion_result);
        $duracion = $row['duracion'];

        // Combine date and time
        $horaCompleta = $hora . ':00';
        $datetime = $fecha . ' ' . $horaCompleta;
        
        // Validate time range
        $horaMin = '10:00:00';
        $horaMax = '18:00:00';
        
        if ($horaCompleta < $horaMin || $horaCompleta > $horaMax) {
            showSweetAlert('error', 'Error', 'La hora debe estar entre las 10:00 AM y 6:00 PM.', null, true);
            exit;
        }
        
        try {
            $now = new DateTime('now');
            $now->setTime(0, 0, 0); // Considerar el día completo
            
            $datetimeLocal = new DateTime($datetime);
            
            // Verificar anticipación mínima de 1 día completo
            $diferencia = $now->diff($datetimeLocal);
            if ($diferencia->days < 1 || $datetimeLocal <= $now) {
                $minAvailableDate = clone $now;
                $minAvailableDate->modify('+1 day');
                showSweetAlert('error', 'Error', 'Debes agendar con al menos 1 día completo de anticipación. La primera fecha disponible es ' . $minAvailableDate->format('d/m/Y'), null, true);
                exit;
            }
            
            // Calcular fecha de fin
            $datetimeFinLocal = clone $datetimeLocal;
            $datetimeFinLocal->modify("+$duracion hours");
            
            // Validar que no pase de las 18:00 horas (permitir exactamente 18:00)
            $horaFin = $datetimeFinLocal->format('H:i:s');
            if ($horaFin > $horaMax) {
                showSweetAlert('error', 'Error', 'El tratamiento no puede terminar después de las 18:00. Por favor, elija una hora más temprana o un tratamiento más corto.', null, true);
                exit;
            }
            
            // Validar citas después de 17:00 (incluyendo las 17:00 exactas)
            $hora17 = new DateTime($fecha . ' 17:00:00');
            if ($datetimeLocal >= $hora17 && $duracion > 1) {
                showSweetAlert('error', 'Error', 'Solo se permiten citas de 1 hora después de las 17:00.', null, true);
                exit;
            }
            
            // Formatear para consulta SQL
            $fechaBD = $datetimeLocal->format('Y-m-d H:i:s');
            $fechaFinBD = $datetimeFinLocal->format('Y-m-d H:i:s');

            // Check for overlapping appointments
            $query = "SELECT * FROM Citas WHERE 
                (fecha < '$fechaFinBD' AND fechaFin > '$fechaBD') AND estado != 'Cancelada'";
            $result = mysqli_query($con, $query);

            if (!$result) {
                showSweetAlert('error', 'Error', 'Error al verificar disponibilidad: ' . mysqli_error($con), null, true);
                exit;
            }

            if (mysqli_num_rows($result) > 0) {
                showSweetAlert('warning', 'Horario ocupado', 'Ya existe una cita en ese horario. Por favor, elija otro.', null, true);
            } else {
                // Insert appointment
                $query = "INSERT INTO Citas (IDpaciente, IDtratamiento, fecha, fechaFin) 
                          VALUES ('$IDpaciente', '$IDtratamiento', '$fechaBD', '$fechaFinBD')";
                $result = mysqli_query($con, $query);

                if ($result) {
                    // Limpiar datos temporales
                    echo "<script>
                        localStorage.removeItem('tempFecha');
                        localStorage.removeItem('tempHora');
                        localStorage.removeItem('tempTratamiento');
                    </script>";
                    showSweetAlert('success', '¡Éxito!', 'Cita agendada correctamente', 'verCitasPacientes_Personal.php');
                } else {
                    showSweetAlert('error', 'Error', 'Error al agendar la cita: ' . mysqli_error($con), null, true);
                }
            }
        } catch (Exception $e) {
            showSweetAlert('error', 'Error', 'Formato de fecha/hora inválido: ' . $e->getMessage(), null, true);
        }
    } else {
        showSweetAlert('error', 'Error', 'Por favor, complete todos los campos requeridos.', null, true);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Cita - RejuveMed</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* Tus estilos CSS existentes */
        :root {
            --primary-color: #4a6fa5;
            --secondary-color: #6b8cae;
            --accent-color: #4a6fa5;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --success-color: #28a745;
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
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            font-family: "Open Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, 
                         Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", Helvetica, Arial, sans-serif; 
        }
        
        .appointment-container {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 600px;
            padding: 30px;
        }
        
        .appointment-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .appointment-header h2 {
            color: var(--primary-color);
            font-size: 24px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .appointment-form {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
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
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .button-group {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
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
            background-color: transparent;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }
        
        .btn-secondary:hover {
            background-color: rgba(74, 111, 165, 0.1);
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
        
        .action-link {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .action-link:hover {
            color: #3a5a8a;
            text-decoration: underline;
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
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            
            .appointment-container {
                padding: 20px;
            }
            
            .button-group {
                flex-direction: column-reverse;
                gap: 10px;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="appointment-container">
        <div class="appointment-header">
            <h2><i class="fas fa-calendar-plus"></i> Agendar Nueva Cita</h2>
        </div>
        
        <form method="POST" class="appointment-form" onsubmit="return validateForm()">
            <div class="form-group">
                <label for="paciente"><i class="fas fa-user"></i> Paciente*</label>
                <input type="text" id="paciente" name="paciente" placeholder="Buscar paciente..." autocomplete="off">
                <input type="hidden" id="IDpaciente" name="IDpaciente">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="fecha"><i class="far fa-calendar-alt"></i> Fecha*</label>
                    <input type="date" id="fecha" name="fecha" required min="<?php 
                        $minDate = new DateTime('now');
                        $minDate->modify('+1 day'); // +1 día completo
                        echo $minDate->format('Y-m-d'); 
                    ?>">
                </div>
                
                <div class="form-group">
                    <label for="hora"><i class="far fa-clock"></i> Hora*</label>
                    <select id="hora" name="hora" required>
                        <option value="">Seleccione una hora</option>
                        <?php 
                        // Generar opciones de hora de 10:00 a 17:00 (última hora disponible para tratamientos de 1 hora)
                        for ($h = 10; $h <= 17; $h++) {
                            echo "<option value='".str_pad($h, 2, '0', STR_PAD_LEFT).":00'>".str_pad($h, 2, '0', STR_PAD_LEFT).":00</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="IDtratamiento"><i class="fas fa-pills"></i> Tratamiento*</label>
                <select id="IDtratamiento" name="IDtratamiento" required onchange="updateDuration()">
                    <option value="">Seleccione un tratamiento</option>
                    <?php
                        foreach ($tratamientos_data as $row) {
                            echo "<option value='" . htmlspecialchars($row['IDtratamiento']) . "' 
                                  data-duracion='" . htmlspecialchars($row['duracion']) . "'>" . 
                                  htmlspecialchars($row['nombre']) . " (Duración: " . htmlspecialchars($row['duracion']) . " hora" . ($row['duracion'] > 1 ? 's' : '') . ")</option>";
                        }
                    ?>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-hourglass-half"></i> Duración</label>
                    <div class="duration-display" id="duracion-display">Seleccione un tratamiento</div>
                    <input type="hidden" id="duracion" name="duracion">
                </div>
                
                <div class="form-group">
                    <label for="fechaFin"><i class="fas fa-stopwatch"></i> Hora de Fin</label>
                    <input type="text" id="fechaFin" name="fechaFin" readonly>
                </div>
            </div>
            
            <div class="button-group">
                <a href="CtalogoRecepcionista.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Regresar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-calendar-check"></i> Agendar Cita
                </button>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script>
        // Restaurar valores del formulario si existen
        document.addEventListener("DOMContentLoaded", function() {
            if (localStorage.getItem('tempFecha')) {
                document.getElementById('fecha').value = localStorage.getItem('tempFecha');
                document.getElementById('hora').value = localStorage.getItem('tempHora');
                document.getElementById('IDtratamiento').value = localStorage.getItem('tempTratamiento');
                updateDuration();
                
                // Cargar horarios ocupados si hay fecha seleccionada
                if (localStorage.getItem('tempFecha')) {
                    fetch(`pacienteAgendarCita_Personal.php?get_occupied_hours=1&fecha=${localStorage.getItem('tempFecha')}`)
                        .then(response => response.json())
                        .then(occupiedHours => updateTimePicker(occupiedHours));
                }
            }
            
            // Escuchar cambios en la fecha para cargar horarios ocupados
            document.getElementById("fecha").addEventListener("change", function() {
                const fecha = this.value;
                if (!fecha) return;
                
                fetch(`pacienteAgendarCita_Personal.php?get_occupied_hours=1&fecha=${fecha}`)
                    .then(response => response.json())
                    .then(occupiedHours => updateTimePicker(occupiedHours));
            });
        });

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

        function updateDuration() {
            const tratamientoSelect = document.getElementById("IDtratamiento");
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
                    fetch(`pacienteAgendarCita_Personal.php?get_occupied_hours=1&fecha=${fecha}`)
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
    const tratamientoSelect = document.getElementById("IDtratamiento");
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

        function validateForm() {
            // Validación de paciente seleccionado
            if (!$("#IDpaciente").val()) {
                Swal.fire({
                    icon: 'error',
                    title: 'Paciente requerido',
                    text: 'Por favor seleccione un paciente de la lista.',
                    confirmButtonColor: '#3085d6'
                });
                return false;
            }

            // Validación de fecha mínima
            const fechaInput = document.getElementById('fecha');
            const hoy = new Date();
            hoy.setHours(0, 0, 0, 0); // Considerar día completo
            
            const fechaCita = new Date(fechaInput.value);
            const diferenciaDias = Math.floor((fechaCita - hoy) / (1000 * 60 * 60 * 24));
            
            if (diferenciaDias < 1) {
                const minAvailableDate = new Date(hoy);
                minAvailableDate.setDate(hoy.getDate() + 1);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Anticipación requerida',
                    text: `Debes agendar con al menos 1 día completo de anticipación. La primera fecha disponible es ${minAvailableDate.toLocaleDateString()}`,
                    confirmButtonColor: '#3085d6'
                });
                return false;
            }
            
            // Validar que no se pase de las 18:00 horas
            const fechaFinInput = document.getElementById('fechaFin');
            if (fechaFinInput.value.includes("(Fuera de horario)")) {
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
        }
    </script>
</body>
</html>