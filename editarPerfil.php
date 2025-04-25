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

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
        $new_password = $_POST['password'];
        $new_telefono = $_POST['telefono'];
        $new_detalles = $_POST['detalles'];

        // Validate inputs
        if (empty($new_password) || empty($new_telefono)) {
            echo "<script>alert('Todos los campos son obligatorios.');</script>";
        } else {
            // Determine the table to update based on user type
            if ($user_type === 'Paciente') {
                $query_update = "UPDATE Pacientes SET password = ?, telefono = ?, detalles = ? WHERE IDpaciente = ?";
            } elseif ($user_type === 'Personal') {
                $query_update = "UPDATE Personal SET password = ?, telefono = ?, detalles = ? WHERE IDpersonal = ?";
            }

            // Execute the update query
            $stmt = $con->prepare($query_update);
            $stmt->bind_param("sssi", $new_password, $new_telefono, $new_detalles, $user_id);

            if ($stmt->execute()) {
                echo "<script>alert('Perfil actualizado exitosamente.'); window.location.href='verPerfil.php';</script>";
            } else {
                echo "<script>alert('Error al actualizar el perfil.');</script>";
            }
            $stmt->close();
        }
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
        }

        .left-column {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .right-column {
            display: flex;
            flex-direction: column;
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
            width: 100%;
            padding: 10px;
            font-size: 15px;
            border: 1px solid var(--color-borde);
            border-radius: var(--border-radius);
            background-color: var(--light-color);
        }

        textarea.detail-value {
            resize: none;
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
            <h2 class="form-title">Editar Perfil</h2>
            <span class="user-type-badge" style="--user-type-color: <?php echo $user_type === 'Paciente' ? '#4a6fa5' : '#ff7e5f'; ?>">
                <?php echo $user_type === 'Paciente' ? 'Paciente' : 'Personal'; ?>
            </span>
        </div>

        <div class="detail-item">
                    <span class="detail-label">Nombre</span>
                    <div class="detail-value">
                        <?php echo htmlspecialchars($user_data['nombre']); ?>
                    </div>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Edad</span>
                    <div class="detail-value">
                        <?php echo htmlspecialchars($user_data['edad']); ?>
                    </div>
                </div>

        <form method="POST" action="">
            <div class="profile-details">
                <div class="left-column">
                    <div class="detail-item password-container">
                        <span class="detail-label">Contraseña</span>
                        <input type="password" class="detail-value" id="password" name="password" 
                               value="<?php echo htmlspecialchars($user_data['password']); ?>" 
                               pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$" 
                               title="La contraseña debe de contener por lo menos 8 caracteres, 1 número y 1 letra" 
                               required>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Teléfono</span>
                        <input type="text" class="detail-value" id="telefono" name="telefono" 
                               value="<?php echo htmlspecialchars($user_data['telefono']); ?>" 
                               pattern="^\d{10}$" 
                               title="El teléfono debe contener exactamente 10 dígitos" 
                               required>
                    </div>
                </div>

                <div class="right-column">
                    <div class="detail-item">
                        <span class="detail-label">Detalles</span>
                        <textarea class="detail-value" id="detalles" name="detalles" rows="8" 
                                  placeholder="Ingrese detalles adicionales"><?php echo htmlspecialchars($user_data['detalles']); ?></textarea>
                    </div>
                </div>
            </div>
            
            <button type="submit" name="update_profile" class="btn-edit-password">
                <i class="fas fa-key"></i> Confirmar cambios
            </button>
        </form>
        
        <button class="btn-salir" onclick="window.location.href='verPerfil.php'">
            <i class="fas fa-sign-out-alt"></i> Cancelar
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
    <script>
        const passwordInput = document.getElementById('password');

        passwordInput.addEventListener('input', function () {
            const pattern = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/;
            if (!pattern.test(passwordInput.value)) {
                passwordInput.setCustomValidity("La contraseña debe de contener por lo menos 8 caracteres, 1 número y 1 letra");
            } else {
                passwordInput.setCustomValidity("");
            }
        });
    </script>
    <script>
        const phoneInput = document.getElementById('telefono');

        phoneInput.addEventListener('input', function () {
            const pattern = /^\d{10}$/;
            if (!pattern.test(phoneInput.value)) {
                phoneInput.setCustomValidity("El teléfono debe contener exactamente 10 dígitos");
            } else {
                phoneInput.setCustomValidity("");
            }
        });
    </script>
</body>
</html>