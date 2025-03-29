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
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #333;
            font-size: 2.5em;
        }
        
        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            font-size: 24px;
            text-decoration: none;
            color: #333;
        }
        
        .form-container {
            display: flex;
            gap: 30px;
            margin-left: 20px;
            margin-top: 20px;
        }
        
        .left-section, .right-section {
            flex: 1;
        }
        
        textarea, input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        textarea {
            height: 150px;
            resize: none;
            background-color: #f9f9f9;
        }
        
        .medical-history {
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 20px;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .medical-history h3 {
            margin-top: 0;
            color: #0066cc;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        .inline-fields {
            display: flex;
            gap: 15px;
        }
        
        .inline-fields .form-group {
            flex: 1;
        }
        
        input[type="text"] {
            background-color: #f9f9f9;
        }
        
        .appointments-title {
            color: #0066cc;
            margin-bottom: 15px;
        }
        
        .appointments-container {
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 20px;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .appointment-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .appointment-card {
            flex: 1;
            color: white;
            padding: 15px;
            border-radius: 6px;
        }
        
        .appointment-pendiente {
            background-color: #0066cc;
        }
        
        .appointment-cancelada {
            background-color: #ff5252;
        }
        
        .appointment-card p {
            margin: 5px 0;
        }
        
        .appointment-field {
            font-weight: bold;
        }
        
        .appointment-status {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        
        .btn-cancelar {
            background-color: #d32f2f;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
            white-space: nowrap;
        }
        
        .btn-cancelar:hover {
            background-color: #b71c1c;
        }
        
        .mensaje {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            text-align: center;
        }
        
        .mensaje-exito {
            background-color: #dff0d8;
            color: #3c763d;
        }
        
        .mensaje-error {
            background-color: #f2dede;
            color: #a94442;
        }
        
        .no-appointments {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
        }
    </style>
</head>
<body>
    <!-- Flecha de retroceso -->
    <a href="javascript:history.back()" class="back-button">←</a>
    
    <!-- Título centrado -->
    <div class="header">
        <h1>RejuveMed</h1>
    </div>

    <!-- Mostrar mensajes -->
    <?php if (!empty($mensaje)): ?>
        <div class="mensaje <?php echo strpos($mensaje, 'Error') !== false ? 'mensaje-error' : 'mensaje-exito'; ?>">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
    <?php endif; ?>

    <!-- Contenedor principal -->
    <div class="form-container">
        <!-- Sección izquierda (historial clínico) -->
        <div class="left-section">
            <div class="medical-history">
                <h3>Historial clínico</h3>
                
                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" readonly 
                           value="<?php echo htmlspecialchars($user_data['nombre'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="edad">Edad</label>
                    <input type="text" id="edad" name="edad" readonly style="width: 80px;" 
                           value="<?php echo htmlspecialchars($user_data['edad'] ?? ''); ?>">
                </div>
                    
                <div class="form-group">
                    <label for="telefono">Teléfono</label>
                    <input type="text" id="telefono" name="telefono" readonly
                           value="<?php echo htmlspecialchars($user_data['telefono'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="detalles">Detalles médicos</label>
                    <textarea id="detalles" name="detalles" readonly rows="4"><?php 
                        echo htmlspecialchars($user_data['detalles'] ?? ''); 
                    ?></textarea>
                </div>
            </div>
        </div>
        
        <!-- Sección derecha (citas agendadas) -->
        <div class="right-section">
            <div class="appointments-container">
                <h3 class="appointments-title">Citas Agendadas</h3>
                
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
                    while($row = $result->fetch_assoc()) {
                        // Formatear fecha y hora
                        $fecha_obj = new DateTime($row['fecha']);
                        $fecha_formateada = $fecha_obj->format('d/m/Y');
                        $hora_formateada = $fecha_obj->format('H:i');
                        
                        // Formatear precio
                        $precio_formateado = '$' . number_format($row['precio'], 2);
                        
                        // Determinar clase CSS según el estado
                        $clase_estado = strtolower($row['estado']) == 'pendiente' ? 
                                         'appointment-pendiente' : 'appointment-cancelada';
                        
                        echo '<div class="appointment-item">';
                        echo '<div class="appointment-card '.$clase_estado.'">';
                        echo '<p class="appointment-status">Estado: '.htmlspecialchars($row['estado']).'</p>';
                        echo '<p><span class="appointment-field">Fecha:</span> '.htmlspecialchars($fecha_formateada).'</p>';
                        echo '<p><span class="appointment-field">Hora:</span> '.htmlspecialchars($hora_formateada).'</p>';
                        echo '<p><span class="appointment-field">Tratamiento:</span> '.htmlspecialchars($row['tratamiento']).'</p>';
                        echo '<p><span class="appointment-field">Precio:</span> '.htmlspecialchars($precio_formateado).'</p>';
                        echo '<p><span class="appointment-field">Descripción:</span> '.nl2br(htmlspecialchars($row['descripcion'])).'</p>';
                        echo '</div>';
                        
                        // Botón de cancelar (solo para citas pendientes)
                        if (strtolower($row['estado']) == 'pendiente') {
                            echo '<form method="POST">';
                            echo '<input type="hidden" name="cita_id" value="'.$row['IDcita'].'">';
                            echo '<button type="submit" name="cancelar_cita" class="btn-cancelar">Cancelar Cita</button>';
                            echo '</form>';
                        }
                        
                        echo '</div>';
                    }
                } else {
                    echo '<p class="no-appointments">No tienes citas agendadas actualmente</p>';
                }
                //hola
                $con->close();
                ?>
            </div>
        </div>
    </div>
</body>
</html>