<?php
session_start();
include ("Conexion.php");
$cnx=conectar();
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="description" content="Solor-RJFJ" />
<link rel="shortcut icon" type="image/png" href="img/voto.png"/>
<link href="css/style.css" rel="stylesheet" />
<link rel="stylesheet" href="1/bootstrap.min.css">
  <script src="1/jquery.min.js"></script>
  <script src="1/bootstrap.min.js"></script>

</head>
<body OnLoad="NoBack();">
<?php
$fecha=date("Y-m-d");
$hora=date("H:i:s");
$usuario=$_SESSION['usr'];
$idmesa=$_GET['idmesaconce'];
$idrecinto=$_GET['idrecintoconce'];
$idtipocandidatura=$_GET['idtipocandidaturaconce'];
mysqli_query($cnx,"INSERT INTO votacion(idmesa,idrecinto,idtipocandidatura,fecha,hora,usuario,estado,imagen) VALUES($idmesa,$idrecinto,$idtipocandidatura,'$fecha','$hora','$usuario','P','NULL')");
	$nv=mysqli_insert_id($cnx);
$c=0;
$candidaturas=mysqli_query($cnx,"SELECT pp.idpartido,pp.descripcion
                    FROM recinto r, municipio m, candidatura c, partidopolitico pp
                    WHERE r.idmunicipio = m.idmunicipio
                    AND c.idmunicipio = m.idmunicipio
                    AND pp.idpartido = c.idpartido
                    AND c.idtipocandidatura=$idtipocandidatura
                    AND r.idrecinto=$idrecinto
                    ORDER BY c.posicion");                   
while ($fp=mysqli_fetch_array($candidaturas)) {
			$c++;
			$valor=$_GET["v".$c];
			$aux=$_GET[$fp[0]];
			if ($aux=="on")
			{
			$voto=mysqli_query($cnx,"INSERT INTO detallevotacion(idvotacion,idpartido,cantidadvoto) VALUES($nv,'$fp[0]',$valor)");
				if ($voto) {
					mysqli_query($cnx,"UPDATE votacion SET idtipocandidatura='$idtipocandidatura', estado= 'T' WHERE idvotacion=$nv");
				};
			};
		};
    echo "<META HTTP-EQUIV='Refresh' CONTENT ='0; URL=Formulariofoto.php?nv=$nv&idrecinto=$idrecinto'>";
  exit;
?>
<script type="text/javascript">
sweetAlert("Exito!","Registro de votos correctamentes","success");
</script>

</body>
</html>

