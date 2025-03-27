<?php
    session_start();

    include("connection.php");

    // Check if the ID is provided in the URL
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $id = $_GET['id'];

        // Delete the patient record from the database
        $query = "DELETE FROM Pacientes WHERE IDpaciente = $id";
        $result = mysqli_query($con, $query);

        if ($result) {
            echo "Paciente eliminado exitosamente.";
        } else {
            echo "Error al eliminar el paciente.";
        }

        // Redirect back to the table of patients
        header("Location: tablaPacientes.php");
        die;
    } else {
        die("ID de paciente no válido.");
    }
?>