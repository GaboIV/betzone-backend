<?php
$app->get('/equipos/ver/{pagina}/{criterio}', function(Request $request, Response $response){

    $pagina = $request->getAttribute('pagina');
    $criterio = $request->getAttribute('criterio');

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

        if ($criterio != 'todos') {
            $criterios = " WHERE upper(nombre_equipo) LIKE upper('%" . $criterio . "%') OR upper(id_wihi_equipo) LIKE upper('%" . $criterio . "%') OR upper(acro) LIKE upper('%" . $criterio . "%') OR upper(nacionalidad) LIKE upper('%" . $criterio . "%') OR upper(estadio) LIKE upper('%" . $criterio . "%') OR upper(id_equipo) LIKE upper('%" . $criterio . "%')";
        } else {
            $criterios = "";
        }
        
        $consulta = "SELECT * FROM equipo ".$criterios." ORDER BY id_equipo DESC LIMIT ".$inicio." , ".$TAMANO_PAGINA;
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
$app->post('/equipos/actualizar', function(Request $request, Response $response){
    $nombre = $request->getParam('nombre_equipo');
    $acro = $request->getParam('acro');
    $estadio = $request->getParam('estadio');
    $nacionalidad = $request->getParam('nacionalidad');
    $id_equipo = $request->getParam('id_equipo');

    $consulta = "UPDATE equipo SET 
                        nombre_equipo = '".$nombre."',
                        acro = '".$acro."',
                        estadio = '".$estadio."',
                        nacionalidad = '".$nacionalidad."'
                        WHERE id_equipo = ".$id_equipo;
    try{
        $db = new db();
        $db = $db->conectar();

        $stmt = $db->prepare($consulta);
        $stmt->bindParam(':nombre_equipo', $nombre);
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

$app->get('/nacionalidades/ver/todos', function(Request $request, Response $response){
    try{
        $db = new db();

        $db = $db->conectar();
        
        $consulta = "SELECT * FROM lista_paises ORDER BY nombre";
        $ejecutar = $db->query($consulta);

        $paises = $ejecutar->fetchAll(PDO::FETCH_OBJ);  
        
        $result = array(
            "status" => "correcto",
            "nacionalidades" => $paises
        );

        $db = null;

        echo json_encode($result);

    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}';
    }
});

$app->get('/deportes/ver/todos', function(Request $request, Response $response){
    try{
        $db = new db();

        $db = $db->conectar();
        
        $consulta = "SELECT * FROM categoria ORDER BY descripcion";
        $ejecutar = $db->query($consulta);

        $deportes = $ejecutar->fetchAll(PDO::FETCH_OBJ);  
        
        $result = array(
            "status" => "correcto",
            "deportes" => $deportes
        );

        $db = null;

        echo json_encode($result);

    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}';
    }
});

$app->get('/imagenes/{criterio}/{imagen}', function(Request $request, Response $response){

    $imagen = $request->getAttribute('imagen');
    $criterio = $request->getAttribute('criterio');

    $directorio = 'imagenes/'.$criterio.'/'.$imagen;
    
    if (file_exists($directorio)) {
        
    } else {
        $directorio = 'imagenes/'.$criterio.'/sinimagen.png';
    }

    $fp = fopen($directorio, 'rb');

    header("Content-Type: image/png");
    header("Content-Length: " . filesize($directorio));

    fpassthru($fp);

    exit;
});

$app->get('/ligas/ver/{pagina}/{criterio}', function(Request $request, Response $response){

    $pagina = $request->getAttribute('pagina');
    $criterio = $request->getAttribute('criterio');

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

        if ($criterio != 'todas') {
            $criterios = " WHERE upper(nombre_liga) LIKE upper('%" . $criterio . "%') OR upper(id_wihi_liga) LIKE upper('%" . $criterio . "%') OR upper(id_liga) LIKE upper('%" . $criterio . "%')";
        } else {
            $criterios = "";
        }
        
        $consulta = "SELECT * FROM liga ".$criterios." ORDER BY id_liga DESC LIMIT ".$inicio." , ".$TAMANO_PAGINA;
        $ejecutar = $db->query($consulta);

        while($fila = $ejecutar->fetch(PDO::FETCH_ASSOC)) {
            $ligas[] = $fila;

            $con_ligas_eq1 = "SELECT * FROM equipo_liga WHERE id_liga='".$ligas[$i]['id_liga']."'";
            $ex_con_ligas_eq1 = $db->query($con_ligas_eq1);
            $regs_con_ligas_eq1 = $ex_con_ligas_eq1->rowCount();

            if ($regs_con_ligas_eq1 == 1) {
                $txt_cant_eq = "equipo";
            } else {
                $txt_cant_eq = "equipos";
            }	

            $ligas[$i]['equipos'] = $regs_con_ligas_eq1." ".$txt_cant_eq;

            $nombre_fichero = 'imagenes/ligas/'.$ligas[$i]['id_liga'].'.png';

            if (file_exists($nombre_fichero)) {
                $ligas[$i]['img'] = $ligas[$i]['id_liga'].'.png';
            } else {
                $ligas[$i]['img'] = null;
            }            

            $i++;
        }     
        
        $result = array(
            "status" => $consulta,
            "ligas" => $ligas
        );

        $db = null;

        echo json_encode($result);

    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}';
    }
});