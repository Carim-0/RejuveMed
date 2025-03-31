<?php
session_start();

include("../connection.php");

// Check if the ID is provided in the URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_paciente = $_GET['id'];

    // Fetch the current data from the Historial Medico table
    $query = "SELECT * FROM `Historial Medico` WHERE IDpaciente = ? LIMIT 1";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $id_paciente);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $historial = $result->fetch_assoc();
    } else {
        die("Historial médico no encontrado para este paciente.");
    }
    $stmt->close();
} else {
    die("ID de paciente no válido.");
}

// Handle form submission to update the historial medico
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $detalles = $_POST['detalles'];

    if (!empty($detalles)) {
        // Update the detalles column in the Historial Medico table
        $query = "UPDATE `Historial Medico` SET detalles = ? WHERE IDpaciente = ?";
        $stmt = $con->prepare($query);
        $stmt->bind_param("si", $detalles, $id_paciente);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo "<script>alert('Historial médico actualizado exitosamente.'); window.location.href='tablaPacientes.php';</script>";
        } else {
            echo "<script>alert('No se realizaron cambios o ocurrió un error.');</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('El campo de detalles no puede estar vacío.');</script>";
    }
}

if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['delete'])) {
    // Delete the record from the Historial Medico table
    $query = "DELETE FROM `Historial Medico` WHERE IDpaciente = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $id_paciente);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "<script>alert('Historial médico eliminado exitosamente.'); window.location.href='tablaPacientes.php';</script>";
    } else {
        echo "<script>alert('Error al eliminar el historial médico.');</script>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Historial Médico</title>
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
    </style>
</head>
<body>
    <div class="contenedor-edicion">
        <div class="header-edicion">
            <h1 class="titulo-edicion">
                <i class="fas fa-notes-medical"></i> Editar Historial Médico
            </h1>
        </div>
        
        <div class="form-content">
            <form method="post">
                <div class="form-group">
                    <label for="id_paciente">ID del Paciente</label>
                    <input type="text" class="form-control" id="id_paciente" name="id_paciente" 
                           value="<?php echo htmlspecialchars($id_paciente); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label for="detalles">Detalles del Historial Médico</label>
                    <textarea class="form-control" id="detalles" name="detalles" rows="6" required><?php 
                        echo htmlspecialchars($historial['detalles']); 
                    ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>

                <button type="submit" name="delete" class="btn btn-primary" style="background-color: var(--color-error);">
                    <i class="fas fa-trash"></i> Eliminar Historial
                </button>
                
                <a href="tablaPacientes.php" class="btn-link">
                    <i class="fas fa-arrow-left"></i> Volver a la lista de pacientes
                </a>
            </form>
        </div>
    </div>
</body>
</html>