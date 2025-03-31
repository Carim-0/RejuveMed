<?php
    session_start();

    include("../connection.php");

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --color-primario: #4a8cff; /*Azul para tratamientos*/
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

        .contenedor-registro {
            background-color: var(--color-fondo);
            border-radius: 10px;
            box-shadow: var(--sombra);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
        }

        .header-registro {
            background-color: var(--color-primario);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .titulo-registro {
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
            
            .header-registro {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="contenedor-registro">
        <div class="header-registro">
            <h1 class="titulo-registro">
                <i class="fas fa-prescription-bottle-alt"></i> Registrar Tratamiento
            </h1>
        </div>
        
        <div class="form-content">
            <form method="POST">
                <div class="form-group">
                    <label for="nombre">Nombre del Tratamiento</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" required>
                </div>
                
                <div class="form-group">
                    <label for="detalles">Detalles</label>
                    <textarea class="textarea-control" id="detalles" name="detalles" rows="3" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="precio">Precio ($)</label>
                    <div class="input-icon">
                        <input type="number" class="form-control" id="precio" name="precio" step="0.01" required>
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="imagenURL">URL de la Imagen</label>
                    <div class="input-icon">
                        <input type="url" class="form-control" id="imagenURL" name="imagenURL" required>
                        <i class="fas fa-image"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Registrar Tratamiento
                </button>

                <a href="tablaTratamientos.php" class="btn-link">
                    <i class="fas fa-arrow-left"></i> Volver a la lista de tratamientos
                </a>
            </form>
        </div>
    </div>
</body>
</html>