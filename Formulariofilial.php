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
					<li><a href="#">Registro</a><i class="icon-angle-right"></i></li>
					<li class="active">Nuevo Filial</li>
				</ul>
			</div>
		</div>
	</div>
	</section>
	<section id="content">
		<div class="container">
			<div class="row">
			    <div class="col-xs-12 col-sm-8 col-md-6 col-sm-offset-2 col-md-offset-3">
					<form role="form" class="register-form" action="Registrofilial.php" method="POST">
						<h2>Por favor regístre <small>en este formulario la información de un filial.</small></h2>
						<hr class="colorgraph">
						<div class="row">
							<div class="col-xs-12 col-sm-6 col-md-6">
								<div class="form-group">
			                        <input type="text" name="ci" id="ci" class="form-control input-lg" placeholder="Carnet de identidad" tabindex="1" style="text-transform:uppercase;" required>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-xs-12 col-sm-6 col-md-6">
								<div class="form-group">
								<input type="text" name="apellidopat" id="apellidopat" class="form-control input-lg" placeholder="Apellido Paterno" tabindex="1" style="text-transform:uppercase;" required>
								</div>
							</div>
							<div class="col-xs-12 col-sm-6 col-md-6">
								<div class="form-group">
								<input type="text" name="apellidomat" id="apellidomat" class="form-control input-lg" placeholder="Apellido Materno" tabindex="1" style="text-transform:uppercase;" required>
								</div>
							</div>
						</div>
						<div class="form-group">
							<input type="text" name="nombre" id="nombre" class="form-control input-lg" placeholder="NOMBRE(S)" tabindex="4" style="text-transform:uppercase;" required>
						</div>
						<div class="row">
							<div class="col-xs-12 col-sm-6 col-md-6">
								<div class="form-group">
									<input type="number" name="cel" id="cel" class="form-control input-lg" placeholder="NUMERO TELEFONICO" tabindex="5" required>
								</div>
							</div>
							<div class="col-xs-12 col-sm-6 col-md-6">
								<div class="form-group">
									<input type="number" name="edad" id="edad" class="form-control input-lg" placeholder="EDAD" tabindex="6" required min="18" max="50">
								</div>
							</div>
						</div>
						<div class='row'>
							<div class="col-xs-12 col-sm-6 col-md-6">
								<div class='form-group'>
								<select name="recinto" id="recinto" class='form-control input-lg' required tabindex="7">
									<option value="">Seleccione recinto</option>
									<?php 
									$result= mysqli_query($cnx,"SELECT * from recinto order by recinto asc"); 
									while($row=mysqli_fetch_assoc($result)){
										
										echo '<option value="'.$row['idrecinto'].'">'.$row['recinto'].'</option>';
									}
									
									?>
								</select>	
								</div>
							</div>

						</div>
						<div class="row">
							<div class="col-xs-12 col-sm-6 col-md-6">
								<div class="form-group">
									<input type="number" name="minmesa" id="minmesa" class="form-control input-lg" placeholder='ini mesa' min='1' max='20'  tabindex="8" required>
								</div>
							</div>
							<div class="col-xs-12 col-sm-6 col-md-6">
								<div class="form-group">
									<input type="number" name="maxmesa" id="maxmesa" class="form-control input-lg" placeholder="fin mesa" tabindex="9"  required min="1" max="70" required>
								</div>
							</div>
						</div>
						<hr class="colorgraph">
						<div class="row">
							<div class="col-xs-12 col-md-6"><input type="submit" value="REGISTRAR" class="btn btn-theme btn-block btn-lg" tabindex="10"></div>
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
    echo '<META HTTP-EQUIV="Refresh" CONTENT ="0; URL=index.php">';
};
include ("footer.php")
?>