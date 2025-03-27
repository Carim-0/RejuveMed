<?php
    session_start();

    include("connection.php");

    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        // Get form data
        $nombre = $_POST['nombre'];
        $detalles = $_POST['detalles'];
        $precio = $_POST['precio'];
        $imagenURL = $_POST['imagenURL'];

        // Validate form data
        if (!empty($nombre) && !empty($detalles) && !empty($precio) && !empty($imagenURL)) {
            // Validate that imagenURL is a valid image URL
            if (filter_var($imagenURL, FILTER_VALIDATE_URL) && preg_match('/\.(jpeg|jpg|png|gif)$/i', $imagenURL)) {
                // Insert into the Tratamientos table
                $query = "INSERT INTO Tratamientos (nombre, detalles, precio, imagenURL) VALUES ('$nombre', '$detalles', '$precio', '$imagenURL')";
                $result = mysqli_query($con, $query);

                if ($result) {
                    echo "<script>alert('Tratamiento registrado exitosamente.'); window.location.href='tablaTratamientos.php';</script>";
                } else {
                    echo "<script>alert('Error al registrar el tratamiento.');</script>";
                }
            } else {
                echo "<script>alert('Por favor, ingrese una URL válida de imagen (jpeg, jpg, png, gif).');</script>";
            }
        } else {
            echo "<script>alert('Por favor, complete todos los campos correctamente.');</script>";
        }
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Nuevo Tratamiento</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .form-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 400px;
        }

        .form-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .form-container label {
            display: block;
            margin: 10px 0 5px;
        }

        .form-container input,
        .form-container textarea,
        .form-container button {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .form-container button {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }

        .form-container button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Registrar Nuevo Tratamiento</h2>
        <form method="POST">
            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre" required>

            <label for="detalles">Detalles:</label>
            <textarea id="detalles" name="detalles" rows="3" required></textarea>

            <label for="precio">Precio:</label>
            <input type="number" id="precio" name="precio" step="0.01" required>

            <label for="imagenURL">URL de la Imagen:</label>
            <input type="url" id="imagenURL" name="imagenURL" required>

            <button type="submit">Registrar</button>
        </form>
    </div>
</body>
</html>