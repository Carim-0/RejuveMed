<?php
    session_start();

    include("../connection.php");
    include("../functions.php");

    $query = "select * from Pacientes";
    $result = mysqli_query($con, $query);
    
    
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../bootstrap.min.css">
    
    <title>Registros de Pacientes</title>

    <style >
         :root {
            --color-primario: #1a37b5; /*Azul para personal*/
            --color-secundario: #f8f9fa;
            --color-terciario: #e9ecef;
            --color-exito: #28a745;
            --color-error: #dc3545;
            --color-texto: #212529;
            --color-borde: #e0e0e0;
        }

        
        body {
            background-color: var(--color-secundario);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-bottom: 20px;
        }

        
        .header-container {
            background-color: var(--color-primario);
            color: white;
            padding: 15px 0;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .header-container h1 {
            color:rgb(255, 255, 255); 
        }


        .nav-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 15px 0;
            flex-wrap: wrap;
        }

        .nav-button {
            background-color: var(--color-primario);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 15px;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .nav-button:hover {
            background-color: #142a8a;
            transform: translateY(-2px);
        }

        .profile-button {
            background-color: white;
            color: var(--color-primario);
            border: none;
            border-radius: 50px;
            padding: 6px 15px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s;
            position: absolute;
            top: 15px;
            right: 15px;
        }

        .profile-button:hover {
            background-color: #f0f0f0;
            transform: translateY(-2px);
        }

        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin: 0 auto;
            max-width: 95%;
        }

        .card-header {
            background-color: var(--color-primario);
            color: white;
            padding: 12px 15px;
            border-bottom: none;
        }

        .card-header h2 {
            font-size: 1.1rem;
            margin: 0;
            text-align: center;
            color:rgb(255, 255, 255);
        }

        .table {
            font-size: 0.9rem;
            margin-bottom: 0;
        }

        .table thead {
            background-color: var(--color-primario);
            color: white;
        }

        .table th {
            padding: 8px 6px;
            font-weight: 500;
            font-size: 0.85rem;
            text-align: center;
        }

        .table td {
            padding: 8px 6px;
            vertical-align: middle;
            text-align: center;
        }

        .table tr:nth-child(even) {
            background-color: rgba(0,0,0,0.02);
        }

        .table tr:hover {
            background-color: rgba(26, 55, 181, 0.03);
        }

        .action-link {
            font-size: 0.8rem;
            padding: 3px 6px;
            margin: 0 2px;
            color: var(--color-primario);
            text-decoration: none;
        }

        .action-link.delete {
            color: var(--color-error);
        }

        .action-link:hover {
            text-decoration: underline;
        }

        .action-link i {
            margin-right: 3px;
        }

        .add-button {
            text-align: center;
            margin: 20px 0;
        }

        .add-button button {
            background-color: var(--color-exito);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 15px;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .add-button button:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .table {
                font-size: 0.8rem;
            }
            
            .table th, .table td {
                padding: 6px 4px;
            }
            
            .nav-button, .add-button button {
                padding: 6px 12px;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 576px) {
            .card {
                max-width: 100%;
                border-radius: 0;
            }
            
            .profile-button {
                position: relative;
                top: auto;
                right: auto;
                display: block;
                margin: 10px auto;
                width: fit-content;
            }
        }
    </style>
</head>
<body>

<div class="header-container">
        <div class="container">
            <h1 class="text-center mb-0"><i class="fa-solid fa-user"></i>Personal</h1>
            <button class="profile-button" onclick="window.location.href='../verPerfil.php'">
                <i class="fas fa-user-circle"></i> Mi Perfil
            </button>
        </div>
    </div>

    <div class="nav-buttons">
        <button class="nav-button" onclick="window.location.href='verCitasPacientes_Personal.php'">
            <i class="fas fa-calendar-alt"></i> Citas
        </button>
    </div>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2 class="h6 mb-0">Pacientes registrados</h2>
                    </div>

                    <div class="card-card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                            <thead>
                            <tr>
                                <td>ID del paciente</td>
                                <td>Nombre</td>
                                <td>Edad</td>
                                <td>Telefono</td>
                                <td>Editar</td>
                                <td>Eliminar</td>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                                while($row = mysqli_fetch_assoc($result))
                                {
                                    echo "<tr>";
                                    echo "<td>".$row['IDpaciente']."</td>";
                                    echo "<td>".$row['nombre']."</td>";
                                    echo "<td>".$row['edad']."</td>";
                                    echo "<td>".$row['telefono']."</td>";
                                    echo "<td>";
                                    echo "<a href='editarPaciente.php?id=".$row['IDpaciente']."'class='action-link' title='Editar'><i class='fas fa-edit'></i>Editar</a>";
                                    echo "<a href='eliminarPaciente.php?id=".$row['IDpaciente']."' class='action-link delete' title='Eliminar' onclick='return confirm(\"¿Estás seguro de que deseas eliminar este paciente?\")'><i class='fas fa-trash-alt'></i>Eliminar</a>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>