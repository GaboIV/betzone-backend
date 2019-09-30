<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

ini_set('date.timezone', 'AMERICA/Caracas');

require '../vendor/autoload.php';
require '../src/config/db.php';

$app = new \Slim\App;

$app->get('/cuentas', function(Request $request, Response $response){
	$db = new db();
    $db = $db->conectar();

    $i = 0;

    $c1 = "SELECT * FROM cuentas ORDER BY nombre ASC";
    $ec1 = $db->query($c1);
    while ($rc1 = $ec1->fetch(PDO::FETCH_ASSOC)){ 
    	$cuentas[] = $rc1; 

    	$c2 = "SELECT * FROM bancos WHERE id_banco = '".$rc1['banco_id']."'";
    	$ec2 = $db->query($c2);
    	$rc2 = $ec2->fetchAll(PDO::FETCH_OBJ);

    	$cuentas[$i]['banco_id'] = $rc2; 

    	$i++; 
    }

    $result = array(
        "cuentas" => $cuentas
    );

    echo json_encode($result);
});

$app->get('/pagos', function(Request $request, Response $response){
	$db = new db();
    $db = $db->conectar();

    $i = 0;

    $c1 = "SELECT * FROM pagos ORDER BY registro DESC";
    $ec1 = $db->query($c1);
    while ($rc1 = $ec1->fetch(PDO::FETCH_ASSOC)){ 
    	$pagos[] = $rc1; 

    	$c2 = "SELECT * FROM bancos WHERE id_banco = '".$rc1['banco_id']."'";
    	$ec2 = $db->query($c2);
    	$rc2 = $ec2->fetchAll(PDO::FETCH_OBJ);

    	$pagos[$i]['banco_id'] = $rc2; 

    	$c3 = "SELECT * FROM cuentas WHERE id_cuenta = '".$rc1['cuenta_id']."'";
    	$ec3 = $db->query($c3);
    	$rc3 = $ec3->fetchAll(PDO::FETCH_OBJ);

    	$pagos[$i]['cuenta_id'] = $rc3; 

    	$c4 = "SELECT id_usuario, usuario FROM usuario WHERE id_usuario = '".$rc1['id_usuario']."'";
    	$ec4 = $db->query($c4);
    	$rc4 = $ec4->fetchAll(PDO::FETCH_OBJ);

    	$pagos[$i]['id_usuario'] = $rc4; 

    	$i++; 
    }

    $result = array(
        "pagos" => $pagos
    );

    echo json_encode($result);
});

$app->get('/bancos', function(Request $request, Response $response){
	$db = new db();
    $db = $db->conectar();

    $i = 0;

    $c1 = "SELECT * FROM bancos ORDER BY nombre ASC";
    $ec1 = $db->query($c1);
    while ($rc1 = $ec1->fetch(PDO::FETCH_ASSOC)){ 
    	$bancos[] = $rc1; 
    	$i++; 
    }

    $result = array(
        "bancos" => $bancos
    );

    echo json_encode($result);
});

$app->post('/pagos/agregar', function(Request $request, Response $response){

    $banco_id = $request->getParam('banco_id');
    $cedula = $request->getParam('cedula');
    $cuenta_id = addslashes($request->getParam('cuenta_id'));
    $fecha_realizada = $request->getParam('fecha_realizada');
    $id_usuario = addslashes($request->getParam('id_usuario'));
    $monto = $request->getParam('monto');
    $referencia = $request->getParam('referencia');
    $fecha = date("Y-m-d H:i:s");

    $consulta = "INSERT INTO pagos (monto, cedula, fecha_realizada, referencia, registro, estatus, id_usuario, banco_id, cuenta_id) VALUES ('".$monto."','".$cedula."','".$fecha_realizada."','".$referencia."','".$fecha."','0','".$id_usuario."','".$banco_id."','".$cuenta_id."')";
    try{
        $db = new db();
        $db = $db->conectar();

        $stmt = $db->prepare($consulta);
        $stmt->bindParam(':referencia', $referencia);
        if ($stmt->execute()){
            $result = array(
                "status" => "correcto"
            );

            echo json_encode($result);
        }     

    } catch(PDOException $e){
        echo '{"error": {"text": '.$consulta.'}';
    }
});

$app->post('/pago/actualizar', function(Request $request, Response $response){
    $id_pago = $request->getParam('id_pago');
    $estatus = $request->getParam('estatus');

    $db = new db();
    $db = $db->conectar();

    $c1 = "SELECT * FROM pagos WHERE id_pago = '$id_pago'";
    $e1 = $db->query($c1);
    $n1 = $e1->rowCount();

    if ($n1 == 0) {
    	$result = array(
            "status" => "error",
            "mensaje" => "Pago inexistente"
        );

        echo json_encode($result);
    } elseif ($n1 == 1) {
    	$pago = $e1->fetch(PDO::FETCH_ASSOC);

    	if ($pago['estatus'] != 0) {
    		$result = array(
	            "status" => "error",
	            "mensaje" => "Pago ya se ha cambiado de estatus"
	        );

	        echo json_encode($result);
    	} else {
    		$id_usuario = $pago['id_usuario'];

    		$c2 = "SELECT id_usuario, disponible FROM usuario WHERE id_usuario = '$id_usuario'";
    		$e2 = $db->query($c2);

    		$n2 = $e2->rowCount();

    		if ($n2 == 0) {
    			$result = array(
		            "status" => "error",
		            "mensaje" => $c2
		        );

		        echo json_encode($result);
    		} else {
		    	$usuario = $e2->fetch(PDO::FETCH_ASSOC);

		    	$n_disponible = $usuario['disponible'] + $pago['monto'];

		    	$cp1 = "UPDATE pagos SET estatus='$estatus' WHERE id_pago='$id_pago'";
                $ecp1 = $db->prepare($cp1);

                if ($ecp1->execute()) {
                	$cp2 = "UPDATE usuario SET disponible='$n_disponible' WHERE id_usuario = '$id_usuario'";
	                $ecp2 = $db->prepare($cp2);
	                if ($ecp2->execute()) {
	                	$result = array(
				            "status" => "correcto",
				            "mensaje" => "Pago #$id_pago fue actualizado correctamente"
				        );

				        echo json_encode($result);
	                }
                }
			}
    	}
	}
});

$app->run();


