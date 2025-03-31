<?php
    session_start();

    include("../connection.php");

    // Ensure the user is logged in
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Paciente') {
        die("Acceso denegado. Por favor, inicie sesión como paciente.");
    }

    $IDpaciente = $_SESSION['user_id']; // Get the current user's ID

    // Fetch available treatments
    $query = "SELECT IDtratamiento, nombre FROM Tratamientos";
    $result = mysqli_query($con, $query);

    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $fecha = $_POST['fecha'];
        $hora = $_POST['hora'];
        $IDtratamiento = $_POST['IDtratamiento'];

        if (!empty($fecha) && !empty($hora) && !empty($IDtratamiento)) {
            // Combine date and time into a single datetime value
            $datetime = $fecha . ' ' . $hora;

            // Insert the new appointment into the Citas table
            $query = "INSERT INTO Citas (IDpaciente, IDtratamiento, fecha) VALUES ('$IDpaciente', '$IDtratamiento', '$datetime')";
            $result = mysqli_query($con, $query);

            if ($result) {
                echo "<script>alert('Cita agendada exitosamente.'); window.location.href='verCitas_Paciente.php';</script>";
            } else {
                echo "<script>alert('Error al agendar la cita.');</script>";
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
            padding: 20px;
            border-radius: 8px;
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
            margin-bottom: 15px;
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
    </style>
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
            <select id="IDtratamiento" name="IDtratamiento" required>
                <option value="">Seleccione un tratamiento</option>
                <?php
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<option value='" . htmlspecialchars($row['IDtratamiento']) . "'>" . htmlspecialchars($row['nombre']) . "</option>";
                    }
                ?>
            </select>

            <button type="submit">Agendar Cita</button>
            <button type="button" onclick="window.location.href='catalogoTratamientos.php'">Regresar</button>
        </form>
    </div>
</body>
</html>