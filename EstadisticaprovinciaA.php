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
					<li><a href="index2.php"><i class="fa fa-home"></i></a><i class="icon-angle-right"></i></li>
					<li><a href="#">Ver Estadistica de </a><i class="icon-angle-right"></i></li>
					<li class="active">Provincias</li>
				</ul>
			</div>
		</div>
	</div>
	</section>
	<section id="content">
		<div class="container">
			<form role="form" class="register-form" action="grafics/GraficoAsambleProvincia.php" method="POST">
				<h2><label>Por favor realice la búsqueda</label> <small>de la provincia.</small></h2>
				<hr class="colorgraph">
				<div class="row">
					<div class="col-xs-12 col-sm-6 col-md-6">
						<div class="form-group">
						<?php
							$provincia =mysqli_query($cnx,"SELECT * FROM provincia");
							?>
							<select name="idprovincia" id="provincia" class="form-control input-lg" tabindex="1">
							<option value='0'> Elija una provincia</option>
							<?php
								    while ($fprovincia = mysqli_fetch_array($provincia)) {
							    	echo '<option value="'.$fprovincia[0].'">'.$fprovincia[1].'</option>';
							        };
							?>
							</select>
						</div>
					</div>
					<div class="col-xs-12 col-sm-6 col-md-6">
						<div class="form-group">
						<button type="submit" class="form-control input-lg btn-round btn-success" name="ok" ><i class="fa fa-signal"></i> GENERAR GRÁFICO ASAMBLEISTA</button>
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