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
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Tratamiento</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --color-primario: #4a8cff; /*Azul para tratamiento*/
            --color-secundario: #f8f9fa;
            --color-terciario: #e9ecef;
            --color-exito: #28a745;
            --color-error: #dc3545;
            --color-texto: #212529;
            --color-borde: #ced4da;
            --color-fondo: #ffffff;
            --sombra: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--color-terciario);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .contenedor-edicion {
            background-color: var(--color-fondo);
            border-radius: 10px;
            box-shadow: var(--sombra);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
        }

        .header-edicion {
            background-color: var(--color-primario);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .titulo-edicion {
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }

        .form-content {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--color-texto);
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--color-borde);
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            border-color: var(--color-primario);
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 140, 255, 0.1);
        }

        .textarea-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--color-borde);
            border-radius: 6px;
            font-size: 16px;
            min-height: 100px;
            resize: vertical;
            transition: border-color 0.3s;
        }

        .btn {
            display: inline-block;
            padding: 12px 20px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            border: none;
        }

        .btn-primary {
            background-color: var(--color-primario);
            color: white;
            width: 100%;
        }

        .btn-primary:hover {
            background-color: #3a7ae8;
        }

        .btn-link {
            color: var(--color-primario);
            text-decoration: none;
            display: inline-block;
            margin-top: 15px;
            font-size: 14px;
        }

        .btn-link:hover {
            text-decoration: underline;
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            color: var(--color-borde);
        }

        @media (max-width: 576px) {
            .form-content {
                padding: 20px;
            }
            
            .header-edicion {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="contenedor-edicion">
        <div class="header-edicion">
            <h1 class="titulo-edicion">
                <i class="fas fa-prescription-bottle-alt"></i> Editar Tratamiento
            </h1>
        </div>
        
        <div class="form-content">
            <form method="post">
                <div class="form-group">
                    <label for="nombre">Nombre del Tratamiento</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" 
                           value="<?php echo htmlspecialchars($tratamiento['nombre']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="detalles">Detalles</label>
                    <textarea class="textarea-control" id="detalles" name="detalles" required><?php echo htmlspecialchars($tratamiento['detalles']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="precio">Precio ($)</label>
                    <div class="input-icon">
                        <input type="number" class="form-control" id="precio" name="precio" 
                               value="<?php echo htmlspecialchars($tratamiento['precio']); ?>" min="0" step="0.01" required>
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="imagenURL">URL de la Imagen</label>
                    <div class="input-icon">
                        <input type="text" class="form-control" id="imagenURL" name="imagenURL" 
                               value="<?php echo htmlspecialchars($tratamiento['imagenURL']); ?>" required>
                        <i class="fas fa-image"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Actualizar Tratamiento
                </button>
                
                <a href="tablaTratamientos.php" class="btn-link">
                    <i class="fas fa-arrow-left"></i> Volver a la lista de tratamientos
                </a>
            </form>
        </div>
    </div>
</body>
</html>