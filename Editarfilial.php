<?php
include ("Conexion.php");
$cnx=conectar();
?>
<html>
<head>
    <meta name="description" content="Solor-RJFJ" />
    <link rel="shortcut icon" type="image/png" href="img/voto.png"/>
	<script src="cssalert/sweetalert.min.js"></script>
    <link rel="stylesheet" type="text/css" href="cssalert/sweetalert.css">
</head>
<body>
<?php 
/*variable que buscara al filial editor*/
$cifilial = $_POST['cifilial'];
/*variables*/
$apellidopat = $_POST['apellidopat'];
$apellidomat = $_POST['apellidomat'];
$nombre = $_POST['nombre'];
$ci = $_POST['ci'];
$edad = $_POST['edad'];
$cel = $_POST['cel'];
$apellidopat=strtoupper($apellidopat);
$apellidomat=strtoupper($apellidomat);
$nombre=strtoupper($nombre);
$psw=md5("$cel");
/*variables*/

/*Registro*/
$filial=mysqli_query($cnx,"UPDATE `filial` SET ci='$ci',paterno='$apellidopat',materno='$apellidomat',nombre='$nombre',cel='$cel',edad='$edad' WHERE ci='$cifilial'");	
	if ($filial)
	{		

	echo "<META HTTP-EQUIV='Refresh' CONTENT ='2; URL=Verfilial.php'>";
	?>
	<script type="text/javascript">
	sweetAlert("Perfecto!","El Filial fue editado correctamente","success");
	</script>
	<?php
	}
	else
		{
		echo "<META HTTP-EQUIV='Refresh' CONTENT ='2; URL=Formulariofilial.php'>";
			?>
		<script type="text/javascript">
				sweetAlert("Oops!","Ocurrio un error en la edicion","error");
				</script>
		<?php
		}
?>
</body>
</html>
