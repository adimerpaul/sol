f<?php
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
<body OnLoad="NoBack();">
<?php 
/*variables*/
$apellidopat = $_POST['apellidopat'];
$apellidomat = $_POST['apellidomat'];
$nombre = $_POST['nombre'];
$ci = $_POST['ci'];
$edad = $_POST['edad'];
$cel = $_POST['cel'];
$recinto = $_POST['recinto'];
$minmesa = $_POST['minmesa'];
$maxmesa = $_POST['maxmesa'];
$apellidopat=strtoupper($apellidopat);
$apellidomat=strtoupper($apellidomat);
$nombre=strtoupper($nombre);
$a=substr("$apellidopat",0,1);
$b=substr("$apellidomat",0,1);
$c=substr("$nombre",0,1);
$n=rand(10,901);
if ($n<100) {
	$n="0".$n;
}
$usr='SOL'.$cel;
$psw=md5($cel);
/*variables*/

/*Registro*/
$filial=mysqli_query($cnx,"INSERT INTO `filial`(ci,paterno,materno,nombre,cel,edad,usuario,psw,idrecinto,minmesa,maxmesa)
 VALUES ('$ci','$apellidopat','$apellidomat','$nombre','$cel','$edad','$usr','$psw','$recinto','$minmesa','$maxmesa');");	
	if ($filial)
	{		
	echo "<META HTTP-EQUIV='Refresh' CONTENT ='2; URL=Formulariofilial.php'>";
	?>
	<script type="text/javascript">
	sweetAlert("Perfecto!","El Filial fue registrado correctamente","success");
	</script>
	<?php
	}
	else
		{
		echo "<META HTTP-EQUIV='Refresh' CONTENT ='2; URL=Formulariofilial.php'>";
			?>
		<script type="text/javascript">
				sweetAlert("Oops!","Ocurrio un error en el registro","error");
				</script>
		<?php
		}
?>
</body>
</html>
