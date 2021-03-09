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
<script src="js/custom.js"></script>
  <script src="1/jquery.min.js"></script>
  <script src="1/bootstrap.min.js"></script>
</head>
<body OnLoad="NoBack();">
<?php
$papeleta=$_GET['papeleta'];
echo "$papeleta";
$fecha=date("Y-m-d");
$hora=date("H:i:s");
$usuario=$_SESSION['usr'];
$idmesa=$_GET['idmesaalca'];
$idrecinto=$_GET['idrecintoalca'];
$idtipocandidatura=$_GET['idtipocandidaturaalca'];
$papeleta=$_GET['papeleta'];
echo "INSERT INTO votacion(idmesa,idrecinto,idtipocandidatura,fecha,hora,usuario,estado,imagen) 
        VALUES($idmesa,$idrecinto,$idtipocandidatura,'$fecha','$hora','$usuario','$papeleta','$papeleta')";
if ($result = mysqli_query($cnx,"SELECT * FROM votacion where usuario='$usuario' AND idtipocandidatura='$idtipocandidatura' AND idmesa='$idmesa' AND idrecinto='$idrecinto'")) {
    /* determinar el número de filas del resultado */

    $row_cnt = mysqli_num_rows($result);
    //echo "sdsd";
    if ($row_cnt==0){
        mysqli_query($cnx,"INSERT INTO votacion(idmesa,idrecinto,idtipocandidatura,fecha,hora,usuario,estado,imagen) 
        VALUES($idmesa,$idrecinto,$idtipocandidatura,'$fecha','$hora','$usuario','$papeleta','$papeleta')");
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
    }

}


  echo "<META HTTP-EQUIV='Refresh' CONTENT ='10; URL=Formulariomesa.php?nv=$nv&idrecinto=$idrecinto'>";
  exit;

?>
<script type="text/javascript">
sweetAlert("Exito!","Registro de votos correctamentes","success");
</script>
</body>
</html>

