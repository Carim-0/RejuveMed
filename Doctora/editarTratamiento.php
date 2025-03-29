<?php
    session_start();

    include("../connection.php");

    // Check if the ID is provided in the URL
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $id = $_GET['id'];

        // Fetch the treatment's current data
        $query = "SELECT * FROM Tratamientos WHERE IDtratamiento = $id LIMIT 1";
        $result = mysqli_query($con, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            $tratamiento = mysqli_fetch_assoc($result);
        } else {
            die("Tratamiento no encontrado.");
        }
    } else {
        die("ID de tratamiento no válido.");
    }

    // Handle form submission to update the treatment's data
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $nombre = $_POST['nombre'];
        $detalles = $_POST['detalles'];
        $precio = $_POST['precio'];
        $imagenURL = $_POST['imagenURL'];

        if (!empty($nombre) && !empty($detalles) && is_numeric($precio) && $precio > 0 && !empty($imagenURL)) {
            // Update the treatment's data in the database
            $query = "UPDATE Tratamientos SET nombre = '$nombre', detalles = '$detalles', precio = $precio, imagenURL = '$imagenURL' WHERE IDtratamiento = $id";
            $result = mysqli_query($con, $query);

            if ($result) {
                echo "<script>alert('Tratamiento actualizado exitosamente.'); window.location.href='tablaTratamientos.php';</script>";
            } else {
                echo "<script>alert('Error al actualizar el tratamiento.');</script>";
            }
        } else {
            echo "<script>alert('Por favor, complete todos los campos correctamente.');</script>";
        }
    }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Editar Tratamiento</title>
</head>
<body>
    <style>
        #box {
            background-color: grey;
            margin: auto;
            width: 300px;
            padding: 20px;
        }

        #text {
            height: 25px;
            border-radius: 5px;
            padding: 4px;
            border: solid thin #aaa;
            width: 100%;
        }

        #button {
            padding: 10px;
            width: 100px;
            color: white;
            background-color: lightblue;
            border: none;
        }
    </style>

    <div id="box">
        <form method="post">
            <div style="font-size: 20px; margin: 10px; color: white;">Editar Tratamiento</div>

            <input id="text" type="text" name="nombre" value="<?php echo $tratamiento['nombre']; ?>" placeholder="Nombre"><br><br>
            <textarea id="text" name="detalles" placeholder="Detalles"><?php echo $tratamiento['detalles']; ?></textarea><br><br>
            <input id="text" type="number" name="precio" value="<?php echo $tratamiento['precio']; ?>" placeholder="Precio"><br><br>
            <input id="text" type="text" name="imagenURL" value="<?php echo $tratamiento['imagenURL']; ?>" placeholder="URL de la Imagen"><br><br>

            <input id="button" type="submit" value="Actualizar"><br><br>
            <a href="tablaTratamientos.php">Volver</a><br><br>
        </form>
    </div>
</body>
</html>