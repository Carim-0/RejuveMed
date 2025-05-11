
<?php
    session_start();

    include("connection.php");
    include("functions.php");

    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        // Something was posted
        $username = $_POST['username'];
        $password = $_POST['password'];

        if (!empty($username) && !empty($password) && !is_numeric($username)) {
            // Check in Pacientes table
            $query = "SELECT * FROM Pacientes WHERE nombre = '$username' LIMIT 1";
            $result = mysqli_query($con, $query);

            if ($result && mysqli_num_rows($result) > 0) {
                $user_data = mysqli_fetch_assoc($result);

                if ($user_data['password'] === $password) {
                    $_SESSION['user_id'] = $user_data['IDpaciente'];
                    $_SESSION['user_type'] = 'Paciente'; // Store user type
                    echo "<script>alert('Login exitoso como Paciente'); window.location.href='Paciente/catalogoTratamientos.php';</script>";
                    die;
                }
            }

            // Check in Personal table
            $query = "SELECT * FROM Personal WHERE nombre = '$username' LIMIT 1";
            $result = mysqli_query($con, $query);

            if ($result && mysqli_num_rows($result) > 0) {
                $user_data = mysqli_fetch_assoc($result);

                if ($user_data['password'] === $password) {
                    $_SESSION['user_id'] = $user_data['IDpersonal'];
                    $_SESSION['user_type'] = 'Personal'; // Store user type

                    // Redirect based on the "nombre" value
                    if ($user_data['nombre'] === "Doctora") {
                        echo "<script>alert('Login exitoso como Doctora'); window.location.href='Doctora/tablaPersonal.php';</script>";
                    } else {
                        echo "<script>alert('Login exitoso como Personal'); window.location.href='Personal/CtalogoRecepcionista.php';</script>";
                    }
                    die;
                }
            }

            // Show error message for incorrect username or password
            echo "<script>alert('Usuario o contraseña incorrectos');</script>";
        } else {
            // Show error message for invalid input
            echo "<script>alert('Introducir información válida');</script>";
        }
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RejuveMed - Inicio de Sesión</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        }
        
        .login-container {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
        }
        
        .login-header {
            background-color: var(--primary-color);
            padding: 25px;
            text-align: center;
            color: white;
        }
        
        .login-header img {
            width: 80px;
            height: 80px;
            margin-bottom: 15px;
        }
        
        .login-header h1 {
            font-size: 24px;
            font-weight: 600;
        }
        
        .login-content {
            padding: 30px;
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .logo-container img {
            width: 150px;
            height: auto;
        }
        
        .login-title {
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 25px;
            font-size: 20px;
            font-weight: 500;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary-color);
        }
        
        .form-input {
            width: 100%;
            padding: 12px 15px 12px 40px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 111, 165, 0.2);
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
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
            background-color: var(--light-color);
            color: var(--dark-color);
            margin-top: 10px;
        }
        
        .btn-secondary:hover {
            background-color: #e9ecef;
        }
        
        .forgot-password {
            text-align: center;
            margin-top: 20px;
            color: var(--secondary-color);
            font-size: 14px;
        }
        
        .forgot-password a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            .login-container {
                max-width: 100%;
            }
            
            .login-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="IMG/imagenlogin.png" alt="Icono RejuveMed">
            <h1>Iniciar sesión</h1>
        </div>
        
        <div class="login-content">
            <div class="logo-container">
                <img src="IMG/logoRejuvemed.png" alt="Logo RejuveMed">
            </div>
            
            <h2 class="login-title">Ingresa tus datos</h2>
            
            <form method="post">
                <div class="form-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" class="form-input" placeholder="Usuario" required>
                </div>
                
                <div class="form-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" class="form-input" placeholder="Contraseña" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Iniciar sesión</button>
            </form>
            
            <button class="btn btn-secondary" onclick="window.location.href='signup.php'">Regístrate</button>
            
            <div class="forgot-password">
                <a href="#">¿Olvidaste tu contraseña?</a>
            </div>
        </div>
    </div>
</body>
</html>