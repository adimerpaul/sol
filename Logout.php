<?php
// start a session
session_start();
 
// assume that we’ve initialized a couple of session variables in the other script already
 
// destroy everything in this session
session_destroy();

echo "<META HTTP-EQUIV='REFRESH' CONTENT='1; URL =index.php'>";
?>
<html>
<head>
    <script src="cssalert/sweetalert.min.js"></script>
    <link rel="stylesheet" type="text/css" href="cssalert/sweetalert.css">
</head>
<body>
    <script type="text/javascript">
    sweetAlert("Adios!","Fin de sesion","success");
  </script>
</body>
</html>