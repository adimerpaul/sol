<?php
session_start();
if($_SESSION['usr']!="")
{
 include ("header.php");?>
	<!-- end header -->
	<section id="inner-headline">
	<div class="container">
		<div class="row">
			<div class="col-lg-12">
				<ul class="breadcrumb">
					<li><a href="index2.php"><i class="fa fa-home"></i></a><i class="icon-angle-right"></i></li>
					<li><a href="#">Recinto / Mesa</a><i class="icon-angle-right"></i></li>
					<li class="active">Actualizar Cantidad de Votantes</li>
				</ul>
			</div>
		</div>
	</div>
	</section>
	<section id="content">
		<div class="container">
			<div class="row">
			    <div class="col-xs-12 col-sm-8 col-md-6 col-sm-offset-2 col-md-offset-3">
					<form name="Frmmesa" role="form" method="POST" class="register-form" >
						<h2>Modificar Datos <small>en este formulario Modifica numero de Votantes.</small></h2>
						<hr class="colorgraph">
						<div class="row">
							<div class="col-xs-12 col-sm-6 col-md-6">
								<div class="form-group">
									<select name='recinto' id='recinto' class="form-control">
										<option value="">Selecionar Recinto</option>
										<?php $recinto=mysqli_query($cnx,"SELECT * FROM recinto");
 										  while ($frecinto = mysqli_fetch_array($recinto)) {
											   echo '<option value="'.$frecinto[0].'">'.$frecinto[4].'</option>';

												
											}
										?>

									</select>
								</div>
							</div>
							<div class="col-xs-12 col-sm-6 col-md-6">
								<div class="form-group" id='mesarecinto'>
										
								</div>
							</div>
						</div>
						<div class="form-group" id='tammesa'>
							
						</div>
						<hr class="colorgraph">
						<div class="row">
							<div class="col-xs-12 col-md-6"><input type="button" disabled value="MODIFICAR" id='modificarmesa' class="btn btn-theme btn-block btn-lg" tabindex="7"></div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</section>
</div>
<script>
$('#recinto').change(function(){
	console.log(this.value);
	var idrec= this.value;
	$.ajax({
            data: {'idrecinto':idrec, 'funcion':"datorecinto" },
            url:   'mesa.php',
            type:  'POST',

            beforeSend: function () 
            {
                
            },

          success: function(data)            
       {
		   //foreach(data){
				 console.log(JSON.parse(data));
				 row=JSON.parse(data);
				 var op='';
				 op='<select name="mesas" id="mesas" class="form-control input-lg"><option value="">Seleccione mesa...</option>';
				 for(var i=0;i<row.length;i++){
					 op+='<option value="'+row[i]['idmesa']+'">'+row[i]['nummesa']+'</option> ';
					 }
					 $('#selmesa').html(op);

					 op+='</select>';
					 console.log(op);
					 $('#mesarecinto').html(op);
					 $('#modificarmesa').prop('disabled',true);
					 $('#tammesa').html('');
				 /*$.each(data,function(){
					 op+='<option value='+data->idrecinto+'>'+data->nummesa+'</option>';

				 });
				 */
				 
		//   }
	   }
	})
});

$('#mesarecinto').change(function(){
	var datomesa=$( "#mesarecinto option:selected" ).prop('value');
	console.log(datomesa);
	if(datomesa!="")
	{
		$.ajax({
            data: {'idmesa':datomesa, 'funcion':"datomesa" },
            url:   'mesa.php',
            type:  'POST',

            beforeSend: function () 
            {
                
            },

          success: function(data)            
       		{
		   //foreach(data){
			console.log(data);
			$('#tammesa').html('<input type="number" name="max" id="max" class="form-control input-lg"  tabindex="4" value="'+data+'" required>');
			$('#modificarmesa').prop('disabled',false);

		}
	
		});}
		else {$('#modificarmesa').prop('disabled',true);}
});
$('#modificarmesa').click(function(){
	var max=$('#max').prop('value');
	var id=$( "#mesarecinto option:selected" ).prop('value');
	console.log(max);
	$.ajax({
            data: {'idmesa':id, 'votosmax':max },
            url:   'modmesa.php',
            type:  'POST',

            beforeSend: function () 
            {
                
            },

          success: function(data)            
       		{
				if(data=1)
				alert("Modificado correctamente");
			   }
	});

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