<?php
function conectar()
{
    date_default_timezone_set('America/La_Paz');
    $conex= mysqli_connect("localhost","example_user","password")or die ("Falla de conexion");
    mysqli_select_db($conex,"example_database");
return $conex;
};
