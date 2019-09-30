<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

ini_set('date.timezone', 'AMERICA/Caracas');

require '../vendor/autoload.php';
require '../src/config/db.php';

$app = new \Slim\App;

$app->get('/mensajes/{criterio}/{id_usuario}/{pagina}', function(Request $request, Response $response){

	$db = new db();
    $db = $db->conectar();

    $id_usuario = $request->getAttribute('id_usuario');
    $criterio = $request->getAttribute('criterio');
    $pagina = $request->getAttribute('pagina');

    $tamano = 20;
    $i = 0;

    if ($pagina == 1 OR $pagina == 8888){ 
        $inicio = 0;
    } else { 
        $inicio = ($pagina - 1) * $tamano; 
    }

    if ($pagina == 8888) {
    	$criterios = " WHERE id_usuario = '$id_usuario' AND serial = '$criterio' ";
    } else {
    	if ($criterio != 'todas') {
	        $criterios = " WHERE id_usuario = '$id_usuario' AND (upper(titulo) LIKE upper('%" . $criterio . "%') OR upper(texto) LIKE upper('%" . $criterio . "%') OR upper(para) LIKE upper('%" . $criterio . "%') OR upper(desde) LIKE upper('%" . $criterio . "%'))";
	    } else {
	        $criterios = " WHERE id_usuario = '$id_usuario' ";
	    }
    }
    
    
    $consulta = "SELECT * FROM mensajes ".$criterios." ORDER BY fecha_hora DESC LIMIT ".$inicio." , ".$tamano;
    $ejecutar = $db->query($consulta);

    while($fila = $ejecutar->fetch(PDO::FETCH_ASSOC)) {
    	$mensajes[] = $fila;
    }

    $result = array(
        "status" => $consulta,
        "mensajes" => $mensajes
    );

    $db = null;

    echo json_encode($result);
});

$app->run();