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
    <title>Rejuvemet -> Agregar usuarios </title>
</head>
<body>
    <!--Inicializamos con el contenedor main-->
    <main class="contenedor-main">
      <!--Inicializamos de cabezera-->
      <header class="cabezera">
        <img src="IMG/ImagenRegistrar.png" alt="Imagen representativa de agregar usuario" height="70px" width="70px" >
        <h1 class="titulo-main">Agregar usuario</h1>
        
      </header>
      <!--Inicializamos parte de ingreso de datos-->
    <section class="seccion-input">
      <div class="contenedor">
        
        <h2 class="seccion-titulo">Ingrese los respectivos datos </h2>
               
                
                                <div id="box">
                        <form method="post">
                        <div class="estilo-input">
                <p class="input-texto">Nombre: </p><input type="text" name="username" class="input" placeholder="Escribe tus nombres"><br><br>
                </div>
                <div class="estilo-input">
                <p class="input-texto">Edad:</p><input type="text" name="edad" class="input-pequeño" placeholder="Escribe tu edad"><br><br>
                <p class="input-texto">Telefono:</p><input type="text" name="telefono" class="input-medio" placeholder="Escribe tu telefono"><br><br>
                </div>
                <div class="estilo-input">
                <p class="input-texto">Contraseña:</p><input type="password" name="password" class="input" placeholder="Elige una Contraseña"><br><br>
                </div>
                <p class="input-texto">Detalles:</p>
                <div class="estilo-input">
                <input type="text" name="detalles" class="input-ancho" placeholder=""><br><br>
                </div>
                <div class="estilo-input">
                <input id="button" type="submit" value="Registrarse"><br><br>
                </div>
                            
                            <a href="login.php">Iniciar Sesión</a><br><br>`
                        </form>
                    </div>
      </div>
    </section><!--fin de sección ingreso de datos-->
    <script src="JS/app.js" defer></script>
    </main><!--cierre contenedor main-->
 
    
</body>
</html>
