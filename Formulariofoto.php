<?php
session_start();
if($_SESSION['usr']!="")
{
 include ("header.php");?>
    <script type="application/javascript">
jQuery('input[type=file]').change(function(){
 var filename = jQuery(this).val().split('\\').pop();
 var idname = jQuery(this).attr('id');
 console.log(jQuery(this));
 console.log(filename);
 console.log(idname);
 jQuery('span.'+idname).next().find('span').html(filename);
});
</script>
   <style>
  .custom-input-file {
  background-color: yellow;
  color: #fff;
  cursor: pointer;
  font-size: 25px;
  font-weight: bold;
  margin: 0 auto 0;
  min-height: 15px;
  overflow: hidden;
  padding: 10px;
  position: relative;
  text-align: center;
  width: 250px;
  height: 250px;
  border-radius: 250px;
}

.custom-input-file .input-file {
 border: 10000px solid transparent;
 cursor: pointer;
 font-size: 10000px;
 margin: 0;
 opacity: 0;
 outline: 0 none;
 padding: 0;
 position: absolute;
 right: -1000px;
 top: -1000px;
 border-radius: 10px;
}
      
  </style>

    <!-- end header -->
	<section id="inner-headline">
	<div class="container">
		<div class="row">
			<div class="col-lg-12">
				<ul class="breadcrumb">
					<li><a href="index2.php"><i class="fa fa-home"></i></a><i class="icon-angle-right"></i></li>
					<li><a href="#">Registro</a><i class="icon-angle-right"></i></li>
					<li class="active">Nuevo Filial</li>
				</ul>
			</div>
		</div>
	</div>
	</section>
	<section id="content">
		<div class="container">
			<div class="row">
			    <div class="col-xs-12 col-sm-8 col-md-6 col-sm-offset-2 col-md-offset-3">
		<form action="Registrofoto.php" method="POST" class="form-horizontal form-label-left"  enctype="multipart/form-data">
        <center>
            <input type='hidden' name='idvotacion' value='<?php echo $_GET['nv'];?>'>
            <input type='hidden' name='idrecinto' value='<?php echo $_GET['idrecinto'];?>'>

            <div class="custom-input-file">
                <br><br>
            <input type="file" id="fichero-tarifas" class="input-file" name="archivo" value="Elegir imagen..." accept="image/*" capture="camera" required/>
            <i class="fa fa-camera" aria-hidden="true"></i>
            <br><h3>PASO 1 <br>CAPTURAR FOTO</h3>
            </div>
    
            <br><br>
        <input type='Submit' value='PASO 2: ENVIAR FOTO' name='ok' class="btn btn-lg btn-danger">
        </center>
        </form>
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
    echo '<META HTTP-EQUIV="Refresh" CONTENT ="0; URL=index.php">';
};
include ("footer.php")
?>