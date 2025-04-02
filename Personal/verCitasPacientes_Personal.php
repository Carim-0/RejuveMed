<?php
session_start();
require_once('../connection.php');
require_once ('../functions.php');

$personal_data = check_login($con);
$personal_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['cancelar_cita'])) {
    $id_cita = $_POST['id_cita'];
    $query = "UPDATE Citas SET estado = 'Cancelada' WHERE IDcita = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $id_cita);
    $stmt->execute();
    $stmt->close();
}

$paciente_actual = null;
$citas = [];
if (isset($_GET['paciente_id'])) {
    $paciente_id = $_GET['paciente_id'];
    $query_citas = "SELECT c.IDcita, c.fecha, t.nombre as tratamiento, c.estado 
                    FROM Citas c
                    JOIN Tratamientos t ON c.IDtratamiento = t.IDtratamiento
                    WHERE c.IDpaciente = ?";
    $stmt = $con->prepare($query_citas);
    $stmt->bind_param("i", $paciente_id);
    $stmt->execute();
    $citas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citas del Paciente</title>
</head>
<body>
    <h2>Citas del Paciente</h2>
    <table border="1">
        <tr>
            <th>Fecha</th>
            <th>Tratamiento</th>
            <th>Estado</th>
            <th>Acción</th>
        </tr>
        <?php foreach ($citas as $cita) : ?>
            <tr>
                <td><?php echo htmlspecialchars($cita['fecha']); ?></td>
                <td><?php echo htmlspecialchars($cita['tratamiento']); ?></td>
                <td><?php echo htmlspecialchars($cita['estado']); ?></td>
                <td>
                    <?php if ($cita['estado'] !== 'Cancelada') : ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id_cita" value="<?php echo $cita['IDcita']; ?>">
                            <button type="submit" name="cancelar_cita">Cancelar</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>