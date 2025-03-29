<?php
    session_start();

    include("../connection.php");

    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        // Get form data
        $nombre = $_POST['nombre'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $telefono = $_POST['telefono'];
        $edad = $_POST['edad'];
        $detalles = isset($_POST['detalles']) ? $_POST['detalles'] : '';

        // Validate form data
        if (!empty($nombre) && !empty($password) && !empty($confirm_password) && !empty($telefono) && !empty($edad) && $password === $confirm_password) {
            // Insert into the Personal table
            $query = "INSERT INTO Personal (nombre, password, telefono, edad, detalles) VALUES ('$nombre', '$password', '$telefono', '$edad', '$detalles')";
            $result = mysqli_query($con, $query);

            if ($result) {
                echo "<script>alert('Personal registrado exitosamente.'); window.location.href='tablaPersonal.php';</script>";
            } else {
                echo "<script>alert('Error al registrar el personal.');</script>";
            }
        } else {
            echo "<script>alert('Por favor, complete todos los campos correctamente y asegúrese de que las contraseñas coincidan.');</script>";
        }
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Nuevo Personal</title>
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
        .form-container textarea,
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
        <h2>Registrar Nuevo Personal</h2>
        <form method="POST">
            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre" required>

            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>

            <label for="confirm_password">Confirmar Contraseña:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <label for="telefono">Teléfono:</label>
            <input type="text" id="telefono" name="telefono" required>

            <label for="edad">Edad:</label>
            <input type="number" id="edad" name="edad" required>

            <label for="detalles">Detalles (opcional):</label>
            <textarea id="detalles" name="detalles" rows="3"></textarea>

            <button type="submit">Registrar</button>
        </form>
    </div>
</body>
</html>