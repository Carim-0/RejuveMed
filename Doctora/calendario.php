<?php
session_start();
include("../connection.php");

// Fetch data from the Citas table
$query = "SELECT IDcita, fecha, estado, IDpaciente, IDtratamiento FROM Citas";
$result = mysqli_query($con, $query);

$events = [];
while ($row = mysqli_fetch_assoc($result)) {
    $events[] = [
        'id' => $row['IDcita'],
        'title' => 'Cita - ' . $row['estado'],
        'start' => $row['fecha'],
        'url' => 'verCitasPacientes_Doctora.php?paciente_id=' . $row['IDpaciente']
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
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        .nav-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            padding: 15px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #ddd;
        }

        .nav-button {
            padding: 10px 20px;
            font-size: 16px;
            color: #fff;
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background-color 0.3s ease;
        }

        .nav-button i {
            font-size: 18px;
        }

        .nav-button:hover {
            background-color: #0056b3;
        }

        #calendar {
            max-width: 100%;
            margin: 20px auto;
            height: calc(100vh - 80px); /* Adjust height to account for navigation buttons */
        }
    </style>
</head>
<body>
    <!-- Navigation Buttons -->
    <div class="nav-buttons">
        <button class="nav-button" onclick="window.location.href='tablaTratamientos.php'">
            <i class="fas fa-pills"></i> Tratamientos
        </button>
        <button class="nav-button" onclick="window.location.href='tablaPacientes.php'">
            <i class="fas fa-user-injured"></i> Pacientes
        </button>
        <button class="nav-button" onclick="window.location.href='tablaPersonal.php'">
            <i class="fas fa-user-shield"></i> Personal
        </button>
        <button class="nav-button" onclick="window.location.href='verCitasPacientes_Doctora.php'">
            <i class="fas fa-calendar-alt"></i> Citas
        </button>
    </div>

    <!-- Calendar -->
    <div id="calendar"></div>

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
                events: <?= $events_json ?>, // Load events from PHP
                eventClick: function (info) {
                    // Open the event URL when clicked
                    if (info.event.url) {
                        window.open(info.event.url, '_blank');
                        info.jsEvent.preventDefault();
                    }
                }
            });

            calendar.render();
        });
    </script>
</body>
</html>