<?php
session_start();
if($_SESSION['usr']!="")
{
 include ("header.php");
$idmunicipio=$_GET['idmunicipio'];
$idprovincia=$_GET['idprovincia'];
 ?>
  <!-- end header -->
  <head>
    <script>
      function buscar() {
          var textoBusqueda = $("input#busqueda").val();
          // console.log(textoBusqueda);
          // return false;
          if (textoBusqueda != '') {
              $.post("busrecintoparamesa.php", {valorBusqueda: textoBusqueda}, function(mensaje) {
                  if (mensaje!=''){
                      $("#resultadoBusqueda").html(mensaje);
                  }
              }); 
          } else { 
              $("#resultadoBusqueda").html('<p>No hay nada que mostrar</p>');
          };
      };
    </script>

  </head>
  <section id="inner-headline">
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <ul class="breadcrumb">
          <li><a href="index2.php"><i class="fa fa-home"></i></a><i class="icon-angle-right"></i></li>
          <li><a href="#">Registro</a><i class="icon-angle-right"></i></li>
          <li class="active">Nueva Votación</li>
        </ul>
      </div>
    </div>
  </div>
  </section>
  <section id="content">
    <div class="container">
        <h1>MESAS CON VOTACIONES CONCLUIDAS POR RECINTOS</h1>
    </div>
    <div class="container">
      <form>
        <h2><label>Por favor realíce la búsqueda</label> <small>del recinto electoral.</small></h2>
        <?php
        if ($_SESSION['usr']=='FJR065') {
        ?>
        <div class="row">
              <center>
              <h3>Escriba el nombre de su recinto <i class="fa fa-angle-down"></i></h3> <input type="text" name="busqueda" id="busqueda" value="" placeholder="" maxlength="50" class="form-control " style="text-transform:uppercase;" autocomplete="off" onKeyUp="buscar();"/>
              </center>
        </div>
        <?php
        }
        ?>
        <hr class="colorgraph">
        <div class="row" id="resultadoBusqueda">
          <?php
          // Rescatamos el parámetro que nos llega mediante la url que invoca xmlhttp
          $municipio=$_GET["idmunicipio"];
          $provincia=$_GET["idprovincia"];
                $result=mysqli_query($cnx,"SELECT r.idrecinto,r.recinto,r.cantidadmesa
                          FROM municipio m INNER JOIN recinto r ON m.idmunicipio=r.idmunicipio 
                            WHERE r.idmunicipio=3");
          while ($fila = mysqli_fetch_array($result)){
          echo "<a href='Listamesascomputadas.php?idrecinto=$fila[0]'><div class='col-lg-4'>
            <div class='pricing-box-alt special'>
              <div class='pricing-heading'>
                <h4><strong>$fila[1]</strong></h4>
              </div>
              <div class='pricing-terms'>
              <h5>$fila[2] Mesas Registradas</h5>
              </div></a>
            </div>
          </div>";
          }
          ?> 
        </div>
        <hr class="colorgraph">
      </form>
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
