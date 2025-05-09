<?php
    session_start();

    include("../connection.php");

    // Check if IDpaciente is provided
    if (isset($_GET['id'])) {
        $id_paciente = $_GET['id'];
    } else {
        echo "ID del paciente no proporcionado.";
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        // Get form data
        $detalles = isset($_POST['detalles']) ? $_POST['detalles'] : '';

        // Validate form data
        if (!empty($detalles)) {
            // Insert into the Historial Medico table
            $query = "INSERT INTO `Historial Medico` (IDpaciente, detalles) VALUES (?, ?)";
            $stmt = $con->prepare($query);
            $stmt->bind_param("is", $id_paciente, $detalles);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                echo "<script>alert('Historial médico registrado exitosamente.'); window.location.href='tablaPacientes.php';</script>";
            } else {
                echo "<script>alert('Error al registrar el historial médico.');</script>";
            }
            $stmt->close();
        } else {
            echo "<script>alert('Por favor, complete el campo de detalles.');</script>";
        }
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Historial Médico</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --color-primario: #1a6fb5;
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

        .contenedor-registro {
            background-color: var(--color-fondo);
            border-radius: 10px;
            box-shadow: var(--sombra);
            width: 100%;
            max-width: 500px;
            overflow: hidden; 
        }

        .header-registro {
            background-color: var(--color-primario);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .titulo-registro {
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
            box-shadow: 0 0 0 3px rgba(26, 55, 181, 0.1);
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
            background-color: #142a8a;
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
    </style>
</head>
<body>
    <div class="contenedor-registro">
        <div class="header-registro">
            <h1 class="titulo-registro">
                <i class="fas fa-notes-medical"></i> Registrar Historial Médico
            </h1>
        </div>
        
        <div class="form-content">
            <form method="post">
            <div class="form-group">
                <label for="id_paciente">ID del Paciente</label>
                <input type="text" class="form-control" id="id_paciente" name="id_paciente" value="<?= htmlspecialchars($id_paciente) ?>" readonly>
            </div>
                
                <div class="form-group">
                    <label for="detalles">Detalles del Historial Médico</label>
                    <textarea class="textarea-control" id="detalles" name="detalles" rows="3" required></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Historial
                </button>
                
                <a href="tablaPacientes.php" class="btn-link">
                    <i class="fas fa-arrow-left"></i> Volver a la lista de pacientes
                </a>
            </form>
        </div>
    </div>
</body>
</html>