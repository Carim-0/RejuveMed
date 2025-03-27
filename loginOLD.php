<?php
    session_start();

    include("connection.php");
    include("functions.php");

    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        // Something was posted
        $username = $_POST['username'];
        $password = $_POST['password'];

        if (!empty($username) && !empty($password) && !is_numeric($username)) {
            // Check in Pacientes table
            $query = "SELECT * FROM Pacientes WHERE nombre = '$username' LIMIT 1";
            $result = mysqli_query($con, $query);

            if ($result && mysqli_num_rows($result) > 0) {
                $user_data = mysqli_fetch_assoc($result);

                if ($user_data['password'] === $password) {
                    $_SESSION['user_id'] = $user_data['IDpaciente'];
                    $_SESSION['user_type'] = 'Paciente'; // Store user type
                    echo "Login exitoso como Paciente";
                    header("Location: tablaPacientes.php");
                    die;
                }
            }

            // Check in Personal table
            $query = "SELECT * FROM Personal WHERE nombre = '$username' LIMIT 1";
            $result = mysqli_query($con, $query);

            if ($result && mysqli_num_rows($result) > 0) {
                $user_data = mysqli_fetch_assoc($result);

                if ($user_data['password'] === $password) {
                    $_SESSION['user_id'] = $user_data['IDpersonal'];
                    $_SESSION['user_type'] = 'Personal'; // Store user type
                    echo "Login exitoso como Personal";
                    header("Location: tablaPacientes.php"); // Redirect to the same page or a different one
                    die;
                }
            }

            echo "Usuario o contraseña incorrectos";
        } else {
            echo "Introducir información válida";
        }
    }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Iniciar Sesión</title>
</head>
<body>
    <style type="text/css">
        #text {
            height: 25px;
            border-radius: 5px;
            padding: 4px;
            border: solid thin #aaa;
            width: 100%;
        }

        #button {
            padding: 10px;
            width: 100px;
            color: white;
            background-color: lightblue;
            border: none;
        }

        #box {
            background-color: grey;
            margin: auto;
            width: 300px;
            padding: 20px;
        }
    </style>

    <div id="box">
        <form method="post">
            <div style="font-size: 20px; margin: 10px; color: white;">Iniciar Sesión</div>
            
            <input id="text" type="text" name="username" placeholder="Usuario"><br><br>
            <input id="text" type="password" name="password" placeholder="Contraseña"><br><br>

            <input id="button" type="submit" value="Login"><br><br>
            
            <a href="signup.php">Registrarse</a><br><br>
        </form>
    </div>
</body>
</html>