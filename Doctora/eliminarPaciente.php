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

// Verificar si el ID es válido
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_paciente = $_GET['id'];
    $fecha_actual = date('Y-m-d H:i:s'); // Fecha actual para el campo fecha_archivado

    // 1. Obtener datos del paciente
    $query_paciente = "SELECT nombre, telefono, edad FROM Pacientes WHERE IDpaciente = $id_paciente";
    $result_paciente = mysqli_query($con, $query_paciente);
    
    if(mysqli_num_rows($result_paciente) > 0) {
        $paciente = mysqli_fetch_assoc($result_paciente);
        
        // 2. Obtener historial médico si existe
        $detalles = "No tiene historial médico";
        $query_historial = "SELECT detalles FROM `Historial Medico` WHERE IDpaciente = $id_paciente";
        $result_historial = mysqli_query($con, $query_historial);
        
        if(mysqli_num_rows($result_historial) > 0) {
            $historial = mysqli_fetch_assoc($result_historial);
            $detalles = $historial['detalles'];
        }
        
        // 3. Insertar en tabla Archivo con todos los campos
        $query_archivar = "INSERT INTO Archivo (
                            IDpaciente, 
                            nombre, 
                            telefono, 
                            edad, 
                            detalles, 
                            fecha_archivado
                          ) VALUES (
                            $id_paciente, 
                            '".mysqli_real_escape_string($con, $paciente['nombre'])."', 
                            '".mysqli_real_escape_string($con, $paciente['telefono'])."', 
                            ".(int)$paciente['edad'].", 
                            '".mysqli_real_escape_string($con, $detalles)."',
                            '$fecha_actual'
                          )";
        
        $result_archivar = mysqli_query($con, $query_archivar);
        
        if(!$result_archivar) {
            $_SESSION['mensaje'] = "Error al archivar el paciente: " . mysqli_error($con);
            $_SESSION['tipo_mensaje'] = "error";
            header("Location: tablaPacientes.php");
            exit();
        }
        
        // 4. Eliminar el historial médico si existe
        $query_delete_historial = "DELETE FROM `Historial Medico` WHERE IDpaciente = $id_paciente";
        mysqli_query($con, $query_delete_historial);
        
        // 5. Eliminar el paciente
        $query_delete_paciente = "DELETE FROM Pacientes WHERE IDpaciente = $id_paciente";
        $result_delete = mysqli_query($con, $query_delete_paciente);

        if ($result_delete) {
            $_SESSION['mensaje'] = "Paciente archivado y eliminado exitosamente.";
            $_SESSION['tipo_mensaje'] = "success";
        } else {
            $_SESSION['mensaje'] = "Error al eliminar el paciente: " . mysqli_error($con);
            $_SESSION['tipo_mensaje'] = "error";
        }
    } else {
        $_SESSION['mensaje'] = "No se encontró el paciente con ID $id_paciente";
        $_SESSION['tipo_mensaje'] = "error";
    }

    // Redirigir a la tabla de pacientes
    header("Location: tablaPacientes.php");
    exit();
} else {
    $_SESSION['mensaje'] = "ID de paciente no válido.";
    $_SESSION['tipo_mensaje'] = "error";
    header("Location: tablaPacientes.php");
    exit();
}
?>