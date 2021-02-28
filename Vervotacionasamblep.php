<?php
session_start();
if($_SESSION['usr']!="")
{
 include ("header.php");?>
<style type="text/css">
    #th2{
        vertical-align:middle;
        color:black;
        text-align: center;
        font-size:15px;
        font-weight: bold;
    }
    #t{
        vertical-align:middle;
        color:black;
        text-align: center;
        font-size:30px;
        font-weight: bold;
    }

</style>

    <!-- end header -->
    <section id="inner-headline">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <ul class="breadcrumb">
                    <li><a href="index2.php"><i class="fa fa-home"></i></a><i class="icon-angle-right"></i></li>
                    <li><a href="#">Sistema</a><i class="icon-angle-right"></i></li>
                    <li class="active">Vista Resumén Votación Asambleista Poblacion</li>
                </ul>
            </div>
        </div>
    </div>
    </section>
    <?php
    $idrecinto=$_GET['idrecinto'];
    $idmesa=$_GET['idmesa'];
    $recinto=mysqli_query($cnx,"SELECT * FROM recinto WHERE idrecinto=$idrecinto");
    $frecinto=mysqli_fetch_array($recinto);
    $nombrerecinto=$frecinto[4];
    $mesa=mysqli_query($cnx,"SELECT * FROM mesa WHERE idmesa=$idmesa");
    $fmesa=mysqli_fetch_array($mesa);
    $numeromesa=$fmesa[2];
    ?>
    <center>
    <section id="content">
        <div class="container">
            <h2><label>Recinto : <?php echo($nombrerecinto) ?></label> <small>Número de mesa : <?php echo($numeromesa) ?></small></h2>
            <hr class="colorgraph">
                <div class="row">
            <div class="col-lg-12">
                <h4>Votacion electoral<strong> encontrada</strong></h4>
            </div>
            <div id="alcalde" class="col-xs-12 col-sm-8 col-md-6 col-sm-offset-2 col-md-offset-3">
                <form action="Registroalcalde.php" method="POST" class="form-horizontal form-label-left" enctype="multipart/form-data">
                    <?php
                    $consultaasamble=mysqli_query($cnx,"SELECT * FROM votacion WHERE idmesa=$idmesa AND idrecinto=$idrecinto AND idtipocandidatura=2");
                    $fconsultaasamble=mysqli_fetch_array($consultaasamble);
                    $img=$fconsultaasamble[8];
                        $votos=mysqli_query($cnx,"SELECT pp.descripcion,pp.logo,dv.cantidadvoto,pp.color FROM votacion v,detallevotacion dv, partidopolitico pp
                            WHERE v.idvotacion=dv.idvotacion
                            AND dv.idpartido=pp.idpartido
                            AND v.idvotacion=$fconsultaasamble[0]
                            ORDER BY dv.iddetallevoto");
                            ?>
                    <label><strong><big>Detalle de votos para ASAMBLEISTA POR POBLACION<strong></big></label>
                    <table class="table responsive-utilities table-bordered">
                    <tr>
                        <th id='th2'>PARTIDO POLITICO</th><th id='th2'>LOGO</th><th id='th2'>CANTIDAD VOTOS</th>
                    </tr>
                    <?php  
                        while ($fvotos=mysqli_fetch_array($votos)) {
                            echo "<tr style='background-color: $fvotos[3];'>
                                <th id='t'>$fvotos[0]</th>
                                <td><br><img style='max-width: 80px; height: 110px;' src='imgpp/$fvotos[1]' class='img-fluid img-thumbnail' alt='Responsive image'/></td>
                                <td id='t' align='right'>$fvotos[2] votos</td>
                                </tr>";
                    };
                    ?>
                    </table>
                     <div>
                        <center>
                            <a href="<?=$img?>">
                               <img src="<?=$img?>" alt="Foto del voto" width="250">
                            </a>
                        </center>
                    </div>
        <?php  
        if ($_SESSION['usr']=='FJR065') {
        ?>
                    <div class="col-xs-12 col-sm-8 col-md-6 col-sm-offset-2 col-md-offset-3" >
                    <a <?php echo "href='grafics/GraficoAsamblePMesa.php?idmesa=$idmesa'";?> class="btn btn-large btn-success"> <i class="fa fa-signal"></i> Ver Gráfico Estadístico</a>
                    </div>
        <?php
        }
        ?>
                </form>
            </div>
        </div>
        <hr class="colorgraph">
    </section>
    </center>
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