<?php
session_start();
include ("Conexion.php");
$cnx=conectar();
?>
<html>
<head>
	<meta name="description" content="Solor-RJFJ" />
    <link rel="shortcut icon" type="image/png" href="img/voto.png"/>
	<script src="cssalert/sweetalert.min.js"></script>
    <link rel="stylesheet" type="text/css" href="cssalert/sweetalert.css">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>
<body>
<?php 

/*variables*/
$idrecinto = $_POST['idrecinto'];
$recinto = $_POST['recinto'];
$cantidadmesa = $_POST['cantidadmesa'];
$aumentomesa = $_POST['aumentomesa'];
$descuentomesa = $_POST['descuentomesa'];
/*variables*/
$recinto=strtoupper($recinto);
/*Registro*/
if ($aumentomesa=="" && $descuentomesa=="") {
	$filial=mysqli_query($cnx,"UPDATE `recinto` SET recinto='$recinto' WHERE idrecinto='$idrecinto'");
}
if ($aumentomesa!="" && $aumentomesa!=0)
{
	$totalmesa=$cantidadmesa+$aumentomesa;
	$cantidadmesa=$cantidadmesa+1;
	$filial=mysqli_query($cnx,"UPDATE `recinto` SET recinto='$recinto',cantidadmesa='$totalmesa' WHERE idrecinto='$idrecinto'");
	/*Registro mesas*/
		for ($i=$cantidadmesa; $i <=$totalmesa; $i++) {
			$mesa=mysqli_query($cnx,"INSERT INTO mesa(idrecinto,nummesa)VALUES($idrecinto,$i);");
		};
}
	if ($filial)
	{		
	echo "<META HTTP-EQUIV='Refresh' CONTENT ='2; URL=Verrecintos.php'>";
	?>
	<script type="text/javascript">
	sweetAlert("Perfecto!","El Recinto fue editado correctamente","success");
	</script>
	<?php
	}
	else
		{
		echo "<META HTTP-EQUIV='Refresh' CONTENT ='2; URL=Verrecintos.php'>";
			?>
		<script type="text/javascript">
				sweetAlert("Oops!","Ocurrio un error en la edicion","error");
				</script>
		<?php
		}
	echo "<META HTTP-EQUIV='Refresh' CONTENT ='12; URL=Verrecintos.php'>";
?>
</body>
</html>
