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
<title>Solor-Formulario Mesa</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="description" content="Solor-RJFJ" />
<link rel="shortcut icon" type="image/png" href="img/voto.png"/>
<!-- css -->
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width" />
<link href="css/bootstrap.min.css" rel="stylesheet" />
<link href="css/cubeportfolio.min.css" rel="stylesheet" />
<link href="css/style.css" rel="stylesheet" />

<!-- Theme skin -->
<link id="t-colors" href="skins/default.css" rel="stylesheet" />

    <!-- boxed bg -->
    <link id="bodybg" href="bodybg/bg1.css" rel="stylesheet" type="text/css" />
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
                    <li class="active">Nueva Votación</li>
                    <li class="active">Seleccion de Mesa de Votación</li>
                </ul>
            </div>
        </div>
    </div>
    </section>
    <?php
    $idrecinto=$_GET['idrecinto'];
    $recinto=mysqli_query($cnx,"SELECT * FROM recinto WHERE idrecinto=$idrecinto");
    $frecinto=mysqli_fetch_array($recinto);
    $nombrerecinto=$frecinto[4];
    ?> 
    <section id="content">
    <div class="container">
        <h1>Recinto : <?php echo($nombrerecinto) ?></h1>
        <?php  
        if ($_SESSION['usr']=='FJR065') {
        ?>
        <center>
        <h3>GRÁFICOS TOTALES ESTADÍSTICOS DE ESTE RECINTO</h3>
        <a <?php echo "href='grafics/GraficoAlcaldeRecinto.php?idrecinto=$idrecinto'";?> class="btn btn-large btn-primary"> <i class="fa fa-signal"></i> Ver Gráfico de Alcalde</a>
        <a <?php echo "href='grafics/GraficoConcejalRecinto.php?idrecinto=$idrecinto'";?> class="btn btn-large btn-primary"> <i class="fa fa-signal"></i> Ver Gráfico de Concejal</a>
        <a <?php echo "href='grafics/GraficoAsambleRecinto.php?idrecinto=$idrecinto'";?> class="btn btn-large btn-primary"> <i class="fa fa-signal"></i> Ver Gráfico de A. Territorio</a>
        <a <?php echo "href='grafics/GraficoAsamblePRecinto.php?idrecinto=$idrecinto'";?> class="btn btn-large btn-primary"> <i class="fa fa-signal"></i> Ver Gráfico de A. Población</a>
        <a <?php echo "href='grafics/GraficoGoberRecinto.php?idrecinto=$idrecinto'";?> class="btn btn-large btn-primary"> <i class="fa fa-signal"></i> Ver Gráfico de Gobernador</a>
        </center><br>
        <?php
        }
        ?>
    </div>
        <div class="container">
            <form role="form" class="register-form">
                <h2><label>Por favor elija una mesa y un tipo de votación</label> <small>para registrar sus votos.</small></h2>
                <hr class="colorgraph">
                <div class="row">
                    <div class="col-lg-12">
                    <h4>Recintos electorales<strong> encontrados</strong></h4>
                        <div class="form-group">
                        <div class="pricing-content">
                          <ul>
                          <?php
                          $mesa=mysqli_query($cnx,"SELECT * FROM mesa WHERE idrecinto=$idrecinto ORDER BY idmesa");
                          while ($fmesa=mysqli_fetch_array($mesa)) {
                          ?>
                            <li><i class="icon-ok"></i><h1><strong> MESA #<?php echo($fmesa[2]); ?></strong></h1>
                            <a <?php echo("href='IniciarvotacionA.php?idmesa=$fmesa[0]&idrecinto=$idrecinto&tipo=A'"); ?>  class="btn btn-mini btn-warning"><i class="icon-bolt"></i> Registrar Votos Alcaldes/Concejales</a>
                            <a <?php echo("href='IniciarvotacionG.php?idmesa=$fmesa[0]&idrecinto=$idrecinto&tipo=G'"); ?>  class="btn btn-mini btn-theme"><i class="icon-bolt"></i> Registrar Votos Gobernadores/Asambleistas</a>
                            <?php  
                          }
                            ?>
                          </ul>
                        </div>
                        </div>
                    </div>
                </div>
                <hr class="colorgraph">
            </form>
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
    echo "<META HTTP-EQUIV='Refresh' CONTENT ='0; URL=Login.php'>";
};
?>
</body>
</body>
</html>