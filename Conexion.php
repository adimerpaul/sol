<?php
function conectar()
{
$conex= mysqli_connect("localhost","id16001660_root","Reynaldo.jesus1289")or die ("Falla de conexion");
mysqli_select_db($conex,"id16001660_bdeleccion");
return $conex;
};
