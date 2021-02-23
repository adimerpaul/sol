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
					<li class="active">Vista Recintos Registrados</li>
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
                        <th id='th2'>#</th> <th id='th2'>PROVINCIA</th><th id='th2'>MUNICIPIO</th><th id='th2'>NOMBRE RECINTO</th>
                                            <?php 
                        if ($_SESSION['usr']=='FJR065') {
                         ?>
                        <th id='th2'><i class="fa fa-trash-o" aria-hidden="true"></i></th><th id='th2'><i class="fa fa-pencil" aria-hidden="true"></i></th></tr>
                        <?php
                        }
                        else
                        {
                        ?>
                    </tr>
                    <?php
                    }
                    $recintos=mysqli_query($cnx,"SELECT p.nombreprovincia, m.nombremunicipio,r.recinto,r.idrecinto
											FROM recinto r, provincia p, municipio m
											WHERE p.idprovincia = m.idprovincia
											AND m.idmunicipio = r.idmunicipio");
											$n=0;
                        while ($frecintos=mysqli_fetch_array($recintos)) {
                            $n=$n+1;
                            echo "<tr>
                                <td id='th'>$n</td>
                                <td id='th'>$frecintos[0]</td>
                                <td id='th'>$frecintos[1]</td>
                                <th id='th'>$frecintos[2]</th>";
                                if ($_SESSION['usr']=='FJR065') {
                                    echo "<td id='th'><a href='Eliminarrecinto.php?id=$frecintos[3]' class='btn-danger btn-sm'>Eliminar</a></td>";
                                    echo "<td id='th'><a href='Formularioeditarrecinto.php?id=$frecintos[3]' class='btn-success btn-sm'>Editar</a></td></tr>";
                                }
                                else{
                                    echo"</tr>";
                                }

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