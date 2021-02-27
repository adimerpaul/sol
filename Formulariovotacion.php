<?php
session_start();
if($_SESSION['usr']!="")
{
 include ("header.php");?>
	<!-- end header -->
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
	    for (var i=1; i<contenidosRecibidos.length;i++) {
	    contenidosAMostrar = contenidosAMostrar+'<div id="ciudades'+i+'"> <a href="http://aprenderaprogramar.com">' + contenidosRecibidos[i]+ '</a></div>';
	    }
	    nodoMostrarResultados.innerHTML = contenidosAMostrar;
	    }
	}
	var cadenaParametros = 'provincia='+encodeURIComponent(provinciaElegido);
	xmlhttp.open('POST', 'btnprovincia.php'); // Método post y url invocada
	xmlhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded'); // Establecer cabeceras de petición
	xmlhttp.send(cadenaParametros); // Envio de parámetros usando POST
	}
</script>
<style type="text/css">
	    #provincia{
        color:black;
        text-align: center;
        font-weight: bold;
    }
</style>
<section id="inner-headline">
	<div class="container">
		<div class="row">
			<div class="col-lg-12">
				<ul class="breadcrumb">
					<li><a href="index2.php"><i class="fa fa-home"></i></a><i class="icon-angle-right"></i></li>
					<li><a href="#">Registro</a><i class="icon-angle-right"></i></li>
					<li class="active">Nueva Votación</li>
				</ul>
			</div>
		</div>
	</div>
	</section>
	<section id="content">
		<div class="container">
			<form role="form" class="register-form" action="" method="POST">
				<h2><label>Por favor realice la búsqueda</label> <small>de la provincia.</small></h2>
				<hr class="colorgraph">
				<div class="row">
					<div class="col-xs-10 col-sm-4 col-md-4">
						<div class="form-group">
						<?php
							$provincia =mysqli_query($cnx,"SELECT * FROM provincia");
							?>
							<select onchange="mostrarSugerencia(this.value)" name="provincia" id="provincia" class="form-control input-lg" tabindex="1">
							<option value='0'> Elija una provincia</option>
							<?php
								    while ($fprovincia = mysqli_fetch_array($provincia)) {
							    	echo '<option value="'.$fprovincia[0].'">'.$fprovincia[1].'</option>';
							        };
							?>
							</select>
						</div>
					</div>
					<div class="col-xs-12 col-sm-8 col-md-8">
						<div class="form-group">
						<p>
						<span id="txtInformacion">
						</span>
						</p>
						</div>
					</div>
				</div>
				<hr class="colorgraph">
			</form>
		</div>
	</section>

</div>
<?php }
else
{
?>
<script type="text/javascript">alert("Primero debes acceder con tu cuenta filial");</script>
<?php
    echo '<META HTTP-EQUIV="Refresh" CONTENT ="0; URL=index.php">';
};
include ("footer.php")
?>