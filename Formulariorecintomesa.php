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
          var municipio=<?=$idmunicipio?>;
          // console.log(textoBusqueda);
          // return false;
          if (textoBusqueda != '') {
              $.post("busrecinto.php", {valorBusqueda: textoBusqueda,municipio:municipio}, function(mensaje) {
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
  <?php
    $municipio=mysqli_query($cnx,"SELECT * FROM municipio WHERE idmunicipio=$idmunicipio");
    $fmunicipio=mysqli_fetch_array($municipio);
    $nombremunicipio=$fmunicipio[1];
    $provincia=mysqli_query($cnx,"SELECT * FROM provincia WHERE idprovincia=$idprovincia");
    $fprovincia=mysqli_fetch_array($provincia);
    $nombreprovincia=$fprovincia[1];
    ?> 
  <section id="content">
    <div class="container">
        <h1>Provincia : <?php echo($nombreprovincia) ?> - Municipio : <?php echo($nombremunicipio) ?></h1>
        <?php  
        if ($_SESSION['usr']=='FJR065') {
        ?>
        <center>
        <h3>GRÁFICOS TOTALES ESTADÍSTICOS DE ESTE MUNICIPIO</h3>
        <a <?php echo "href='grafics/GraficoAlcaldeMunicipio.php?idmunicipio=$idmunicipio'";?> class="btn btn-large btn-info"> <i class="fa fa-signal"></i> Ver Gráfico de Alcalde</a>
        <a <?php echo "href='grafics/GraficoConcejalMunicipio.php?idmunicipio=$idmunicipio'";?> class="btn btn-large btn-info"> <i class="fa fa-signal"></i> Ver Gráfico de Concejal</a>
        <a <?php echo "href='grafics/GraficoAsambleMunicipio.php?idmunicipio=$idmunicipio'";?> class="btn btn-large btn-info"> <i class="fa fa-signal"></i> Ver Gráfico de A. Territorio</a>
        <a <?php echo "href='grafics/GraficoAsamblePMunicipio.php?idmunicipio=$idmunicipio'";?> class="btn btn-large btn-info"> <i class="fa fa-signal"></i> Ver Gráfico de A. Población</a>
        <a <?php echo "href='grafics/GraficoGoberMunicipio.php?idmunicipio=$idmunicipio'";?> class="btn btn-large btn-info"> <i class="fa fa-signal"></i> Ver Gráfico de Gobernador</a>
        </center><br>
        <?php 
        }
        ?>
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
          if ($municipio) {
          $result=mysqli_query($cnx,"SELECT r.idrecinto,r.recinto,r.cantidadmesa
                          FROM municipio m INNER JOIN recinto r 
                            ON m.idmunicipio=r.idmunicipio
                            WHERE r.idmunicipio='".$municipio."'");
          while ($fila = mysqli_fetch_array($result)){
          echo "<a href='Formulariomesa.php?idrecinto=$fila[0]'><div class='col-lg-4'>
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

          } else {
              echo 'No se han recibido datos';
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
