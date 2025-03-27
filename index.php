<?php
session_start();
    
    include("connection.php");
    include("functions.php");    

    $user_data = check_login($con);

?>

<!DOCTYPE html>
<html>
<head>
    <title>RejuveMed</title>
</head>
<body>
    <a href="logout.php">Cerrar Sesion</a>
    <h1>Esta es la pagina index</h1>

    <br>
    Hola, <?php echo $user_data['nombre']; ?>
    
</body>
</html>