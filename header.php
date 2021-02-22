<?php
include ("Conexion.php");
$cnx=conectar();

echo '
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Solor-Formulario Recinto</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="description" content="Solor-RJFJ" />
<link rel="shortcut icon" type="image/png" href="img/voto.png"/>
<!-- css -->
<link href="css/bootstrap.min.css" rel="stylesheet" />
<link href="css/cubeportfolio.min.css" rel="stylesheet" />
<link href="css/style.css" rel="stylesheet" />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<!-- Theme skin -->
<link id="t-colors" href="skins/default.css" rel="stylesheet" />

	<!-- boxed bg -->
    <link id="bodybg" href="bodybg/bg1.css" rel="stylesheet" type="text/css" />
    
<script>
function mostrarSugerencia(str) {
var provinciaElegido = document.getElementById("provincia").value;

var xmlhttp;
var contenidosRecibidos = new Array();
var nodoMostrarResultados = document.getElementById("listaCiudades");
var contenidosAMostrar = "";

if (str.length==0) { document.getElementById("txtInformacion").innerHTML=""; nodoMostrarResultados.innerHTML = ""; return; }

xmlhttp=new XMLHttpRequest();
xmlhttp.onreadystatechange = function() {
  if (xmlhttp.readyState==4 && xmlhttp.status==200) {
    contenidosRecibidos = xmlhttp.responseText.split(",");
    document.getElementById("txtInformacion").innerHTML=contenidosRecibidos[0];
    nodoMostrarResultados.innerHTML = contenidosAMostrar;
    }
}
var cadenaParametros = "provincia="+encodeURIComponent(provinciaElegido);
xmlhttp.open("POST", "cboprovincia.php"); // Método post y url invocada
xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded"); // Establecer cabeceras de petición
xmlhttp.send(cadenaParametros); // Envio de parámetros usando POST
}
</script>

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
                            <ul class="dropdown-menu">';
                        if ($_SESSION["usr"]=="FJR065") {
                            echo '<li><a href="Formulariorecinto.php" class=" btn btn-round btn-danger"><h5>Nuevo Recinto</h5></a></li>
                                <li><a href="Formulariofilial.php" class=" btn btn-round btn-danger"><h5>Nuevo Filial</h5></a></li>
                                <li><a href="Formulariorecintoup.php" class=" btn btn-round btn-danger"><h5>Mod Mesa</h5></a></li>';
                            }
                            echo '<li><a href="Formulariovotacion.php" class=" btn btn-round btn-danger"><h5>Nueva Votación</h5></a></li>
                                </ul>
                        </li>
                        <li>';
                        if ($_SESSION["usr"]=="FJR065") {
                        echo '    
                            <a href="#" class="dropdown-toggle " data-toggle="dropdown" data-hover="dropdown"><h4>Ver registros  <i class="fa fa-angle-down"></i></h4></a>
                            <ul class="dropdown-menu">
                                <li><a href="Verrecintos.php" class="btn btn-round btn-danger"><h5>Ver Recintos</h5></a></li>
                                <li><a href="Verfilial.php" class="btn btn-round btn-danger"><h5>Ver Filiales</h5></a></li>
                                <li><a href="EstadisticaprovinciaG.php" class="btn btn-round btn-danger"><h5>Ver Estadísticas de Provincia G</h5></a></li>
                                <li><a href="EstadisticaprovinciaA.php" class="btn btn-round btn-danger"><h5>Ver Estadísticas de Provincia A</h5></a></li>
                            </ul>   
                        </li>';
                        }
                        echo '
                        <li>
                            <a href="#" class="dropdown-toggle " data-toggle="dropdown" data-hover="dropdown"><h4>Configuración  <i class="fa fa-angle-down"></i></h4></a>
                            <ul class="dropdown-menu">
                            
                                
                                <li><a href="Logout.php" class="btn btn-round btn-danger"><h5>Salir/Cerrar cuenta de '.$_SESSION["usr"].'</h5></a></li>
                            </ul>   
                        </li>
                        <li class="dropdown"><a href="#" class="dropdown-toggle " data-toggle="dropdown" data-hover="dropdown" data-delay="0" data-close-others="false"></a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </header>';
?>