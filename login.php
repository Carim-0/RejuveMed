
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
                    echo "<script>alert('Login exitoso como Paciente'); window.location.href='Paciente/catalogoTratamientos.php';</script>";
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

                    // Redirect based on the "nombre" value
                    if ($user_data['nombre'] === "Doctora") {
                        echo "<script>alert('Login exitoso como Doctora'); window.location.href='Doctora/tablaPersonal.php';</script>";
                    } else {
                        echo "<script>alert('Login exitoso como Personal'); window.location.href='Personal/CtalogoRecepcionista.php';</script>";
                    }
                    die;
                }
            }

            // Show error message for incorrect username or password
            echo "<script>alert('Usuario o contraseña incorrectos');</script>";
        } else {
            // Show error message for invalid input
            echo "<script>alert('Introducir información válida');</script>";
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="login_style.css">
    <link href = "https://fonts.googleapis.com/css2?family=Jost:wght@500&display=swap" rel ="login_style.css">
    <title>Rejuvemet -> Login </title>
</head>
<body> 

            <div class="main">
                <!-- <input type="checkbox" id="ckk" aria-hidden="true"> -->

                <div class="login">
                      <form method="post">
                          <div class="input">
                              <input type="text" name="username" class="input-medio" placeholder="Usuario: " required><br><br>
                          </div>
                          <div class="input">
                              <input type="password" name="password" class="input-medio" placeholder="Contraseña: " required><br><br>
                          </div>
                          <div class="input">
                              <button type="submit" class="buton-añadir-login" aling="center">Iniciar sesión</button>
                          </div>
                      </form>
                      <div class="button">
                          <button class="buton-añadir-login" onclick="window.location.href='signup.php'">Registrate</button>
                      </div>
                </div>
          </section><!--fin de sección ingreso de datos-->
          <script src="JS/app.js" defer></script>
          </main><!--cierre contenedor main-->
</body>
</html>
