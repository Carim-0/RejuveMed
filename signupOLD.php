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
<html>
<head>
    <title>Registrarse</title>
</head>
<body>
    <style type="text/css">
        #text{
            height: 25px;
            border-radius: 5px;
            padding: 4px;
            border: solid thin #aaa;
            width: 100%;
        }

        #button{
            padding: 10px;
            width: 100px;
            color: white;
            background-color: lightblue;
            border: none;
        }

        #box{
            background-color: grey;
            margin: auto;
            width: 300px;
            padding: 20px;
        }
    </style>

    <div id="box">
        <form method="post">
            <div style="font-size: 20px; margin: 10px; color: white;">Registrarse</div>
            
            <input id="text" type="text" name="username" placeholder="Usuario"><br><br>
            <input id="text" type="password" name="password" placeholder="Contraseña"><br><br>
            <input id="text" type="edad" name="edad" placeholder="Edad"><br><br>
            <input id="text" type="telefono" name="telefono" placeholder="Telefono"><br><br>

            <input id="button" type="submit" value="Login"><br><br>
            
            <a href="login.php">Iniciar Sesión</a><br><br>`
        </form>
    </div>
</body>
</html>