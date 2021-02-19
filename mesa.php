<?php
session_start();
include ("Conexion.php");
$cnx=conectar();
if($_SESSION['usr']!="")
{
    //variables POST

    if($_POST['funcion']=='datorecinto'){
    $id = $_POST["idrecinto"];
    $ar=array();
    //consulta mysql para insertar los datos del empleados
    $result= mysqli_query($cnx,"SELECT * from mesa where idrecinto=$id"); 
    while($row=mysqli_fetch_assoc($result)){
        $ar[]=$row;
    }
    echo json_encode($ar);
    }
    elseif($_POST['funcion']=='datomesa'){
        $idm = $_POST["idmesa"];
        $result= mysqli_query($cnx,"SELECT * from mesa where idmesa=$idm"); 
        echo mysqli_fetch_row($result)[3];
    }

}
?>