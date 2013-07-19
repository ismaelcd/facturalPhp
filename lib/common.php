<?php

	// Creamos la ruta absoluta del sitio
    $FACTURALPHP_ROOT = "/var/www/facturalPhp";
    
    // Cargamos la conexión SQL y PostGres al servidor
    require $FACTURALPHP_ROOT.'/lib/conn.php';
    //require $INFUENTE_ROOT.'/lib/pg_conn.php';
    
    // Cargamos el header
    require $FACTURALPHP_ROOT.'/lib/header.php';

?>