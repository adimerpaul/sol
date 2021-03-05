<?php  
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Ingreso a Sistema</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="description" content="Bootstrap 3 template for corporate business" />
<!-- css -->
<link href="css/bootstrap.min.css" rel="stylesheet" />
<link href="css/cubeportfolio.min.css" rel="stylesheet" />
<link rel="stylesheet" type="text/css" href="css/estilocheck.css">
<link href="css/style.css" rel="stylesheet" />
<script src="js/jquery.min.js"></script>
<script src="cssalert/sweetalert.min.js"></script>
<link rel="stylesheet" type="text/css" href="cssalert/sweetalert.css">
<!-- Theme skin -->
<link id="t-colors" href="skins/default.css" rel="stylesheet" />
<!-- boxed bg -->
<link id="bodybg" href="bodybg/bg1.css" rel="stylesheet" type="text/css" />
<script type="text/javascript">
function validacion()
{
	if (document.getElementById("aceptar").checked==false) {
		alert("¿Usted acepta los términos y condiciones de uso?");
		document.getElementById("<strong>aceptar</strong>").focus();
		return false;
	}
	if (document.getElementById("aceptar").checked==true) {
		document.Formlogin.submit();
	}
	return false;

}
</script> 

</head>
<body OnLoad="NoBack();">

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
					<li><a href="#">Sistema</a><i class="icon-angle-right"></i></li>
					<li class="active">Acceso</li>
				</ul>
			</div>
		</div>
	</div>
	</section>
	<section id="content">
		<div class="container">
			<div class="row">
			    <div class="col-xs-12 col-sm-8 col-md-6 col-sm-offset-2 col-md-offset-3">
					<form name="Formlogin" role="form" class="register-form" action="Registrologin.php" method="POST" onsubmit="return validacion()">
						<h2>Accede...<small> ingresa los datos de tu cuenta filial</small></h2>
						<hr class="colorgraph">

						<div class="form-group">
							<input type="text" name="usuario" id="usuario" class="form-control input-lg" placeholder="Usuario Filial" tabindex="4" autocomplete="off" style="text-transform:uppercase;"  required>
						</div>
						<div class="form-group">
							<input type="password" name="contrase" class="form-control input-lg" id="exampleInputPassword1" placeholder="Contraseña" tabindex="5" autocomplete="off" style="text-transform:uppercase;"  required>
						</div>

						<div class="form-group">
							<div>
			                        Acepto los términos y condiciones de funcionamiento&nbsp&nbsp&nbsp
			                        <input type="checkbox" name="aceptar" id="aceptar" value="1">
							</div>
						</div>
						
						<hr class="colorgraph">
						<div class="row">
							<div class="col-xs-12 col-md-6"><input type="submit" value="Acceder" class="btn btn-primary btn-block btn-lg" tabindex="7"></div>
							<div class="col-xs-12 col-md-6">No puedes acceder a tu cuenta? <a href="#">Contáctanos</a></div>
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
<script src="js/jquery.cubeportfolio.min.js"></script>
<script src="js/google-code-prettify/prettify.js"></script>
<script src="js/animate.js"></script>

</body>
<footer>
<div class="text-center p-3" style="background-color: rgba(0, 0, 0, 0.2);">
© 2021 Copyright:
<a class="text-dark" href=" ">Ing. Flores Jaillita Reynaldo; Ing. Chambi Ajata Adimer; Ing. Lopez Gutierrez Alejandro</a>
</div>
</footer>
</html>
