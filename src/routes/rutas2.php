<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app = new \Slim\App;

$app->get('/hola', function(Request $request, Response $response){
	$result = array(
        "status" => "correcto"
    );

    echo json_encode($result);
});