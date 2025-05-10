<?php
session_start();
include("../connection.php");

// Fetch data from the Citas table
$query = "SELECT c.IDcita, c.fecha, c.fechaFin, c.estado, c.IDpaciente, t.nombre as tratamiento, p.nombre as paciente 
          FROM Citas c
          JOIN Tratamientos t ON c.IDtratamiento = t.IDtratamiento
          JOIN Pacientes p ON c.IDpaciente = p.IDpaciente
          WHERE c.estado = 'Pendiente'";
$result = mysqli_query($con, $query);

$events = [];
while ($row = mysqli_fetch_assoc($result)) {
    $startTime = date('H:i', strtotime($row['fecha']));
    $endTime = date('H:i', strtotime($row['fechaFin']));
    $events[] = [
        'id' => $row['IDcita'],
        'title' => $startTime . ' - ' . $endTime . "\n" . $row['tratamiento'] . "\nPaciente: " . $row['paciente'],
        'start' => $row['fecha'],
        'url' => 'verCitasPacientes_Personal.php?paciente_id=' . $row['IDpaciente']
    ];
}

// Convert events to JSON format
$events_json = json_encode($events);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario de Citas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a6fa5;
            --secondary-color: #6b8cae;
            --accent-color: #4a6fa5;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --success-color: #28a745;
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .nav-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            padding: 20px;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .nav-button {
            padding: 12px 24px;
            font-size: 16px;
            color: white;
            background-color: var(--primary-color);
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-button i {
            font-size: 18px;
        }

        .nav-button:hover {
            background-color: #3a5a8a;
            transform: translateY(-2px);
        }

        .page-title {
            color: var(--primary-color);
            text-align: center;
            margin: 20px 0;
            font-size: 28px;
            font-weight: 600;
        }

        #calendar {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            height: calc(100vh - 200px);
        }

        /* FullCalendar Custom Styles */
        .fc-toolbar-title {
            color: var(--primary-color);
            font-weight: 600;
        }

        .fc-button {
            background-color: var(--primary-color) !important;
            border: none !important;
            border-radius: var(--border-radius) !important;
            transition: all 0.3s ease !important;
        }

        .fc-button:hover {
            background-color: #3a5a8a !important;
        }

        .fc-button-active {
            background-color: var(--secondary-color) !important;
        }

        .fc-event {
            background-color: var(--primary-color) !important;
            border: none !important;
            border-radius: 4px !important;
            padding: 3px 5px !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
        }

        .fc-event:hover {
            background-color: #3a5a8a !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1) !important;
        }

        .fc-daygrid-event-dot {
            border-color: white !important;
        }

        .fc-day-today {
            background-color: rgba(74, 111, 165, 0.1) !important;
        }

        @media (max-width: 768px) {
            .nav-buttons {
                gap: 10px;
                padding: 15px;
            }
            
            .nav-button {
                padding: 10px 15px;
                font-size: 14px;
            }
            
            #calendar {
                height: calc(100vh - 180px);
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="page-title">Calendario de Citas</h1>
        
        <!-- Navigation Buttons -->
        <div class="nav-buttons">
            <button class="nav-button" onclick="window.location.href='pacienteAgendarCita_Personal.php'">
                <i class="fas fa-book"></i> Agendar cita
            </button>
            
        </div>

        <!-- Calendar -->
        <div id="calendar"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var calendarEl = document.getElementById('calendar');

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth', // Month view
                locale: 'es', // Spanish locale
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                buttonText: {
                    today: 'Hoy',
                    month: 'Mes',
                    week: 'Semana',
                    day: 'Día'
                },
                events: <?= $events_json ?>, // Load events from PHP
                eventClick: function (info) {
                    // Redirect to the event's URL
                    if (info.event.url) {
                        window.location.href = info.event.url;
                        info.jsEvent.preventDefault(); // Prevent default browser behavior
                    }
                },
                eventContent: function (info) {
                    return {
                        html: `<div>${info.event.title.replace(/\n/g, "<br>")}</div>`
                    };
                }
            });

            calendar.render();
        });
    </script>
</body>
</html>