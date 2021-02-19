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

<title>Solor-Inicio de Registro de Votos</title>

<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<!-- css -->

<meta charset="UTF-8" />

<meta name="description" content="Solor-RJFJ" />

<link rel="shortcut icon" type="image/png" href="img/voto.png"/>

<meta name="viewport" content="width=device-width" />

<link href="css/bootstrap.min.css" rel="stylesheet" />

<link href="css/cubeportfolio.min.css" rel="stylesheet" />

<link href="css/style.css" rel="stylesheet" />

<link rel="stylesheet" type="text/css" href="css/estilocheck.css">

<script src="js/jquery.min.js"></script>

<!-- Theme skin -->

<link id="t-colors" href="skins/default.css" rel="stylesheet" />

<!-- boxed bg -->

<link id="bodybg" href="bodybg/bg1.css" rel="stylesheet" type="text/css" />



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

    .inputd{

        color:black;
        text-align: center;
        font-size: 40px;
        font-weight: bold;
        letter-spacing: 2rem;
        width:250px;
        height: 90px;

    }
    #select{

        color:black;
        text-align: center;
        font-weight: bold;

    }
    #th2{

        color:black;
        text-align: center;
        font-size:30px;
        font-weight: bold;
    }

</style>

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
                        <h2>Cantidad Ingresada: <big class="cant">0</big></h2>
                        <hr class="colorgraph">
                            <div class="table-responsive">
                                <table class="table">

                                    <tr>

                                        <th></th><th >Partido</th><th >Logo</th><th >Num votos</th>


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
                                        
                                        ORDER BY pp.idpartido");

                                    while ($fp=mysqli_fetch_array($candidaturas)) {

                                        $c++;

                                        echo "<tr style='background-color: $fp[3];'>
    
                                <td><input type='checkbox' name='$fp[0]' checked></td>
    
                                <th style='font-size: 20px;color: black'>$fp[1]</th>
                                
                                <td width=150><img style='max-width: 150%; height: auto;' src='imgpp/$fp[2]' class='img-fluid img-thumbnail' alt='Responsive image'/>ss</td>
    
                                <td><input class='form-control input-lg' style='color: black;font-weight: bold;font-size: 30px' type='tel' value='0' name='v$c' id='input$c' onkeypress='return checknum(event)' onkeyup='sumar()' tabindex='$c' maxlength='3' max='200' min='0' autocomplete='off' required></td>
    
                                </tr>";

                                    }

                                    ?>

                                </table>
                            </div>


                        <input type='Submit' value='SUBIR VOTACION'  name='ok' class="btn btn-lg btn-danger btnenviar">

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
                        <h2>Cantidad Ingresada: <big class="cant">0</big></h2>
                        <hr class="colorgraph">
                            <div class="table-responsive">
                                <table class="table">
                                    <tr>
                                        <th></th><th >Partido</th><th >Logo</th><th >Num votos</th>
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
                                        
                                        ORDER BY pp.idpartido");

                                    while ($fp=mysqli_fetch_array($candidaturas)) {

                                        $c++;

                                        echo "<tr style='background-color: $fp[3];'>
    
                                <td><input type='checkbox' name='$fp[0]' checked></td>
    
                                <th><p style='font-size: 30px;color: black'>$fp[1]</p></th>
                                
                                <td><img width='150' style='height: auto;' src='imgpp/$fp[2]' class='img-fluid img-thumbnail' alt='Responsive image'/></td>
    
                                <td><input class='form-control input-lg' style='color: black;font-weight: bold;font-size: 30px' type='tel' name='v$c' id='input$c-c' onkeypress='return checknum(event)' tabindex='$c'
                                onkeyup='sumar()' value='0' maxlength='3' max='200' min='0' autocomplete='off' required></td>
    
                                </tr>";

                                    }

                                    ?>

                                </table>
                            </div>

    
                        <input type='Submit' value='SUBIR VOTACION' name='ok' class="btn btn-lg btn-danger btnenviar">
    
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
<script type="text/javascript">
    var votosmax=<?=$votosmax?>;
    function sumar(){
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
            $('.cant').text('Cantidad excedida!');
            $('.btnenviar').hide();    
        }
        //console.log(sum);
        //return false;
    }
</script>
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
