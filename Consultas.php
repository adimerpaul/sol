<?php
session_start();
include ("Conexion.php");
$cnx=conectar();
if ($_POST["fun"]=='ultimos'){
//    echo "adimer";
    $cn=mysqli_query($cnx,"SELECT * 
FROM filial f, votacion v, tipocandidatura t,mesa m ,recinto r 
WHERE f.usuario=v.usuario 
AND t.idtipocandidatura=v.idtipocandidatura
AND m.idmesa=v.idmesa
AND r.idrecinto=v.idrecinto
ORDER by v.idvotacion
LIMIT ".$_POST["cantidad"]."
");
//    $myObj=array();
    $array=array();
//    $a='';
    while($fd=mysqli_fetch_array($cn)) {
        $array[] = $fd;
    };
    echo json_encode($array);
}
if ($_POST["fun"]=='eliminar'){
//    echo "adimer";
    mysqli_query($cnx,"DELETE FROM votacion WHERE idvotacion='".$_POST["id"]."'");
    $cn=mysqli_query($cnx,"SELECT * 
FROM filial f, votacion v, tipocandidatura t,mesa m ,recinto r 
WHERE f.usuario=v.usuario 
AND t.idtipocandidatura=v.idtipocandidatura
AND m.idmesa=v.idmesa
AND r.idrecinto=v.idrecinto
ORDER by v.idvotacion
LIMIT ".$_POST["cantidad"]."
");
//    $myObj=array();
    $array=array();
//    $a='';
    while($fd=mysqli_fetch_array($cn)) {
        $array[] = $fd;
    };
    echo json_encode($array);
}

?>
