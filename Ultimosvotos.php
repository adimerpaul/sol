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
    <title>Ver Votaciones Registrados</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Solor-RJFJ" />
    <link rel="shortcut icon" type="image/png" href="img/voto.png"/>
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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.23/css/jquery.dataTables.min.css">
    <!--tables-->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
<!--    <script src="js/stacktable.js"></script>-->


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
                                <li><a href="Vervotaciones.php" class="btn btn-round btn-danger"><h5>Ver Votaciones</h5></a></li>
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
                        <li><a href="#">Sistema</a><i class="icon-angle-right"></i></li>
                        <li class="active">Vista Votaciones Registrados</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
    <section id="content">
        <div class="container">
            <div class="row">
                <div class="col-xs-12">
                    <div class="table-responsive">
                        <form action="">
                            <label for="">Cantidad de muestra: </label><input id="cantidad" type="text" value="10">
                        </form>
                        <table id="example" class="display" style="width:100%">
                            <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Nombre</th>
                                <th>Celular</th>
                                <th>Recinto-Mesa</th>
                                <th>Tipo votacion</th>
                                <th>Imagen</th>
                                <th>Id</th>
                                <th>Opciones</th>
                            </tr>
                            </thead>
                            <tbody>
<!--                            <tr>-->
<!--                                <td>Tiger Nixon</td>-->
<!--                                <td>System Architect</td>-->
<!--                                <td>Edinburgh</td>-->
<!--                                <td>61</td>-->
<!--                                <td>2011/04/25</td>-->
<!--                                <td>$320,800</td>-->
<!--                            </tr>-->
                            </tbody>
                        </table>
                    </div>
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
<!--<script src="https://code.jquery.com/jquery-3.5.1.js"></script>-->
<script src="https://cdn.datatables.net/1.10.23/js/jquery.dataTables.min.js"></script>
<script>
    window.onload=function () {
        var t = $('#example').DataTable({
            "order": [[ 0, "desc" ]],
            "pageLength":50
        });
        $('#example').on('click', '.Mybtn', function () {
            if (confirm('Seguro de eliminar?')){
                var RowIndex = $(this).closest('tr');
                var data = t.row(RowIndex).data();
                // alert(data[6]);
                let id=data[6];
                $.ajax({
                    url:'./Consultas.php',
                    type:'POST',
                    data:{fun:'eliminar',id,cantidad:$('#cantidad').val()},
                    success:function (res) {
                        // console.log(res);
                        t.clear()
                            .draw();
                        // t.destroy();
                        // $('#example').empty();
                        // t = $('#example').DataTable();
                        let array=JSON.parse(res);
                        // console.log(array);
                        array.forEach(r=>{
                            // console.log(r.imagen);
                            let ima='';
                            if (r.imagen=='NULL'){
                                // ima='Sin foto';
                            }else{
                                // ima='<a href="'+r.imagen+'" target="_blank">Ver foto</a>';
                            }
                            t.row.add( [
                                r.hora,
                                r.nombre+' '+r.paterno+' '+r.materno,
                                r.cel,
                                r.recinto+' '+r.nummesa,
                                r.descripcion,
                                ima,
                                r.idvotacion,
                                '<button class="Mybtn">Eliminar</button>'
                            ] ).draw( false );
                        })
                    }
                })
            }

        });

        function myFunction() {
            setInterval(function(){
                // console.log("Hello");
                $.ajax({
                    url:'./Consultas.php',
                    type:'POST',
                    data:{fun:'ultimos',cantidad:$('#cantidad').val()},
                    success:function (res) {
                         // console.log(res);
                        t.clear()
                            .draw();
                        // t.destroy();
                        // $('#example').empty();
                        // t = $('#example').DataTable();
                        let array=JSON.parse(res);
                        console.log(array);
                        array.forEach(r=>{
                            // console.log(r.imagen);
                            let ima='';
                            if (r.imagen=='NULL'){
                                ima='';
                            }else{
                                ima='<a href="'+r.imagen+'" target="_blank"><img src="'+r.imagen+'" width="150" /></a>';
                            }
                            t.row.add( [
                                r.hora,
                                r.nombre+' '+r.paterno+' '+r.materno,
                                r.cel,
                                r.recinto+' '+r.nummesa,
                                r.descripcion,
                                ima,
                                r.idvotacion,
                                '<button class="Mybtn">Eliminar</button>'
                            ] ).draw( false );
                        })
                    }
                })
            }, 3000);
        }
        myFunction();

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
