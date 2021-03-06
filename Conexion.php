<?php
function conectar()
{
    date_default_timezone_set('America/La_Paz');
    $conex= mysqli_connect("localhost","root","123")or die ("Falla de conexion");
    mysqli_select_db($conex,"bdeleccion");
return $conex;
};
