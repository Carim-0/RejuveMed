<?php
    session_start();

    include("connection.php");

    // Ensure the user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        die("Acceso denegado. Por favor, inicie sesión.");
    }

    $user_id = $_SESSION['user_id']; // Get the current user's ID
    $user_type = $_SESSION['user_type']; // Get the current user's type

    // Fetch the user's data based on their type
    if ($user_type === 'Paciente') {
        $query = "SELECT * FROM Pacientes WHERE IDpaciente = '$user_id' LIMIT 1";
    } elseif ($user_type === 'Personal') {
        $query = "SELECT * FROM Personal WHERE IDpersonal = '$user_id' LIMIT 1";
    } else {
        die("Tipo de usuario no válido.");
    }

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
    <title>Perfil del Usuario</title>
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
        <h2 class="form-title">Perfil del Usuario</h2>
        <div class="data">
            <p>ID: <span id="user_id"><?php echo htmlspecialchars($user_type === 'Paciente' ? $user_data['IDpaciente'] : $user_data['IDpersonal']); ?></span></p>
            <p>Nombre: <span id="nombre"><?php echo htmlspecialchars($user_data['nombre']); ?></span></p>
            <p>Contraseña: <span id="password">********</span></p>
            <button class="btn-toggle-password" onclick="togglePassword()">Mostrar Contraseña</button>
            <p>Teléfono: <span id="telefono"><?php echo htmlspecialchars($user_data['telefono']); ?></span></p>
            <p>Edad: <span id="edad"><?php echo htmlspecialchars($user_data['edad']); ?></span></p>
            <p>Detalles: <span id="detalles"><?php echo htmlspecialchars($user_data['detalles']); ?></span></p>
            <p>Tipo de cuenta: <span id="tipo_cuenta"><?php echo $user_type === 'Paciente' ? 'Paciente' : 'Personal'; ?></span></p>
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