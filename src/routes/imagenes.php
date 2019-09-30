<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app = new \Slim\App;

$app->get('/imagenes/equipo/{imagen}', function(Request $request, Response $response){

    $imagen = $request->getAttribute('imagen');

    $directorio = 'imagenes/equipos/'.$imagen;
    
    if (file_exists($directorio)) {
        
    } else {
        $directorio = 'imagenes/equipos/sinimagen.png';
    }

    $fp = fopen($directorio, 'rb');

    header("Content-Type: image/png");
    header("Content-Length: " . filesize($directorio));

    fpassthru($fp);

    exit;
});