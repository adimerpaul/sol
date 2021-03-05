<?php
session_start();
if($_SESSION['usr']!="")
{
 include ("header.php");?>
    <script type="text/javascript">
function mostrar(id) {
    $('.cant').text('0');
    $('.inputd').each(function(){
            $(this).val(0);
    });
    if (id == "1") {
        $("#alcalde").show();
        $("#concejal").hide();
    }

    if (id == "4") {
        $("#alcalde").hide();
        $("#concejal").show();
    }
}
</script>

<script>
function checknum(e) {
    tecla = (document.all) ? e.keyCode : e.which;
    //Tecla de retroceso para borrar, siempre la permite
    if (tecla == 8) {
        return true;
    }
    // Patron de entrada, en este caso solo acepta numeros
    patron = /[0-9]+/;
    tecla_final = String.fromCharCode(tecla);
    return patron.test(tecla_final);
}
</script>

<style type="text/css">
    #input{
        color:black;
        text-align: center;
        font-size: 32px;
        font-weight: bold;
        letter-spacing: 0.1rem;
        width:100px;
        height: 90px;
    }
    #select{
        color:black;
        text-align: center;
        font-weight: bold;
    }
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
        font-size:20px;
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

                    <li><a href="#">Registro</a><i class="icon-angle-right"></i></li>

                    <li class="active">Nueva Votación</li>

                    <li class="active">Selección Votación Alcalde/Concejal</li>

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
    $votosmax=$fmesa[3];
    ?>

    <center>

    <section id="content">

        <div class="container">

            <h2><label>Recinto : <?php echo($nombrerecinto) ?></label> <small>Número de mesa : <?php echo($numeromesa) ?></small></h2>

            <hr class="colorgraph">

            <div class="row">

                <div class="col-xs-12 col-sm-6 col-md-6">

                    <div class="form-group">

                    <select id="select" name="papeleta" class="form-control input-lg" onChange="mostrar(this.value);">

                        <option value="0"> Elige un tipo de papeleta...</option>

                        <?php

                        $tipo=$_GET['tipo'];

                        $candidatura=mysqli_query($cnx,"SELECT * FROM tipocandidatura WHERE tipo='$tipo'");

                        while ($fcandidatura=mysqli_fetch_array($candidatura)) {

                            echo "<option value= '$fcandidatura[0]'> $fcandidatura[1]";

                        };

                        ?>

                    </select>

                    </div>

                </div>

            </div>
        </div>

    <!--RECINTOS-->

    <div class="container">

        <div class="row">

                <div id="alcalde" style="display: none;" class="col-xs-12 col-sm-8 col-md-6 col-sm-offset-2 col-md-offset-3">
                        <?php
                        
                         $consultaalcalde=mysqli_query($cnx,"SELECT * FROM votacion WHERE idmesa=$idmesa AND idrecinto=$idrecinto AND idtipocandidatura=1");

                        $numconsultaalcalde=mysqli_num_rows($consultaalcalde);

                        $fconsultaalcalde=mysqli_fetch_array($consultaalcalde);

                        if ($numconsultaalcalde>0) {

                            echo "<h5>LA VOTACION PARA ESTA MESA EN ESTE RECINTO YA HA SIDO REALIZADA</h5>";

                             echo "<a class='btn btn-primary' href='Vervotacionalcalde.php?idvotacion=$fconsultaalcalde[0]&idmesa=$idmesa&idrecinto=$idrecinto&tipo=1'>VER VOTACION</a>";

                        }

                        else

                        {

                        ?>
                    <form action="Registroalcalde.php" method="GET" class="form-horizontal form-label-left" enctype="multipart/form-data">

                        <input type='hidden' name='idtipocandidaturaalca' value='1'>

                        <input type='hidden' name='idrecintoalca' value='<?php echo $idrecinto;?>'>

                        <input type='hidden' name='idmesaalca' value='<?php echo $idmesa;?>'>
                        <center><h2 style="color:#d9232d;"><i><u>REGISTRO DE VOTOS PARA ALCALDE</u></i></h2></center>
                        <hr class="colorgraph">
                        <h3>Cantidad maxima de electores: <big><?=$votosmax?></big></h3>
                        <h2>Cantidad ingresada: <big class="cant">0</big></h2>
                        <hr class="colorgraph">
                            <table class="table responsive-utilities table-bordered table-hover">
                                <tr>
                                <th id="th2">CASILLA N°</th><th id="th2">PARTIDO POLITICO</th><th id="th2">LOGO</th><th id="th2">REGISTRO VOTOS</th>
                                </tr>

                                            <?php

                                            $c=0;

                                            $candidaturas=mysqli_query($cnx,"SELECT pp.idpartido,pp.descripcion,pp.logo,pp.color

                                                FROM recinto r, municipio m, candidatura c, partidopolitico pp

                                                WHERE r.idmunicipio = m.idmunicipio

                                                AND c.idmunicipio = m.idmunicipio

                                                AND pp.idpartido = c.idpartido

                                                AND c.idtipocandidatura=1

                                                AND r.idrecinto=$idrecinto
                                                
                                                ORDER BY c.posicion");

                                            while ($fp=mysqli_fetch_array($candidaturas)) {

                                                $c++;

                                        echo "<tr style='background-color: $fp[3];'>
                                        <td style='display: none;'><input type='checkbox' name='$fp[0]'checked></td>
                                        <th id='t'>$c</th>
                                        <th id='t'>$fp[1]</th>
                                        <td><br><img style='max-width: 80px; height: 110px;' src='imgpp/$fp[2]' class='img-fluid img-thumbnail' alt='Responsive image'/></td>
                                        <td id='th2'><input class='form-control input-lg inputd' type='tel' name='v$c' id='input' onkeypress='return checknum(event)'
                                        onkeyup='sumar()' value='0' id='input$c-c' tabindex='$c' maxlength='3' max='200' min='0' autocomplete='off' required></td>
                                        </tr>";

                                            }

                                            ?>

                            </table>

                        <button type='Submit'   name='ok' class="btn btn-lg btn-danger btnenviar">SUBIR VOTACION <i class="fa fa-paper-plane"></i></button>

                        <?php

                        };

                        ?>

                    </form>

                </div>

                <div id="concejal" style="display: none;" class="col-xs-12 col-sm-8 col-md-6 col-sm-offset-2 col-md-offset-3">

                   <form action="Registroconcejal.php" method="GET" class="form-horizontal form-label-left" enctype="multipart/form-data">
    
                        <?php
                        
                        $consultaconcejal=mysqli_query($cnx,"SELECT * FROM votacion WHERE idmesa=$idmesa AND idrecinto=$idrecinto AND idtipocandidatura=4");
    
                        $fconsultaconcejal=mysqli_fetch_array($consultaconcejal);
    
                        $numconsultaconcejal=mysqli_num_rows($consultaconcejal);
    
                        if ($numconsultaconcejal>0) {
    
                            echo "<h5>LA VOTACION PARA ESTA MESA EN ESTE RECINTO YA HA SIDO REALIZADA</h5>";
    
                                 echo "<a class='btn btn-primary' href='Vervotacionconcejal.php?idvotacion=$fconsultaconcejal[0]&idmesa=$idmesa&idrecinto=$idrecinto&tipo=4'>VER VOTACION</a>";
    
                        }
    
                        else
    
                        {
    
                        ?>
    
                        <input type='hidden' name='idtipocandidaturaconce' value='4'>
    
                        <input type='hidden' name='idrecintoconce' value='<?php echo $idrecinto;?>'>
    
                        <input type='hidden' name='idmesaconce' value='<?php echo $idmesa;?>'>
                        <center><h2 style="color:#d9232d;"><i><u>REGISTRO DE VOTOS PARA CONCEJAL</u></i></h2></center>
                        <hr class="colorgraph">
                        <h3>Cantidad maxima de electores: <big><?=$votosmax?></big></h3>
                        <h2>Cantidad ingresada: <big class="cant">0</big></h2>
                        <hr class="colorgraph">
                        <table class="table responsive-utilities table-bordered table-hover">
                        <tr>
                        <th id="th2">CASILLA N°</th><th id="th2">PARTIDO POLITICO</th><th id="th2">LOGO</th><th id="th2">REGISTRO VOTOS</th>
                        </tr>
                                <?php
                                $c=0;
                                $candidaturas=mysqli_query($cnx,"SELECT pp.idpartido,pp.descripcion,pp.logo,pp.color
                                    FROM recinto r, municipio m, candidatura c, partidopolitico pp
                                    WHERE r.idmunicipio = m.idmunicipio
                                    AND c.idmunicipio = m.idmunicipio
                                    AND pp.idpartido = c.idpartido
                                    AND c.idtipocandidatura=4
                                    AND r.idrecinto=$idrecinto
                                    ORDER BY c.posicion");
                                while ($fp=mysqli_fetch_array($candidaturas)) {
                                    $c++;
                            echo "<tr style='background-color: $fp[3];'>
                            <td style='display: none;'><input type='checkbox' name='$fp[0]'checked></td>
                            <th id='t'>$c</th>
                            <th id='t'>$fp[1]</th>
                            <td><br><img style='max-width: 80px; height: 110px;' src='imgpp/$fp[2]' class='img-fluid img-thumbnail' alt='Responsive image'/></td>
                            <td id='th2'><input class='form-control input-lg inputd' type='tel' name='v$c' id='input' onkeypress='return checknum(event)'
                            onkeyup='sumar()' value='0' id='input$c-c' tabindex='$c' maxlength='3' max='200' min='0' autocomplete='off' required></td>
                            </tr>";
                                }
                                ?>
                        </table>
                        <button type='Submit'   name='ok' class="btn btn-lg btn-danger btnenviar">SUBIR VOTACION <i class="fa fa-paper-plane"></i></button>
                        <?php
                        };
                        ?>
    
                    </form>

                </div>

        </div>

    </div>



    </section>

    </center>

</div>


<script type="text/javascript">
    var votosmax=<?=$votosmax?>;
    function sumar(){
        //console.log('as');
        var sum = 0;
        $('.inputd').each(function(){
            //console.log($(this).val());
            sum += parseFloat($(this).val());  // Or this.innerHTML, this.innerText
        });    
        if (sum<=votosmax) {
            if (isNaN(sum)) {
                $('.cant').text('Un valor vacio!');    
            }else{
                $('.cant').text(sum);
            }
            $('.btnenviar').show();
        }else{
            $('.cant').text('Cantidad NO VALIDA!');
            $('.btnenviar').hide();    
        }
        //console.log(sum);
        //return false;
    }
</script>
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
