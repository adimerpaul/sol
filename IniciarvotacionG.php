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

    if (id == "2") {
        $("#asambleistap").show();
        $("#asambleista").hide();
        $("#gobernador").hide();
    }
    if (id == "3") {
        $("#asambleistap").hide();
        $("#asambleista").show();
        $("#gobernador").hide();
    }
    if (id == "5") {
        $("#asambleistap").hide();
        $("#asambleista").hide();
        $("#gobernador").show();
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

                    <li class="active">Selección Votación Asambleista/Gobernador</li>

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

                <div id="asambleista" style="display: none;" class="col-xs-12 col-sm-8 col-md-6 col-sm-offset-2 col-md-offset-3">
                        <?php
                        
                        $consultaasamble=mysqli_query($cnx,"SELECT * FROM votacion WHERE idmesa=$idmesa AND idrecinto=$idrecinto AND idtipocandidatura=3");

                        $numconsultaasamble=mysqli_num_rows($consultaasamble);

                        $fconsultaasamble=mysqli_fetch_array($consultaasamble);

                        if ($numconsultaasamble>0) {

                            echo "<h5>LA VOTACION PARA ESTA MESA EN ESTE RECINTO YA HA SIDO REALIZADA </h5>";

                             echo "<a class='btn btn-primary' href='Vervotacionasamble.php?idvotacion=$fconsultaasamble[0]&idmesa=$idmesa&idrecinto=$idrecinto&tipo=1'>VER VOTACION</a>";

                        }

                        else

                        {

                        ?>
                        <form action="Registroasamble.php" method="GET" class="form-horizontal form-label-left" enctype="multipart/form-data" validate>

                        <input type='hidden' name='idtipocandidaturaasamble' value='3'>

                        <input type='hidden' name='idrecintoasamble' value='<?php echo $idrecinto;?>'>

                        <input type='hidden' name='idmesaasamble' value='<?php echo $idmesa;?>'>
                        <center><h2 style="color:#d9232d;"><i><u>REGISTRO DE VOTOS PARA ASAMBLEISTA POR TERRITORIO</u></i></h2></center>
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

                                        AND c.idtipocandidatura=3

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

                <div id="gobernador" style="display: none;" class="col-xs-12 col-sm-8 col-md-6 col-sm-offset-2 col-md-offset-3">

                    <form action="Registrogobernador.php" method="GET" class="form-horizontal form-label-left" enctype="multipart/form-data" validate>
    
                        <?php
                        
                        $consultagobernador=mysqli_query($cnx,"SELECT * FROM votacion WHERE idmesa=$idmesa AND idrecinto=$idrecinto AND idtipocandidatura=5");
    
                        $fconsultagobernador=mysqli_fetch_array($consultagobernador);
    
                        $numconsultagobernador=mysqli_num_rows($consultagobernador);
    
                        if ($numconsultagobernador>0) {
    
                            echo "<h5>LA VOTACION PARA ESTA MESA EN ESTE RECINTO YA HA SIDO REALIZADA</h5>";
    
                                 echo "<a class='btn btn-primary' href='Vervotaciongobernador.php?idvotacion=$fconsultagobernador[0]&idmesa=$idmesa&idrecinto=$idrecinto&tipo=5'>VER VOTACION</a>";
    
                        }
    
                        else
    
                        {
    
                        ?>
    
                        <input type='hidden' name='idtipocandidaturagober' value='5'>
    
                        <input type='hidden' name='idrecintogober' value='<?php echo $idrecinto;?>'>
    
                        <input type='hidden' name='idmesagober' value='<?php echo $idmesa;?>'>
                        <center><h2 style="color:#d9232d;"><i><u>REGISTRO DE VOTOS PARA GOBERNADOR</u></i></h2></center> 
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

                                        AND c.idtipocandidatura=5

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
                <div id="asambleistap" style="display: none;" class="col-xs-12 col-sm-8 col-md-6 col-sm-offset-2 col-md-offset-3">

                <form action="Registrogobernadorp.php" method="GET" class="form-horizontal form-label-left" enctype="multipart/form-data" validate>

                    <?php

                    $consultagobernador=mysqli_query($cnx,"SELECT * FROM votacion WHERE idmesa=$idmesa AND idrecinto=$idrecinto AND idtipocandidatura=2");

                    $fconsultagobernador=mysqli_fetch_array($consultagobernador);

                    $numconsultagobernador=mysqli_num_rows($consultagobernador);

                    if ($numconsultagobernador>0) {

                        echo "<h5>LA VOTACION PARA ESTA MESA EN ESTE RECINTO YA HA SIDO REALIZADA</h5>";

                        echo "<a class='btn btn-primary' href='Vervotacionasamblep.php?idvotacion=$fconsultagobernador[0]&idmesa=$idmesa&idrecinto=$idrecinto&tipo=2'>VER VOTACION</a>";

                    }

                    else

                    {

                        ?>

                        <input type='hidden' name='idtipocandidaturagober' value='2'>

                        <input type='hidden' name='idrecintogober' value='<?php echo $idrecinto;?>'>

                        <input type='hidden' name='idmesagober' value='<?php echo $idmesa;?>'>
                        <center><h2 style="color:#d9232d;"><i><u>REGISTRO DE VOTOS PARA ASAMBLEISTA POR POBLACION</u></i></h2></center>
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

                                        AND c.idtipocandidatura=2

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

