<?php

function check_login($con)
{
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
        $id = $_SESSION['user_id'];
        $user_type = $_SESSION['user_type'];

        if ($user_type === 'Paciente') {
            // revisar tabla pacientes
            $query = "SELECT * FROM Pacientes WHERE IDpaciente = '$id'";
        } elseif ($user_type === 'Personal') {
            // revisar tabla personal
            $query = "SELECT * FROM Personal WHERE IDpersonal = '$id'";
        } else {
            header("Location: login.php");
            die;
        }

        $result = mysqli_query($con, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            $user_data = mysqli_fetch_assoc($result);
            return $user_data;
        }
    }

    header("Location: login.php");
    die;
}

?>
