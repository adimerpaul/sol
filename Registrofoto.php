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
<title></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="description" content="Solor-RJFJ" />
<link rel="shortcut icon" type="image/png" href="img/voto.png"/>
<!-- css -->
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width" />
<link href="css/bootstrap.min.css" rel="stylesheet" />
<link href="css/cubeportfolio.min.css" rel="stylesheet" />
<link href="css/style.css" rel="stylesheet" />
<!-- Theme skin -->
<link id="t-colors" href="skins/default.css" rel="stylesheet" />
    <!-- boxed bg -->
<link id="bodybg" href="bodybg/bg1.css" rel="stylesheet" type="text/css" />
<style type="text/css">
*{ font-family:Segoe, "Segoe UI", "DejaVu Sans", "Trebuchet MS", Verdana, sans-serif}
.main{ margin:auto; border:1px solid #7C7A7A; width:50%; text-align:left; padding:30px; background:#85c587}
input[type=submit]{ background:#6ca16e; width:100%;
    padding:5px 15px; 
    background:#ccc; 
    cursor:pointer;
    font-size:16px;
}
table td{ padding:5px;}
</style>
</head>
<body OnLoad="NoBack();">
<div id="wrapper">
    <section id="content">
        <div class="container">
            <form role="form" class="register-form">
                <h2><label>Proceso de votación completada</label> <small>...en un momento volvemos a selección de mesa</small></h2>
                <hr class="colorgraph">
                <div>
                    <center>
                           <?php
                              $idvotacion=$_POST['idvotacion'];
                              $idrecinto=$_POST['idrecinto'];
                                $directorio = 'imgpadron/';
                                $tipoArchivo = strtolower(pathinfo($_FILES["archivo"]["name"], PATHINFO_EXTENSION));
                                $archivo = $directorio.basename($_FILES['archivo']['name']);
                                $adjunto = $directorio ."P-" .$idvotacion ."." . $tipoArchivo;
                                if (move_uploaded_file($_FILES['archivo']['tmp_name'], $adjunto)) {
                                      echo "<h5>El archivo es válido y se cargó correctamente.</h5><br><br>";
                                       echo"<a href='".$adjunto."' target='_blank'><img src='".$adjunto."' width='150'></a>";
                                    } else {
                                       echo "<h5>La subida ha fallado</h5>";
                                    }
                            mysqli_query($cnx,"UPDATE votacion SET imagen='$adjunto' WHERE idvotacion=$idvotacion");
                            echo "<META HTTP-EQUIV='Refresh' CONTENT ='3; URL=Formulariomesa.php?idrecinto=$idrecinto'>";
                            ?>
                            <script type="text/javascript">
                            sweetAlert("Exito!","Registro de foto correctamente","success");
                            </script>
                    </center>
                </div>
                <hr class="colorgraph">
            </form>
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
<?php
}
else
{
?>
<script type="text/javascript">alert('Primero debes acceder con tu cuenta filial');</script>
<?php
    echo "<META HTTP-EQUIV='Refresh' CONTENT ='0; URL=index.php'>";
};
?>
</body>
</body>
</html>