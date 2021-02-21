<?php
session_start();
include ("Conexion.php");
$cnx=conectar();
if($_SESSION['usr']!="")
{
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Solor-Formulario Recinto</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="description" content="Solor-RJFJ" />
<link rel="shortcut icon" type="image/png" href="img/voto.png"/>
<!-- css -->
<link href="css/bootstrap.min.css" rel="stylesheet" />
<link href="css/cubeportfolio.min.css" rel="stylesheet" />
<link href="css/style.css" rel="stylesheet" />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<!-- Theme skin -->
<link id="t-colors" href="skins/default.css" rel="stylesheet" />
<script type="text/javascript" src="jquery.inputlimiter.1.3.1.min.js"></script>

	<!-- boxed bg -->
	<link id="bodybg" href="bodybg/bg1.css" rel="stylesheet" type="text/css" />
<script>
function mostrarSugerencia(str) {
var provinciaElegido = document.getElementById("provincia").value;

var xmlhttp;
var contenidosRecibidos = new Array();
var nodoMostrarResultados = document.getElementById('listaCiudades');
var contenidosAMostrar = '';

if (str.length==0) { document.getElementById("txtInformacion").innerHTML=""; nodoMostrarResultados.innerHTML = ''; return; }

xmlhttp=new XMLHttpRequest();
xmlhttp.onreadystatechange = function() {
  if (xmlhttp.readyState==4 && xmlhttp.status==200) {
    contenidosRecibidos = xmlhttp.responseText.split(",");
    document.getElementById("txtInformacion").innerHTML=contenidosRecibidos[0];
    nodoMostrarResultados.innerHTML = contenidosAMostrar;
    }
}
var cadenaParametros = 'provincia='+encodeURIComponent(provinciaElegido);
xmlhttp.open('POST', 'cboprovincia.php'); // Método post y url invocada
xmlhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded'); // Establecer cabeceras de petición
xmlhttp.send(cadenaParametros); // Envio de parámetros usando POST
}
</script>
</head>
<body>
<div id="wrapper">
	<!-- start header -->
		<header>
        <div class="navbar navbar-default navbar-static-top">
            <div class="container">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="index.php"><img src="img/Solor.png" alt="" width="150" height="50" /></a>
                </div>
                <div class="navbar-collapse collapse ">
                    <ul class="nav navbar-nav">
                        <li class="dropdown active">
                            <a href="index.php" class="dropdown-toggle " data-toggle="dropdown" data-hover="dropdown" data-delay="0" data-close-others="false"><h4>Inicio</h4></a>
                        </li>
                        <li>
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-delay="0" data-close-others="false"><h4>Registrar  <i class="fa fa-angle-down"></i></h4></a>
                            <ul class="dropdown-menu">
                                <li><a href="Formulariorecinto.php" class=" btn btn-round btn-danger"><h5>Nuevo Recinto</h5></a></li>
                                <li><a href="Formulariofilial.php" class=" btn btn-round btn-danger"><h5>Nuevo Filial</h5></a></li>
                                <li><a href="Formulariovotacion.php" class=" btn btn-round btn-danger"><h5>Nueva Votación</h5></a></li>
                                <li><a href="Formulariorecintoup.php" class=" btn btn-round btn-danger"><h5>Mod Mesa</h5></a></li>
                            </ul>
                        </li>
                        <li>
                        <?php  
                        if ($_SESSION['usr']=='FJR065') {
                        ?>
                            <a href="#" class="dropdown-toggle " data-toggle="dropdown" data-hover="dropdown"><h4>Ver registros  <i class="fa fa-angle-down"></i></h4></a>
                            <ul class="dropdown-menu">
                                <li><a href="Verrecintos.php" class="btn btn-round btn-danger"><h5>Ver Recintos</h5></a></li>
                                <li><a href="Verfilial.php" class="btn btn-round btn-danger"><h5>Ver Filiales</h5></a></li>
                                <li><a href="EstadisticaprovinciaG.php" class="btn btn-round btn-danger"><h5>Ver Estadísticas de Provincia G</h5></a></li>
                                <li><a href="EstadisticaprovinciaA.php" class="btn btn-round btn-danger"><h5>Ver Estadísticas de Provincia A</h5></a></li>
                            </ul>   
                        </li>
                        <?php               
                        }
                        ?>
                        <li>
                            <a href="#" class="dropdown-toggle " data-toggle="dropdown" data-hover="dropdown"><h4>Configuración  <i class="fa fa-angle-down"></i></h4></a>
                            <ul class="dropdown-menu">
                                <?php
                            if ($_SESSION['usr'!=""]) {
                            ?>
                                <li><a href="Login.php" class="btn btn-round btn-danger"><h5>Login</h5></a></li>
                                <?php 
                                   }
                                   else{
                                ?>
                                <li><a href="Login.php" class="btn btn-round btn-danger"><h5>Cambiar de Usuario</h5></a></li>
                                <?php 
                                }?>
                                <li><a href="Logout.php" class="btn btn-round btn-danger"><h5>Salir/Cerrar cuenta de  <?php  echo $_SESSION['usr'];?></h5></a></li>
                            </ul>   
                        </li>
                        <li class="dropdown"><a href="#" class="dropdown-toggle " data-toggle="dropdown" data-hover="dropdown" data-delay="0" data-close-others="false"></a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </header>
	<!-- end header -->
	<section id="inner-headline">
	<div class="container">
		<div class="row">
			<div class="col-lg-12">
				<ul class="breadcrumb">
					<li><a href="index.php"><i class="fa fa-home"></i></a><i class="icon-angle-right"></i></li>
					<li><a href="#">Registro</a><i class="icon-angle-right"></i></li>
					<li class="active">Nuevo Recinto</li>
				</ul>
			</div>
		</div>
	</div>
	</section>
	<section id="content">
		<div class="container">
			<div class="row">
			    <div class="col-xs-12 col-sm-8 col-md-6 col-sm-offset-2 col-md-offset-3">
					<form name="Frmmesa" role="form" method="POST" class="register-form" >
						<h2>Por favor regístre <small>en este formulario la información de un recinto electoral.</small></h2>
						<hr class="colorgraph">
						<div class="row">
							<div class="col-xs-12 col-sm-6 col-md-6">
								<div class="form-group">
									<select name='recinto' id='recinto' class="form-control">
										<option value="">Selecionar Recinto</option>
										<?php $recinto=mysqli_query($cnx,"SELECT * FROM recinto");
 										  while ($frecinto = mysqli_fetch_array($recinto)) {
											   echo '<option value="'.$frecinto[0].'">'.$frecinto[4].'</option>';

												
											}
										?>

									</select>
								</div>
							</div>
							<div class="col-xs-12 col-sm-6 col-md-6">
								<div class="form-group" id='mesarecinto'>
										
								</div>
							</div>
						</div>
						<div class="form-group" id='tammesa'>
							
						</div>
						<hr class="colorgraph">
						<div class="row">
							<div class="col-xs-12 col-md-6"><input type="button" disabled value="MODIFICAR" id='modificarmesa' class="btn btn-theme btn-block btn-lg" tabindex="7"></div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</section>
</div>
<a href="#" class="scrollup"><i class="fa fa-angle-up active"></i></a>
<!-- javascript 
    ================================================== -->
<!-- Placed at the end of the document so the pages load faster -->
<script src="js/jquery.min.js"></script>
<script src="js/modernizr.custom.js"></script>
<script src="js/jquery.easing.1.3.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/jquery.appear.js"></script>
<script src="js/stellar.js"></script>
<script src="js/classie.js"></script>
<script src="js/uisearch.js"></script>
<script src="js/jquery.cubeportfolio.min.js"></script>
<script src="js/google-code-prettify/prettify.js"></script>
<script src="js/animate.js"></script>
<script src="js/custom.js"></script>
<script>
$('#recinto').change(function(){
	console.log(this.value);
	var idrec= this.value;
	$.ajax({
            data: {'idrecinto':idrec, 'funcion':"datorecinto" },
            url:   'mesa.php',
            type:  'POST',

            beforeSend: function () 
            {
                
            },

          success: function(data)            
       {
		   //foreach(data){
				 console.log(JSON.parse(data));
				 row=JSON.parse(data);
				 var op='';
				 op='<select name="mesas" id="mesas" class="form-control input-lg"><option value="">Seleccione mesa...</option>';
				 for(var i=0;i<row.length;i++){
					 op+='<option value="'+row[i]['idmesa']+'">'+row[i]['nummesa']+'</option> ';
					 }
					 $('#selmesa').html(op);

					 op+='</select>';
					 console.log(op);
					 $('#mesarecinto').html(op);
					 $('#modificarmesa').prop('disabled',true);
					 $('#tammesa').html('');
				 /*$.each(data,function(){
					 op+='<option value='+data->idrecinto+'>'+data->nummesa+'</option>';

				 });
				 */
				 
		//   }
	   }
	})
});

$('#mesarecinto').change(function(){
	var datomesa=$( "#mesarecinto option:selected" ).prop('value');
	console.log(datomesa);
	if(datomesa!="")
	{
		$.ajax({
            data: {'idmesa':datomesa, 'funcion':"datomesa" },
            url:   'mesa.php',
            type:  'POST',

            beforeSend: function () 
            {
                
            },

          success: function(data)            
       		{
		   //foreach(data){
			console.log(data);
			$('#tammesa').html('<input type="number" name="max" id="max" class="form-control input-lg"  tabindex="4" value="'+data+'" required>');
			$('#modificarmesa').prop('disabled',false);

		}
	
		});}
		else {$('#modificarmesa').prop('disabled',true);}
});
$('#modificarmesa').click(function(){
	var max=$('#max').prop('value');
	var id=$( "#mesarecinto option:selected" ).prop('value');
	console.log(max);
	$.ajax({
            data: {'idmesa':id, 'votosmax':max },
            url:   'modmesa.php',
            type:  'POST',

            beforeSend: function () 
            {
                
            },

          success: function(data)            
       		{
				if(data=1)
				alert("Modificado correctamente");
			   }
	});

});
</script>
<?php
}
else
{
?>
<script type="text/javascript">alert('Primero debes acceder con tu cuenta filial');</script>
<?php
    echo "<META HTTP-EQUIV='Refresh' CONTENT ='0; URL=Login.php'>";
};
?>
</body>
</html>