<?php

    function check_login($con)
    {
        if(isset($_SESSION['user_id']))
        {
            $id = $_SESSION['user_id'];
            $query = "select * from Pacientes where IDpaciente = '$id'";

            $result = mysqli_query($con, $query);
            if($result && mysqli_num_rows($result) > 0)
            {
                $user_data = mysqli_fetch_assoc($result);
                return $user_data;
            }
        }
        //Redirect to login
        header("Location: login.php");
        die;
    }

    
?>