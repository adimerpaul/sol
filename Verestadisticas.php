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
					<li class="active">Acceso</li>
				</ul>
			</div>
		</div>
	</div>
	</section>
	<section id="content">
	<div id="container" style="min-width: 310px; height: 400px; max-width: 600px; margin: 0 auto">
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
