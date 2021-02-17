<?php
session_start();
include ("Conexion.php");
$cnx=conectar();
if($_SESSION['usr']!="")
{
?>
<html>
<body>
<head>
<meta charset="utf-8">
<title>Solor-Formulario Recinto Mesas</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="description" content="Solor-RJFJ" />

<!-- css -->
<link href="css/bootstrap.min.css" rel="stylesheet" />
<link href="css/cubeportfolio.min.css" rel="stylesheet" />
<link href="css/style.css" rel="stylesheet" />
<!-- Theme skin -->
<link id="t-colors" href="skins/default.css" rel="stylesheet" />
<!-- boxed bg -->
<link id="bodybg" href="bodybg/bg1.css" rel="stylesheet" type="text/css" />
<style type="text/css">
#busqueda{
        color:black;
        text-align: center;
        font-size: 20px;
        font-weight: bold;
        width:400px;
        height: 40px;
    }

</style>
<script>
function buscar() {
    var textoBusqueda = $("input#busqueda").val();
    
    if (textoBusqueda != "") {
        $.post("busrecinto.php", {valorBusqueda: textoBusqueda}, function(mensaje) {
            $("#resultadoBusqueda").html(mensaje);
        }); 
    } else { 
        ("#resultadoBusqueda").html('<p>No hay nada que mostrar</p>');
    };
};
</script>
</head>
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
                        echo"";
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
        </ul>
      </div>
    </div>
  </div>
  </section>
  <?php
    $idmunicipio=$_GET['idmunicipio'];
    $idprovincia=$_GET['idprovincia'];
    $municipio=mysqli_query($cnx,"SELECT * FROM municipio WHERE idmunicipio=$idmunicipio");
    $fmunicipio=mysqli_fetch_array($municipio);
    $nombremunicipio=$fmunicipio[1];
    $provincia=mysqli_query($cnx,"SELECT * FROM provincia WHERE idprovincia=$idprovincia");
    $fprovincia=mysqli_fetch_array($provincia);
    $nombreprovincia=$fprovincia[1];
    ?> 
  <section id="content">
    <div class="container">
        <h1>Provincia : <?php echo($nombreprovincia) ?> - Municipio : <?php echo($nombremunicipio) ?></h1>
        <?php  
        if ($_SESSION['usr']=='FJR065') {
        ?>
        <center>
        <h3>GRÁFICOS TOTALES ESTADÍSTICOS DE ESTE MUNICIPIO</h3>
        <a <?php echo "href='grafics/GraficoAlcaldeMunicipio.php?idmunicipio=$idmunicipio'";?> class="btn btn-large btn-info"> <i class="fa fa-signal"></i> Ver Gráfico de Alcalde</a>
        <a <?php echo "href='grafics/GraficoConcejalMunicipio.php?idmunicipio=$idmunicipio'";?> class="btn btn-large btn-info"> <i class="fa fa-signal"></i> Ver Gráfico de Concejal</a>
        <a <?php echo "href='grafics/GraficoAsambleMunicipio.php?idmunicipio=$idmunicipio'";?> class="btn btn-large btn-info"> <i class="fa fa-signal"></i> Ver Gráfico de Asambleista</a>
        <a <?php echo "href='grafics/GraficoGoberMunicipio.php?idmunicipio=$idmunicipio'";?> class="btn btn-large btn-info"> <i class="fa fa-signal"></i> Ver Gráfico de Gobernador</a>
        </center><br>
        <?php 
        }
        ?>
    </div>
    <div class="container">
      <form>
        <h2><label>Por favor realíce la búsqueda</label> <small>del recinto electoral.</small></h2>
        <div class="row">

              <center>
              <h3>Escriba el nombre de su recinto <i class="fa fa-angle-down"></i></h3> <input type="text" name="busqueda" id="busqueda" value="" placeholder="" maxlength="50" class="form-control " style="text-transform:uppercase;" autocomplete="off" onKeyUp="buscar();"/>
              </center>

        </div>
        <hr class="colorgraph">
        <div class="row" id="resultadoBusqueda">
          <?php
          // Rescatamos el parámetro que nos llega mediante la url que invoca xmlhttp
          $municipio=$_GET["idmunicipio"];
          $provincia=$_GET["idprovincia"];
          if ($municipio) {
          $result=mysqli_query($cnx,"SELECT r.idrecinto,r.recinto,r.cantidadmesa
                          FROM municipio m INNER JOIN recinto r 
                            ON m.idmunicipio=r.idmunicipio
                            WHERE r.idmunicipio='".$municipio."'");
          while ($fila = mysqli_fetch_array($result)){
          echo "<div class='col-lg-4'>
            <div class='pricing-box-alt special'>
              <div class='pricing-heading'>
                <h4><strong>$fila[1]</strong></h4>
              </div>
              <a href='Formulariomesa.php?idrecinto=$fila[0]'><div class='pricing-terms'>
              <h5>$fila[2] Mesas Registradas</h5>
              </div></a>
            </div>
          </div>";
          }

          } else {
              echo 'No se han recibido datos';
          }
          ?> 
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
</html>