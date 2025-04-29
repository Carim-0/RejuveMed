<?php
session_start();
require_once('../connection.php');
require_once('../functions.php');

// Verificar login
$doctor_data = check_login($con);
$doctor_id = $_SESSION['user_id'];

function showSweetAlert($icon, $title, $text, $redirect = null) {
  echo "<script>
  document.addEventListener('DOMContentLoaded', function() {
      Swal.fire({
          icon: '$icon',
          title: '$title',
          text: '$text',
          confirmButtonColor: '#3085d6'
      })";
  if ($redirect) {
      echo ".then((result) => { if (result.isConfirmed) { window.location.href = '$redirect'; } })";
  }
  echo ";});
  </script>";
}

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

if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_message = "La cita se agendó correctamente.";
}

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
    $query_citas = "SELECT c.IDcita, c.fecha, t.nombre as tratamiento, c.estado, 
                TIMESTAMPDIFF(HOUR, c.fecha, c.fechaFin) as duracion_real 
                FROM Citas c
                JOIN Tratamientos t ON c.IDtratamiento = t.IDtratamiento
                WHERE c.IDpaciente = ?
                ORDER BY c.fecha ASC";
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
    $duracion = (int)$_POST['duracion']; // Use the selected duration
    $paciente_id = $_POST['paciente_id'];
    $estado = 'Pendiente';

     // Combinar fecha y hora
     $datetime = $fecha . ' ' . $hora . ':00';
            
     // Validar el timepo entre las 10 am y 6 pm
     $horaMin = '10:00:00';
     $horaMax = '18:00:00';
     $horaCompleta = $hora . ':00';
     
     if ($horaCompleta < $horaMin || $horaCompleta > $horaMax) {
         showSweetAlert('error', 'Error', 'La hora debe estar entre las 10:00 AM y 6:00 PM.', 'pacienteAgendarCita_Personal.php');
         exit;
     }

    // Check for overlapping appointments
    $query = "SELECT * FROM Citas WHERE (fecha <= ? AND fechaFin >= ?)";
    $stmt = $con->prepare($query);
    $stmt->bind_param("ss", $fechaFin, $datetime);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows > 0) {
        showSweetAlert('warning', 'Horario ocupado', 'Ya existe una cita en ese horario. Por favor, elija otro.', 'verCitasPacientes_Doctora.php');
    } else {
        // Insert the new appointment into the Citas table
        $query = "INSERT INTO Citas (fecha, fechaFin, IDpaciente, IDtratamiento, estado) VALUES (?, ?, ?, ?, ?)";
        $stmt = $con->prepare($query);
        $stmt->bind_param("sssis", $datetime, $fechaFin, $paciente_id, $IDtratamiento, $estado);

        if ($stmt->execute()) {
            header("Location: verCitasPacientes_Doctora.php?paciente_id=$paciente_id&success=1");
            exit;
        } else {
            $error_message = "Error al agendar la cita: " . $con->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vista Doctor - RejuveMed</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    :root {
      --primary-color: #4a6fa5;
      --secondary-color: #6b8cae;
      --accent-color: #4a6fa5;
      --light-color: #f8f9fa;
      --dark-color: #343a40;
      --success-color: #28a745;
      --warning-color: #ffc107;
      --danger-color: #dc3545;
      --border-radius: 8px;
      --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      background-color: #f0f2f5;
      display: flex;
      min-height: 100vh;
    }

    /* Sidebar Styles */
    .sidebar {
      width: 350px;
      background-color: white;
      border-right: 1px solid #e0e0e0;
      padding: 25px;
      overflow-y: auto;
    }

    .search-container {
      margin-bottom: 25px;
      position: relative;
    }

    .search-input {
      width: 100%;
      padding: 12px 15px;
      padding-left: 40px;
      border: 1px solid #ddd;
      border-radius: var(--border-radius);
      font-size: 15px;
      transition: all 0.3s;
    }

    .search-input:focus {
      border-color: var(--primary-color);
      outline: none;
      box-shadow: 0 0 0 3px rgba(74, 111, 165, 0.2);
    }

    .search-icon {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--secondary-color);
    }

    .sidebar-title {
      color: var(--primary-color);
      font-size: 20px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .patient-list {
      list-style: none;
    }

    .patient-item {
      padding: 15px;
      border-radius: var(--border-radius);
      margin-bottom: 10px;
      cursor: pointer;
      transition: all 0.3s;
      border-left: 3px solid transparent;
    }

    .patient-item:hover {
      background-color: rgba(74, 111, 165, 0.1);
    }

    .patient-item.active {
      background-color: rgba(74, 111, 165, 0.1);
      border-left: 3px solid var(--primary-color);
    }

    .patient-name {
      font-weight: 500;
      color: var(--dark-color);
      margin-bottom: 5px;
    }

    .patient-age {
      font-size: 14px;
      color: var(--secondary-color);
    }

    /* Main Content Styles */
    .main-content {
      flex: 1;
      padding: 30px;
      overflow-y: auto;
    }

    .patient-card {
      background-color: white;
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
      padding: 30px;
      margin-bottom: 30px;
    }

    .card-title {
      color: var(--primary-color);
      font-size: 24px;
      margin-bottom: 25px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 20px;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: var(--secondary-color);
      font-size: 14px;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: var(--border-radius);
      font-size: 15px;
    }

    .form-group input[readonly],
    .form-group textarea[readonly] {
      background-color: var(--light-color);
      color: #777;
      border-color: #eee;
    }

    .form-group textarea {
      min-height: 120px;
      resize: vertical;
    }

    .section-title {
      color: var(--primary-color);
      font-size: 18px;
      margin: 25px 0 15px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* Appointments List */
    .appointments-list {
      list-style: none;
      margin: 20px 0;
    }

    .appointment-item {
      padding: 15px;
      border-radius: var(--border-radius);
      margin-bottom: 15px;
      border-left: 4px solid var(--primary-color);
      background-color: rgba(74, 111, 165, 0.05);
    }

    .appointment-field {
      font-weight: 500;
      color: var(--secondary-color);
      font-size: 14px;
      margin-bottom: 5px;
    }

    .appointment-value {
      color: var(--dark-color);
      margin-bottom: 10px;
    }

    .appointment-actions {
      display: flex;
      justify-content: flex-end;
      margin-top: 10px;
    }

    .edit-link {
      color: var(--primary-color);
      text-decoration: none;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .edit-link:hover {
      text-decoration: underline;
    }

    /* New Appointment Form */
    .appointment-form {
      margin-top: 30px;
    }

    .form-actions {
      display: flex;
      justify-content: flex-end;
      margin-top: 20px;
    }

    .btn {
      padding: 12px 25px;
      border-radius: var(--border-radius);
      font-size: 16px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-primary {
      background-color: var(--primary-color);
      color: white;
      border: none;
    }

    .btn-primary:hover {
      background-color: #3a5a8a;
      transform: translateY(-2px);
    }

    /* Navigation Buttons */
    .nav-buttons {
      position: fixed;
      bottom: 20px;
      right: 20px;
      display: flex;
      gap: 15px;
    }

    .nav-button {
      padding: 12px 20px;
      background-color: var(--primary-color);
      color: white;
      border: none;
      border-radius: var(--border-radius);
      cursor: pointer;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .nav-button:hover {
      background-color: #3a5a8a;
    }

    /* Alerts */
    .alert {
      padding: 15px;
      margin-bottom: 25px;
      border-radius: var(--border-radius);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .alert-success {
      background-color: rgba(var(--success-color), 0.2);
      color: var(--success-color);
      border-left: 4px solid var(--success-color);
    }

    .alert-danger {
      background-color: rgba(var(--danger-color), 0.2);
      color: var(--danger-color);
      border-left: 4px solid var(--danger-color);
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 40px 0;
      color: #777;
    }

    .empty-state i {
      font-size: 48px;
      margin-bottom: 15px;
      color: var(--secondary-color);
    }

    /* Responsive */
    @media (max-width: 1024px) {
      body {
        flex-direction: column;
      }
      
      .sidebar {
        width: 100%;
        border-right: none;
        border-bottom: 1px solid #e0e0e0;
      }
      
      .nav-buttons {
        position: static;
        margin-top: 20px;
        justify-content: center;
      }
    }
  </style>
</head>

<body>
  <div class="sidebar">
    <form action="" method="GET" class="search-container">
      <i class="fas fa-search search-icon"></i>
      <input type="search" name="search" class="search-input" 
             placeholder="Buscar paciente..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
    </form>

    <h2 class="sidebar-title"><i class="fas fa-users"></i> Pacientes</h2>
    <ul class="patient-list">
      <?php if (!empty($pacientes)): ?>
        <?php foreach ($pacientes as $paciente): ?>
          <li class="patient-item <?= (isset($_GET['paciente_id']) && $_GET['paciente_id'] == $paciente['IDpaciente']) ? 'active' : '' ?>"
              onclick="window.location.href='?search=<?= urlencode($_GET['search'] ?? '') ?>&paciente_id=<?= $paciente['IDpaciente'] ?>'">
            <div class="patient-name"><?= htmlspecialchars($paciente['nombre']) ?></div>
            <div class="patient-age"><?= htmlspecialchars($paciente['edad']) ?> años</div>
          </li>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-state">
          <i class="fas fa-search"></i>
          <p>Ingrese un término de búsqueda</p>
        </div>
      <?php endif; ?>
    </ul>
  </div>

  <div class="main-content">
    <?php if (isset($paciente_actual)): ?>
      <div class="patient-card">
        <h2 class="card-title"><i class="fas fa-user-injured"></i> Información del Paciente</h2>
        
        <?php if (!empty($success_message)): ?>
          <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
          </div>
        <?php elseif (!empty($error_message)): ?>
          <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
          </div>
        <?php endif; ?>

        <div class="form-grid">
          <div class="form-group">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" readonly
                   value="<?= htmlspecialchars($paciente_actual['nombre'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label for="edad">Edad</label>
            <input type="text" id="edad" name="edad" readonly
                   value="<?= htmlspecialchars($paciente_actual['edad'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label for="telefono">Teléfono</label>
            <input type="text" id="telefono" name="telefono" readonly
                   value="<?= htmlspecialchars($paciente_actual['telefono'] ?? '') ?>">
          </div>
        </div>

        <h3 class="section-title"><i class="fas fa-info-circle"></i> Detalles</h3>
        <div class="form-group">
          <textarea id="detalles" name="detalles" readonly><?= 
            htmlspecialchars($paciente_actual['detalles'] ?? '') 
          ?></textarea>
        </div>

        <h3 class="section-title"><i class="fas fa-file-medical"></i> Historial Médico</h3>
        <div class="form-group">
          <textarea id="historial" name="historial" readonly><?= 
            htmlspecialchars($historial_medico['detalles'] ?? '') 
          ?></textarea>
        </div>

        <h3 class="section-title"><i class="fas fa-calendar-check"></i> Citas Agendadas</h3>
        <ul class="appointments-list">
    <?php if (!empty($citas)): ?>
        <?php foreach ($citas as $cita): ?>
            <li class="appointment-item">
                <div class="appointment-field">Fecha y Hora</div>
                <div class="appointment-value"><?= htmlspecialchars($cita['fecha']) ?></div>
                
                <div class="appointment-field">Tratamiento</div>
                <div class="appointment-value"><?= htmlspecialchars($cita['tratamiento']) ?></div>
                
                <div class="appointment-field">Estado</div>
                <div class="appointment-value"><?= htmlspecialchars($cita['estado']) ?></div>
                
                <div class="appointment-field">Duración</div>
                <div class="appointment-value"><?= htmlspecialchars($cita['duracion_real']) ?> hora<?= $cita['duracion_real'] > 1 ? 's' : '' ?></div>
                
                <div class="appointment-actions">
                    <a href="EditarCita.php?id=<?= $cita['IDcita'] ?>" class="edit-link">
                        <i class="fas fa-edit"></i> Editar Cita
                    </a>
                </div>
            </li>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="far fa-calendar-times"></i>
            <p>No hay citas agendadas</p>
        </div>
    <?php endif; ?>
</ul>

        <!-- Formulario de Nueva Cita -->
        <h3 class="section-title"><i class="fas fa-plus-circle"></i> Nueva Cita</h3>
        <form method="POST" id="citaForm" class="appointment-form">
          <input type="hidden" name="paciente_id" value="<?= $paciente_actual['IDpaciente'] ?>">
          
          <div class="form-grid">
            <div class="form-group">
              <label for="fecha">Fecha</label>
              <input type="date" id="fecha" name="fecha"  required min="<?php echo date('Y-m-d', strtotime('+1 days')); ?>" >
            </div>
            <div class="form-group">
              <label for="hora">Hora</label>
              <input type="time" id="hora" name="hora" required min="10:00" max="18:00"  step="3600">
            </div>
          </div>
         
          <div class="form-group">
            <label for="tratamiento">Tratamiento</label>
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

          <div class="form-grid">
            <div class="form-group">
              <label for="duracion">Duración</label>
              <select id="duracion" name="duracion" required>
                <option value="">Seleccione la duración</option>
              </select>
            </div>
            <div class="form-group">
              <label for="horaFin">Hora de Fin</label>
              <input type="text" id="horaFin" name="horaFin" readonly>
            </div>
          </div>

          <div class="form-actions">
            <button type="submit" name="agendar_cita" class="btn btn-primary">
              <i class="fas fa-calendar-plus"></i> Agendar Cita
            </button>
          </div>
        </form>
      </div>
    <?php else: ?>
      <div class="patient-card">
        <div class="empty-state">
          <i class="fas fa-user-injured"></i>
          <h2>Seleccione un paciente</h2>
          <p>Busque y seleccione un paciente de la lista para ver su información médica y citas.</p>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="nav-buttons">
    <button class="nav-button" onclick="window.location.href='tablaPersonal.php'">
      <i class="fas fa-user-shield"></i> Personal
    </button>
    <button class="nav-button" onclick="window.location.href='tablaTratamientos.php'">
      <i class="fas fa-pills"></i> Tratamientos
    </button>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
        const tratamientoSelect = document.getElementById("IDtratamiento");
        const duracionSelect = document.getElementById("duracion");
        const horaInput = document.getElementById("hora");
        const horaFinInput = document.getElementById("horaFin");

        // Populate the duracion combo box based on the selected treatment
        function updateDuracionOptions() {
            const selectedOption = tratamientoSelect.options[tratamientoSelect.selectedIndex];
            const maxDuracion = selectedOption.getAttribute("data-duracion");

            // Clear existing options
            duracionSelect.innerHTML = '<option value="">Seleccione la duración</option>';

            // Populate the combo box with values from 1 to maxDuracion
            if (maxDuracion) {
                for (let i = 1; i <= parseInt(maxDuracion, 10); i++) {
                    const option = document.createElement("option");
                    option.value = i;
                    option.textContent = `${i} hora${i > 1 ? 's' : ''}`;
                    duracionSelect.appendChild(option);
                }
            }

            // Clear hora de fin when treatment changes
            horaFinInput.value = "";
        }

        // Calculate and update hora de fin based on selected duration
        function updateHoraFin() {
            const selectedDuracion = parseInt(duracionSelect.value, 10);
            const horaInicio = horaInput.value;

            if (selectedDuracion && horaInicio) {
                const [hours, minutes] = horaInicio.split(":").map(Number);
                const horaFin = new Date();
                horaFin.setHours(hours + selectedDuracion, minutes);
                horaFinInput.value = `${horaFin.getHours().toString().padStart(2, '0')}:${horaFin.getMinutes().toString().padStart(2, '0')}`;
            } else {
                horaFinInput.value = "";
            }
        }

        // Attach event listeners
        tratamientoSelect.addEventListener("change", updateDuracionOptions);
        duracionSelect.addEventListener("change", updateHoraFin);
        horaInput.addEventListener("input", updateHoraFin);
    });
</script>
</body>
</html>
<?php $con->close(); ?>
