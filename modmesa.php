<?php
session_start();
include ("Conexion.php");
$cnx=conectar();
?>
<?php 

/*variables*/
$idmesa = $_POST['idmesa'];
$votosmax = $_POST['votosmax'];
/*variables*/
		/*Registro*/
		$mesa_up=mysqli_query($cnx,"UPDATE mesa set votosmax='$votosmax' where idmesa='$idmesa'");	
		echo 1;
			?>
