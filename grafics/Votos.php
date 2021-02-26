<?php
session_start();
include ("../Conexion.php");
$cnx=conectar();

$idmunicipio=$_GET['idmunicipio'];
$cn=mysqli_query($cnx,"SELECT pp.descripcion, SUM( dv.cantidadvoto ),pp.color
                                    FROM municipio m, recinto r, votacion v, detallevotacion dv, partidopolitico pp
                                    WHERE m.idmunicipio=r.idmunicipio
                                    AND r.idrecinto=v.idrecinto
                                    AND v.idvotacion=dv.idvotacion
                                    AND pp.idpartido=dv.idpartido
                                    AND v.idtipocandidatura=1
                                    AND m.idmunicipio=$idmunicipio
                                    GROUP BY dv.idpartido");
//$a='';
$myObj=array();
$array=array();
$a='';
while($fd=mysqli_fetch_array($cn)) {
    $myObj['name'] = $fd[0];
    $myObj['y'] = $fd[1];
    $myObj['color'] = $fd[2];
    //$myObj->city = "New York";
    //$a = array('<foo>',"'bar'",'"baz"','&blong&', "\xc3\xa9");
//    $a.= "{
//                        name: '".$fd[0]."',
//                        y: ".$fd[1]." ,color: '$fd[2]'},";
    $array[] = $myObj;
};
echo json_encode($array);

?>
