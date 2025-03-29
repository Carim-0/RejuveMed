<?php
    session_start();

    include("../connection.php");
    include("../functions.php");

    $query = "SELECT * FROM Tratamientos";
    $result = mysqli_query($con, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../bootstrap.min.css">
    <title>Registros de Tratamientos</title>
</head>
<body>
    <style type="text/css">
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

        #box {
            background-color: grey;
            margin: auto;
            width: 300px;
            padding: 20px;
        }

        .switch-button {
            margin: 20px;
            text-align: center;
        }

        .switch-button button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .switch-button button:hover {
            background-color: #0056b3;
        }

        .profile-button {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .profile-button:hover {
            background-color: #0056b3;
        }

        .add-button {
            margin-top: 20px;
            text-align: center;
        }

        .add-button button {
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .add-button button:hover {
            background-color: #218838;
        }
    </style>

    <button class="profile-button" onclick="window.location.href='../verPerfil.php'">Ver Perfil</button>

    <div class="switch-button">
        <button onclick="window.location.href='tablaPersonal.php'">Ir a Personal</button>
        <button onclick="window.location.href='tablaPacientes.php'">Ir a Pacientes</button>
        <button onclick="window.location.href='verCitasPacientes_Doctora.php'">Ir a Citas</button>
    </div>

    <div class="container">
        <div class="row mt-5">
            <div class="col">
                <div class="card mt-5">
                    <div class="card-header">
                        <h2 class="display-6 text-center">Tratamientos Registrados</h2>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered text-center">
                            <tr class="bg-dark text-white">
                                <td>ID del Tratamiento</td>
                                <td>Nombre</td>
                                <td>Detalles</td>
                                <td>Precio</td>
                                <td>Editar</td>
                                <td>Eliminar</td>
                            </tr>
                            <?php
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>";
                                    echo "<td>" . $row['IDtratamiento'] . "</td>";
                                    echo "<td>" . $row['nombre'] . "</td>";
                                    echo "<td>" . $row['detalles'] . "</td>";
                                    echo "<td>" . $row['precio'] . "</td>";
                                    echo "<td><a href='editarTratamiento.php?id=" . $row['IDtratamiento'] . "'>Editar</a></td>";
                                    echo "<td><a href='eliminarTratamiento.php?id=" . $row['IDtratamiento'] . "' onclick='return confirm(\"¿Estás seguro de que deseas eliminar este tratamiento?\")'>Eliminar</a></td>";
                                    echo "</tr>";
                                }
                            ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="add-button">
            <button onclick="window.location.href='registrarTratamiento.php'">Registrar Nuevo Tratamiento</button>
        </div>
    </div>
</body>
</html>