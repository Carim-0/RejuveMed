<?php
    session_start();

    include("connection.php");

    // Ensure the user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        die("Acceso denegado. Por favor, inicie sesión.");
    }

    $user_id = $_SESSION['user_id']; // Get the current user's ID
    $user_type = $_SESSION['user_type']; // Get the current user's type

    // Determine the return URL based on user type and ID
    $return_url = '';
    if ($user_type === 'Paciente') {
        $return_url = './Paciente/catalogoTratamientos.php';
        $query = "SELECT * FROM Pacientes WHERE IDpaciente = '$user_id' LIMIT 1";
    } elseif ($user_type === 'Personal') {
        if ($user_id == 1) {
            $return_url = './Doctora/tablaTratamientos.php';
        } else {
            $return_url = './Personal/CtalogoRecepcionista.php';
        }
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
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil del Usuario</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="user_style.css">
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
        
        .profile-container {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 600px;
            padding: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .form-title {
            color: var(--primary-color);
            font-size: 24px;
            font-weight: 600;
        }
        
        .user-type-badge {
            background-color: var(--user-type-color);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .profile-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .detail-item {
            margin-bottom: 15px;
        }
        
        .detail-label {
            display: block;
            color: var(--secondary-color);
            font-size: 14px;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .detail-value {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            min-height: 40px;
            font-size: 15px;
            color: var(--dark-color);
        }
        
        .password-container {
            position: relative;
            grid-column: span 2;
        }
        
        .btn-toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--secondary-color);
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-toggle-password:hover {
            color: var(--primary-color);
        }
        
        .btn-edit-password {
            width: 100%;
            padding: 12px;
            background-color: var(--success-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 15px;
        }
        
        .btn-edit-password:hover {
            background-color: #218838;
        }
        
        .btn-salir {
            width: 100%;
            padding: 12px;
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-salir:hover {
            background-color: #3a5a8a; 
        }
        
        .btn-volver {
            width: 100%;
            padding: 12px;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
        }
        
        .btn-volver:hover {
            background-color: #5a6268;
        }
        
        @media (max-width: 768px) {
            .profile-details {
                grid-template-columns: 1fr;
            }
            
            .password-container {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <h2 class="form-title">Mi Perfil</h2>
            <span class="user-type-badge" style="--user-type-color: <?php echo $user_type === 'Paciente' ? '#4a6fa5' : '#ff7e5f'; ?>">
                <?php echo $user_type === 'Paciente' ? 'Paciente' : 'Personal'; ?>
            </span>
        </div>
        
        <div class="profile-details">
            <div class="detail-item">
                <span class="detail-label">ID de Usuario</span>
                <div class="detail-value">
                    <?php echo htmlspecialchars($user_type === 'Paciente' ? $user_data['IDpaciente'] : $user_data['IDpersonal']); ?>
                </div>
            </div>
            
            <div class="detail-item">
                <span class="detail-label">Nombre</span>
                <div class="detail-value">
                    <?php echo htmlspecialchars($user_data['nombre']); ?>
                </div>
            </div>
            
            <div class="detail-item password-container">
                <span class="detail-label">Contraseña</span>
                <div class="detail-value" id="password">
                    ********
                    <button class="btn-toggle-password" onclick="togglePassword()">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="detail-item">
                <span class="detail-label">Teléfono</span>
                <div class="detail-value">
                    <?php echo htmlspecialchars($user_data['telefono']); ?>
                </div>
            </div>
            
            <div class="detail-item">
                <span class="detail-label">Edad</span>
                <div class="detail-value">
                    <?php echo htmlspecialchars($user_data['edad']); ?>
                </div>
            </div>
            
            <div class="detail-item" style="grid-column: span 2">
                <span class="detail-label">Detalles</span>
                <div class="detail-value">
                    <?php echo htmlspecialchars($user_data['detalles']); ?>
                </div>
            </div>
        </div>
        
        <button class="btn-edit-password" onclick="window.location.href='editarPerfil.php'">
            <i class="fas fa-pencil"></i> Editar 
        </button>
        
        <button class="btn-salir" onclick="window.location.href='logout.php'">
            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
        </button>

        <button class="btn-volver" onclick="window.location.href='<?php echo $return_url; ?>'">
            <i class="fas fa-arrow-left"></i> Volver
        </button>
    </div>

    <script>
        let isPasswordVisible = false;

        function togglePassword() {
            const passwordElement = document.getElementById('password');
            const eyeIcon = passwordElement.querySelector('i');
            
            if (isPasswordVisible) {
                passwordElement.firstChild.textContent = '********';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
                isPasswordVisible = false;
            } else {
                passwordElement.firstChild.textContent = '<?php echo htmlspecialchars($user_data['password']); ?>';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
                isPasswordVisible = true;
            }
        }
    </script>
</body>
</html>