<?php
    session_start();

    include("../connection.php");

    // Ensure the user is logged in
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Personal') {
        die("Acceso denegado. Por favor, inicie sesión como personal.");
    }

    $IDpersonal = $_SESSION['user_id']; // Get the current user's ID

    function showSweetAlert($icon, $title, $text, $redirect = null) {
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
        }
        echo ";});
        </script>";
    }

    // Fetch available treatments with duration
    $query = "SELECT IDtratamiento, nombre, duracion FROM Tratamientos";
    $result = mysqli_query($con, $query);

    // Fetch patients for autocomplete
    $pacientes_query = "SELECT IDpaciente, nombre FROM Pacientes";
    $pacientes_result = mysqli_query($con, $pacientes_query);
    $pacientes = [];
    while ($row = mysqli_fetch_assoc($pacientes_result)) {
        $pacientes[] = [
            'id' => $row['IDpaciente'],
            'nombre_completo' => $row['nombre']
        ];
    }

    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $fecha = $_POST['fecha'];
        $hora = $_POST['hora'];
        $IDtratamiento = $_POST['IDtratamiento'];
        $IDpaciente = $_POST['IDpaciente'];
        $duracion = (int)$_POST['duracion'];

        if (!empty($fecha) && !empty($hora) && !empty($IDtratamiento) && !empty($IDpaciente)) {
            // Combine date and time
            $datetime = $fecha . ' ' . $hora . ':00';
            
            // Validate time range
            $horaMin = '10:00:00';
            $horaMax = '18:00:00';
            $horaCompleta = $hora . ':00';
            
            if ($horaCompleta < $horaMin || $horaCompleta > $horaMax) {
                showSweetAlert('error', 'Error', 'La hora debe estar entre las 10:00 AM y 6:00 PM.', 'pacienteAgendarCita_Personal.php');
                exit;
            }
            
            try {
                // Calculate end time
                $startDateTime = new DateTime($datetime);
                $endDateTime = clone $startDateTime;
                $endDateTime->modify("+$duracion hours");
                
                $fechaFin = $endDateTime->format('Y-m-d H:i:s');
                $datetime = $startDateTime->format('Y-m-d H:i:s');

                // Check for overlapping appointments
                $query = "SELECT * FROM Citas WHERE 
                          (fecha < '$fechaFin' AND fechaFin > '$datetime')";
                $result = mysqli_query($con, $query);

                if (mysqli_num_rows($result) > 0) {
                    showSweetAlert('warning', 'Horario ocupado', 'Ya existe una cita en ese horario.', 'pacienteAgendarCita_Personal.php');
                } else {
                    // Insert appointment
                    $query = "INSERT INTO Citas (IDpaciente, IDtratamiento, fecha, fechaFin) 
                              VALUES ('$IDpaciente', '$IDtratamiento', '$datetime', '$fechaFin')";
                    $result = mysqli_query($con, $query);

                    if ($result) {
                        showSweetAlert('success', '¡Éxito!', 'Cita agendada correctamente', 'verCitasPacientes_Personal.php');
                    } else {
                        showSweetAlert('error', 'Error', 'Error al agendar: ' . mysqli_error($con));
                    }
                }
            } catch (Exception $e) {
                showSweetAlert('error', 'Error', 'Formato de fecha/hora inválido.');
            }
        } else {
            echo "<script>alert('Complete todos los campos.');</script>";
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
        
        <form method="POST" class="appointment-form">
            <div class="form-group">
                <label for="paciente"><i class="fas fa-user"></i> Paciente*</label>
                <input type="text" id="paciente" name="paciente" placeholder="Buscar paciente..." autocomplete="off">
                <input type="hidden" id="IDpaciente" name="IDpaciente">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="fecha"><i class="far fa-calendar-alt"></i> Fecha*</label>
                    <input type="date" id="fecha" name="fecha" required min="<?php echo date('Y-m-d', strtotime('+1 days')); ?>">
                </div>
                
                <div class="form-group">
                    <label for="hora"><i class="far fa-clock"></i> Hora*</label>
                    <input type="time" id="hora" name="hora" required min="10:00" max="18:00" step="3600">
                </div>
            </div>
            
            <div class="form-group">
                <label for="IDtratamiento"><i class="fas fa-pills"></i> Tratamiento*</label>
                <select id="IDtratamiento" name="IDtratamiento" required onchange="updateDuration()">
                    <option value="">Seleccione un tratamiento</option>
                    <?php
                        mysqli_data_seek($result, 0); // Reset pointer to beginning
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<option value='" . htmlspecialchars($row['IDtratamiento']) . "' 
                                  data-duracion='" . htmlspecialchars($row['duracion']) . "'>" . 
                                  htmlspecialchars($row['nombre']) . "</option>";
                        }
                    ?>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="duracion"><i class="fas fa-hourglass-half"></i> Duración</label>
                    <input type="text" id="duracion" name="duracion" readonly>
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
                <button type="submit" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#detalleModal">
                    <i class="fas fa-calendar-check"></i> Agendar Cita
                </button>
            </div>
        </form>
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

        function updateDuration() {
            const tratamientoSelect = document.getElementById("IDtratamiento");
            const duracionInput = document.getElementById("duracion");
            const fechaFinInput = document.getElementById("fechaFin");

            const selectedOption = tratamientoSelect.options[tratamientoSelect.selectedIndex];
            const duracion = selectedOption.getAttribute("data-duracion");

            duracionInput.value = duracion ? `${duracion} horas` : "";

            const horaInicio = document.getElementById("hora").value;
            const fechaInicio = document.getElementById("fecha").value;

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

        document.addEventListener("DOMContentLoaded", function () {
            const tratamientoSelect = document.getElementById("IDtratamiento");
            const horaInput = document.getElementById("hora");
            const fechaInput = document.getElementById("fecha");

            tratamientoSelect.addEventListener("change", updateDuration);
            horaInput.addEventListener("input", updateDuration);
            fechaInput.addEventListener("change", updateDuration);
        });

        function validateForm() {
            // Validación de fecha en el cliente
            const fechaInput = document.getElementById('fecha');
            const minDate = new Date();
            minDate.setDate(minDate.getDate() + 1); // Mínimo mañana
            
            if (new Date(fechaInput.value) < minDate) {
                Swal.fire({
                    icon: 'error',
                    title: 'Fecha inválida',
                    text: 'La fecha debe ser al menos pasado mañana.'
                });
                return false;
            }

            // Validación de paciente seleccionado
            if (!$("#IDpaciente").val()) {
                Swal.fire({
                    icon: 'error',
                    title: 'Paciente requerido',
                    text: 'Por favor seleccione un paciente de la lista.'
                });
                return false;
            }
            
            return true;
        }
    </script>
</body>
</html>