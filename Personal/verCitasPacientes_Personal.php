<?php
session_start();
require_once('../connection.php');
require_once ('../functions.php');

// Verificar login (debes adaptar esta función para el doctor)
$personal_data = check_login($con);
$personal_id = $_SESSION['user_id'];

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
if (isset($_GET['paciente_id'])) {
    $paciente_id = $_GET['paciente_id'];
    $query = "SELECT * FROM Pacientes WHERE IDpaciente = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $paciente_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $paciente_actual = $result->fetch_assoc();
    $stmt->close();
    
    // Obtener citas del paciente
    $query_citas = "SELECT c.IDcita, c.fecha, t.nombre as tratamiento, c.estado, t.duracion 
                    FROM Citas c
                    JOIN Tratamientos t ON c.IDtratamiento = t.IDtratamiento
                    WHERE c.IDpaciente = ?";
    $stmt = $con->prepare($query_citas);
    $stmt->bind_param("i", $paciente_id);
    $stmt->execute();
    $citas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Procesar cancelación de cita
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['cancelar_cita'])) {
    $cita_id = $_POST['cita_id'];
    $paciente_id = $_POST['paciente_id'];
    
    $query = "UPDATE Citas SET estado = 'Cancelada' WHERE IDcita = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $cita_id);
    
    if ($stmt->execute()) {
        $success_message = "La cita se ha cancelado correctamente";
        
        // Actualizar la lista de citas
        $query_citas = "SELECT c.IDcita, c.fecha, t.nombre as tratamiento, c.estado, t.duracion 
                        FROM Citas c
                        JOIN Tratamientos t ON c.IDtratamiento = t.IDtratamiento
                        WHERE c.IDpaciente = ?";
        $stmt_citas = $con->prepare($query_citas);
        $stmt_citas->bind_param("i", $paciente_id);
        $stmt_citas->execute();
        $citas = $stmt_citas->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_citas->close();
    } else {
        $error_message = "Error al cancelar la cita: " . $con->error;
    }
    $stmt->close();
}

// Procesar agendamiento de nueva cita
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['agendar_cita'])) {
  $fecha = $_POST['fecha'];
  $hora = $_POST['hora'];
  $IDtratamiento = $_POST['IDtratamiento'];
  $paciente_id = $_POST['paciente_id'];
  $estado = 'Pendiente';

  if (!empty($fecha) && !empty($hora) && !empty($IDtratamiento) && !empty($paciente_id)) {
      // Combinar fecha y hora en formato datetime
      $fecha_hora = $fecha . ' ' . $hora . ':00';

      // Consulta preparada para insertar la cita
      $query = "INSERT INTO Citas (fecha, IDpaciente, IDtratamiento, estado) VALUES (?, ?, ?, ?)";
      $stmt = $con->prepare($query);
      $stmt->bind_param("siis", $fecha_hora, $paciente_id, $IDtratamiento, $estado);
      
      if ($stmt->execute()) {
          $success_message = "La cita se agendó correctamente";
          
          // Actualizar la lista de citas
          $query_citas = "SELECT c.IDcita, c.fecha, t.nombre as tratamiento, c.estado, t.duracion 
                          FROM Citas c
                          JOIN Tratamientos t ON c.IDtratamiento = t.IDtratamiento
                          WHERE c.IDpaciente = ?";
          $stmt_citas = $con->prepare($query_citas);
          $stmt_citas->bind_param("i", $paciente_id);
          $stmt_citas->execute();
          $citas = $stmt_citas->get_result()->fetch_all(MYSQLI_ASSOC);
          $stmt_citas->close();
      } else {
          $error_message = "Error al agendar la cita: " . $con->error;
      }
      $stmt->close();
  } else {
      $error_message = "Por favor complete todos los campos requeridos";
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
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .appointments-list li:last-child {
      border-bottom: none;
    }

    .appointment-info {
      flex: 1;
    }

    .cancel-btn {
      padding: 0.3rem 0.6rem;
      background-color: #dc3545;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 0.8rem;
    }

    .cancel-btn:hover {
      background-color: #c82333;
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

  <!-- Contenido principal (Formulario de detalles del paciente) -->
  <div class="main-content">
    <?php if (isset($paciente_actual)): ?>
      <div class="patient-information">
        <h2>Información del Paciente</h2>
        
        <?php if (isset($success_message)): ?>
          <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
          <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>
        
        <form>
          <!-- Datos personales -->
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


          <!-- Citas -->
          <h3>Citas Agendadas</h3>
          <ul class="appointments-list">
            <?php if (!empty($citas)): ?>
              <?php foreach ($citas as $cita): ?>
                <li>
                  <div class="appointment-info">
                    <strong>Fecha:</strong> <?= htmlspecialchars($cita['fecha']) ?><br>
                    <strong>Tratamiento:</strong> <?= htmlspecialchars($cita['tratamiento']) ?><br>
                    <strong>Estado:</strong> <?= htmlspecialchars($cita['estado']) ?><br>
                    <strong>Duración:</strong> <?= htmlspecialchars($cita['duracion']) ?> <strong>hrs</strong>
                  </div>
                  <?php if ($cita['estado'] != 'Cancelada'): ?>
                    <form method="POST" style="display: inline;">
                      <input type="hidden" name="cita_id" value="<?= $cita['IDcita'] ?>">
                      <input type="hidden" name="paciente_id" value="<?= $paciente_actual['IDpaciente'] ?>">
                      <button type="submit" name="cancelar_cita" class="cancel-btn">Cancelar</button>
                    </form>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            <?php else: ?>
              <li>No hay citas agendadas</li>
            <?php endif; ?>
          </ul>

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
              <?php
              $query = "SELECT IDtratamiento, nombre FROM Tratamientos";
              $result = $con->query($query);
              while ($tratamiento = $result->fetch_assoc()):
              ?>
                <option value="<?= $tratamiento['IDtratamiento'] ?>">
                  <?= htmlspecialchars($tratamiento['nombre']) ?>
                </option>
              <?php endwhile; ?>
            </select>
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

  <!-- Bottom buttons -->
  <div class="bottom-buttons">
    <button onclick="window.location.href='CtalogoRecepcionista.php'">Regresar</button>
  </div>

  <script>
    // Auto-enfocar el campo de búsqueda al cargar la página
    document.querySelector('.search-bar input').focus();
    
    // Validación del formulario de nueva cita
    document.getElementById('citaForm')?.addEventListener('submit', function(e) {
      const fecha = document.getElementById('fecha').value;
      const hora = document.getElementById('hora').value;
      
      if (!fecha || !hora) {
        e.preventDefault();
        alert('Por favor complete todos los campos requeridos');
        return;
      }
    
      // Validar fecha/hora no sea en el pasado
      const ahora = new Date();
      const fechaCita = new Date(fecha + 'T' + hora);
      
      if (fechaCita < ahora) {
        if (!confirm('La fecha y hora de la cita son en el pasado. ¿Desea continuar?')) {
          e.preventDefault();
        }
      }
    });
  </script>
</body>

</html>
<?php $con->close(); ?>


