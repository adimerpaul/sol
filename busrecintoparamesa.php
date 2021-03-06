<?php
//Archivo de conexión a la base de datos
include ("Conexion.php");
$cnx=conectar();

//Variable de búsqueda
$consultaBusqueda = $_POST['valorBusqueda'];
$idmunicipio = $_POST['municipio'];


//echo $idmunicipio;
//exit;
//Filtro anti-XSS
$caracteres_malos = array("<", ">", "\"", "'", "/", "<", ">", "'", "/");
$caracteres_buenos = array("&lt;", "&gt;", "&quot;", "&#x27;", "&#x2F;", "&#060;", "&#062;", "&#039;", "&#047;");
$consultaBusqueda = str_replace($caracteres_malos, $caracteres_buenos, $consultaBusqueda);

//Variable vacía (para evitar los E_NOTICE)
$mensaje = "";

//Comprueba si $consultaBusqueda está seteado
if (isset($consultaBusqueda)) {

	//Selecciona todo de la tabla asociado 
	//donde el nombre sea igual a $consultaBusqueda, 
	$consulta = mysqli_query($cnx,"SELECT r.idrecinto,r.recinto,r.cantidadmesa FROM municipio m INNER JOIN recinto r ON m.idmunicipio=r.idmunicipio WHERE r.idmunicipio=3 AND 					r.recinto LIKE '%$consultaBusqueda%'");

	//Obtiene la cantidad de filas que hay en la consulta
	$filas = mysqli_num_rows($consulta);
	//Si no existe ninguna fila que sea igual a $consultaBusqueda, entonces mostramos el siguiente mensaje
	if ($filas == 0)
	{
		$mensaje = "<center><h1 style='color:#d9232d;'><i><u>No hay ningún recinto con ese nombre</u></i></h1></center>";
	}
	else {
		//Si existe alguna fila que sea igual a $consultaBusqueda, entonces mostramos el siguiente mensaje
		$consultaBusqueda=strtoupper($consultaBusqueda);
		echo '<h4>Resultados para <strong>'.$consultaBusqueda.'</strong><h4>';
		//La variable $resultado contiene el array que se genera en la consulta, así que obtenemos los datos y los mostramos en un bucle
		while($fila = mysqli_fetch_array($consulta)) {
			//Output
			echo "<div class='col-lg-4'>
            <div class='pricing-box-alt special'>
              <div class='pricing-heading'>
                <h4><strong>$fila[1]</strong></h4>
              </div>
              <a href='Listamesascomputadas.php?idrecinto=$fila[0]'><div class='pricing-terms'>
              <h5>$fila[2] Mesas Registradas</h5>
              </div></a>
            </div>
          </div>";
		};//Fin while $resultados
	}; //Fin else $filas
};//Fin isset $consultaBusqueda
//Devolvemos el mensaje que tomará jQuery
echo $mensaje;
?>
