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
                    <h4>Mesas de votación <strong> encontradas</strong></h4>
                        <div class="form-group">
                        <div class="pricing-content">
                          <ul>
                          <?php
                          //echo "SELECT * FROM mesa WHERE idrecinto=$idrecinto AND idmesa='".$_SESSION['ci']."' ORDER BY idmesa";
                          $filial=mysqli_query($cnx,"SELECT * FROM filial WHERE usuario='".$_SESSION['usr']."'");
                          $ffilial=mysqli_fetch_array($filial);
                          if ($_SESSION['usr']=='FJR065'){
                              $mesa=mysqli_query($cnx,"SELECT * FROM mesa WHERE idrecinto=$idrecinto ORDER BY idmesa");
                          }else{
                              $mesa=mysqli_query($cnx,"SELECT * FROM mesa WHERE idrecinto=$idrecinto AND nummesa=$ffilial[0] ORDER BY idmesa");
                          }
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
