<?php
    session_start();

    include("connection.php");

    // Ensure the user is logged in
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Paciente') {
        die("Acceso denegado. Por favor, inicie sesión como paciente.");
    }

    $IDpaciente = $_SESSION['user_id']; // Get the current user's ID

    // Fetch the user's data from the database
    $query = "SELECT * FROM Pacientes WHERE IDpaciente = '$IDpaciente' LIMIT 1";
    $result = mysqli_query($con, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user_data = mysqli_fetch_assoc($result);
    } else {
        die("Error al obtener los datos del usuario.");
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil del Paciente</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="user_style.css">
    <style>
        .btn-toggle-password {
            margin-top: 10px;
            padding: 5px 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-toggle-password:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="contenedor-login">
        <h2 class="form-title">Perfil del Paciente</h2>
        <div class="data">
            <p>ID: <span id="IDpaciente"><?php echo htmlspecialchars($user_data['IDpaciente']); ?></span></p>
            <p>Nombre: <span id="nombre"><?php echo htmlspecialchars($user_data['nombre']); ?></span></p>
            <p>Contraseña: <span id="password">********</span></p>
            <button class="btn-toggle-password" onclick="togglePassword()">Mostrar Contraseña</button>
            <p>Edad: <span id="edad"><?php echo htmlspecialchars($user_data['edad']); ?></span></p>
            <p>Teléfono: <span id="telefono"><?php echo htmlspecialchars($user_data['telefono']); ?></span></p>
            <p>Detalles: <span id="detalles"><?php echo htmlspecialchars($user_data['detalles']); ?></span></p>                      
        </div>
        <button class="btn-salir" onclick="window.location.href='logout.php'">
            <i class="fas fa-sign-out-alt"></i> Salir sesión
        </button>
    </div>

    <script>
        let isPasswordVisible = false;

        function togglePassword() {
            const passwordElement = document.getElementById('password');
            if (isPasswordVisible) {
                passwordElement.textContent = '********';
                isPasswordVisible = false;
            } else {
                passwordElement.textContent = '<?php echo htmlspecialchars($user_data['password']); ?>';
                isPasswordVisible = true;
            }
        }
    </script>
</body>
</html>