<?php
session_start();
include ("Conexion.php");
$cnx=conectar();
?>
<html>
<head>
  <title></title>
</head>
<body>
<div id="alcalde" style="display: none;" class="col-xs-12 col-sm-8 col-md-6 col-sm-offset-2 col-md-offset-3">
    <form action="Registroalcalde.php" method="POST" class="form-horizontal form-label-left" enctype="multipart/form-data">
        <?php
        $consultaalcalde=mysql_query("SELECT * FROM votacion WHERE idmesa=$idmesa AND idrecinto=$idrecinto AND idtipocandidatura=1");
        $numconsultaalcalde=mysql_num_rows($consultaalcalde);
        if ($numconsultaalcalde>0) {
            echo "Ya no puedes";
        }
        else
        {
        ?>
        <input type='hidden' name='nv' value='<?php echo $nv; ?>'>
        <input type='hidden' name='idtipocandidatura' value='1'>
        <input type='hidden' name='idrecinto' value='<?php echo $idrecinto;?>'>
        <input type='hidden' name='idmesa' value='<?php echo $idmesa;?>'>
        <table class="table responsive-utilities table-bordered table-hover">
        <tr>
        <th>ELEGIR</th><th>SIGLA</th><th>CANTIDAD</th>
        </tr>
        <?php
            $c=0;
            $candidaturas=mysql_query("SELECT pp.idpartido,pp.descripcion,pp.logo
                        FROM recinto r, municipio m, candidatura c, partidopolitico pp
                        WHERE r.idmunicipio = m.idmunicipio
                        AND c.idmunicipio = m.idmunicipio
                        AND pp.idpartido = c.idpartido
                        AND c.idtipocandidatura=1
                        AND r.idrecinto=$idrecinto");
            while ($fp=mysql_fetch_array($candidaturas)) {
                $num=rand(5,201);
                $c++;
                echo "<tr>
                <td><input type='checkbox' name='$fp[0]' checked></td>
                <th>$fp[1]</th>
                <td><input class='form-control input-lg' type='text' name='v$c' id='v$c' onkeypress='return checknum(event)' tabindex='$c' min='0' max='1000' autocomplete='off' value='$num'  required></td>
                </tr>";
            }
        ?>
        </table>
        <span class="archivo">
            <input type="file" name="archivo" id="archivo" style="visibility: hidden" accept="image/*" capture="camera">
        </span>
        <label for="archivo">
            <h1 style="color:#FFF"><i class="fa fa-camera"></i></h1>
        </label><br><br>
        <input type='Submit' value='SUBIR VOTACION' name='ok' class="btn btn-lg btn-danger">
        <?php
        };
        ?>
    </form>
</div>
<div id="gobernador" style="display: none;" class="col-xs-12 col-sm-8 col-md-6 col-sm-offset-2 col-md-offset-3">
    <form action="Registrogobernador.php" method="POST" class="form-horizontal form-label-left" enctype="multipart/form-data">
        <?php
        $consultaalcalde=mysql_query("SELECT * FROM votacion WHERE idmesa=$idmesa AND idrecinto=$idrecinto AND idtipocandidatura=5");
        $numconsultaalcalde=mysql_num_rows($consultaalcalde);
        if ($numconsultaalcalde>0) {
            echo "Ya no puedes";
        }
        else
        {
        ?>
        <input type='hidden' name='nv' value='<?php echo $nv; ?>'>
        <input type='hidden' name='idtipocandidatura' value='5'>
        <input type='hidden' name='idrecinto' value='<?php echo $idrecinto;?>'>
        <input type='hidden' name='idmesa' value='<?php echo $idmesa;?>'>
        <table class="table responsive-utilities table-bordered table-hover">
        <tr>
        <th>ELEGIR</th><th>SIGLA</th><th>CANTIDAD</th>
        </tr>
        <?php
            $c=0;
            $candidaturas=mysql_query("SELECT pp.idpartido,pp.descripcion,pp.logo
                FROM candidatura c, partidopolitico pp
                WHERE pp.idpartido=c.idpartido
                AND c.idtipocandidatura=5");
            while ($fp=mysql_fetch_array($candidaturas)) {
                $num=rand(5,201);
                $c++;
                echo "<tr>
                <td><input type='checkbox' name='$fp[0]' checked></td>
                <th>$fp[1]</th>
                <td><input class='form-control input-lg' type='text' name='v$c' id='v$c' onkeypress='return checknum(event)' tabindex='$c' min='0' max='1000' autocomplete='off' value='$num' required></td>
                </tr>";
            }
        ?>
        </table>
        <span class="archivo">
            <input type="file" name="archivo2" id="archivo" style="visibility: hidden" accept="image/*" capture="camera">
        </span>
        <label for="archivo">
            <h1 style="color:#FFF"><i class="fa fa-camera"></i></h1>
        </label><br><br>
        <input type='Submit' value='SUBIR VOTACION' name='ok' class="btn btn-lg btn-danger">
        <?php
        };
        ?>
    </form>
</div>
<?php

?>
</body>
</html>
