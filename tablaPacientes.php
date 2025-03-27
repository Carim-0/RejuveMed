<?php
    session_start();

    include("connection.php");
    include("functions.php");

    $query = "select * from Pacientes";
    $result = mysqli_query($con, $query);
    
    
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="bootstrap.min.css">
    <title>Registros de Pacientes</title>
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
    </style>

    
<button class="profile-button" onclick="window.location.href='verPerfil.php'">Ver Perfil</button>

    <div class="container">
        <div class="row mt-5">
            <div class="col">
                <div class="card mt-5">
                    <div class="card-header">
                        <h2 class="display-6 text-center">Pacientes registrados</h2>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered text-center">
                            <tr class="bg-dark text-white">
                                <td>ID del paciente</td>
                                <td>Nombre</td>
                                <td>Edad</td>
                                <td>Telefono</td>
                                <td>Editar</td>
                                <td>Eliminar</td>
                            </tr>
                            <tr>
                            <?php
                                while($row = mysqli_fetch_assoc($result))
                                {
                                    echo "<tr>";
                                    echo "<td>".$row['IDpaciente']."</td>";
                                    echo "<td>".$row['nombre']."</td>";
                                    echo "<td>".$row['edad']."</td>";
                                    echo "<td>".$row['telefono']."</td>";
                                    echo "<td><a href='editarPaciente.php?id=".$row['IDpaciente']."'>Editar</a></td>";
                                    echo "<td><a href='eliminarPaciente.php?id=".$row['IDpaciente']."' onclick='return confirm(\"¿Estás seguro de que deseas eliminar este paciente?\")'>Eliminar</a></td>";
                                    echo "</tr>";
                                }
                            ?>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>