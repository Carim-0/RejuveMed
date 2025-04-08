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
    <title>Bienvenido </title>
    <link rel="stylesheet" href="tratamientos_style.css">
    <style>
        .treatments {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }

        .treatment {
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            width: 200px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .treatment img {
            width: 100%;
            height: auto;
            border-radius: 8px;
        }

        .treatment h3 {
            font-size: 18px;
            margin: 10px 0;
        }

        /* Estilo para el botón "Ver citas agendadas" */
        .btn.ver-citas {
            background-color: #007bff; /* Azul */
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        .btn.ver-citas:hover {
            background-color: #0056b3; /* Azul oscuro */
        }

        /* Estilo para el botón "Ver tratamiento" */
        .treatment button.ver-tratamiento {
            background-color: #28a745; /* Verde */
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }

        .treatment button.ver-tratamiento:hover {
            background-color: #218838; /* Verde oscuro */
        }

        .header-buttons {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
        }

        .header-button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        .header-button:hover {
            background-color: #0056b3;
        }

        .header-button.historial {
            background-color: #007bff;
        }

        .header-button.historial:hover {
            background-color: #007bff;
        }
    </style>
</head>
<body>
    <div class="header-buttons">
        <button class="header-button historial" onclick="window.location.href='VerHistorial_Paciente.php'">
            <i class="fas fa-history"></i> Ver Historial
        </button>
        <button class="header-button" onclick="window.location.href='../ver
