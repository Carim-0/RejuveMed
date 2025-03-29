<?php
    session_start();

    include("../connection.php");

    // Check if the ID is provided in the URL
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $id = $_GET['id'];

        // Delete the treatment record from the database
        $query = "DELETE FROM Tratamientos WHERE IDtratamiento = $id";
        $result = mysqli_query($con, $query);

        if ($result) {
            echo "<script>alert('Tratamiento eliminado exitosamente.'); window.location.href='tablaTratamientos.php';</script>";
        } else {
            echo "<script>alert('Error al eliminar el tratamiento.');</script>";
        }
    } else {
        die("ID de tratamiento no válido.");
    }
?>