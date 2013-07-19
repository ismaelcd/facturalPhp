<?php

	// Creamos la ruta absoluta del sitio
    $INFUENTE_ROOT = "/var/www/facturalPhp";
    
    // Cargamos la conexión SQL y PostGres al servidor
    require $INFUENTE_ROOT.'/lib/conn.php';
    //require $INFUENTE_ROOT.'/lib/pg_conn.php';
    
    // Cargamos el header
    require $INFUENTE_ROOT.'/lib/header.php';

?>