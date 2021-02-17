<?php
function conectar()
{
$conex= mysqli_connect("localhost","example_user","password")or die ("Falla de conexion");
mysqli_select_db($conex,"example_database");
return $conex;
};
