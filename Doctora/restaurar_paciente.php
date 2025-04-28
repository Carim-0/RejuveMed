<?php
session_start();
include("../connection.php");
include("../functions.php");

// Verificar permisos
$user_data = check_login($con);
if($_SESSION['user_type'] !== 'Personal') {
    header("Location: ../unauthorized.php");
    exit();
}

// Verificar que se recibió el ID del paciente
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: HistorialArchivo.php?error=ID inválido");
    exit();
}

$id_paciente = (int)$_GET['id'];

// Verificar que el historial existe en Archivo
$query = "SELECT * FROM Archivo WHERE IDpaciente = $id_paciente";
$result = mysqli_query($con, $query);

if(mysqli_num_rows($result) == 0) {
    header("Location: HistorialArchivo.php?error=Historial no encontrado en archivo");
    exit();
}

// Obtener los datos del historial archivado
$archivado = mysqli_fetch_assoc($result);

// Depuración: Verificar los datos obtenidos
error_log("Datos del archivo: " . print_r($archivado, true));

// Iniciar transacción
mysqli_begin_transaction($con);

try {
    // 1. Actualizar el historial médico en la tabla Pacientes
    $query_update = "UPDATE Pacientes SET detalles = ? WHERE IDpaciente = ?";
    
    $stmt = mysqli_prepare($con, $query_update);
    if(!$stmt) {
        throw new Exception("Error al preparar la consulta: " . mysqli_error($con));
    }
    
    // Asegurarse de que estamos usando el campo correcto del archivo
    $detalles = isset($archivado['detalles']) ? $archivado['detalles'] : '';
    
    $bind_result = mysqli_stmt_bind_param($stmt, "si", $detalles, $id_paciente);
    if(!$bind_result) {
        throw new Exception("Error al vincular parámetros: " . mysqli_stmt_error($stmt));
    }
    
    $execute_result = mysqli_stmt_execute($stmt);
    if(!$execute_result) {
        throw new Exception("Error al ejecutar la consulta: " . mysqli_stmt_error($stmt));
    }
    
    // Verificar cuántas filas fueron afectadas
    $affected_rows = mysqli_stmt_affected_rows($stmt);
    error_log("Filas afectadas en Pacientes: " . $affected_rows);
    
    if($affected_rows === 0) {
        throw new Exception("No se encontró el paciente en la tabla Pacientes o los datos eran idénticos");
    }
    
    // 2. Eliminar el registro de Archivo
    $query_delete = "DELETE FROM Archivo WHERE IDpaciente = $id_paciente";
    $delete_result = mysqli_query($con, $query_delete);
    
    if(!$delete_result) {
        throw new Exception("Error al eliminar del archivo: " . mysqli_error($con));
    }
    
    // Confirmar transacción
    mysqli_commit($con);
    
    // Redirigir con mensaje de éxito
    header("Location: HistorialArchivo.php?success=Historial médico restaurado correctamente");
    exit();
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    mysqli_rollback($con);
    error_log("Error en restaurar_paciente.php: " . $e->getMessage());
    header("Location: HistorialArchivo.php?error=" . urlencode($e->getMessage()));
    exit();
} finally {
    if(isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
}