<?php
    session_start();

    include("connection.php");
    include("functions.php");

    $query = "select * from Personal";
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
        #text{
            height: 25px;
            border-radius: 5px;
            padding: 4px;
            border: solid thin #aaa;
            width: 100%;
        }

        #button{
            padding: 10px;
            width: 100px;
            color: white;
            background-color: lightblue;
            border: none;
        }

        #box{
            background-color: grey;
            margin: auto;
            width: 300px;
            padding: 20px;
        }
    </style>

    <div class="container">
        <div class="row mt-5">
            <div class="col">
                <div class="card mt-5">
                    <div class="card-header">
                        <h2 class="display-6 text-center">Personal Registrado</h2>
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
                                    echo "<td>".$row['IDpersonal']."</td>";
                                    echo "<td>".$row['nombre']."</td>";
                                    echo "<td>".$row['edad']."</td>";
                                    echo "<td>".$row['telefono']."</td>";
                                    echo "<td><a href='editarPersonal.php?id=".$row['IDpersonal']."'>Editar</a></td>";
                                    echo "<td><a href='eliminarPersonal.php?id=".$row['IDpersonal']."' onclick='return confirm(\"¿Estás seguro de que deseas eliminar este personal?\")'>Eliminar</a></td>";
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