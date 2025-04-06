<?php
session_start();
require_once '../connection.php';
require_once '../functions.php';

// Verificar login y obtener datos del paciente
$user_data = check_login($con);
$paciente_id = $_SESSION['user_id'];

// Procesar cancelación de cita
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_cita'])) {
    $cita_id = $_POST['cita_id'];
    
    // Actualizar estado en la base de datos
    $update_query = "UPDATE Citas SET estado = 'Cancelada' WHERE IDcita = ? AND IDpaciente = ?";
    $stmt = $con->prepare($update_query);
    $stmt->bind_param("ii", $cita_id, $paciente_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $mensaje = "Cita cancelada correctamente";
    } else {
        $mensaje = "Error al cancelar la cita";
    }
    $stmt->close();
    
    // Recargar la página para ver los cambios
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RejuveMed - Historial Clínico</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a6fa5;
            --secondary-color: #6b8cae;
            --accent-color: #4a6fa5;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
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
            padding: 20px;
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .page-title {
            color: var(--primary-color);
            font-size: 28px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: var(--border-radius);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background-color: #3a5a8a;
            transform: translateY(-2px);
        }
        
        .btn-outline {
            background-color: transparent;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }
        
        .btn-outline:hover {
            background-color: rgba(74, 111, 165, 0.1);
        }
        
        .content-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
            height: fit-content;
        }
        
        .card-title {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--secondary-color);
            font-size: 14px;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 15px;
        }
        
        .form-group input[readonly],
        .form-group textarea[readonly] {
            background-color: var(--light-color);
            color: #777;
            border-color: #eee;
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .inline-fields {
            display: flex;
            gap: 15px;
        }
        
        .appointment-item {
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        
        .appointment-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .appointment-card {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 15px;
        }
        
        .appointment-pendiente {
            background-color: rgba(var(--primary-color), 0.1);
            border-left: 4px solid var(--primary-color);
        }
        
        .appointment-cancelada {
            background-color: rgba(var(--danger-color), 0.1);
            border-left: 4px solid var(--danger-color);
        }
        
        .appointment-completada {
            background-color: rgba(var(--success-color), 0.1);
            border-left: 4px solid var(--success-color);
        }
        
        .appointment-field {
            font-weight: 500;
            color: var(--secondary-color);
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .appointment-value {
            color: var(--dark-color);
            margin-bottom: 10px;
        }
        
        .appointment-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 15px;
        }
        
        .status-pendiente {
            background-color: rgba(var(--warning-color), 0.2);
            color: blue;
        }
        
        .status-cancelada {
            background-color: rgba(var(--danger-color), 0.2);
            color: var(--danger-color);
        }
        
        .status-completada {
            background-color: rgba(var(--success-color), 0.2);
            color: var(--success-color);
        }
        
        .appointment-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-sm {
            padding: 8px 15px;
            font-size: 13px;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: white;
            border: none;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .btn-success {
            background-color: var(--success-color);
            color: white;
            border: none;
        }
        
        .btn-success:hover {
            background-color: #218838;
        }
        
        .no-appointments {
            text-align: center;
            color: #777;
            font-style: italic;
            padding: 30px 0;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background-color: rgba(var(--success-color), 0.2);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }
        
        .alert-danger {
            background-color: rgba(var(--danger-color), 0.2);
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }
        
        @media (max-width: 1024px) {
            .content-container {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
                width: 100%;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="header-container">
        <h1 class="page-title"><i class="fas fa-file-medical"></i> Historial Clínico</h1>
        
        <div class="action-buttons">
            <a href="VerHistorial_Paciente.php" class="btn btn-primary">
                <i class="fas fa-history"></i> Ver Historial
            </a>
            <a href="../verPerfil.php" class="btn btn-outline">
                <i class="fas fa-user"></i> Ver Perfil
            </a>
            <a href="catalogoTratamientos.php" class="btn btn-outline">
                <i class="fas fa-spa"></i> Catálogo
            </a>
        </div>
    </div>

    <!-- Mostrar mensajes -->
    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?php echo strpos($mensaje, 'Error') !== false ? 'danger' : 'success'; ?>">
            <i class="fas <?php echo strpos($mensaje, 'Error') !== false ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i>
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
    <?php endif; ?>

    <!-- Contenedor principal -->
    <div class="content-container">
        <!-- Sección izquierda (datos del usuario) -->
        <div class="card">
            <h2 class="card-title"><i class="fas fa-user-circle"></i> Datos del Paciente</h2>
            
            <div class="form-group">
                <label for="nombre">Nombre completo</label>
                <input type="text" id="nombre" name="nombre" readonly 
                       value="<?php echo htmlspecialchars($user_data['nombre'] ?? ''); ?>">
            </div>
            
            <div class="inline-fields">
                <div class="form-group" style="flex: 1;">
                    <label for="edad">Edad</label>
                    <input type="text" id="edad" name="edad" readonly 
                           value="<?php echo htmlspecialchars($user_data['edad'] ?? ''); ?>">
                </div>
                
                <div class="form-group" style="flex: 2;">
                    <label for="telefono">Teléfono</label>
                    <input type="text" id="telefono" name="telefono" readonly
                           value="<?php echo htmlspecialchars($user_data['telefono'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="detalles">Detalles médicos</label>
                <textarea id="detalles" name="detalles" readonly><?php 
                    echo htmlspecialchars($user_data['detalles'] ?? ''); 
                ?></textarea>
            </div>
        </div>
        
        <!-- Sección derecha (citas agendadas) -->
        <div class="card">
            <h2 class="card-title"><i class="fas fa-calendar-check"></i> Citas Agendadas</h2>
            
            <?php
            // Consulta para obtener las citas con información completa
            $query = "SELECT 
                        c.IDcita,
                        c.fecha, 
                        t.nombre as tratamiento, 
                        t.detalles as descripcion,
                        t.precio,
                        c.estado
                      FROM Citas c
                      JOIN Tratamientos t ON c.IDtratamiento = t.IDtratamiento
                      WHERE c.IDpaciente = '$paciente_id'
                      ORDER BY c.fecha";
            
            $result = $con->query($query);
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Formatear fecha y hora
                    $fecha_obj = new DateTime($row['fecha']);
                    $fecha_formateada = $fecha_obj->format('d/m/Y');
                    $hora_formateada = $fecha_obj->format('H:i a');

                    // Formatear precio
                    $precio_formateado = '$' . number_format($row['precio'], 2);

                    // Determinar clases CSS según el estado
                    $clase_estado = 'appointment-' . strtolower($row['estado']);
                    $clase_status = 'status-' . strtolower($row['estado']);

                    echo '<div class="appointment-item">';
                    echo '<div class="appointment-card '.$clase_estado.'">';
                    echo '<span class="appointment-status '.$clase_status.'">';
                    $icono = (strtolower($row['estado']) == 'pendiente') ? 'fa-clock' : 
                            ((strtolower($row['estado']) == 'cancelada') ? 'fa-times-circle' : 'fa-check-circle');
                    echo '<i class="fas '.$icono.'"></i> ' . htmlspecialchars($row['estado']);
                    echo '</span>';
                    
                    echo '<div class="appointment-field">Fecha y hora</div>';
                    echo '<div class="appointment-value">'.htmlspecialchars($fecha_formateada).' a las '.htmlspecialchars($hora_formateada).'</div>';
                    
                    echo '<div class="appointment-field">Tratamiento</div>';
                    echo '<div class="appointment-value">'.htmlspecialchars($row['tratamiento']).'</div>';
                    
                    echo '<div class="appointment-field">Precio</div>';
                    echo '<div class="appointment-value">'.htmlspecialchars($precio_formateado).'</div>';
                    
                    echo '<div class="appointment-field">Descripción</div>';
                    echo '<div class="appointment-value">'.nl2br(htmlspecialchars($row['descripcion'])).'</div>';
                    echo '</div>';

                    echo '<div class="appointment-actions">';
                    
                    // Botón de editar
                    echo '<a href="editarCita_Paciente.php?id='.$row['IDcita'].'" class="btn btn-outline btn-sm">';
                    echo '<i class="fas fa-edit"></i> Editar';
                    echo '</a>';

                    // Botón de cancelar (solo para citas pendientes)
                    if (strtolower($row['estado']) == 'pendiente') {
                        echo '<form method="POST" style="display: inline;">';
                        echo '<input type="hidden" name="cita_id" value="'.$row['IDcita'].'">';
                        echo '<button type="submit" name="cancelar_cita" class="btn btn-danger btn-sm">';
                        echo '<i class="fas fa-times"></i> Cancelar';
                        echo '</button>';
                        echo '</form>';
                    }
                    
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<div class="no-appointments">';
                echo '<i class="far fa-calendar-times" style="font-size: 24px; margin-bottom: 10px;"></i>';
                echo '<p>No tienes citas agendadas actualmente</p>';
                echo '</div>';
            }
            
            $con->close();
            ?>
        </div>
    </div>
</body>
</html>