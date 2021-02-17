<?php
session_start();
include("Conexion.php");
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
$contrase = $_POST['contrase'];
$usuario = $_POST['usuario'];
$contrase=strtoupper($contrase);
$usuario=strtoupper($usuario);
$contrasecod=md5($contrase);
$consulta=mysqli_query($cnx,"SELECT * FROM `filial` WHERE usuario='$usuario' AND psw='$contrasecod'");
$nfilas=mysqli_num_rows($consulta);
if($nfilas>0)
	{
		$_SESSION['usr'] = $usuario;
		//session_register("usr");
		echo "<META HTTP-EQUIV='REFRESH' CONTENT='0; URL=index.php'>";
		?>
	 	<script type="text/javascript">
	 	sweetAlert("Exito!","Acceso Permitido","success");
	 	</script>
<?php
	} 
	else
	{
	echo "<META HTTP-EQUIV='Refresh' CONTENT ='0; URL=Login.php'>";
	?>
 	<script type="text/javascript">
 	sweetAlert("Oops!","Verifique sus datos","warning");
 	</script>
	<?php
	}
?>
</body>

</html>