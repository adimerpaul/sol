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
                    plotBackgroundColor: null,
                    plotBorderWidth: null,
                    plotShadow: false,
                    type: 'pie',
                    options3d: {
                        enabled: true,
                        alpha: 45
                    }
                },
                title: {
                    text: 'TOTAL ACUMULADO CANDIDATO ALCALDE(SA) EN ESTE MUNICIPIO'
                },
                tooltip: {
                    pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
                },
                subtitle: {
                    text: 'Partidos politicos participantes'
                },
                // plotOptions: {
                //     pie: {
                //         innerSize: 100,
                //         depth: 45
                //     }
                // },
                plotOptions: {
                    pie: {
                        innerSize: 100,
                        depth: 45,
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: true,
                            format: '<b>{point.name}</b>: {point.percentage:.1f} %'
                        }
                    }
                },
                xAxis: {
                    labels: {
                        skew3d: true,
                        style: {
                            fontSize: '20px'
                        }
                    }
                },
                series: [{
                    colorByPoint: true,
                    //data: ['http://localhost/sol/grafics/Votos.php?idmunicipio=3']
                    // data: [10, 9, 8, 7, 6, 5, 4, 3, 2,5]
                    data: [
                        <?php
                        $idmunicipio=$_GET['idmunicipio'];
                        $cn=mysqli_query($cnx,"SELECT pp.descripcion, SUM( dv.cantidadvoto ),pp.color
                                    FROM municipio m, recinto r, votacion v, detallevotacion dv, partidopolitico pp
                                    WHERE m.idmunicipio=r.idmunicipio
                                    AND r.idrecinto=v.idrecinto
                                    AND v.idvotacion=dv.idvotacion
                                    AND pp.idpartido=dv.idpartido
                                    AND v.idtipocandidatura=3
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
                        text: 'TOTAL ACUMULADO CANDIDATO ASAMBLEISTA EN ESTE MUNICIPIO'
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
                                    AND v.idtipocandidatura=3
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
                                    AND v.idtipocandidatura=3
                                    AND m.idmunicipio=$idmunicipio
                                    GROUP BY dv.idpartido");
                    $c=0;
                    $votosnovalidos=0;
                    $votosvalidos=0;
                    while($fd=mysqli_fetch_array($cn))
                   {
                    if ($fd[0]=='BLANCO' || $fd[0]=='NULO') {
                        $votosnovalidos+=$fd[1];
                    }
                    else{
                        $votosvalidos+=$fd[1];
                    }
                    echo"{
                        name: '".$fd[0]."',
                        y: ".$fd[1]." ,color: '$fd[2]'},";
                   };
                   
                    ?>
                    ]
                }]
            });
            var chart1 = $('#dona').highcharts();
            var column1 = $('#column').highcharts();
            var idmunicipio=<?=$idmunicipio?>;
            function myFunction() {
                setInterval(function(){
                    // console.log("Hello");

                    $.ajax({
                        url:'./Votos.php?idmunicipio='+idmunicipio+'&func=territorio',
                        success:function (res) {
                            let array=JSON.parse(res);
                            let a=[];
                            let votosvalidos=0;
                            let votosnovalidos=0;
                            array.forEach(r=>{
                                // console.log(r.color);
                                a.push({name: r.name, y: parseInt(r.y) , color: r.color});
                                if (r.name=='BLANCO' || r.name=='NULO'){
                                    votosnovalidos+=parseInt(r.y);
                                }else{
                                    votosvalidos+=parseInt(r.y);
                                }
                            });
                            $('.votosvalidos').html(votosvalidos+' VOTOS');
                            $('.votosnovalidos').html(votosnovalidos+' VOTOS');
                            console.log(a);
                            chart1.series[0].update({
                                data: a
                            },false);
                            column1.series[0].update({
                                data: a
                            },false);
                            chart1.redraw();
                            column1.redraw();
                        }
                    })
                }, 3000);
            }
            myFunction();
        });
    });
</script>

    </head>
<body>
<div class="row">
    <div class="col">
    <a href="../index2.php" class="btn btn-block btn-danger"><i class="glyphicon glyphicon-circle-arrow-left"></i> VOLVER A INICIO</a>  
    </div>
</div>
<div class="container">
    <figure class="highcharts-figure">
    <div id="dona"></div>
        <center>
            <i class="glyphicon glyphicon-ok"></i> <big>VOTOS VALIDOS: <strong class="votosvalidos"><?=$votosvalidos?> VOTOS</strong></big>
            <br>
            <i class="glyphicon glyphicon-remove"></i> <big>VOTOS NO VALIDOS: <strong class="votosnovalidos"><?=$votosnovalidos?> VOTOS</strong></big>
        </center>
    </figure>
    <figure class="highcharts-figure">
    <div id="column"></div>
        <center>
            <i class="glyphicon glyphicon-ok"></i> <big>VOTOS VALIDOS: <strong class="votosvalidos"><?=$votosvalidos?> VOTOS</strong></big>
            <br>
            <i class="glyphicon glyphicon-remove"></i> <big>VOTOS NO VALIDOS: <strong class="votosnovalidos"><?=$votosnovalidos?> VOTOS</strong></big>
        </center>
    </figure>
</div>
<br><br>
<div class="row">
    <div class="col">
    <a href="../index2.php" class="btn btn-block btn-danger"><i class="glyphicon glyphicon-circle-arrow-left"></i> VOLVER A INICIO</a>  
    </div>
</div>
</body>
</html>
