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
echo "<select name='municipio' id='municipio' class='form-control input-lg' tabindex='2'>";
echo "<option> Seleccione un municipio...</option>";
while ($fila = mysqli_fetch_array($result)){
    echo "<option value='$fila[0]'> $fila[1]</option>";
}
echo "</select>";
//Devolvemmos la cadena de respuesta
mysqli_free_result($result);
mysqli_close($cnx);
} else {
    echo 'No se han recibido datos';
}
?>
</body>
</html>