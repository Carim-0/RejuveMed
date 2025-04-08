<?php
    session_start();
    
    include("../connection.php");
    include("../functions.php");    

    $user_data = check_login($con);

    // Fetch data from the "Tratamientos" table
    $query = "SELECT IDtratamiento, nombre, imagenURL FROM Tratamientos";
    $result = mysqli_query($con, $query);

    if (!$result) {
        die("Error al obtener los tratamientos: " . mysqli_error($con));
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido</title>
    <link rel="stylesheet" href="tratamientos_style.css">
    <style>
        /* Estilo general para el contenedor de tratamientos */
        .treatments {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            margin-top: 40px;
        }

        /* Estilo para cada caja de tratamiento */
        .treatment {
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            width: 250px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            background-color: #f8f9fa; /* Fondo más suave */
            transition: all 0.3s ease; /* Transición suave al hacer hover */
        }

        .treatment:hover {
            transform: translateY(-10px); /* Efecto de elevarse al hacer hover */
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.2); /* Sombra más fuerte */
        }

        .treatment img {
            width: 100%;
            height: 100px; /* Ajusta la altura */
            object-fit: cover; /* Mantiene la imagen recortada correctamente */
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .treatment h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .treatment button {
            background-color: #28a745; /* Verde */
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            text-transform: uppercase;
            transition: all 0.3s ease;
            margin-top: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Sombra suave */
        }

        .treatment button:hover {
            background-color: #218838; /* Verde oscuro */
            transform: translateY(-6px); /* Efecto de elevarse al hacer hover */
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.2); /* Sombra más profunda */
        }

        /* Estilo para el mensaje de bienvenida */
        h1 {
            background-color: #007bff; /* Azul */
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            margin-top: 40px;
            font-size: 24px;
            font-weight: bold;
        }

        /* Estilo para los botones de la parte superior */
        .header-buttons {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
        }

        .header-button {
            padding: 12px 24px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Sombra suave */
        }

        .header-button:hover {
            background-color: #0056b3; /* Azul oscuro */
            transform: translateY(-6px); /* Elevar ligeramente */
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.2); /* Sombra más profunda */
        }

        /* Botones específicos para "Ver citas" y "Agendar" */
        .btn {
            background-color: #007bff; /* Azul */
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            display: inline-block;
            margin-top: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Sombra suave */
        }

        .btn:hover {
            background-color: #0056b3; /* Azul oscuro */
            transform: translateY(-6px); /* Elevar ligeramente */
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.2); /* S*
