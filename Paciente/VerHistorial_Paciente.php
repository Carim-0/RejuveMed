<?php
session_start();
include("../connection.php");
include("../functions.php");

// Verificación de usuario
$user_data = check_login($con);
if ($_SESSION['user_type'] !== 'Paciente') {
    header("Location: ../login.php");
    die();
}

$id_paciente = $user_data['IDpaciente'];
$fecha_actual = date('Y-m-d H:i:s');

// Consulta optimizada
$query = "SELECT c.IDcita, c.fecha, t.nombre as tratamiento
          FROM Citas c
          JOIN Tratamientos t ON c.idtratamiento = t.IDtratamiento
          WHERE c.idpaciente = ? AND c.fecha < ?
          ORDER BY c.fecha DESC";
$stmt = $con->prepare($query);
$stmt->bind_param("is", $id_paciente, $fecha_actual);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Historial Médico | VisionClinic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-primario: #1a37b5;
            --color-primario-hover: #142a8a;
            --color-secundario: #ffffff;
            --color-fondo: #f8fafc;
            --color-texto: #2d3748;
            --color-texto-claro: #718096;
            --color-borde: #e2e8f0;
            --sombra: 0 4px 6px rgba(0, 0, 0, 0.05);
            --sombra-elevada: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--color-fondo);
            color: var(--color-texto);
            line-height: 1.6;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: var(--sombra-elevada);
        }

        .header {
            background: linear-gradient(135deg, var(--color-primario) 0%, #0d1b3e 100%);
            color: white;
            padding: 2rem 0;
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiPjxkZWZzPjxwYXR0ZXJuIGlkPSJwYXR0ZXJuIiB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHBhdHRlcm5Vbml0cz0idXNlclNwYWNlT25Vc2UiIHBhdHRlcm5UcmFuc2Zvcm09InJvdGF0ZSg0NSkiPjxyZWN0IHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgZmlsbD0icmdiYSgyNTUsMjU1LDI1NSwwLjA1KSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3QgZmlsbD0idXJsKCNwYXR0ZXJuKSIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIvPjwvc3ZnPg==');
            opacity: 0.3;
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
        }

        .header p {
            font-size: 1rem;
            opacity: 0.9;
            position: relative;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin: 1.5rem 0;
            background-color: var(--color-primario);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: var(--sombra);
            border: none;
            cursor: pointer;
            font-weight: 500;
        }

        .back-button:hover {
            background-color: var(--color-primario-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .historial-container {
            padding: 2rem;
            margin-bottom: 3rem;
        }

        .historial-item {
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .historial-item::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--color-primario);
            transition: all 0.3s ease;
        }

        .historial-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .historial-item:hover::before {
            width: 6px;
            background: linear-gradient(to bottom, var(--color-primario), #3b82f6);
        }

        .historial-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .tratamiento-nombre {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--color-primario);
            position: relative;
            padding-left: 1.5rem;
        }

        .tratamiento-nombre::before {
            content: "•";
            position: absolute;
            left: 0;
            color: var(--color-primario);
            font-size: 1.5rem;
        }

        .tratamiento-fecha {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: var(--color-texto-claro);
            background: rgba(226, 232, 240, 0.5);
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
        }

        .no-historial {
            text-align: center;
            padding: 3rem;
            color: var(--color-texto-claro);
        }

        .no-historial i {
            font-size: 3rem;
            color: var(--color-borde);
            margin-bottom: 1rem;
        }

        .no-historial h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--color-texto);
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.5rem;
            }
            
            .historial-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .tratamiento-fecha {
                align-self: flex-start;
            }
            
            .historial-container {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1><i class="fas fa-file-medical"></i> Historial Médico</h1>
            <p>Registro completo de tus procedimientos realizados</p>
        </div>
    </div>

    <div class="container">
        <button onclick="window.location.href='catalogoTratamientos.php'" class="back-button glass-card">
            <i class="fas fa-arrow-left"></i> Volver al Panel
        </button>

        <div class="historial-container glass-card">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="historial-item glass-card">
                        <div class="historial-header">
                            <span class="tratamiento-nombre"><?php echo htmlspecialchars($row['tratamiento']); ?></span>
                            <span class="tratamiento-fecha">
                                <i class="far fa-calendar-alt"></i> 
                                <?php echo date('d/m/Y H:i', strtotime($row['fecha'])); ?>
                            </span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-historial glass-card">
                    <i class="fas fa-file-import"></i>
                    <h3>Historial Vacío</h3>
                    <p>No se encontraron procedimientos realizados en tu historial</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Efecto de carga futurista
        document.addEventListener('DOMContentLoaded', () => {
            const items = document.querySelectorAll('.historial-item');
            items.forEach((item, index) => {
                setTimeout(() => {
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>