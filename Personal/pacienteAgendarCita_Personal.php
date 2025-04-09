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

    // Fetch available treatments
    $query = "SELECT IDtratamiento, nombre FROM Tratamientos";
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



    
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['agendar_cita'])) {
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $IDtratamiento = $_POST['IDtratamiento'];
    $paciente_id = $_POST['paciente_id'];
    $estado = 'Pendiente';

    // Fetch the treatment duration
    $tratamiento_query = "SELECT duracion FROM Tratamientos WHERE IDtratamiento = ?";
    $stmt = $con->prepare($tratamiento_query);
    $stmt->bind_param("i", $IDtratamiento);
    $stmt->execute();
    $tratamiento_result = $stmt->get_result();
    $tratamiento = $tratamiento_result->fetch_assoc();
    $stmt->close();

    if (!$tratamiento) {
        $error_message = "El tratamiento seleccionado no es válido.";
    } else {
        $duracion = (int)$tratamiento['duracion'];




         // Combine date and time into a single datetime value
        $datetime = $fecha . ' ' . $hora;

        // Get the current date
        $currentDate = date('Y-m-d');

        // Validate that the selected date is not in the past and not the same as today
        if ($fecha <= $currentDate) {
            echo "<script>alert('La fecha tiene que ser después de mañana como mínimo.');</script>";
            echo "<script>window.location.href = 'pacienteAgendarCita_Personal.php';</script>";
            exit;
            }

            // Calculate fechaFin by adding the duration to the start time
            $startDateTime = new DateTime($datetime);
            $startDateTime->modify("+$duracion hours");
            $fechaFin = $startDateTime->format('Y-m-d H:i:s');

            // Validate that the hour is within the allowed range
            if ($hora < "10:00:00" || $hora > "18:00:00" || $startDateTime->format('H:i') > "18:00") {
              echo "<script>alert('La hora debe estar entre las 10:00 AM y las 6:00 PM.');</script>";
            echo "<script>window.location.href = 'pacienteAgendarCita_Personal.php';</script>";
            exit;
            } 

            
            // Check for overlapping appointments
            $query = "SELECT * FROM Citas WHERE 
                      (fecha <= ? AND fechaFin >= ?)";
            $stmt = $con->prepare($query);
            $stmt->bind_param("ss", $fechaFin, $datetime); // Removed $paciente_id
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();

            if ($result->num_rows > 0) {
              showSweetAlert('warning', 'Horario ocupado', 'Ya existe una cita en ese horario. Por favor, elija otro.', 'pacienteAgendarCita_Personal.php');
            } else {
                // Insert the new appointment into the Citas table
                $query = "INSERT INTO Citas (fecha, fechaFin, IDpaciente, IDtratamiento, estado) VALUES (?, ?, ?, ?, ?)";
                $stmt = $con->prepare($query);
                $estado = 'Pendiente'; // Set the default value for estado
                $stmt->bind_param("sssis", $datetime, $fechaFin, $paciente_id, $IDtratamiento, $estado);

                if ($stmt->execute()) {
                    showSweetAlert('success', '¡Éxito!', 'Cita agendada correctamente', 'pacienteAgendarCita_Personal.php');
                } else {
                    showSweetAlert('error', 'Error', 'Ocurrió un error al agendar la cita');
                }
            }
        } else {
            echo "<script>alert('Por favor, complete todos los campos.');</script>";
        }
    }


?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Cita</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .form-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 400px;
        }

        .form-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .form-container label {
            display: block;
            margin: 10px 0 5px;
        }

        .form-container input,
        .form-container select,
        .form-container button {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .form-container button {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }

        .form-container button:hover {
            background-color: #0056b3;
        }
        
        .textarea-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--color-borde);
            border-radius: 6px;
            font-size: 16px;
            min-height: 100px;
            resize: vertical;
            transition: border-color 0.3s;
        }

        .btn {
            display: inline-block;
            padding: 12px 20px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            border: none;
        }

        .btn-primary {
            background-color: var(--color-primario);
            color: white;
            width: 100%;
        }

        .btn-primary:hover {
            background-color: #3a7ae8;
        }
        
        .btn-link {
            color: var(--color-primario);
            text-decoration: none;
            display: inline-block;
            margin-top: 15px;
            font-size: 14px;
        }

        .btn-link:hover {
            text-decoration: underline;
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            color: var(--color-borde);
        }

        /* Autocomplete styles */
        .ui-autocomplete {
            max-height: 200px;
            overflow-y: auto;
            overflow-x: hidden;
            background-color: #fff;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .ui-menu-item {
            padding: 8px 12px;
            cursor: pointer;
        }
        
        .ui-menu-item:hover {
            background-color: #007bff;
            color: white;
        }
        
        .ui-helper-hidden-accessible {
            display: none;
        }

        .overlay{
            position: fixed;
            top:0;
            left:0;
            width:100%;
            height:100%;
            display:flex;
            justify-content:center;
            align-items:center;
            visibility:hidden;
            opacity:0;
            transition: opacity 0.3 ease;
        }

        .overlay.active{
            visibility: visible;
            opacity:1;
        }

        .popup{
            background-color: rgba(94, 110, 141, 0.9);
            padding: 30px;
            border-radius: 8px;
            text-align: center;
        }




        @media (max-width: 576px) {
            .form-content {
                padding: 20px;
            }
            
            .header-registro {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Agendar Cita</h2>
        <form method="POST">
            <label for="paciente">Paciente:</label>
            <input type="text" id="paciente" name="paciente" placeholder="Buscar paciente..." autocomplete="off">
            <input type="hidden" id="IDpaciente" name="IDpaciente">

            <label for="fecha">Fecha:</label>
            <input type="date" id="fecha" name="fecha" required min="<?php echo date('Y-m-d', strtotime('+1 days')); ?>">

            <label for="hora">Hora:</label>
            <input type="time" id="hora" name="hora" required min="10:00" max="18:00"  step="3600">

            <label for="IDtratamiento">Tratamiento:</label>
            <select id="IDtratamiento" name="IDtratamiento" required>
                <option value="">Seleccione un tratamiento</option>
                <?php
                    mysqli_data_seek($result, 0); // Reset pointer to beginning
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<option value='" . htmlspecialchars($row['IDtratamiento']) . "'>" . htmlspecialchars($row['nombre']) . "</option>";
                    }
                ?>
            </select>
            
            <button type="submit" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#detalleModal">
                    <i class="fas fa-calendar-check"></i> Agendar Cita
                </button>
     
                <a href="CtalogoRecepcionista.php" class="btn-link">
                <i class="fas fa-arrow-left"></i> Regresar
            </a>
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
    </script>
</body>
</html>