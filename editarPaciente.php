<?php
    session_start();

    include("connection.php");

    // Check if the ID is provided in the URL
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $id = $_GET['id'];

        // Fetch the patient's current data
        $query = "SELECT * FROM Pacientes WHERE IDpaciente = $id LIMIT 1";
        $result = mysqli_query($con, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            $patient = mysqli_fetch_assoc($result);
        } else {
            die("Paciente no encontrado.");
        }
    } else {
        die("ID de paciente no válido.");
    }

    // Handle form submission to update the patient's data
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $nombre = $_POST['nombre'];
        $edad = $_POST['edad'];
        $telefono = $_POST['telefono'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (!empty($nombre) && is_numeric($edad) && is_numeric($telefono) && $edad > 0 && $telefono > 0) {
            // Check if the password fields are filled and match
            if (!empty($new_password) && $new_password === $confirm_password) {
                // Update the patient's data including the password
                $query = "UPDATE Pacientes SET nombre = '$nombre', edad = $edad, telefono = '$telefono', password = '$new_password' WHERE IDpaciente = $id";
            } else if (empty($new_password)) {
                // Update the patient's data without changing the password
                $query = "UPDATE Pacientes SET nombre = '$nombre', edad = $edad, telefono = '$telefono' WHERE IDpaciente = $id";
            } else {
                echo "Las contraseñas no coinciden.";
                die;
            }

            mysqli_query($con, $query);

            echo "Paciente actualizado exitosamente.";
            header("Location: tablaPacientes.php");
            die;
        } else {
            echo "Por favor, introduzca información válida.";
        }
    }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Editar Paciente</title>
</head>
<body>
    <style type="text/css">
        #text {
            height: 25px;
            border-radius: 5px;
            padding: 4px;
            border: solid thin #aaa;
            width: 100%;
        }

        #button {
            padding: 10px;
            width: 100px;
            color: white;
            background-color: lightblue;
            border: none;
        }

        #box {
            background-color: grey;
            margin: auto;
            width: 300px;
            padding: 20px;
        }
    </style>

    <div id="box">
        <form method="post">
            <div style="font-size: 20px; margin: 10px; color: white;">Editar Paciente</div>

            <input id="text" type="text" name="nombre" value="<?php echo $patient['nombre']; ?>" placeholder="Nombre"><br><br>
            <input id="text" type="number" name="edad" value="<?php echo $patient['edad']; ?>" placeholder="Edad"><br><br>
            <input id="text" type="text" name="telefono" value="<?php echo $patient['telefono']; ?>" placeholder="Teléfono"><br><br>

            <input id="text" type="password" name="new_password" placeholder="Nueva Contraseña (opcional)"><br><br>
            <input id="text" type="password" name="confirm_password" placeholder="Confirmar Nueva Contraseña"><br><br>

            <input id="button" type="submit" value="Actualizar"><br><br>
            <a href="tablaPacientes.php">Volver</a><br><br>
        </form>
    </div>
</body>
</html>