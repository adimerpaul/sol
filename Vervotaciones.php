<?php
session_start();
if($_SESSION['usr']!="")
{
 include ("header.php");?>

 
<style type="text/css">
    #th{
        color:steelblue;
        font-size:120%;
    }
    #th2{
        color:#656565;
        font-size:150%;
    }
</style>

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
			    <div class="col-xs-12 col-sm-8 col-md-6 col-sm-offset-2 col-md-offset-3">
					<table class="table responsive-utilities table-bordered table-hover">
                    <tr>
                        <th>#</th>
                        <th id='th2'>NOMBRE COMPLETO</th>
                        <th id='th2'>ID FILIAL</th>
                        <th id='th2'>NUMERO CELULAR</th>
                        <th id='th2'>TIPO DE VOTACION</th>
                        <th id='th2'>IMAGEN</th>
                        <th id='th2'>ESTADO</th>
                        <th id='th2'>ELIMINAR</th>
                    </tr>
                    <?php
                    $c=0;
                    $votaciones=mysqli_query($cnx,"SELECT CONCAT(f.nombre,' ',f.paterno,' ',f.materno),v.usuario,f.cel,t.descripcion,v.imagen,v.estado,v.idvotacion FROM filial f, votacion v, tipocandidatura t WHERE f.usuario=v.usuario AND t.idtipocandidatura=v.idtipocandidatura ORDER by v.idvotacion");
                    while ($fvotaciones=mysqli_fetch_array($votaciones)) {
                        $c++;
                            echo "<tr>
                                <td id='th'>$c</td>
                                <td id='th'>$fvotaciones[0]</td>
                                <td id='th'>$fvotaciones[1]</td>
                                <td id='th'>$fvotaciones[2]</td>
                                <td id='th'>$fvotaciones[3]</td>
                                <td width=100><a href='".$fvotaciones[4]."' target='_blank'><img download style='max-width: 100%; height: auto;' src='$fvotaciones[4]' class='img-fluid img-thumbnail' alt='Responsive image'/></a></td>";
                                if ($fvotaciones[5]=='T') {
                                    echo "<td id='th'>EXITO</td>";
                                }
                                else{
                                    echo "<td id='th'>FALLÓ</td>";
                                }
                            echo "<td id='th'><a href='EliminarVoto.php?id=$fvotaciones[6]'>Eliminar</a></td></tr>";
                    };
                    ?>
                    </table>
				</div>
			</div>
		</div>
	</section>
</div>
<?php }
else
{
?>
<script type="text/javascript">alert("Primero debes acceder con tu cuenta filial");</script>
<?php
    echo '<META HTTP-EQUIV="Refresh" CONTENT ="0; URL=Login.php">';
};
include ("footer.php")
?>
