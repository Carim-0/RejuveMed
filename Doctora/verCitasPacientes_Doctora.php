<?php
session_start();
require_once('../connection.php');
require_once('../functions.php');

// Verificar login
$doctor_data = check_login($con);
$doctor_id = $_SESSION['user_id'];

// Procesar búsqueda de pacientes
$pacientes = [];
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    $search_term = $_GET['search'];
    $query = "SELECT IDpaciente, nombre, edad, telefono, detalles FROM Pacientes 
              WHERE nombre LIKE ? OR telefono LIKE ?";
    $stmt = $con->prepare($query);
    $search_param = "%$search_term%";
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    $pacientes = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Obtener datos de un paciente específico
$paciente_actual = null;
$historial_medico = null;
$citas = [];
$error_message = '';
$success_message = '';

if (isset($_GET['paciente_id'])) {
    $paciente_id = $_GET['paciente_id'];

    // Query para obtener detalles del paciente
    $query = "SELECT * FROM Pacientes WHERE IDpaciente = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $paciente_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $paciente_actual = $result->fetch_assoc();
    $stmt->close();

    // Query para obtener historial médico
    $query_historial = "SELECT detalles FROM `Historial Medico` WHERE IDpaciente = ?";
    $stmt = $con->prepare($query_historial);
    $stmt->bind_param("i", $paciente_id);
    $stmt->execute();
    $result_historial = $stmt->get_result();
    $historial_medico = $result_historial->fetch_assoc();
    $stmt->close();

    // Obtener citas del paciente
    $query_citas = "SELECT c.IDcita, c.fecha, t.nombre as tratamiento, c.estado, t.duracion 
                    FROM Citas c
                    JOIN Tratamientos t ON c.IDtratamiento = t.IDtratamiento
                    WHERE c.IDpaciente = ?
                    ORDER BY c.fecha ASC"; // Order by fecha in ascending order
    $stmt = $con->prepare($query_citas);
    $stmt->bind_param("i", $paciente_id);
    $stmt->execute();
    $citas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Procesar agendamiento de nueva cita
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['agendar_cita'])) {
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $IDtratamiento = $_POST['IDtratamiento'];
    $paciente_id = $_POST['paciente_id'];
    $estado = 'Pendiente';

    // Fetch the treatment duration
    $tratamiento_query = "SELECT duracion FROM Tratamientos WHERE IDtratamiento = ?";
    $stmt = $con->prepare($tratamiento_query);
    $stmt->bind_param("i", $IDtratamiento);
    $stmt->execute();
    $tratamiento_result = $stmt->get_result();
    $tratamiento = $tratamiento_result->fetch_assoc();
    $stmt->close();

    if (!$tratamiento) {
        $error_message = "El tratamiento seleccionado no es válido.";
    } else {
        $duracion = (int)$tratamiento['duracion'];




            // Combine date and time into a single datetime value
            $datetime = $fecha . ' ' . $hora;

           // Get the current date
        $currentDate = date('Y-m-d');

        // Validate that the selected date is not in the past and not the same as today
        if ($fecha <= $currentDate) {
            echo "<script>alert('La fecha debe ser un día futuro. No puede ser hoy ni una fecha pasada.');</script>";
            echo "<script>window.location.href = 'verCitasPacientes_Doctora.php';</script>";
            exit;
            }

            // Calculate fechaFin by adding the duration to the start time
            $startDateTime = new DateTime($datetime);
            $startDateTime->modify("+$duracion hours");
            $fechaFin = $startDateTime->format('Y-m-d H:i:s');

            // Validate that the hour is within the allowed range
            if ($hora < "10:00:00" || $hora > "18:00:00" || $startDateTime->format('H:i') > "18:00") {
              echo "<script>alert('La hora debe estar entre las 10:00 AM y las 6:00 PM.');</script>";
            echo "<script>window.location.href = 'verCitasPacientes_Doctora.php';</script>";
            exit;
            } 

            // Check for overlapping appointments
            $query = "SELECT * FROM Citas WHERE 
                      (fecha <= ? AND fechaFin >= ?) AND IDpaciente = ?";
            $stmt = $con->prepare($query);
            $stmt->bind_param("ssi", $fechaFin, $datetime, $paciente_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();

            if ($result->num_rows > 0) {
              echo "<script>alert('Ya existe una cita en ese horario. Por favor, elija otro horario.');</script>";
            echo "<script>window.location.href = 'verCitasPacientes_Doctora.php';</script>";
                
            } else {
                // Insert the new appointment into the Citas table
                $query = "INSERT INTO Citas (fecha, fechaFin, IDpaciente, IDtratamiento, estado) VALUES (?, ?, ?, ?, ?)";
                $stmt = $con->prepare($query);
                $estado = 'Pendiente'; // Set the default value for estado
                $stmt->bind_param("sssis", $datetime, $fechaFin, $paciente_id, $IDtratamiento, $estado);

                if ($stmt->execute()) {
                    $success_message = "La cita se agendó correctamente.";
                } else {
                    $error_message = "Error al agendar la cita: " . $con->error;
                }
                $stmt->close();
                
            }
        
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vista Doctor - RejuveMed</title>
  <style>
    :root {
      --color-primario: #1a37b5;
      --color-secundario: #FFF9EB;
      --color-terciario: #C4C4C4;
      --color-button: #fe652b;
      --color-button-hover: #501801;
      --color-text: #444444;
      --color-fondo: #e2dfdf;
      --color-white: #FFFFFF;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: Arial, sans-serif;
      display: flex;
      background-color: #f5f5f5;
      border: 0;
      box-sizing: border-box;
      padding: 20px;
      min-height: 100vh;
    }

    .search-bar input,
    .search-btn,
    .search-btn:before,
    .search-btn:after {
      transition: all 0.25s ease-out;
    }

    .search-bar input,
    .search-btn {
      width: 3em;
      height: 3em;
    }

    .search-bar input:invalid:not(:focus),
    .search-btn {
      cursor: pointer;
    }

    .search-bar,
    .search-bar input:focus,
    .search-bar input:valid {
      width: 80%;
    }

    .search-bar input:focus,
    .search-bar input:not(:focus)+.search-btn:focus {
      outline: transparent;
    }

    .search-bar {
      margin: auto;
      padding: 1.5em;
      justify-content: center;
      max-width: 20em;
    }

    .search-bar input {
      background: transparent;
      border-radius: 1.5em;
      box-shadow: 0 0 0 0.4em #171717 inset;
      padding: 0.75em;
      transform: translate(0.5em, 0.5em) scale(0.5);
      transform-origin: 100% 0;
      -webkit-appearance: none;
      -moz-appearance: none;
      appearance: none;
    }

    .search-bar input::-webkit-search-decoration {
      -webkit-appearance: none;
    }

    .search-bar input:focus,
    .search-bar input:valid {
      background: #fff;
      border-radius: 0.375em 0 0 0.375em;
      box-shadow: 0 0 0 0.1em #d9d9d9 inset;
      transform: scale(1);
    }

    .search-btn {
      background: #171717;
      border-radius: 0 0.75em 0.75em 0 / 0 1.5em 1.5em 0;
      padding: 0.75em;
      position: relative;
      transform: translate(0.25em, 0.25em) rotate(45deg) scale(0.25, 0.125);
      transform-origin: 0 50%;
    }

    .search-btn:before,
    .search-btn:after {
      content: "";
      display: block;
      opacity: 0;
      position: absolute;
    }

    .search-btn:before {
      border-radius: 50%;
      box-shadow: 0 0 0 0.2em #f1f1f1 inset;
      top: 0.75em;
      left: 0.75em;
      width: 1.2em;
      height: 1.2em;
    }

    .search-btn:after {
      background: #f1f1f1;
      border-radius: 0 0.25em 0.25em 0;
      top: 51%;
      left: 51%;
      width: 0.75em;
      height: 0.25em;
      transform: translate(0.2em, 0) rotate(45deg);
      transform-origin: 0 50%;
    }

    .search-btn span {
      display: inline-block;
      overflow: hidden;
      width: 1px;
      height: 1px;
    }

    /* Active state */
    .search-bar input:focus+.search-btn,
    .search-bar input:valid+.search-btn {
      background: #2762f3;
      border-radius: 0 0.375em 0.375em 0;
      transform: scale(1);
    }

    .search-bar input:focus+.search-btn:before,
    .search-bar input:focus+.search-btn:after,
    .search-bar input:valid+.search-btn:before,
    .search-bar input:valid+.search-btn:after {
      opacity: 1;
    }

    .search-bar input:focus+.search-btn:hover,
    .search-bar input:valid+.search-btn:hover,
    .search-bar input:valid:not(:focus)+.search-btn:focus {
      background: #0c48db;
    }

    .search-bar input:focus+.search-btn:active,
    .search-bar input:valid+.search-btn:active {
      transform: translateY(1px);
    }

    /* Barra lateral (lista de pacientes) */
    .sidebar {
      width: 30%;
      background-color: #f1f1f1;
      padding: 1rem;
      overflow-y: auto;
    }

    .sidebar h2 {
      margin-bottom: 1rem;
      margin-top: 0;
      color: #0066cc;
    }

    .patient-information {
      border: 1px solid #ccc;
      border-radius: 8px;
      padding: 20px;
      background-color: white;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .patient-list {
      list-style: none;
    }

    .patient-list li {
      padding: 0.8rem;
      border-bottom: 1px solid #ccc;
      cursor: pointer;
      transition: background-color 0.2s;
    }

    .patient-list li:hover {
      background-color: #e0e0e0;
    }

    .patient-list li.active {
      background-color: #0066cc;
      color: white;
    }

    /* Contenido principal (formulario y detalles) */
    .main-content {
      flex: 1;
      padding: 1rem;
      overflow-y: auto;
    }

    .form-container {
      display: flex;
      gap: 30px;
      margin-left: 20px;
      margin-top: 20px;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      margin-bottom: 15px;
      width: 100%;
    }

    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
      color: #555;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 0.5rem;
      border: 1px solid #ccc;
      border-radius: 4px;
    }

    .form-group input[readonly] {
      background-color: #f5f5f5;
    }

    .appointments-list {
      list-style: none;
      margin: 1rem 0;
      padding: 0;
      border: 1px solid #ccc;
      border-radius: 4px;
      color: #555;
    }

    .appointments-list li {
      padding: 0.8rem;
      border-bottom: 1px solid #eee;
      color: #555;
    }

    .appointments-list li:last-child {
      border-bottom: none;
    }

    /* Encabezado de sección */
    h3 {
      margin-top: 1.5rem;
      margin-bottom: 0.5rem;
      color: #0066cc;
    }

    /* Botones */
    .actions {
      margin-top: 1.5rem;
      display: flex;
      gap: 10px;
    }

    .btn {
      padding: 0.6rem 1.2rem;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-weight: bold;
      transition: background-color 0.3s;
    }

    .btn-save {
      background-color: #28a745;
      color: #fff;
    }

    .btn-save:hover {
      background-color: #218838;
    }

    .btn-delete {
      background-color: #dc3545;
      color: #fff;
    }

    .btn-delete:hover {
      background-color: #c82333;
    }

    /* Mensajes */
    .alert {
      padding: 10px;
      margin-bottom: 15px;
      border-radius: 4px;
    }

    .alert-success {
      background-color: #d4edda;
      color: #155724;
    }

    .alert-danger {
      background-color: #f8d7da;
      color: #721c24;
    }

    /* Responsive */
    @media (max-width: 768px) {
      body {
        flex-direction: column;
      }
      
      .sidebar {
        width: 100%;
      }
    }

    .bottom-buttons {
      position: fixed;
      bottom: 20px;
      left: 20px;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .bottom-buttons button {
      padding: 10px 20px;
      background-color: #007bff;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 14px;
    }

    .bottom-buttons button:hover {
      background-color: #0056b3;
    }
  </style>
</head>

<body>
  <div class="sidebar">
    <form action="" method="GET" class="search-bar">
      <input type="search" name="search" pattern=".*\S.*" required 
             placeholder="Buscar paciente..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
      <button class="search-btn" type="submit">
        <span>Buscar</span>
      </button>
    </form>

    <h2>Pacientes:</h2>
    <ul class="patient-list">
      <?php if (!empty($pacientes)): ?>
        <?php foreach ($pacientes as $paciente): ?>
          <li class="<?= (isset($_GET['paciente_id']) && $_GET['paciente_id'] == $paciente['IDpaciente']) ? 'active' : '' ?>"
              onclick="window.location.href='?search=<?= urlencode($_GET['search'] ?? '') ?>&paciente_id=<?= $paciente['IDpaciente'] ?>'">
            <?= htmlspecialchars($paciente['nombre']) ?> 
            (<?= htmlspecialchars($paciente['edad']) ?> años)
          </li>
        <?php endforeach; ?>
      <?php else: ?>
        <li>Ingrese un término de búsqueda</li>
      <?php endif; ?>
    </ul>
  </div>

  <div class="main-content">
    <?php if (isset($paciente_actual)): ?>
      <div class="patient-information">
        <h2>Información del Paciente</h2>
        
        <?php if (!empty($success_message)): ?>
          <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php elseif (!empty($error_message)): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <div class="patient-details">
          <div class="form-group">
            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre" readonly
                   value="<?= htmlspecialchars($paciente_actual['nombre'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label for="edad">Edad:</label>
            <input type="text" id="edad" name="edad" readonly
                   value="<?= htmlspecialchars($paciente_actual['edad'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label for="telefono">Teléfono:</label>
            <input type="text" id="telefono" name="telefono" readonly
                   value="<?= htmlspecialchars($paciente_actual['telefono'] ?? '') ?>">
          </div>

          <h3>Detalles</h3>
          <div class="form-group">
            <textarea id="detalles" name="detalles" readonly rows="4"><?= 
              htmlspecialchars($paciente_actual['detalles'] ?? '') 
            ?></textarea>
          </div>

          <h3>Historial Medico</h3>
          <div class="form-group">
            <textarea id="historial" name="historial" readonly rows="4"><?= 
              htmlspecialchars($historial_medico['detalles'] ?? '') 
            ?></textarea>
          </div>

          <h3>Citas Agendadas</h3>
          <ul class="appointments-list">
            <?php if (!empty($citas)): ?>
              <?php foreach ($citas as $cita): ?>
                <li>
                  <strong>Fecha:</strong> <?= htmlspecialchars($cita['fecha']) ?><br>
                  <strong>Tratamiento:</strong> <?= htmlspecialchars($cita['tratamiento']) ?><br>
                  <strong>Estado:</strong> <?= htmlspecialchars($cita['estado']) ?><br>
                  <strong>Duración:</strong> <?= htmlspecialchars($cita['duracion']) ?> <strong>hrs</strong>
                  <a href="EditarCita.php?id=<?= $cita['IDcita'] ?>" title="Editar">✏️</a>
                </li>
              <?php endforeach; ?>
            <?php else: ?>
              <li>No hay citas agendadas</li>
            <?php endif; ?>
          </ul>
        </div>
        
          <!-- Detalles de nueva cita -->
        <h3>Nueva Cita</h3>
        <form method="POST" id="citaForm">
          <input type="hidden" name="paciente_id" value="<?= $paciente_actual['IDpaciente'] ?>">
          
          <div class="form-container">
            <div class="form-group">
              <label for="fecha">Fecha:</label>
              <input type="date" id="fecha" name="fecha" required>
            </div>
            <div class="form-group">
              <label for="hora">Hora:</label>
              <input type="time" id="hora" name="hora" required>
            </div>
          </div>
         
          <div class="form-group">
            <label for="tratamiento">Tratamiento:</label>
            <select id="IDtratamiento" name="IDtratamiento" required>
                <option value="">Seleccione un tratamiento</option>
                <?php
                $query = "SELECT IDtratamiento, nombre, duracion FROM Tratamientos";
                $result = $con->query($query);
                while ($tratamiento = $result->fetch_assoc()):
                ?>
                    <option value="<?= $tratamiento['IDtratamiento'] ?>" data-duracion="<?= $tratamiento['duracion'] ?>">
                        <?= htmlspecialchars($tratamiento['nombre']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="duracion">Duración (horas):</label>
            <input type="text" id="duracion" name="duracion" readonly>
          </div>
          <div class="form-group">
            <label for="horaFin">Hora de Fin:</label>
            <input type="text" id="horaFin" name="horaFin" readonly>
          </div>

          <div class="actions">
              <button type="submit" name="agendar_cita" class="btn btn-save">Agendar Cita</button>
          </div>
        </form>
      </div>
    <?php else: ?>
      <div class="patient-information">
        <h2>Seleccione un paciente</h2>
        <p>Busque y seleccione un paciente de la lista para ver su información médica y citas.</p>
      </div>
    <?php endif; ?>
  </div>

  <div class="bottom-buttons">
    <button onclick="window.location.href='tablaPersonal.php'">Ir a Personal</button>
    <button onclick="window.location.href='tablaTratamientos.php'">Ir a Tratamientos</button>
  </div>

  <script>
    document.querySelector('.search-bar input').focus();
    
    document.getElementById('citaForm')?.addEventListener('submit', function(e) {
      const fecha = document.getElementById('fecha').value;
      const hora = document.getElementById('hora').value;
      
      if (!fecha || !hora) {
        e.preventDefault();
        alert('Por favor complete todos los campos requeridos');
        return;
      }
      
      
      
      
    });

    document.addEventListener("DOMContentLoaded", function () {
        const tratamientoSelect = document.getElementById("IDtratamiento");
        const horaInput = document.getElementById("hora");
        const duracionInput = document.getElementById("duracion");
        const horaFinInput = document.getElementById("horaFin");

        // Update duration and horaFin when the treatment or hora changes
        function updateFields() {
            const selectedOption = tratamientoSelect.options[tratamientoSelect.selectedIndex];
            const duracion = selectedOption.getAttribute("data-duracion");
            const horaInicio = horaInput.value;

            // Update duracion field
            duracionInput.value = duracion ? `${duracion} horas` : "";

            // Calculate and update horaFin field
            if (duracion && horaInicio) {
                const [hours, minutes] = horaInicio.split(":").map(Number);
                const duracionHoras = parseInt(duracion, 10);
                const horaFin = new Date();
                horaFin.setHours(hours + duracionHoras, minutes);
                horaFinInput.value = `${horaFin.getHours().toString().padStart(2, '0')}:${horaFin.getMinutes().toString().padStart(2, '0')}`;
            } else {
                horaFinInput.value = "";
            }
        }

        // Attach event listeners
        tratamientoSelect.addEventListener("change", updateFields);
        horaInput.addEventListener("input", updateFields);
    });
  </script>
</body>
</html>
<?php $con->close(); ?>