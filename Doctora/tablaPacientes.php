<?php
    session_start();

    include("../connection.php");
    include("../functions.php");

    $query = "select * from Pacientes";
    $result = mysqli_query($con, $query);
    
    
?>





<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Registros de Pacientes</title>
    <style>
        :root {
            --color-primario: #1a6fb5;
            --color-secundario: #f8f9fa;
            --color-terciario: #e9ecef;
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

        .page-title {
            font-weight: 600;
            margin: 0;
            font-size: 1.5rem;
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
        }

        .table td {
            padding: 8px 6px;
            vertical-align: middle;
        }

        .table tr:nth-child(even) {
            background-color: rgba(0,0,0,0.02);
        }

        .table tr:hover {
            background-color: rgba(26, 111, 181, 0.03);
        }

        .action-link {
            font-size: 0.8rem;
            padding: 3px 6px;
            margin: 0 2px;
        }

        .action-link i {
            margin-right: 3px;
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

        @media (max-width: 768px) {
            .page-title {
                font-size: 1.2rem;
            }
            
            .profile-button {
                padding: 5px 12px;
                font-size: 0.8rem;
            }
            
            .table {
                font-size: 0.8rem;
            }
            
            .table th, .table td {
                padding: 6px 4px;
            }
        }

        @media (max-width: 576px) {
            .card {
                max-width: 100%;
                border-radius: 0;
            }
            
            .header-container {
                padding: 10px 0;
            }
        }
    </style>
</head>
<body>
    <div class="header-container">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="page-title"><i class="fas fa-user-injured"></i> Pacientes</h1>
                <button class="profile-button" onclick="window.location.href='../verPerfil.php'">
                    <i class="fas fa-user-circle"></i> Perfil
                </button>
            </div>
        </div>
    </div>

    <div class="nav-buttons">
        <button class="nav-button" onclick="window.location.href='tablaTratamientos.php'">
            <i class="fas fa-pills"></i> Tratamientos
        </button>
        <button class="nav-button" onclick="window.location.href='tablaPersonal.php'">
            <i class="fas fa-user-shield"></i> Personal
        </button>
        <button class="nav-button" onclick="window.location.href='verCitasPacientes_Doctora.php'">
            <i class="fas fa-calendar-alt"></i> Citas
        </button>
    </div>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2 class="h6 mb-0">Listado de pacientes</h2>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Edad</th>
                                <th>Teléfono</th>
                                <th>Opciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                while($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>";
                                    echo "<td>".$row['IDpaciente']."</td>";
                                    echo "<td>".$row['nombre']."</td>";
                                    echo "<td>".$row['edad']."</td>";
                                    echo "<td>".$row['telefono']."</td>";
                                    echo "<td class='d-flex justify-content-center'>";
                                    echo "<a href='editarPaciente.php?id=".$row['IDpaciente']."' class='action-link' title='Editar'><i class='fas fa-edit'></i></a>";
                                    echo "<a href='eliminarPaciente.php?id=".$row['IDpaciente']."' class='action-link delete' title='Eliminar' onclick='return confirm(\"¿Eliminar este paciente?\")'><i class='fas fa-trash-alt'></i></a>";
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>