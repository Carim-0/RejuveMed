<?php
    session_start();

    include("../connection.php");

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
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Paciente</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --color-primario: #1a6fb5;  /*Azul para paciente*/
            --color-secundario: #f8f9fa;
            --color-terciario: #e9ecef;
            --color-exito: #28a745;
            --color-error: #dc3545;
            --color-texto: #212529;
            --color-borde: #ced4da;
            --color-fondo: #ffffff;
            --sombra: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--color-terciario);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .contenedor-edicion {
            background-color: var(--color-fondo);
            border-radius: 10px;
            box-shadow: var(--sombra);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
        }

        .header-edicion {
            background-color: var(--color-primario);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .titulo-edicion {
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }

        .form-content {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--color-texto);
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--color-borde);
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            border-color: var(--color-primario);
            outline: none;
            box-shadow: 0 0 0 3px rgba(26, 111, 181, 0.1); 
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
            background-color: #0d4b7a; 
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

        .password-note {
            font-size: 13px;
            color: #6c757d;
            margin-top: 5px;
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
            
            .header-edicion {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="contenedor-edicion">
        <div class="header-edicion">
            <h1 class="titulo-edicion">
                <i class="fas fa-user-edit"></i> Editar Paciente
            </h1>
        </div>
        
        <div class="form-content">
            <form method="post">
                <div class="form-group">
                    <label for="nombre">Nombre completo</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" 
                           value="<?php echo htmlspecialchars($patient['nombre']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="edad">Edad</label>
                    <input type="number" class="form-control" id="edad" name="edad" 
                           value="<?php echo htmlspecialchars($patient['edad']); ?>" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="telefono">Teléfono</label>
                    <div class="input-icon">
                        <input type="text" class="form-control" id="telefono" name="telefono" 
                               value="<?php echo htmlspecialchars($patient['telefono']); ?>" required>
                        <i class="fas fa-phone"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="new_password">Nueva contraseña (opcional)</label>
                    <div class="input-icon">
                        <input type="password" class="form-control" id="new_password" name="new_password" 
                               placeholder="Actualizar Contraseña">
                        <i class="fas fa-lock"></i>
                    </div>
                    <p class="password-note">Mínimo 8 caracteres, incluir números y letras</p>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmar nueva contraseña</label>
                    <div class="input-icon">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               placeholder="Repite la nueva contraseña">
                        <i class="fas fa-lock"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Actualizar Paciente
                </button>
                
                <a href="tablaPacientes.php" class="btn-link">
                    <i class="fas fa-arrow-left"></i> Volver a la lista de pacientes
                </a>
            </form>
        </div>
    </div>
</body>
</html>