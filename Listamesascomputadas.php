<?php
session_start();
if($_SESSION['usr']!="")
{
 include ("header.php");
?>
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
					<li class="active">Vista de Mesas y sus estados</li>
				</ul>
			</div>
		</div>
	</div>
	</section>
    <?php
            $idrecinto=$_GET['idrecinto'];
            $recinto=mysqli_query($cnx,"SELECT * FROM recinto WHERE idrecinto=$idrecinto");
            $frecinto=mysqli_fetch_array($recinto);
    ?>
	<section id="content">
			<div class="col-sm-offset-1 col-md-offset-1">
			    <div>
                    <h1>Recinto : <?php echo($frecinto[4]) ?></h1>
                    <center><h3>Orden de llenado de votación <big>--></big></h3></center>
					<table class="table responsive-utilities table-bordered table-hover">
                        <tr>
                            <th id='th2'>#</th><th id='th2'>VOTACION 1</th><th id='th2'>VOTACION 2</th><th id='th2'>VOTACION 3</th><th id='th2'> VOTACION 4</th><th id='th2'>VOTACION 5</th>
                        </tr>
                        <?php
                        $n=0;
                        echo "<tr>";
                        $result=mysqli_query($cnx,"SELECT * FROM mesa WHERE idrecinto=$idrecinto");
                        while ($row=mysqli_fetch_array($result)) {
                            $n=$n+1;
                            echo "<td id='th'>Mesa: $n</td>";
                            $mesavotada=mysqli_query($cnx,"SELECT *
                            FROM tipocandidatura t
                            WHERE EXISTS (
                            SELECT *
                            FROM votacion v
                            WHERE t.idtipocandidatura = v.idtipocandidatura AND idmesa=$row[0]
                            )");
                            while ($fmesa=mysqli_fetch_array($mesavotada)) {
                            echo "<td id='th'><a class='btn btn-sm btn-success'>$fmesa[1]</a></td>";
                            };
                        echo"</tr>";
                        };
                        ?>
                    </table>
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
