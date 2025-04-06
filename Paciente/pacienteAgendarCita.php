<?php
    session_start();

    include("../connection.php");

    // Ensure the user is logged in
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Paciente') {
        die("Acceso denegado. Por favor, inicie sesión como paciente.");
    }

    $IDpaciente = $_SESSION['user_id']; // Get the current user's ID

    // Fetch available treatments
    $query = "SELECT IDtratamiento, nombre, duracion FROM Tratamientos";
    $result = mysqli_query($con, $query);

    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $fecha = $_POST['fecha'];
        $hora = $_POST['hora'];
        $IDtratamiento = $_POST['IDtratamiento'];
        $duracion = (int)$_POST['duracion']; // Ensure duracion is cast to an integer

        // Get the current date
        $currentDate = date('Y-m-d');

        // Validate that the selected date is not in the past and not the same as today
        if ($fecha <= $currentDate) {
            echo "<script>alert('La fecha debe ser un día futuro. No puede ser hoy ni una fecha pasada.');</script>";
            echo "<script>window.location.href = 'pacienteAgendarCita.php';</script>";
            exit;
        }

        // Validate that the hour is within the allowed range
        if ($hora < "10:00:00" || $hora > "18:00:00") {
            echo "<script>alert('La hora debe estar entre las 10:00 AM y las 6:00 PM.');</script>";
            echo "<script>window.location.href = 'pacienteAgendarCita.php';</script>";
            exit;
        }

        if (!empty($fecha) && !empty($hora) && !empty($IDtratamiento) && !empty($duracion)) {
            // Combine date and time into a single datetime value
            $datetime = $fecha . ' ' . $hora;

            // Calculate fechaFin by adding the duration to the start time
            $startDateTime = new DateTime($datetime);
            $startDateTime->modify("+$duracion hours"); // Add the duration as hours
            $fechaFin = $startDateTime->format('Y-m-d H:i:s');

            // Check for overlapping appointments
            $query = "SELECT * FROM Citas WHERE 
                      (fecha <= '$fechaFin' AND fechaFin >= '$datetime')";
            $result = mysqli_query($con, $query);

            if (mysqli_num_rows($result) > 0) {
                echo "<script>alert('Ya existe una cita en ese horario. Por favor, elija otro horario.');</script>";
                echo "<script>window.location.href = 'pacienteAgendarCita.php';</script>";
                exit;
            } else {
                // Insert the new appointment into the Citas table
                $query = "INSERT INTO Citas (IDpaciente, IDtratamiento, fecha, fechaFin) VALUES ('$IDpaciente', '$IDtratamiento', '$datetime', '$fechaFin')";
                $result = mysqli_query($con, $query);

                if ($result) {
                    echo "<script>alert('Cita agendada exitosamente.'); window.location.href='verCitas_Paciente.php';</script>";
                } else {
                    echo "<script>alert('Error al agendar la cita.');</script>";
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
            box-shadow: var(0 4px 6px rgba(0, 0, 0, 0.1);)
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

        @media (max-width: 576px) {
            .form-content {
                padding: 20px;
            }
            
            .header-registro {
                padding: 15px;
            }
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            initializeEventListeners();
        });

        function initializeEventListeners() {
            const tratamientoSelect = document.getElementById("IDtratamiento");
            const horaInput = document.getElementById("hora");
            const fechaInput = document.getElementById("fecha");

            // Attach the onchange event listener to the dropdown
            tratamientoSelect.addEventListener("change", updateDuration);

            // Attach the input event listener to the hora field to recalculate fechaFin
            horaInput.addEventListener("input", updateDuration);

            // Attach the onchange event listener to the fecha field
            fechaInput.addEventListener("change", updateDuration);
        }

        function updateDuration() {
            const tratamientoSelect = document.getElementById("IDtratamiento");
            const duracionInput = document.getElementById("duracion");
            const fechaFinInput = document.getElementById("fechaFin");

            // Get the selected option
            const selectedOption = tratamientoSelect.options[tratamientoSelect.selectedIndex];

            // Get the "data-duracion" attribute from the selected option
            const duracion = selectedOption.getAttribute("data-duracion");

            // Update the "duracion" input field
            duracionInput.value = duracion ? `${duracion} horas` : "";

            // Calculate and update the "fechaFin" input field
            const horaInicio = document.getElementById("hora").value;
            if (duracion && horaInicio) {
                const [hours, minutes] = horaInicio.split(":").map(Number);
                const duracionHoras = parseInt(duracion, 10);
                const fechaFin = new Date();
                fechaFin.setHours(hours + duracionHoras, minutes);
                fechaFinInput.value = `${fechaFin.getHours().toString().padStart(2, '0')}:${fechaFin.getMinutes().toString().padStart(2, '0')}`;
            } else {
                fechaFinInput.value = "";
            }
        }
    </script>
</head>
<body>
    <div class="form-container">
        <h2>Agendar Cita</h2>
        <form method="POST">
            <label for="fecha">Fecha:</label>
            <input type="date" id="fecha" name="fecha" required>

            <label for="hora">Hora:</label>
            <input type="time" id="hora" name="hora" required>

            <label for="IDtratamiento">Tratamiento:</label>
            <select id="IDtratamiento" name="IDtratamiento" required onchange="updateDuration()">
                <option value="">Seleccione un tratamiento</option>
                <?php
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<option value='" . htmlspecialchars($row['IDtratamiento']) . "' data-duracion='" . htmlspecialchars($row['duracion']) . "'>" . htmlspecialchars($row['nombre']) . "</option>";
                    }
                ?>
            </select>

            <label for="duracion">Duración (horas):</label>
            <input type="text" id="duracion" name="duracion" readonly>

            <label for="fechaFin">Hora de Fin:</label>
            <input type="text" id="fechaFin" name="fechaFin" readonly>

            <button type="submit">Agendar Cita</button>
            <a href="catalogoTratamientos.php" class="btn-link">
                <i class="fas fa-arrow-left"></i> Regresar
            </a>
        </form>
    </div>
</body>
</html>