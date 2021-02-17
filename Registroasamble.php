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
   <style>
  .custom-input-file {
  background-color: #0000FF;
  color: #fff;
  cursor: pointer;
  font-size: 18px;
  font-weight: bold;
  margin: 0 auto 0;
  min-height: 15px;
  overflow: hidden;
  padding: 10px;
  position: relative;
  text-align: center;
  width: 250px;
  height: 250px;
  border-radius: 250px;
}

.custom-input-file .input-file {
 border: 10000px solid transparent;
 cursor: pointer;
 font-size: 10000px;
 margin: 0;
 opacity: 0;
 outline: 0 none;
 padding: 0;
 position: absolute;
 right: -1000px;
 top: -1000px;
 border-radius: 10px;
}
      
  </style>
<script> 
   $(document).ready(function()
   {
      $("#noticias").modal("show");
   });
</script>
<script>
  function ShowModal(){
     $('#noticias').modal('show')
  }
</script>
</head>
<body OnLoad="NoBack();">
    <center><button onclick="ShowModal()" class="btn btn-primary">Mostrar Subir Imagen</button></center>
<?php

$usuario=$_SESSION['usr'];
$idmesa=$_GET['idmesaasamble'];
$idrecinto=$_GET['idrecintoasamble'];
$idtipocandidatura=$_GET['idtipocandidaturaasamble'];
$fecha=date("Y-m-d");
$hora=date("H:i:s");
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
                    ORDER BY pp.idpartido");
                    
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

?>
<script type="text/javascript">
sweetAlert("Exito!","Registro de votos correctamentes","success");
</script>
<script type="application/javascript">
jQuery('input[type=file]').change(function(){
 var filename = jQuery(this).val().split('\\').pop();
 var idname = jQuery(this).attr('id');
 console.log(jQuery(this));
 console.log(filename);
 console.log(idname);
 jQuery('span.'+idname).next().find('span').html(filename);
});
</script>
<div id="noticias" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Suba una foto/imagen de esta votación</h4>
      </div>
      <div class="modal-body">
        <form action="Registrofoto.php" method="POST" class="form-horizontal form-label-left" enctype="multipart/form-data">
        <center>
            <input type='hidden' name='idvotacion' value='<?php echo $nv;?>'>
            <input type='hidden' name='idrecinto' value='<?php echo $idrecinto;?>'>

            <div class="custom-input-file">
                <br><br>
            <input type="file" id="fichero-tarifas" class="input-file" name="archivo" value="Elegir imagen..." accept="image/*" capture="camera" required/>
            <i class="fa fa-camera" aria-hidden="true"></i>
            <br><h3>PASO 1</h3>
            </div>
    
            <br><br>
        <input type='Submit' value='PASO 2: SUBIR FOTO/IMAGEN' name='ok' class="btn btn-lg btn-danger">
        </center>
        </form>
      </div>
    </div>
  </div>
</div>
</body>
</html>

