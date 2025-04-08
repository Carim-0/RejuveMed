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

// Fetch the patient's name from the Pacientes table
$query_paciente = "SELECT nombre FROM Pacientes WHERE IDpaciente = ? LIMIT 1";
$stmt = $con->prepare($query_paciente);
$stmt->bind_param("i", $id_paciente);
$stmt->execute();
$result_paciente = $stmt->get_result();

if ($result_paciente && $result_paciente->num_rows > 0) {
    $paciente = $result_paciente->fetch_assoc();
} else {
    die("Paciente no encontrado.");
}
$stmt->close();

// Get the current system date
$currentDate = date('Y-m-d');

// Fetch the cita with the same date and matching IDpaciente
$query_cita = "SELECT c.IDcita, c.fecha, c.estado, t.nombre as tratamiento, t.duracion 
               FROM Citas c
               JOIN Tratamientos t ON c.IDtratamiento = t.IDtratamiento
               WHERE DATE(c.fecha) = ? AND c.IDpaciente = ? LIMIT 1";
$stmt = $con->prepare($query_cita);
$stmt->bind_param("si", $currentDate, $id_paciente);
$stmt->execute();
$result_cita = $stmt->get_result();

$cita = null;
if ($result_cita && $result_cita->num_rows > 0) {
    $cita = $result_cita->fetch_assoc();
}
$stmt->close();

// Procesar eliminación del historial
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['delete'])) {
    // 1. Primero archivamos en la tabla Archivo
    $query_archivar = "INSERT INTO Archivo (IDpaciente, detalles) VALUES (?, ?)";
    $stmt = $con->prepare($query_archivar);
    $stmt->bind_param("is", $id_paciente, $historial['detalles']);
    $archivado = $stmt->execute();
    $stmt->close();
    
    if ($archivado) {
        // 2. Luego eliminamos el registro original
        $query_eliminar = "DELETE FROM `Historial Medico` WHERE IDpaciente = ?";
        $stmt = $con->prepare($query_eliminar);
        $stmt->bind_param("i", $id_paciente);
        $eliminado = $stmt->execute();
        
        if ($eliminado && $stmt->affected_rows > 0) {
            echo "<script>
                alert('Historial médico archivado y eliminado exitosamente.');
                window.location.href='tablaPacientes.php';
            </script>";
        } else {
            echo "<script>alert('Error al eliminar el historial médico.');</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Error al archivar el historial médico.');</script>";
    }
    exit;
}

// Handle form submission to update the historial medico and cita
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete'])) {
    $detalles = $_POST['detalles'];

    // Update the Historial Médico
    if (!empty($detalles)) {
        $query = "UPDATE `Historial Medico` SET detalles = ? WHERE IDpaciente = ?";
        $stmt = $con->prepare($query);
        $stmt->bind_param("si", $detalles, $id_paciente);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $historial_updated = true;
        } else {
            $historial_updated = false;
        }
        $stmt->close();
    } else {
        echo "<script>alert('El campo de detalles no puede estar vacío.');</script>";
    }

    // Update the Cita (if available)
    if (isset($_POST['id_cita']) && isset($_POST['estado'])) {
        $new_estado = $_POST['estado'];
        $id_cita = $_POST['id_cita'];

        $query_update_cita = "UPDATE Citas SET estado = ? WHERE IDcita = ?";
        $stmt = $con->prepare($query_update_cita);
        $stmt->bind_param("si", $new_estado, $id_cita);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $cita_updated = true;
        } else {
            $cita_updated = false;
        }
        $stmt->close();
    }

    // Display success or error messages
    if ($historial_updated || $cita_updated) {
        echo "<script>alert('Cambios guardados exitosamente.'); window.location.href='editarHistorialMedico.php?id=$id_paciente';</script>";
    } else {
        echo "<script>alert('No se realizaron cambios o ocurrió un error.');</script>";
    }
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
            --color-error:rgb(136, 56, 64);
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

        .contenedor-principal {
            display: flex;
            gap: 20px;
            justify-content: center;
            align-items: flex-start;
        }

        .contenedor-edicion {
            flex: 1;
            background-color: var(--color-fondo);
            border-radius: 10px;
            box-shadow: var(--sombra);
            overflow: hidden;
        }

        .header-edicion {
            background-color: var(--color-primario);
            color: white;
            padding: 20px;
            text-align: center;
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
        }
    </style>
</head>
<body>
    <div class="contenedor-principal">
        <div class="contenedor-edicion">
            <div class="header-edicion">
                <h1 class="titulo-edicion">
                    <i class="fas fa-notes-medical"></i> Editar Historial Médico
                </h1>
            </div>
            
            <div class="form-content">
                <form method="post">
                    <!-- Left Form: Editar Historial Médico -->
                    <div class="form-group">
                        <label for="id_paciente">ID del Paciente</label>
                        <input type="text" class="form-control" id="id_paciente" name="id_paciente" 
                               value="<?php echo htmlspecialchars($id_paciente); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="nombre">Nombre del Paciente</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" 
                               value="<?php echo htmlspecialchars($paciente['nombre']); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="detalles">Detalles del Historial Médico</label>
                        <textarea class="form-control" id="detalles" name="detalles" rows="6" required><?php 
                            echo htmlspecialchars($historial['detalles']); 
                        ?></textarea>
                    </div>

                    <!-- Right Form: Cita del Día -->
                    <?php if ($cita): ?>
                        <div class="form-group">
                            <label for="id_cita">ID de la Cita</label>
                            <input type="text" class="form-control" id="id_cita" name="id_cita" 
                                   value="<?php echo htmlspecialchars($cita['IDcita']); ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label for="fecha_cita">Fecha de la Cita</label>
                            <input type="text" class="form-control" id="fecha_cita" name="fecha_cita" 
                                   value="<?php echo htmlspecialchars(date('d/m/Y', strtotime($cita['fecha']))); ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label for="tratamiento">Tratamiento</label>
                            <input type="text" class="form-control" id="tratamiento" name="tratamiento" 
                                   value="<?php echo htmlspecialchars($cita['tratamiento']); ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label for="duracion">Duración</label>
                            <input type="text" class="form-control" id="duracion" name="duracion" 
                                   value="<?php echo htmlspecialchars($cita['duracion'] . ' horas'); ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label for="estado">Estado</label>
                            <select class="form-control" id="estado" name="estado">
                                <option value="Pendiente" <?php echo ($cita['estado'] === 'Pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="Cancelada" <?php echo ($cita['estado'] === 'Cancelada') ? 'selected' : ''; ?>>Cancelada</option>
                                <option value="Completada" <?php echo ($cita['estado'] === 'Completada') ? 'selected' : ''; ?>>Completada</option>
                            </select>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>

                    <button type="submit" name="delete" class="btn btn-primary" style="background-color: var(--color-error);"
                            onclick="return confirm('¿Estás seguro de que deseas eliminar este historial médico?');">
                        <i class="fas fa-trash"></i> Eliminar Historial
                    </button>
                    
                    <a href="tablaPacientes.php" class="btn-link">
                        <i class="fas fa-arrow-left"></i> Volver a la lista de pacientes
                    </a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>