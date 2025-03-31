<?php
session_start();
require_once '../connection.php';
require_once '../functions.php';

// Verificar login y permisos
$user_data = check_login($con);
if ($_SESSION['user_type'] !== 'Personal') {
    header("Location: ../unauthorized.php");
    exit();
}

// Obtener datos de la cita a editar
$cita = [];
$paciente = [];
$tratamientos = [];
$mensaje = '';

if (isset($_GET['id'])) {
    $cita_id = $_GET['id'];
    
    // Obtener información de la cita
    $query = "SELECT c.*, t.nombre as tratamiento_nombre, p.nombre as paciente_nombre 
              FROM Citas c
              JOIN Tratamientos t ON c.IDtratamiento = t.IDtratamiento
              JOIN Pacientes p ON c.IDpaciente = p.IDpaciente
              WHERE c.IDcita = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $cita_id);
    $stmt->execute();
    $cita = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$cita) {
        $mensaje = "Cita no encontrada";
    }
}

// Obtener lista de tratamientos para el select
$query = "SELECT IDtratamiento, nombre FROM Tratamientos ORDER BY nombre";
$result = $con->query($query);
$tratamientos = $result->fetch_all(MYSQLI_ASSOC);

// Procesar actualización de cita
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_cita'])) {
    $cita_id = $_POST['cita_id'];
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $estado = $_POST['estado'];
    $tratamiento_id = $_POST['tratamiento'];
    
    // Validar datos
    if (empty($fecha) || empty($hora) || empty($tratamiento_id)) {
        $mensaje = "Por favor complete todos los campos requeridos";
    } else {
        // Combinar fecha y hora en un solo campo datetime
        $fecha_hora = $fecha . ' ' . $hora . ':00';
        
        // Actualizar en base de datos (sin el campo observaciones)
        $query = "UPDATE Citas SET 
                  fecha = ?,
                  estado = ?,
                  IDtratamiento = ?
                  WHERE IDcita = ?";
        $stmt = $con->prepare($query);
        $stmt->bind_param("sssi", $fecha_hora, $estado, $tratamiento_id, $cita_id);
        
        if ($stmt->execute()) {
            $mensaje = "Cita actualizada correctamente";
            // Actualizar datos mostrados
            $cita['fecha'] = $fecha_hora;
            $cita['estado'] = $estado;
            $cita['IDtratamiento'] = $tratamiento_id;
        } else {
            $mensaje = "Error al actualizar la cita: " . $con->error;
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
    <title>Editar Cita - RejuveMed</title>
    <style>
        :root {
            --color-primario: #1a37b5;
            --color-secundario: #FFF9EB;
            --color-terciario: #C4C4C4;
            --color-button: #fe652b;
            --color-button-hover: #501801;
            --color-text: #444444;
            --color-fondo: #e2dfdf;
            --color-white: #FFFFFF;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: var(--color-fondo);
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: var(--color-primario);
            margin-bottom: 20px;
            text-align: center;
        }

        .patient-info {
            background-color: var(--color-secundario);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .patient-info h2 {
            color: var(--color-primario);
            margin-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--color-text);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--color-terciario);
            border-radius: 4px;
            font-size: 16px;
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.3s;
        }

        .btn-primary {
            background-color: var(--color-primario);
            color: white;
        }

        .btn-primary:hover {
            background-color: #0d2b9a;
        }

        .btn-secondary {
            background-color: var(--color-terciario);
            color: var(--color-text);
        }

        .btn-secondary:hover {
            background-color: #b0b0b0;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: flex-end;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }

        .status-pendiente {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-cancelada {
            background-color: #f8d7da;
            color: #721c24;
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Editar Cita</h1>
        
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?= strpos($mensaje, 'Error') !== false ? 'danger' : 'success' ?>">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($cita)): ?>
            <p>Cita no encontrada</p>
        <?php else: ?>
            <div class="patient-info">
                <h2>Paciente: <?= htmlspecialchars($cita['paciente_nombre']) ?></h2>
                <p>Tratamiento actual: <?= htmlspecialchars($cita['tratamiento_nombre']) ?></p>
                <p>Estado: 
                    <span class="status-badge status-<?= strtolower($cita['estado']) ?>">
                        <?= htmlspecialchars($cita['estado']) ?>
                    </span>
                </p>
            </div>
            
            <form method="POST">
                <input type="hidden" name="cita_id" value="<?= $cita['IDcita'] ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha">Fecha*</label>
                        <input type="date" id="fecha" name="fecha" required 
                               value="<?= htmlspecialchars(date('Y-m-d', strtotime($cita['fecha']))) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="hora">Hora*</label>
                        <input type="time" id="hora" name="hora" required 
                               value="<?= htmlspecialchars(date('H:i', strtotime($cita['fecha']))) ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="tratamiento">Tratamiento*</label>
                        <select id="tratamiento" name="tratamiento" required>
                            <?php foreach ($tratamientos as $tratamiento): ?>
                                <option value="<?= $tratamiento['IDtratamiento'] ?>" 
                                    <?= $tratamiento['IDtratamiento'] == $cita['IDtratamiento'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tratamiento['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="estado">Estado*</label>
                        <select id="estado" name="estado" required>
                            <option value="Pendiente" <?= $cita['estado'] == 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="Cancelada" <?= $cita['estado'] == 'Cancelada' ? 'selected' : '' ?>>Cancelada</option>
                        </select>
                    </div>
                </div>
                
                <div class="button-group">
                    <button type="submit" name="actualizar_cita" class="btn btn-primary">Guardar Cambios</button>
                    <a href="/Rejuvemed/Doctora/verCitasPacientes_Doctora.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script>
        // Validación de fecha/hora futura
        document.querySelector('form').addEventListener('submit', function(e) {
            const fecha = document.getElementById('fecha').value;
            const hora = document.getElementById('hora').value;
            const ahora = new Date();
            const fechaCita = new Date(fecha + 'T' + hora);
            
            if (fechaCita < ahora) {
                if (!confirm('La fecha y hora de la cita son en el pasado. ¿Desea continuar?')) {
                    e.preventDefault();
                }
            }
        });
    </script>
</body>
</html>
<?php $con->close(); ?>