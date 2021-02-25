<?php
include ("Conexion.php");
$cnx=conectar();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <script src="cssalert/sweetalert.min.js"></script>
    <link rel="stylesheet" type="text/css" href="cssalert/sweetalert.css">
</head>
<body>
<?php
$id=$_GET['id'];
$filial=mysqli_query($cnx,"DELETE FROM votacion WHERE idvotacion=$id");
if ($filial)
{
    echo "<META HTTP-EQUIV='Refresh' CONTENT ='2; URL=Vervotaciones.php'>";
    ?>
    <script type="text/javascript">
        sweetAlert("Perfecto!","El Filial fue eliminado correctamente","success");
    </script>
<?php
}
else
{
echo "<META HTTP-EQUIV='Refresh' CONTENT ='2; URL=Vervotaciones.php'>";
?>
    <script type="text/javascript">
        sweetAlert("Oops!","Ocurrio un error en la eliminación","error");
    </script>
    <?php
}
?>
</body>
</html>
