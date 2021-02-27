<?php
include("Conexion.php");
$cnx=conectar();
?>
<html>
<body>
<meta charset="utf-8"/>
 <?php
// Rescatamos el parámetro que nos llega mediante la url que invoca xmlhttp
$provincia=$_POST["provincia"];
$resultadoConsulta = '';
$msg = ''.$provincia;
if ($provincia) {
    $tildes = $cnx->query("SET NAMES 'utf8'"); //Para que se muestren las tildes correctamente
    $result = mysqli_query($cnx, "SELECT m.idmunicipio,m.nombremunicipio
                FROM municipio AS m, provincia AS p
                WHERE p.idprovincia = m.idprovincia
                AND m.idprovincia = '".$provincia."'");
    $c=0;
while ($fila = mysqli_fetch_array($result)){
	$c++;
	$op=$c%2;
	if ($op==0) {
	echo "<a class='btn btn-warning btn-lg' href='Formulariorecintomesa.php?idprovincia=$provincia&idmunicipio=$fila[0]'>$c.- $fila[1]</a>&nbsp;&nbsp;";
	}

	else
	{
		echo "<a class='btn btn-danger btn-lg' href='Formulariorecintomesa.php?idprovincia=$provincia&idmunicipio=$fila[0]'>$c.- $fila[1]</a> &nbsp;&nbsp;";
	}


}
//Devolvemmos la cadena de respuesta
mysqli_free_result($result);
mysqli_close($cnx);
} else {
    echo 'No se han recibido datos';
}
?>
</body>
</html>