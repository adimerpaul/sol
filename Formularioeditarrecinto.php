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
<title>Solor-Formulario Editar Recinto</title>
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
                    <a class="navbar-brand" href="index2.php"><img src="img/Solor.png" alt="" width="150" height="50" /></a>
                </div>
                <div class="navbar-collapse collapse ">
                    <ul class="nav navbar-nav">
                        <li class="dropdown active">
                            <a href="index2.php" class="dropdown-toggle " data-toggle="dropdown" data-hover="dropdown" data-delay="0" data-close-others="false"><h4>Inicio</h4></a>
                        </li>
                        <li>
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-delay="0" data-close-others="false"><h4>Registrar  <i class="fa fa-angle-down"></i></h4></a>
                            <ul class="dropdown-menu">
                                <li><a href="Formulariorecinto.php" class=" btn btn-round btn-danger"><h5>Nuevo Recinto</h5></a></li>
                                <li><a href="Formulariofilial.php" class=" btn btn-round btn-danger"><h5>Nuevo Filial</h5></a></li>
                                <li><a href="Formulariovotacion.php" class=" btn btn-round btn-danger"><h5>Nueva Votación</h5></a></li>
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
					<li><a href="index2.php"><i class="fa fa-home"></i></a><i class="icon-angle-right"></i></li>
					<li><a href="#">Registro</a><i class="icon-angle-right"></i></li>
					<li class="active">Editar datos Recinto</li>
				</ul>
			</div>
		</div>
	</div>
	</section>
	<?php
	$id=$_GET['id'];
	$recinto=mysqli_query($cnx,"SELECT * FROM recinto WHERE idrecinto=$id");
	$frecinto=mysqli_fetch_array($recinto);
	?>
	<section id="content">
		<div class="container">
			<div class="row">
			    <div class="col-xs-12 col-sm-8 col-md-6 col-sm-offset-2 col-md-offset-3">
					<form name="Frmrecinto" role="form" method="POST" class="register-form" action="Editarrecinto.php">
					<input type="hidden" name="idrecinto" <?php echo "value='$id'";?>>
						<h2>Por favor edite <small>en este formulario la información del recinto electoral.</small></h2>
						<hr class="colorgraph">
						<div class="row">
							<div class="col-xs-12 col-sm-6 col-md-6">
								<div class="form-group">
			                        <input type="text" name="ciudad" id="ciudad" class="form-control input-lg" placeholder="Ciudad" tabindex="1" value="ORURO" style="text-transform:uppercase;" onfocus="this.blur();" required>
								</div>
							</div>
							<div class="col-xs-12 col-sm-6 col-md-6">
								<div class="form-group">
									<?php
									$provincia=mysql_query("SELECT * FROM provincia WHERE idprovincia =$frecinto[1]");
									$fprovincia=mysql_fetch_array($provincia);
									?>
									<select name="provincia" id="provincia" class="form-control input-lg">
									 <option value="0"><?php echo "$fprovincia[1]";?></option>
									</select>
								</div>
							</div>
						</div>
						<div class="row">
						<div class="col-xs-12 col-sm-6 col-md-6">
							<div class="form-group">
								<?php
								$municipio=mysql_query("SELECT * FROM municipio WHERE idmunicipio=$frecinto[2]");
								$fmunicipio=mysql_fetch_array($municipio);
								?>
								<select name="municipio" id="municipio" class="form-control input-lg">
								 <option value="0"><?php echo "$fmunicipio[1]";?></option>
								</select>
							</div>
						</div>
						</div>
						<div class="form-group">
							<input type="text" name="recinto" id="recinto" class="form-control input-lg" placeholder="NOMBRE DEL RECINTO ELECTORAL" tabindex="4" style="text-transform:uppercase;" autocomplete="off" <?php echo "value='$frecinto[4]'";?> required>
						</div>
						<div class="row">
							<div class="col-xs-12 col-sm-6 col-md-6">
								<div class="form-group">
									<input type="text" name="cantidadmesa" id="cantidadmesa" class="form-control input-lg" placeholder="CANTIDAD DE MESAS" min="0" max="100" tabindex="4" style="text-transform:uppercase;"  <?php echo "value='$frecinto[5]'";?> onfocus="this.blur();" >
								</div>
							</div>
							<div class="col-xs-12 col-sm-6 col-md-6">
								<div class="form-group">
									<input type="number" name="aumentomesa" id="aumentomesa" class="form-control input-lg" placeholder="AUMENTAR MESAS" min="0" max="100" tabindex="5">
								</div>
							</div>
						</div>
						<hr class="colorgraph">
						<div class="row">
							<div class="col-xs-12 col-md-6"><input type="submit" value="APLICAR CAMBIOS" class="btn btn-theme btn-block btn-lg" tabindex="7"></div>
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
<?php
}
else
{
?>
<script type="text/javascript">alert('Primero debes acceder con tu cuenta filial');</script>
<?php
    echo "<META HTTP-EQUIV='Refresh' CONTENT ='0; URL=index.php'>";
};
?>
</body>
</html>