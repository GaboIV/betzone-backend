<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app = new \Slim\App;

//Obtener todos los clientes
$app->get('/equipos/ver/{pagina}', function(Request $request, Response $response){

    $pagina = $request->getAttribute('pagina');

    try{
        $db = new db();
        $db = $db->conectar();

        $TAMANO_PAGINA = 20;
        $i = 0;

        if ($pagina == 1){ 
            $inicio = 0;
        } else { 
            $inicio = ($pagina - 1) * $TAMANO_PAGINA; 
        }
        
        $consulta = "SELECT * FROM equipo ORDER BY id_equipo ASC LIMIT ".$inicio." , ".$TAMANO_PAGINA;
        $ejecutar = $db->query($consulta);

        while($fila = $ejecutar->fetch(PDO::FETCH_ASSOC)) {
            $equipos[] = $fila;

            $nombre_fichero = 'imagenes/equipos/'.$equipos[$i]['id_equipo'].'.png';

            if (file_exists($nombre_fichero)) {
                $equipos[$i]['img'] = $equipos[$i]['id_equipo'].'.png';
            } else {
                $equipos[$i]['img'] = null;
            }

            $ejecutar2 = $db->query("SELECT id_liga FROM equipo_liga WHERE id_equipo = '". $fila['id_equipo'] . "'");
            $ligas = array();
            $j = 0;

            while ($fila2 = $ejecutar2->fetch(PDO::FETCH_ASSOC)) {
                $ejecutar3 = $db->query("SELECT nombre_liga FROM liga WHERE id_liga = '". $fila2['id_liga'] . "'");
                $fila3 = $ejecutar3->fetch(PDO::FETCH_ASSOC);

                $equipos[$i]['ligas'][$j]['id_liga'] = $fila2['id_liga'];
                $equipos[$i]['ligas'][$j]['nombre'] = $fila3['nombre_liga'];

                $j++;
            }            

            $i++;
        }     
        
        $result = array(
            "status" => "correcto",
            "equipos" => $equipos
        );

        $db = null;

        echo json_encode($result);

    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}';
    }
});

//Obtener un solo cliente
$app->get('/api/clientes/{id}', function(Request $request, Response $response){

    $id = $request->getAttribute('id');

    $consulta = "SELECT * FROM clientes WHERE id='$id'";
    try{
        // Instanciar la base de datos
        $db = new db();

        // Conexión
        $db = $db->conectar();
        $ejecutar = $db->query($consulta);
        $cliente = $ejecutar->fetchAll(PDO::FETCH_OBJ);
        $db = null;

        //Exportar y mostrar en formato JSON
        echo json_encode($cliente);
        
    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}';
    }
});


// Agregar Cliente
$app->post('/api/clientes/agregar', function(Request $request, Response $response){
    $nombre = $request->getParam('nombre');
    $apellidos = $request->getParam('apellidos');
    $telefono = $request->getParam('telefono');
    $email = $request->getParam('email');
    $direccion = $request->getParam('direccion');
    $ciudad = $request->getParam('ciudad');
    $departamento = $request->getParam('departamento');


    $consulta = "INSERT INTO clientes (nombre, apellidos, telefono, email, direccion, ciudad, departamento) VALUES
    (:nombre, :apellidos, :telefono, :email, :direccion, :ciudad, :departamento)";
    try{
        // Instanciar la base de datos
        $db = new db();

        // Conexión
        $db = $db->conectar();
        $stmt = $db->prepare($consulta);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':apellidos',  $apellidos);
        $stmt->bindParam(':telefono',      $telefono);
        $stmt->bindParam(':email',      $email);
        $stmt->bindParam(':direccion',    $direccion);
        $stmt->bindParam(':ciudad',       $ciudad);
        $stmt->bindParam(':departamento',      $departamento);
        $stmt->execute();
        echo '{"notice": {"text": "Cliente agregado"}';
    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}';
    }
});


// Actualizar Cliente
$app->put('/api/clientes/actualizar/{id}', function(Request $request, Response $response){
    $id = $request->getAttribute('id');
    $nombre = $request->getParam('nombre');
    $apellidos = $request->getParam('apellidos');
    $telefono = $request->getParam('telefono');
    $email = $request->getParam('email');
    $direccion = $request->getParam('direccion');
    $ciudad = $request->getParam('ciudad');
    $departamento = $request->getParam('departamento');


     $consulta = "UPDATE clientes SET
				nombre 	        = :nombre,
				apellidos 	    = :apellidos,
                telefono	    = :telefono,
                email		    = :email,
                direccion   	= :direccion,
                ciudad 		    = :ciudad,
                departamento    = :departamento
			WHERE id = $id";


    try{
        // Instanciar la base de datos
        $db = new db();

        // Conexion
        $db = $db->conectar();
        $stmt = $db->prepare($consulta);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':apellidos',  $apellidos);
        $stmt->bindParam(':telefono',      $telefono);
        $stmt->bindParam(':email',      $email);
        $stmt->bindParam(':direccion',    $direccion);
        $stmt->bindParam(':ciudad',       $ciudad);
        $stmt->bindParam(':departamento',      $departamento);
        $stmt->execute();
        echo '{"notice": {"text": "Cliente actualizado"}';
    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}';
    }
});


// Borrar cliente
$app->delete('/api/clientes/borrar/{id}', function(Request $request, Response $response){
    $id = $request->getAttribute('id');
    $sql = "DELETE FROM clientes WHERE id = $id";
    try{
        // Instanciar la base de datos
        $db = new db();

        // Conexion
        $db = $db->conectar();
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $db = null;
        echo '{"notice": {"text": "Cliente borrado"}';
    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}';
    }
});