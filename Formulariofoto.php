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
<SCRIPT LANGUAGE="JavaScript">
history.forward()
</SCRIPT>
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
            <input type="file" id="seleccionArchivos" class="input-file" name="archivo" value="Elegir imagen..." accept="image/*" capture="camera" required/>
            <i class="fa fa-camera" aria-hidden="true"></i>
            <br><h3>PASO 1 <br>SELECCIONAR FOTO</h3>
            <i class="fa fa-search"></i>
            </div>
            <br><br>
                     <img id="imagenPrevisualizacion" width=450px>
            <br><br>
        <button type='Submit' name='ok' class="btn btn-lg btn-danger"><i class="fa fa-paper-plane"></i> PASO 2: ENVIAR FOTO</button>
        </center>
        </form>
				</div>
			</div>
		</div>
	</section>
</div>

    <script>
        // Obtener referencia al input y a la imagen

const $seleccionArchivos = document.querySelector("#seleccionArchivos"),
  $imagenPrevisualizacion = document.querySelector("#imagenPrevisualizacion");

// Escuchar cuando cambie
$seleccionArchivos.addEventListener("change", () => {
  // Los archivos seleccionados, pueden ser muchos o uno
  const archivos = $seleccionArchivos.files;
  // Si no hay archivos salimos de la función y quitamos la imagen
  if (!archivos || !archivos.length) {
    $imagenPrevisualizacion.src = "";
    return;
  }
  // Ahora tomamos el primer archivo, el cual vamos a previsualizar
  const primerArchivo = archivos[0];
  // Lo convertimos a un objeto de tipo objectURL
  const objectURL = URL.createObjectURL(primerArchivo);
  // Y a la fuente de la imagen le ponemos el objectURL
  $imagenPrevisualizacion.src = objectURL;
});
    </script>
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