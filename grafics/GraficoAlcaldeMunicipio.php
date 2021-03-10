<?php
session_start();
include ("../Conexion.php");
$cnx=conectar();

$idmunicipio=$_GET['idmunicipio'];
$cn=mysqli_query($cnx,"SELECT COUNT(*) as cantidad
FROM recinto r2 INNER JOIN mesa m ON m.idrecinto =r2.idrecinto 
WHERE r2.idmunicipio =$idmunicipio");
$c=0;
while($fd=mysqli_fetch_array($cn))
{
    $cantidadtotal=$fd[0];
};
$cn=mysqli_query($cnx,"SELECT COUNT(*) as cantidad
FROM recinto r2 INNER JOIN mesa m ON m.idrecinto =r2.idrecinto 
WHERE r2.idmunicipio =$idmunicipio");
$c=0;
while($fd=mysqli_fetch_array($cn))
{
    $cantidadtotal=$fd[0];
};

$cn=mysqli_query($cnx,"SELECT COUNT(*) as cantidad
FROM recinto r2 INNER JOIN mesa m ON m.idrecinto =r2.idrecinto 
WHERE r2.idmunicipio =$idmunicipio
AND r2.idrecinto IN (SELECT idrecinto FROM votacion v WHERE v.idtipocandidatura=1)");
$c=0;
while($fd=mysqli_fetch_array($cn))
{
    $cantidadvotada=$fd[0];
};
$porcentaje=($cantidadvotada/$cantidadtotal)*100

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
    // {
    //     chart: {
    //         plotBackgroundColor: null,
    //             plotBorderWidth: null,
    //             plotShadow: false,
    //             type: 'pie'
    //     },
    //     title: {
    //         text: 'Browser market shares in January, 2018'
    //     },
    //     tooltip: {
    //         pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
    //     },
    //     accessibility: {
    //         point: {
    //             valueSuffix: '%'
    //         }
    //     },
    //     plotOptions: {
    //         pie: {
    //             allowPointSelect: true,
    //                 cursor: 'pointer',
    //                 dataLabels: {
    //                 enabled: true,
    //                     format: '<b>{point.name}</b>: {point.percentage:.1f} %'
    //             }
    //         }
    //     },
    //     series: [{
    //         name: 'Brands',
    //         colorByPoint: true,
    //         data: [{
    //             name: 'Chrome',
    //             y: 61.41,
    //             sliced: true,
    //             selected: true
    //         }, {
    //             name: 'Internet Explorer',
    //             y: 11.84
    //         }, {
    //             name: 'Firefox',
    //             y: 10.85
    //         }, {
    //             name: 'Edge',
    //             y: 4.67
    //         }, {
    //             name: 'Safari',
    //             y: 4.18
    //         }, {
    //             name: 'Sogou Explorer',
    //             y: 1.64
    //         }, {
    //             name: 'Opera',
    //             y: 1.6
    //         }, {
    //             name: 'QQ',
    //             y: 1.2
    //         }, {
    //             name: 'Other',
    //             y: 2.61
    //         }]
    //     }]
    // }
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
                                    AND v.idtipocandidatura=1
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
                        text: 'TOTAL ACUMULADO CANDIDATO ALCALDE(SA) EN ESTE MUNICIPIO'
                      },

                      subtitle: {
                        text: 'Partidos politicos participantes'
                      },
                      plotOptions: {
                        column: {
                          depth: 65
                        }
                      },
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
                                    AND v.idtipocandidatura=1
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
                    $votosvalidos=0;
                    $votosnovalidos=0;
                    $cn=mysqli_query($cnx,"SELECT pp.descripcion, SUM( dv.cantidadvoto ),pp.color
                                    FROM municipio m, recinto r, votacion v, detallevotacion dv, partidopolitico pp
                                    WHERE m.idmunicipio=r.idmunicipio
                                    AND r.idrecinto=v.idrecinto
                                    AND v.idvotacion=dv.idvotacion
                                    AND pp.idpartido=dv.idpartido
                                    AND v.idtipocandidatura=1
                                    AND m.idmunicipio=$idmunicipio
                                    GROUP BY dv.idpartido");
                    $c=0;
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
                        url:'./Votos.php?idmunicipio=' + idmunicipio + '&func=alcalde',
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
 
<div class="container">
    <figure class="highcharts-figure">
    <div id="dona"></div>
        <center>
        <i class="glyphicon glyphicon-ok"></i> <big>VOTOS VALIDOS: <strong class="votosvalidos"><?=$votosvalidos?> VOTOS</strong></big>
        <br>
        <i class="glyphicon glyphicon-remove"></i> <big>VOTOS NO VALIDOS: <strong class="votosnovalidos"><?=$votosnovalidos?> VOTOS</strong></big>
        <?php
        $porcentaje=mysqli_query($cnx,"SELECT(cantidadvoto) FROM detallevotacion");
         while($fd=mysqli_fetch_array($porcentaje))
            {
                $sum=$sum+$fd[0];
            }
            $sum=($sum*100)/223133;
        ?>
       <br><br><i class="glyphicon glyphicon-signal"></i> <big>AL <?=number_format ($sum,2)?> %:</big>
        </center>
        <br><br>
    </figure>
    <figure class="highcharts-figure">
    <div id="column"></div>
        <center>
        <i class="glyphicon glyphicon-ok"></i> <big>VOTOS VALIDOS: <strong class="votosvalidos"><?=$votosvalidos?> VOTOS</strong></big>
        <br>
        <i class="glyphicon glyphicon-remove"></i> <big>VOTOS NO VALIDOS: <strong class="votosnovalidos"><?=$votosnovalidos?> VOTOS</strong></big>
        <br>
        </center>
    </figure>
</div>
<br><br>
 
</body>
</html>
