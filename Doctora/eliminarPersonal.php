<?php
    session_start();

    include("../connection.php");

    // Check if the ID is provided in the URL
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $id = $_GET['id'];

        // Delete the personal record from the database
        $query = "DELETE FROM Personal WHERE IDpersonal = $id";
        $result = mysqli_query($con, $query);

        if ($result) {
            echo "Personal eliminado exitosamente.";
        } else {
            echo "Error al eliminar el personal.";
        }

        // Redirect back to the table of personal
        header("Location: tablaPersonal.php");
        die;
    } else {
        die("ID de personal no válido.");
    }
?>