<?php
session_start();
if($_SESSION['usr']!="")
{
 include ("header.php");?>
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
					<form name="Frmrecinto" role="form" method="POST" class="register-form" action="Registrorecinto.php">
						<h2>Por favor regístre <small>en este formulario la información de un recinto electoral.</small></h2>
						<hr class="colorgraph">
						<div class="row">
							<div class="col-xs-12 col-sm-6 col-md-6">
								<div class="form-group">
			                        <input type="text" name="ciudad" id="ciudad" class="form-control input-lg" placeholder="Ciudad" tabindex="1" value="ORURO" style="text-transform:uppercase;" required>
								</div>
							</div>
							<div class="col-xs-12 col-sm-6 col-md-6">
								<div class="form-group">
										<?php
										$provincia =mysqli_query($cnx,"SELECT * FROM provincia");

										?>
										<select onchange="mostrarSugerencia(this.value)" name="provincia" id="provincia" class="form-control input-lg">
										 <option value="0">Seleccione una provincia...</option>
										<?php
											    while ($fprovincia = mysqli_fetch_array($provincia)) {
										    	echo '<option value="'.$fprovincia[0].'">'.$fprovincia[1].'</option>';
										        };
										?>
										</select>
								</div>
							</div>
						</div>
						<div class="row">
						<div class="col-xs-12 col-sm-6 col-md-6">
							<p><span style="color:brown;" id="txtInformacion"><select name="municipio" id="municipio" class="form-control input-lg" tabindex="2">
								<option value="0"></option>
								</select></span></p>
							
						</div>
						</div>
						<div class="form-group">
							<input type="text" name="recinto" id="recinto" class="form-control input-lg" placeholder="NOMBRE DEL RECINTO ELECTORAL" tabindex="4" style="text-transform:uppercase;" autocomplete="off" required>
						</div>
						<div class="row">
						<div class="col-xs-12 col-sm-6 col-md-6">
							<div class="form-group">
								<input type="number" name="cantidadmesa" id="cantidadmesa" class="form-control input-lg" placeholder="CANTIDAD DE MESAS" min="0" max="100" tabindex="4" style="text-transform:uppercase;" required>
							</div>
						</div>
						</div>
						<hr class="colorgraph">
						<div class="row">
							<div class="col-xs-12 col-md-6"><input type="submit" value="REGISTRAR" class="btn btn-theme btn-block btn-lg" tabindex="7"></div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</section>
</div>
<?php }
else
{
?>
<script type="text/javascript">alert("Primero debes acceder con tu cuenta filial");</script>
<?php
    echo '<META HTTP-EQUIV="Refresh" CONTENT ="0; URL=Login.php">';
};
include ("footer.php")
?>