<?php
session_start();
include ("../Conexion.php");
$cnx=conectar();
?>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <script type="text/javascript" src="js/jquery.min.js"></script>
        <script src="js/jquery.min.js"></script>
        <script src="https://code.highcharts.com/highcharts.js"></script>
        <script src="https://code.highcharts.com/highcharts-3d.js"></script>
        <script src="https://code.highcharts.com/modules/exporting.js"></script>
        <script src="https://code.highcharts.com/modules/export-data.js"></script>
        <script src="https://code.highcharts.com/modules/accessibility.js"></script>
        <link href="../css/bootstrap.min.css" rel="stylesheet" />
        <link href="../css/style.css" rel="stylesheet" />
        <link href="../css/cubeportfolio.min.css" rel="stylesheet" />

<script type="text/javascript">
    $(function () {
        $(document).ready(function () {
            // Build the chart
            $('#dona').highcharts({
                chart: {
                    type: 'pie',
                    options3d: {
                        enabled: true,
                        alpha: 45
                    }
                },
                title: {
                    text: 'TOTAL ACUMULADO CANDIDATO GOBERNADOR(A) EN ESTE MUNICIPIO'
                },
                subtitle: {
                    text: 'Partidos politicos participantes'
                },
                plotOptions: {
                    pie: {
                        innerSize: 100,
                        depth: 45
                    }
                },
                    xAxis: {
                        categories: [<?php
                        $idmunicipio=$_GET['idmunicipio'];
                        $cn=mysqli_query($cnx,"SELECT pp.descripcion
                                    FROM municipio m, recinto r, votacion v, detallevotacion dv, partidopolitico pp
                                    WHERE m.idmunicipio=r.idmunicipio
                                    AND r.idrecinto=v.idrecinto
                                    AND v.idvotacion=dv.idvotacion
                                    AND pp.idpartido=dv.idpartido
                                    AND v.idtipocandidatura=5
                                    AND m.idmunicipio=$idmunicipio
                                    GROUP BY dv.idpartido");
                        $c=0;
                        while($fd=mysqli_fetch_array($cn))
                       {
                        echo "'".$fd[0]."',"; 
                       };
                         ?>],
                        labels: {
                            skew3d: true,
                            style: {
                                fontSize: '20px'
                            }
                        }
                    },
                    series: [{
                    colorByPoint: true,
                    data: [
                    <?php
                    $idmunicipio=$_GET['idmunicipio'];
                    $cn=mysqli_query($cnx,"SELECT pp.descripcion, SUM( dv.cantidadvoto ),pp.color
                                    FROM municipio m, recinto r, votacion v, detallevotacion dv, partidopolitico pp
                                    WHERE m.idmunicipio=r.idmunicipio
                                    AND r.idrecinto=v.idrecinto
                                    AND v.idvotacion=dv.idvotacion
                                    AND pp.idpartido=dv.idpartido
                                    AND v.idtipocandidatura=5
                                    AND m.idmunicipio=$idmunicipio
                                    GROUP BY dv.idpartido");
                    $c=0;
                    while($fd=mysqli_fetch_array($cn))
                   {
                    echo"{
                        name: '".$fd[0]."',
                        y: ".$fd[1]." ,color: '$fd[2]'},";
                   };
                   
                    ?>
                    ]
                }]
            });
        });
    });
</script>

<script type="text/javascript">
    $(function () {
        $(document).ready(function () {
            // Build the chart
            $('#column').highcharts({
                chart: {
                        renderTo: 'column',
                        type: 'column',
                        options3d: {
                          enabled: true,
                          alpha: 15,
                          beta: 15,
                          depth: 50,
                          viewDistance: 25
                        }
                        },
                    title: {
                        text: 'TOTAL ACUMULADO CANDIDATO GOBERNADOR (A) EN ESTE MUNICIPIO'
                      },
                      subtitle: {
                        text: 'Partidos politicos participantes'
                      },
                      plotOptions: {
                        column: {
                          depth: 65
                        }
                      },
                    yAxis: {
                        title: {
                            text: 'Cantidad de votos de 100 en 100'
                        }
                    },
                    xAxis: {
                        categories: [<?php
                        $idmunicipio=$_GET['idmunicipio'];
                        $cn=mysqli_query($cnx,"SELECT pp.descripcion
                                    FROM municipio m, recinto r, votacion v, detallevotacion dv, partidopolitico pp
                                    WHERE m.idmunicipio=r.idmunicipio
                                    AND r.idrecinto=v.idrecinto
                                    AND v.idvotacion=dv.idvotacion
                                    AND pp.idpartido=dv.idpartido
                                    AND v.idtipocandidatura=5
                                    AND m.idmunicipio=$idmunicipio
                                    GROUP BY dv.idpartido");
                        $c=0;
                        while($fd=mysqli_fetch_array($cn))
                       {
                        echo "'".$fd[0]."',"; 
                       };
                         ?>],
                        labels: {
                            skew3d: true,
                            style: {
                                fontSize: '20px'
                            }
                        }
                    },
                    series: [{
                    colorByPoint: true,
                    data: [
                    <?php
                    $idmunicipio=$_GET['idmunicipio'];
                    $cn=mysqli_query($cnx,"SELECT pp.descripcion, SUM( dv.cantidadvoto ),pp.color
                                    FROM municipio m, recinto r, votacion v, detallevotacion dv, partidopolitico pp
                                    WHERE m.idmunicipio=r.idmunicipio
                                    AND r.idrecinto=v.idrecinto
                                    AND v.idvotacion=dv.idvotacion
                                    AND pp.idpartido=dv.idpartido
                                    AND v.idtipocandidatura=5
                                    AND m.idmunicipio=$idmunicipio
                                    GROUP BY dv.idpartido");
                    $c=0;
                    while($fd=mysqli_fetch_array($cn))
                   {
                    echo"{
                        name: '".$fd[0]."',
                        y: ".$fd[1]." ,color: '$fd[2]'},";
                   };
                   
                    ?>
                    ]
                }]
            });
        });
    });
</script>

    </head>
<body>
<div class="row">
    <div class="col">
    <a href="../index.php" class="btn btn-block btn-danger"><i class="glyphicon glyphicon-circle-arrow-left"></i> VOLVER A INICIO</a>  
    </div>
</div>
<div class="container">
    <figure class="highcharts-figure">
    <div id="dona"></div>
    </figure>
    <figure class="highcharts-figure">
    <div id="column"></div>
    </figure>
</div>
<div class="row">
    <div class="col">
    <a href="../index.php" class="btn btn-block btn-danger"><i class="glyphicon glyphicon-circle-arrow-left"></i> VOLVER A INICIO</a>  
    </div>
</div>
</body>
</html>
