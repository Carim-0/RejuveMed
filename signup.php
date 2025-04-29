<?php
session_start();

    include("connection.php");
    include("functions.php");

    if($_SERVER['REQUEST_METHOD'] == "POST")
    {
        //something was posted
        $username = $_POST['username'];
        $password = $_POST['password'];
        $edad = $_POST['edad'];
        $telefono = $_POST['telefono'];

        if(!empty($username) && !empty($password) && !empty($edad) && !empty($telefono) && is_numeric($edad) 
        && is_numeric($telefono) && $edad > 0 && $telefono > 0 && !is_numeric($username)) 
        {
            //save to database
            $query = "insert into Pacientes (nombre, password, edad, telefono) values ('$username', '$password', $edad, '$telefono')";

            mysqli_query($con, $query);
            
            echo "Registro exitoso";

            header("Location: login.php");
            die;
        }else
        {
            echo "Introducir información válida";
        }
    } 

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="login_style.css">
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@500&display=swap" rel="stylesheet">
    <title>Rejuvemet -> Agregar usuarios</title>
</head>
<body>

<div class="signup">
    <form method="post">
        <label class="sign" aria-hidden="true">Registro</label>
        
        <input type="text" name="username" placeholder="Nombre completo" required>
        <input type="text" name="edad" placeholder="Edad" required>
        <input type="tel" name="telefono" placeholder="Teléfono" required>
        <input type="password" name="password" placeholder="Contraseña" required>
        
        <div class="justified">
        <textarea name="detalles" class="input-medio" placeholder="Detalles adicionales" rows="3"></textarea>
        </div>
        <div class="button-container">
            <button type="submit" class="buton">Registrarse</button>
            <button type="button" class="buton" onclick="window.location.href='login.php'">Volver a Login</button>
        </div>
    </form>
</div>
    <script src="JS/app.js" defer></script>
</body>
</html>