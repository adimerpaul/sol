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
$usuario=$_SESSION['usr'];
$user = $_POST['user'];
$contraseña = $_POST['contraseña'];
$provincia = $_POST['provincia'];
$municipio = $_POST['municipio'];
$ciudad = $_POST['ciudad'];
$recinto = $_POST['recinto'];
$cantidadmesa = $_POST['cantidadmesa'];
$fecha=date("Y-m-d");
$hora=date("H:i:s");
/*variables*/
$user=strtoupper($user);
$recinto=strtoupper($recinto);
		/*Registro*/
		$recinto_insert=mysqli_query($cnx,"INSERT INTO `recinto`(idprovincia,idmunicipio,ciudad,recinto,cantidadmesa,fecha,hora,filial) VALUES ('$provincia','$municipio','$ciudad','$recinto','$cantidadmesa','$fecha','$hora','YO')");	
			if ($recinto_insert)
			{
			$recinto_id=mysqli_insert_id($cnx);
			echo"$recinto_id";
			/*Registro mesas*/
			for ($i=1; $i <=$cantidadmesa; $i++) {
				$mesa=mysqli_query($cnx,"INSERT INTO mesa(idrecinto,nummesa)VALUES($recinto_id,$i);");
			};
			echo "El Recinto fue registrado correctamente...";
			echo "<META HTTP-EQUIV='Refresh' CONTENT ='3; URL=Formulariorecinto.php'>";
			?>
			<script type="text/javascript">
			sweetAlert("Perfecto!","El Recinto fue registrado correctamente","success");
			</script>
			<?php
			}
			else{
			    echo "Ocurrio un error...";
			echo "<META HTTP-EQUIV='Refresh' CONTENT ='5; URL=Formulariorecinto.php'>";
			?>
			<script type="text/javascript">
			sweetAlert("Oops!","Ocurrio un error ineseperado","error");
			</script>
			<?php
			}
?>
</body>
</html>
