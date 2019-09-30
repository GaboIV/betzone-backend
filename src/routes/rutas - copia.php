<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app = new \Slim\App;

$app->get('/equipos/ver/{pagina}/{criterio}/{liga}', function(Request $request, Response $response){

    $pagina = $request->getAttribute('pagina');
    $criterio = $request->getAttribute('criterio');
    $liga = $request->getAttribute('liga');

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
            $criterios = " WHERE (upper(equipo.nombre_equipo) LIKE upper('%" . $criterio . "%') OR upper(equipo.id_wihi_equipo) LIKE upper('%" . $criterio . "%') OR upper(equipo.acro) LIKE upper('%" . $criterio . "%') OR upper(equipo.nacionalidad) LIKE upper('%" . $criterio . "%') OR upper(equipo.estadio) LIKE upper('%" . $criterio . "%') OR upper(equipo.id_equipo) LIKE upper('%" . $criterio . "%')) ";
        } else {
            $criterios = "";
        }

        if ($liga != 'todas') {
            $criterios = $criterios . " AND equipo_liga.id_liga=".$liga;
        }
        
        $consulta = "SELECT DISTINCT equipo_liga.id_equipo, equipo.nombre_equipo, equipo.id_wihi_equipo, equipo.acro, equipo.nacionalidad, equipo.estadio FROM equipo_liga INNER JOIN equipo ON equipo_liga.id_equipo = equipo.id_equipo ". $criterios ." ORDER BY equipo.id_equipo DESC LIMIT ".$inicio.",".$TAMANO_PAGINA;  
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
            "consulta" => $consulta,
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

$app->get('/hora/actual', function(Request $request, Response $response){
    
    try{
    	
        $fecha = date("Y-m-d H:i:s");
        $result = array(
            "status" => "correcto",
            "fecha" => $fecha
        );

        echo json_encode($result);

    } catch(PDOException $e){
        echo '{"error": {"text": "Error"}';
    }
});

$app->get('/ticket/ver/{id_usuario}', function(Request $request, Response $response){

    $id_usuario = $request->getAttribute('id_usuario');

    $decim_tot = 1;

    $cod_temp = '';

    $i = 0;
    $n = 0;
    $vuelta = 0;

    $db = new db();
    $db = $db->conectar();
    
    $s1 = "SELECT * FROM 1_x_34prly WHERE id_usuario = '$id_usuario' ORDER BY id_1_x_34PRLY DESC";
    $es1 = $db->query($s1);
    $ns1 = $es1->rowCount();

    if ($ns1 > 0) {
        while($fila = $es1->fetch(PDO::FETCH_ASSOC)) {
            $decim_tot = 1;
            $a_ganar = 1;
            $ticketes[] = $fila;
            $ticketes[$n]['correlativo'] = str_pad($fila['id_1_x_34PRLY'], 8, "0", STR_PAD_LEFT);

            $n++;

            $codigo = $fila['cod_seguridad'];

            $monto = $fila['monto'];

            $c6 = "SELECT * FROM seleccion WHERE id_usuario = '$id_usuario' AND (id_ticket = '".$fila['cod_seguridad']."') ORDER BY id_seleccion";
            $e6 = $db->query($c6);
            $n6 = $e6->rowCount();

            if ($n6 > 0) {
                $j = 0;

                while($f6 = $e6->fetch(PDO::FETCH_ASSOC)) {                   

                    if ($codigo != $cod_temp && $cod_temp != '') {
                        $i++;
                    }

                    $ticketes[$i]['selecciones'][$j] = $f6;

                    if ($f6['id_deporte'] != '27') {
                        $c7 = "SELECT * FROM participante WHERE id_participante = '".$f6['id_select']."'";
                        $ec7 = $db->query($c7);
                        $nc7 = $ec7->rowCount();

                        if ($nc7 > 0) {
                            $f7 = $ec7->fetch(PDO::FETCH_ASSOC);

                            $ticketes[$i]['selecciones'][$j]['status'] = $f7['status']; 

                            $id_equipo = $f7["id_equipo1"];

                            $div_equipo_part1 = $f6["valor"];

                            $div_div = explode("/", $div_equipo_part1);

                            if (!isset($div_div[1])) {
                                $div_div[1] = 1;
                            }                

                            $c8 = "SELECT * FROM equipo WHERE id_equipo = '$id_equipo'";
                            $e8 = $db->query($c8);
                            $f8 = $e8->fetch(PDO::FETCH_ASSOC);                            

                            $id_partido = $f7["id_partido"];

                            $c9 = "SELECT * FROM p_futbol WHERE id_partido = '$id_partido'";
                            $e9 = $db->query($c9);
                            $f9 = $e9->fetch(PDO::FETCH_ASSOC);

                            $name_partido =  $f9["id_wihi_partido"];                            
                             
                            $ticketes[$i]['selecciones'][$j]['equipo'] = $f8['nombre_equipo'];   
                            $ticketes[$i]['selecciones'][$j]['fecha_inicio'] = $f9['fecha_inicio'];                         

                            $decimal_odd = (intval($div_div[0]) / intval($div_div[1])) + 1;

                            $decim_tot = $decimal_odd * $decim_tot;     
                            

                            $name_d1 = explode("!", $name_partido);
                            $name_d2 = explode(".", $name_d1[1]);

                            $l1 = "SELECT * FROM equipo WHERE id_equipo='$name_d2[0]'";
                            $el1 = $db->query($l1);
                            $rl1 = $el1->fetch(PDO::FETCH_ASSOC);

                            $name_equipo1 = $rl1["nombre_equipo"];                      

                            $l2 = "SELECT * FROM equipo WHERE id_equipo='$name_d2[1]'";
                            $el2 = $db->query($l2);
                            $rl2 = $el2->fetch(PDO::FETCH_ASSOC);

                            $name_equipo2 = $rl2["nombre_equipo"];

                            $encuentro = "$name_equipo1 vs $name_equipo2";

                            $ticketes[$i]['selecciones'][$j]['encuentro'] = $encuentro;

                        }
                    } elseif ($f6['id_deporte'] == '27') {
                        $c7 = "SELECT * FROM inscripcion WHERE id_inscripcion = '".$f6['id_select']."'";
                        $e7 = $db->query($c7);
                        $n7 = $e7->rowCount();

                        $dividendo = $f6["valor"];

                        if ($dividendo != null) {
                            $cuota = $dividendo;

                            $decim_tot = $cuota * $decim_tot;
                        } else {
                            $decim_tot = null;
                        }

                        if ($n7 > 0) {
                            $r7 = $e7->fetch(PDO::FETCH_ASSOC);
                            $ticketes[$i]['selecciones'][$j]['id_select'] = $r7; 
                            $k = 0;

                            $c8 = "SELECT * FROM caballo WHERE id_caballo = '".$r7['id_caballo']."'";
                            $e8 = $db->query($c8);
                            $n8 = $e8->rowCount();

                            if ($n8 > 0) {
                                $r8 = $e8->fetch(PDO::FETCH_ASSOC);
                                $ticketes[$i]['selecciones'][$j]['id_select']['id_caballo'] = $r8;                     
                            }   

                            $c9 = "SELECT * FROM carrera WHERE id_carrera = '".$r7['id_carrera']."'";
                            $e9 = $db->query($c9);
                            $n9 = $e9->rowCount();

                            if ($n9 > 0) {
                                $r9 = $e9->fetch(PDO::FETCH_ASSOC);
                                $ticketes[$i]['selecciones'][$j]['id_select']['id_carrera'] = $r9;                     
                            }

                            $c10 = "SELECT * FROM hipodromo WHERE id_hipodromo = '".$r9['id_hipodromo']."'";
                            $e10 = $db->query($c10);
                            $n10 = $e10->rowCount();

                            if ($n10 > 0) {
                                $r10 = $e10->fetch(PDO::FETCH_ASSOC);
                               $ticketes[$i]['selecciones'][$j]['id_select']['id_carrera']['id_hipodromo'] = $r10;                     
                            }
                        } 

                    }      

                    $j++;               

                    $vuelta = 1;        

                    $cod_temp = $codigo;                                     
                }

                if ($decim_tot > 1) {
                    $a_ganar = $decim_tot * $monto;
                }

                $ticketes[$i]['cuota'] = $decim_tot;
                $ticketes[$i]['a_ganar'] = $a_ganar;
            }
        }
    }

    $result = array(
        "status" => "correcto",
        "ticketes" => $ticketes
    );

    $db = null;

    echo json_encode($result);

});


$app->post('/ticket/agregar', function(Request $request, Response $response){
    $id_usuario = $request->getParam('id_usuario');
    $montos = $request->getParam('montos');
    $wines = $request->getParam('wines');
    $i = 0;
    $j = 0;
    $m = 1;    
    $fecha = date("Y-m-d H:i:s");

    $db = new db();
    $db = $db->conectar();

    $monto = explode("#", $montos);
    $win = explode("#", $wines);

    $s1 = "SELECT * FROM seleccion WHERE id_usuario = '$id_usuario' AND (id_ticket = '0' OR id_ticket = '')";
    $es1 = $db->query($s1);
    $ns1 = $es1->rowCount();

    if ($ns1 > 0) {
        while($fila = $es1->fetch(PDO::FETCH_ASSOC)) {
            $cod_serial = substr(md5(rand()),0,10);
            $selecciones[] = $fila;

            $c7 = "SELECT * FROM inscripcion WHERE id_inscripcion = '".$fila['id_select']."'";
            $e7 = $db->query($c7);
            $n7 = $e7->rowCount();

            if ($n7 > 0) {
                $r7 = $e7->fetch(PDO::FETCH_ASSOC);
                $ticketes[$i]['selecciones']['id_select'] = $r7; 
                $k = 0;

                $c8 = "SELECT * FROM caballo WHERE id_caballo = '".$r7['id_caballo']."'";
                $e8 = $db->query($c8);
                $n8 = $e8->rowCount();

                if ($n8 > 0) {
                    $r8 = $e8->fetch(PDO::FETCH_ASSOC);
                    $ticketes[$i]['selecciones']['id_select']['id_caballo'] = $r8;                     
                }   

                $c9 = "SELECT * FROM carrera WHERE id_carrera = '".$r7['id_carrera']."'";
                $e9 = $db->query($c9);
                $n9 = $e9->rowCount();

                if ($n9 > 0) {
                    $r9 = $e9->fetch(PDO::FETCH_ASSOC);
                    $ticketes[$i]['selecciones']['id_select']['id_carrera'] = $r9;                     
                }

                $c10 = "SELECT * FROM hipodromo WHERE id_hipodromo = '".$r9['id_hipodromo']."'";
                $e10 = $db->query($c10);
                $n10 = $e10->rowCount();

                if ($n10 > 0) {
                    $r10 = $e10->fetch(PDO::FETCH_ASSOC);
                   $ticketes[$i]['selecciones']['id_select']['id_carrera']['id_hipodromo'] = $r10;                     
                }
            }
            
            if ($monto[$m] != '' OR $monto[$m] > '0') {
                $s2 = "UPDATE seleccion SET id_ticket='$cod_serial' WHERE id_usuario='$id_usuario' AND id_seleccion = '".$fila['id_seleccion']."'";
                $es2 = $db->prepare($s2);
                if ($es2->execute()) {
                    $s3 = "INSERT INTO 1_x_34prly (cod_seguridad, id_usuario, fecha_hora, monto, a_ganar, estatus) VALUES ('$cod_serial', '$id_usuario', '$fecha', '$monto[$m]', '0', '0')";
                    $es3 = $db->prepare($s3);
                    if ($es3->execute()) {
                       $ticketes[$i]['id_usuario'] = $id_usuario;
                       $ticketes[$i]['cod_seguridad'] = $cod_serial;
                       $ticketes[$i]['fecha_hora'] = $fecha;
                       $ticketes[$i]['monto'] = $monto[$m];
                       $ticketes[$i]['a_ganar'] = 'Según dividendo';
                       $ticketes[$i]['id_seleccion'] = $fila['id_seleccion'];

                    }
                }
            }

            $i++; $m++;
        }
    }    

    $result = array(
        "status" => "correcto",
        "ticketes" => $ticketes
    );

    echo json_encode($result);
  
});

$app->post('/ticket/agregard', function(Request $request, Response $response){
    $id_usuario = $request->getParam('id_usuario');
    $monto = $request->getParam('montos');
    $i = 0;
    $j = 0;   
    $decim_tot = 1;
    $cod_serial = substr(md5(rand()),0,10);
    $fecha = date("Y-m-d H:i:s");

    $db = new db();
    $db = $db->conectar();

    $s1 = "SELECT * FROM seleccion WHERE id_usuario = '$id_usuario' AND (id_ticket = '0' OR id_ticket = '')";
    $es1 = $db->query($s1);
    $ns1 = $es1->rowCount();

    $ticketes[0] = []; 

    if ($ns1 > 0) {
        while($fila = $es1->fetch(PDO::FETCH_ASSOC)) {                   

            $c7 = "SELECT a.id_partido, a.id_liga, a.fecha_inicio, b.nombre_liga, b.id_categoria, b.id_pais, c.descripcion  FROM p_futbol a INNER JOIN liga b on a.id_liga = b.id_liga INNER JOIN categoria c on b.id_categoria = c.id_categoria WHERE a.id_partido = '".$fila['muestra']."'";
            $e7 = $db->query($c7);
            $n7 = $e7->rowCount();

            if ($n7 > 0) {
                $filap = $e7->fetch(PDO::FETCH_ASSOC);
                $ticketes[0]['selecciones'][$i] = $filap;
                $ticketes[0]['selecciones'][$i]['dividendo'] = $fila['valor'];

                $id_select = $fila["id_select"]; 

                $c7 = "SELECT * FROM participante WHERE id_participante = '$id_select'";
                $ec7 = $db->query($c7);
                $nc7 = $ec7->rowCount();

                if ($nc7 > 0) {
                    $f7 = $ec7->fetch(PDO::FETCH_ASSOC);

                    $id_equipo = $f7["id_equipo1"];

                    $div_equipo_part1 = $f7["dividendo"];

                    $div_div = explode("/", $div_equipo_part1);

                    if (!isset($div_div[1])) {
                        $div_div[1] = 1;
                    }

                    $decimal_odd = (intval($div_div[0]) / intval($div_div[1])) + 1;
                    $decim_tot = $decimal_odd * $decim_tot;

                    $a_ganar = $decim_tot * $monto;

                    $c8 = "SELECT * FROM equipo WHERE id_equipo = '$id_equipo'";
                    $e8 = $db->query($c8);
                    $f8 = $e8->fetch(PDO::FETCH_ASSOC);

                    $ticketes[0]['selecciones'][$i]['equipo'] = $f8['nombre_equipo'];

                    $id_partido = $f7["id_partido"];

                    $c9 = "SELECT * FROM p_futbol WHERE id_partido = '$id_partido'";
                    $e9 = $db->query($c9);
                    $f9 = $e9->fetch(PDO::FETCH_ASSOC);

                    $name_partido =  $f9["id_wihi_partido"];

                    $name_d1 = explode("!", $name_partido);
                    $name_d2 = explode(".", $name_d1[1]);

                    $l1 = "SELECT * FROM equipo WHERE id_equipo='$name_d2[0]'";
                    $el1 = $db->query($l1);
                    $rl1 = $el1->fetch(PDO::FETCH_ASSOC);

                    $name_equipo1 = $rl1["nombre_equipo"];                      

                    $l2 = "SELECT * FROM equipo WHERE id_equipo='$name_d2[1]'";
                    $el2 = $db->query($l2);
                    $rl2 = $el2->fetch(PDO::FETCH_ASSOC);

                    $name_equipo2 = $rl2["nombre_equipo"];

                    $encuentro = "$name_equipo1 vs $name_equipo2";

                    $ticketes[0]['selecciones'][$i]['encuentro'] = $encuentro;
                }

               
            }


            
            if ($monto != '' OR $monto > '0') {
                $s2 = "UPDATE seleccion SET id_ticket='$cod_serial' WHERE id_usuario='$id_usuario' AND id_seleccion = '".$fila['id_seleccion']."'";
                $es2 = $db->prepare($s2);
                if ($es2->execute()) {
                    
                }
            }

            $i++;
        }

        $s3 = "INSERT INTO 1_x_34prly (cod_seguridad, id_usuario, fecha_hora, monto, a_ganar, estatus) VALUES ('$cod_serial', '$id_usuario', '$fecha', '$monto', '0', '0')";
        $es3 = $db->prepare($s3);
        if ($es3->execute()) {
           $ticketes[0]['id_usuario'] = $id_usuario;
           $ticketes[0]['cod_seguridad'] = $cod_serial;
           $ticketes[0]['correlativo'] = '00000001';
           $ticketes[0]['fecha_hora'] = $fecha;
           $ticketes[0]['monto'] = $monto;
           $ticketes[0]['cuota'] = $decim_tot;
           $ticketes[0]['a_ganar'] = $a_ganar;
           $ticketes[0]['id_seleccion'] = $fila['id_seleccion'];

        }
    }    

    $result = array(
        "status" => "correcto",
        "ticketes" => $ticketes,
        "numero" => $c7
    );

    echo json_encode($result);

  
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
        $i = 0;
        $db = $db->conectar();
        
        $consulta = "SELECT * FROM lista_paises ORDER BY nombre";
        $ejecutar = $db->query($consulta);

        while($fila = $ejecutar->fetch(PDO::FETCH_ASSOC)) {
            $paises[] = $fila;

            $nombre_fichero = 'imagenes/nacionalidades/'.$paises[$i]['id_lista_p'].'.png';

            if (file_exists($nombre_fichero)) {
                $paises[$i]['img'] = $paises[$i]['id_lista_p'].'.png';
            } else {
                $paises[$i]['img'] = null;
            } 

            $i++;
        } 
        
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

$app->post('/ligas/actualizar', function(Request $request, Response $response){
    $nombre = $request->getParam('nombre_liga');
    $importancia = $request->getParam('importancia');
    $nacionalidad = $request->getParam('id_pais');
    $categoria = $request->getParam('id_categoria');
    $id_liga = $request->getParam('id_liga');

    $consulta = "UPDATE liga SET 
                        nombre_liga = '".$nombre."',
                        importancia = '".$importancia."',
                        id_categoria = '".$categoria."',
                        id_pais = '".$nacionalidad."'
                        WHERE id_liga = ".$id_liga;
    try{
        $db = new db();
        $db = $db->conectar();

        $stmt = $db->prepare($consulta);
        $stmt->bindParam(':nombre_liga', $nombre);
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

$app->post('/ligas/agregar', function(Request $request, Response $response){
    $nombre = addslashes($request->getParam('nombre_liga'));
    $nacionalidad = $request->getParam('id_pais');
    $categoria = $request->getParam('id_categoria');
    $url = addslashes($request->getParam('url'));

    $consulta = "INSERT INTO liga (nombre_liga, id_categoria, id_pais, url) VALUES ('".$nombre."','".$categoria."','".$nacionalidad."','".$url."')";
    try{
        $db = new db();
        $db = $db->conectar();

        $stmt = $db->prepare($consulta);
        $stmt->bindParam(':nombre_liga', $nombre);
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

$app->post('/nacionalidades/actualizar', function(Request $request, Response $response){
    $nombre = $request->getParam('nombre');
    $importancia = $request->getParam('importancia');
    $id_pais = $request->getParam('id_lista_p');

    $consulta = "UPDATE lista_paises SET 
                        nombre = '".$nombre."',
                        importancia = '".$importancia."'
                        WHERE id_lista_p = ".$id_pais;
    try{
        $db = new db();
        $db = $db->conectar();

        $stmt = $db->prepare($consulta);
        $stmt->bindParam(':nombre', $nombre);
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

$app->post('/tipoApuestas/actualizar', function(Request $request, Response $response){
    $nombre = $request->getParam('descripcion_ta');
    $importancia = $request->getParam('importancia');
    $id_categoria = $request->getParam('id_categoria');
    $indice = $request->getParam('indice');
    $agregado = $request->getParam('agregado');
    $opcion = $request->getParam('opcion');
    $id_ta = $request->getParam('id_ta');

    $consulta = "UPDATE tipo_apuesta SET 
                        descripcion_ta = '".$nombre."',
                        importancia = '".$importancia."',
                        indice = '".$indice."',
                        agregado = '".$agregado."',
                        opcion = '".$opcion."',
                        id_categoria = '".$id_categoria."',
                        WHERE id_ta = ".$id_ta;
    try{
        $db = new db();
        $db = $db->conectar();

        $stmt = $db->prepare($consulta);
        $stmt->bindParam(':descripcion_ta', $nombre);
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

$app->get('/tipoApuestas/ver/todos', function(Request $request, Response $response){
    try{
        $db = new db();
        $i = 0;
        $db = $db->conectar();
        
        $consulta = "SELECT * FROM tipo_apuesta ORDER BY id_ta";
        $ejecutar = $db->query($consulta);

        while($fila = $ejecutar->fetch(PDO::FETCH_ASSOC)) {
            $tipo_apuestas[] = $fila;

            $i++;
        } 
        
        $result = array(
            "status" => "correcto",
            "tipoApuestas" => $tipo_apuestas
        );

        $db = null;

        echo json_encode($result);

    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}';
    }
});

$app->get('/actualizaciones/ver/todas', function(Request $request, Response $response){

    $pagina = $request->getAttribute('pagina');
    $criterio = $request->getAttribute('criterio');

    try{
        $db = new db();
        $db = $db->conectar();

        $TAMANO_PAGINA = 20;
        $i = 0;
        $t = "";

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
        
        $consulta = "SELECT * FROM liga ".$criterios." ORDER BY id_categoria, nombre_liga ASC";
        $ejecutar = $db->query($consulta);

        while($fila = $ejecutar->fetch(PDO::FETCH_ASSOC)) {
            $ligas[] = $fila;

            if ($t != $ligas[$i]['id_categoria']) {
                $t = $ligas[$i]['id_categoria'];
                $ligas[$i]['titulo'] = $t;
            }

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
            "actualizaciones" => $ligas
        );

        $db = null;

        echo json_encode($result);

    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}';
    }
});

$app->get('/partidos/ver/{pagina}/{criterio}', function(Request $request, Response $response){
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
            if ($criterio[0] == '!') {
                $crites = explode("!", $criterio);
                $criterios = " WHERE p_futbol.id_partido = '$crites[1]' ";
            } else {
                $criterios = " WHERE liga.id_categoria = '$criterio' ";
            }
            
        } else {
            $criterios = "";
        }
        
        $consulta = "SELECT * FROM p_futbol INNER JOIN liga ON p_futbol.id_liga=liga.id_liga ".$criterios." ORDER BY id_partido DESC LIMIT ".$inicio.",".$TAMANO_PAGINA; 
        $ejecutar = $db->query($consulta);

        while($fila = $ejecutar->fetch(PDO::FETCH_ASSOC)) {
            $partidos[] = $fila;

            $nombre_fichero = 'imagenes/partidos/'.$partidos[$i]['id_partido'].'.png';

            if (file_exists($nombre_fichero)) {
                $partidos[$i]['img'] = $partidos[$i]['id_partido'].'.png';
            } else {
                $partidos[$i]['img'] = null;
            }     

            $consulta3 = "SELECT * FROM categoria WHERE id_categoria = '".$fila['id_categoria']."' ORDER BY descripcion";
            $ejecutar3 = $db->query($consulta3);

            $deportes = $ejecutar3->fetchAll(PDO::FETCH_OBJ); 

            $partidos[$i]['id_categoria'] = $deportes;

            $ejecutar2 = $db->query("SELECT * FROM participante WHERE id_partido = '". $fila['id_partido'] . "'");
            $ligas = array();
            $j = 0;

            while ($fila2 = $ejecutar2->fetch(PDO::FETCH_ASSOC)) {
                $ejecutar3 = $db->query("SELECT * FROM equipo WHERE id_equipo = '". $fila2['id_equipo1'] . "'");
                $fila3 = $ejecutar3->fetch(PDO::FETCH_ASSOC);

                $nombre_fichero = 'imagenes/equipos/'.$fila3['id_equipo'].'.png';

                if (file_exists($nombre_fichero)) {
                    $partidos[$i]['equipos'][$j]['img'] = $fila3['id_equipo'].'.png';
                } else {
                    $partidos[$i]['equipos'][$j]['img'] = null;
                }

                if ($fila['id_categoria'] == '22') {
                    $p1 = "SELECT * FROM pitcher WHERE id_equipo = '".$fila3['id_equipo']."'";
                    $ep1 = $db->query($p1);

                    while($fp1 = $ep1->fetch(PDO::FETCH_ASSOC)) {
                        $partidos[$i]['equipos'][$j]['pitchers'][] = $fp1['nombre'];
                    }
                }

                $partidos[$i]['equipos'][$j]['id_participante'] = $fila2['id_participante'];
                $partidos[$i]['equipos'][$j]['id_equipo'] = $fila2['id_equipo1'];
                $partidos[$i]['equipos'][$j]['nombre'] = $fila3['nombre_equipo'];
                $partidos[$i]['equipos'][$j]['dividendo'] = $fila2['dividendo'];
                $partidos[$i]['equipos'][$j]['proveedor'] = $fila2['proveedor'];
                $partidos[$i]['equipos'][$j]['dato'] = $fila2['dato'];

                $j++;
            }  
            

            $i++;
        }     
        
        $result = array(
            "status" => "correcto",
            "partidos" => $partidos
        );

        $db = null;

        echo json_encode($result);

    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}';
    }
});

$app->get('/partidos/ver/destacados', function(Request $request, Response $response){

    try{
        $db = new db();
        $db = $db->conectar();

        $fecha_for_1 = date("Y-m-d H:i:s");

		$fecha_manana = date_create($fecha_for_1);
		date_add($fecha_manana, date_interval_create_from_date_string('7 days'));
		$fecha_de_manana = date_format($fecha_manana, 'd-m-Y');
		$fecha_manana = date_format($fecha_manana, 'Y-m-d H:i:s');

		$fecha_for_2 = date("H:i:s");

		$fecha_compuesta = $fecha_for_1." ".$fecha_for_2;        

        $consulta = "SELECT a.id_partido, a.id_liga, a.fecha_inicio, a.destacado, a.importancia, b.nombre_liga, b.id_categoria, c.descripcion  FROM p_futbol a INNER JOIN liga b on a.id_liga = b.id_liga INNER JOIN categoria c on b.id_categoria = c.id_categoria WHERE a.fecha_inicio >= '$fecha_compuesta' AND a.fecha_inicio < '$fecha_manana' AND a.destacado = '1' ORDER BY b.importancia, b.nombre_liga, a.fecha_inicio";

        $ejecutar = $db->query($consulta);

        $i = 0;
        $id_temp = 0;

        while($fila = $ejecutar->fetch(PDO::FETCH_ASSOC)) {
            $partidos[] = $fila;

            $ejecutar2 = $db->query("SELECT * FROM participante WHERE id_partido = '". $fila['id_partido'] . "'");
            $ligas = array();
            $j = 0;

            while ($fila2 = $ejecutar2->fetch(PDO::FETCH_ASSOC)) {
                $ejecutar3 = $db->query("SELECT * FROM equipo WHERE id_equipo = '". $fila2['id_equipo1'] . "'");
                $fila3 = $ejecutar3->fetch(PDO::FETCH_ASSOC);

                $partidos[$i]['equipos'][$j]['id_equipo'] = $fila2['id_equipo1'];

                $nombre_fichero = 'imagenes/equipos/'.$partidos[$i]['equipos'][$j]['id_equipo'].'.png';

                if (file_exists($nombre_fichero)) {
                    $partidos[$i]['equipos'][$j]['img'] = $partidos[$i]['equipos'][$j]['id_equipo'].'.png';
                } else {
                    $partidos[$i]['equipos'][$j]['img'] = null;
                }

                $partidos[$i]['equipos'][$j]['nombre'] = $fila3['nombre_equipo'];
                $partidos[$i]['equipos'][$j]['dividendo'] = $fila2['dividendo'];
                $partidos[$i]['equipos'][$j]['proveedor'] = $fila2['proveedor'];

                $j++;
            }  

            if ($j == 2) {
                $titulo = $partidos[$i]['equipos'][0]['nombre']." vs ".$titulo = $partidos[$i]['equipos'][1]['nombre'];
            } elseif ($j == 3){
                $titulo = $partidos[$i]['equipos'][0]['nombre']." vs ".$titulo = $partidos[$i]['equipos'][2]['nombre'];
            }

            if (isset($titulo)) {
            	$partidos[$i]['titulo'] = $titulo;
            } else {
            	$partidos[$i]['titulo'] = null;
            }

            

            $i++;
        }  
        
        if ( $i == '0' ) {
            $partidos[] = "";
        }
        
        $result = array(
            "status" => "correcto",
            "consulta" => $consulta,
            "destacados" => $partidos
        );

        $db = null;

        echo json_encode($result);

    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}';
    }
});

$app->post('/partidos/actualizar', function(Request $request, Response $response){
    $destacado = $request->getParam('destacado');
    $importancia = $request->getParam('importancia');
    $id_partido = $request->getParam('id_partido');

    $consulta = "UPDATE p_futbol SET 
                        destacado = '".$destacado."',
                        importancia = '".$importancia."'
                        WHERE id_partido = ".$id_partido;
    try{
        $db = new db();
        $db = $db->conectar();

        $stmt = $db->prepare($consulta);
        $stmt->bindParam(':id_partido', $id_partido);
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

$app->post('/partidos/agregarDatos', function(Request $request, Response $response){
    $pitcher1 = $request->getParam('pitcher1');
    $pitcher2 = $request->getParam('pitcher2');
    $era1 = $request->getParam('era1');
    $era2 = $request->getParam('era2');
    $id_part1 = $request->getParam('id_part1');
    $id_part2 = $request->getParam('id_part2');
    $id_equipo1 = $request->getParam('id_equipo1');
    $id_equipo2 = $request->getParam('id_equipo2');

    $db = new db();
    $db = $db->conectar();

    try{

        if ($pitcher1 != '') {
            $c1 = "SELECT * FROM pitcher WHERE nombre = '$pitcher1'";
            $ec1 = $db->query($c1);
            $nc1 = $ec1->rowCount();

            if ($nc1 == 0) {
                $cp = "INSERT INTO pitcher (nombre, era, id_equipo) VALUES ('$pitcher1','$era1','$id_equipo1')";
                if($ecp = $db->query($cp)){
                    $id_pitcher1 = $db->lastInsertId();                    
                }
            } elseif ($nc1 > 0) {
                $p1 = $ec1->fetch(PDO::FETCH_ASSOC);

                $id_pitcher1 = $p1['id_pitcher'];

                $cp2 = "UPDATE pitcher SET era='$era1' WHERE nombre='$pitcher1'";
                $ecp2 = $db->prepare($cp2);
                if ($ecp2->execute()) {

                }
            }

            $cp31 = "UPDATE participante SET dato='$id_pitcher1' WHERE id_participante='$id_part1'";
            $ecp31 = $db->prepare($cp31);
            if ($ecp31->execute()) {
                $sta_p1 = "correct";
            }
        }

        if ($pitcher2 != '') {
            $c2 = "SELECT * FROM pitcher WHERE nombre = '$pitcher2'";
            $ec2 = $db->query($c2);
            $nc2 = $ec2->rowCount();

            if ($nc2 == 0) {
                $cp = "INSERT INTO pitcher (nombre, era, id_equipo) VALUES ('$pitcher2','$era2','$id_equipo2')";
                if($ecp = $db->query($cp)){
                    $id_pitcher2 = $db->lastInsertId();
                }
            } elseif ($nc2 > 0) {
                $p2 = $ec2->fetch(PDO::FETCH_ASSOC);

                $id_pitcher2 = $p2['id_pitcher'];

                $cp2 = "UPDATE pitcher SET era='$era2' WHERE nombre='$pitcher2'";
                $ecp2 = $db->prepare($cp2);
                if ($ecp2->execute()) {

                }
            }

            $cp32 = "UPDATE participante SET dato='$id_pitcher2' WHERE id_participante='$id_part2'";
            $ecp32 = $db->prepare($cp32);
            if ($ecp32->execute()) {
                $sta_p1 = "correct";
            }
        }

        if ($sta_p1 == "correct") {
            $result = array(
                "status" => $sta_p1
            );
        } else {
            $result = array(
                "status" => 'error'
            );
        }

        echo json_encode($result);            

    } catch(PDOException $e){
        echo '{"error": {"text": '.$consulta.'}';
    }
});

$app->get('/partidos/categoria/{id_categoria}', function(Request $request, Response $response){
    $id_categoria = $request->getAttribute('id_categoria');

    if (isset($_GET["fecha"])) {
        $dato_fecha = $_GET["fecha"];
    } else {
        $dato_fecha = "";
    }
    
    try{
        $db = new db();
        $db = $db->conectar();

        $fecha_for_1 = date("Y-m-d H:i:s");

		$fecha_manana = date_create($fecha_for_1);
		date_add($fecha_manana, date_interval_create_from_date_string('1 days'));
		$fecha_de_manana = date_format($fecha_manana, 'd-m-Y');
		

		$fecha_for_2 = date("H:i:s");

		$fecha_compuesta = $fecha_for_1." ".$fecha_for_2;  

        $ligatemp = '';   

        if ($dato_fecha == "hoy") {
               $fecha_manana = date_format($fecha_manana, 'Y-m-d');
           }   else {
                $fecha_manana = date_format($fecha_manana, 'Y-m-d H:i:s');
           }

        $consulta = "SELECT a.id_partido, a.id_liga, a.fecha_inicio, b.nombre_liga, b.id_categoria, b.id_pais, c.descripcion  FROM p_futbol a INNER JOIN liga b on a.id_liga = b.id_liga INNER JOIN categoria c on b.id_categoria = c.id_categoria WHERE c.id_categoria = '".$id_categoria."' AND a.fecha_inicio >= '$fecha_compuesta' AND a.fecha_inicio < '$fecha_manana' ORDER BY b.importancia DESC, b.nombre_liga ASC, a.fecha_inicio ASC";

        $ejecutar = $db->query($consulta);

        $i = 0;
        $id_temp = 0;

        $revision = '0';

        while($fila = $ejecutar->fetch(PDO::FETCH_ASSOC)) {
            $partidos[] = $fila;

            if ($fila['nombre_liga'] == $ligatemp) {
                $partidos[$i]['nombre_liga'] = null;
            } else {
                $ligatemp = $fila['nombre_liga'];
                $revision = '0';
            }

            $newDia_partido = date("d-m-Y", strtotime($fila['fecha_inicio']));

            if ($newDia_partido == $fecha_de_manana AND $revision == '0') {
                $partidos[$i]['manana'] = true;
                $revision = '1';
            }
            

            $ejecutar2 = $db->query("SELECT * FROM participante WHERE id_partido = '". $fila['id_partido'] . "'");
            $ligas = array();
            $j = 0;

            while ($fila2 = $ejecutar2->fetch(PDO::FETCH_ASSOC)) {
                $ejecutar3 = $db->query("SELECT * FROM equipo WHERE id_equipo = '". $fila2['id_equipo1'] . "'");
                $fila3 = $ejecutar3->fetch(PDO::FETCH_ASSOC);

                $partidos[$i]['equipos'][$j]['id_participante'] = $fila2['id_participante'];
                $partidos[$i]['equipos'][$j]['id_equipo'] = $fila2['id_equipo1'];

                $nombre_fichero = 'imagenes/equipos/'.$partidos[$i]['equipos'][$j]['id_equipo'].'.png';

                if (file_exists($nombre_fichero)) {
                    $partidos[$i]['equipos'][$j]['img'] = $partidos[$i]['equipos'][$j]['id_equipo'].'.png';
                } else {
                    $partidos[$i]['equipos'][$j]['img'] = null;
                }

                $partidos[$i]['equipos'][$j]['nombre'] = $fila3['nombre_equipo'];
                $partidos[$i]['equipos'][$j]['dividendo'] = $fila2['dividendo'];
                $partidos[$i]['equipos'][$j]['proveedor'] = $fila2['proveedor'];
                $partidos[$i]['equipos'][$j]['nacionalidad'] = $fila3['nacionalidad'];

                $ejecutar3 = $db->query("SELECT * FROM lista_paises WHERE id_lista_p = '". $fila3['nacionalidad'] . "'");
                $fila4 = $ejecutar3->fetch(PDO::FETCH_ASSOC);

                $partidos[$i]['equipos'][$j]['pais'] = $fila4['nombre'];

                $j++;
            }  

            if ($j == 2) {
                $titulo = $partidos[$i]['equipos'][0]['nombre']." vs ".$titulo = $partidos[$i]['equipos'][1]['nombre'];
            } elseif ($j == 3){
                $titulo = $partidos[$i]['equipos'][0]['nombre']." vs ".$titulo = $partidos[$i]['equipos'][2]['nombre'];
            }

            if (isset($titulo)) {
            	$partidos[$i]['titulo'] = $titulo;
            } else {
            	$partidos[$i]['titulo'] = null;
            }

            

            $i++;
        }  
        
        if ( $i == '0' ) {
            $partidos[] = "";
        }
        
        $result = array(
            "status" => "correcto",
            "consulta" => $consulta,
            "partidos" => $partidos
        );

        $db = null;

        echo json_encode($result);

    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}';
    }
});

$app->post('/inicioSesion', function(Request $request, Response $response){
    $usuario = $request->getParam('usuario');
    $pass = $request->getParam('contrasena');

	$db = new db();
    $db = $db->conectar();

    $consulta = "SELECT * FROM usuario WHERE usuario = '$usuario' AND password = '$pass'";
    $ejecutar = $db->query($consulta);
    $registros = $ejecutar->rowCount();
       
        if ($registros > 0){
        	$fila = $ejecutar->fetch(PDO::FETCH_ASSOC);

            $usuario = $fila;

            $pass = md5($fila['password'].$fila['usuario']);
            $time = strtotime("now");

            $usuario['password'] = ':D';
            $usuario['token'] = $pass."###".$time;

            $result = array(
                "status" => "correcto",
                "usuario" => $usuario
            );
            
        } else {
            	$result = array(
                "status" => "incorrecto"
            );

            
            }

echo json_encode($result);
    
});

// Agregar Cliente
$app->post('/usuarios/crear', function(Request $request, Response $response){
    $nombres = $request->getParam('nombres');
    $apellidos = $request->getParam('apellidos');
    $usuario = $request->getParam('usuario');
    $nacimiento = $request->getParam('nacimiento');
    $email = $request->getParam('email');
    $password = $request->getParam('password');
    $numerico = $request->getParam('numerico');
    $id_pais = $request->getParam('id_pais');
    $tratamiento = $request->getParam('tratamiento');
    $disponible = 0;
    $en_juego = 0;
    $puntos = 0;
    $id_rol = 2;
    $id_estatus = 0;
    $cedula = $request->getParam('cedula');
    $telefono = $request->getParam('telefono');

    $consulta = "INSERT INTO usuario (nacimento, usuario, password, numerico, disponible, en_juego, puntos, id_rol, id_estatus, nombres, apellidos, cedula, telefono, email, id_pais, tratamiento) VALUES ('$nacimiento','$usuario','$password','$numerico', '$disponible', '$en_juego','5','$id_rol','$id_estatus','$nombres','$apellidos','$cedula','$telefono','$email','$id_pais','$tratamiento')";
    try{
        $db = new db();
        $db = $db->conectar();

        $stmt = $db->prepare($consulta);
        $stmt->bindParam(':usuario', $usuario);
        if ($stmt->execute()){

        	$cod_serial = substr(md5(rand()),0,10);

        	$id_usuario = $db->lastInsertId();

        	$linke = "https://apuestas2018.000webhostapp.com/#/activacion/".$cod_serial;
					$linke_o = $cod_serial;

					$para      = $email;
					$titulo    = 'Activación de Cuenta BetZone';
					$mensaje = '
						<div style="border: 1px #CCCCCC solid; width: 550px; overflow: hidden; color:black; margin: 0 auto;">
						<div style="background: #14805E;">
						<div style="width: 300px; display: inline-block; vertical-align: middle; text-align: center;"><img src="http://i65.tinypic.com/qmytmt.png" height="70px"></div>
						<div style="display: inline-block; vertical-align: middle; text-align: center;"><a href="betzone.com.ve"><button style="width: 200px; margin: 0 auto; height: 37px;">Ir a la página</button></a></div>
						</div>
						<div style="background: white; border-top: #CCCCCC 1px solid; border-bottom: #CCCCCC 1px solid; height: 25px; text-align: center; padding-top: 6px;">Activación de cuenta nueva en BetZone</div>
						<div style="padding: 10px;">
						<p>Estimado(a):<b>'.$nombres.' '.$apellidos.'</b></p>
						<p>Para activar tu usuario en el portal, ingresa al siguiente enlace: <a href="'.$linke.'">'.$linke.'</a></p>
						<p>Por seguridad, recomendamos que copies y pegues la dirección electrónica indicada en tu explorador web. BetZone no solicita información confidencial ni claves para activar tu usuario.</p>
						<p>Este es una dirección de correo exclusiva para el envío de notificaciones. Si tienes alguna duda o sugerencia o necesitas más información, te invitamos a acceder a la sección Atención al Usuario.</p>
						<p><b>Por favor, no responda este correo.</b></p>
						<p><b>BetZone</b></p>											
						</div>
						</div>
						';
					$cabeceras  = 'MIME-Version: 1.0' . "\r\n";
					$cabeceras .= 'Content-type: text/html; charset=utf-8' . "\r\n";

					// Cabeceras adicionales
					$cabeceras .= 'To: '.$nombres.' <'.$email.'>' . "\r\n";
					$cabeceras .= 'From: Cuentas de BetZone <cuentas@betzone.com.ve>' . "\r\n";
					$cabeceras .= 'Cc: cuentas@betzone.com.ve' . "\r\n";
					$cabeceras .= 'Bcc: cuentas@betzone.com.ve' . "\r\n";

					if (mail($para, $titulo, $mensaje, $cabeceras)) {						
						$c5 = "UPDATE usuario SET urlAct='$linke_o' WHERE id_usuario='$id_usuario'";
						$ec5 = $db->prepare($c5);
						if ($ec5->execute()) {
							
						}
					}


            $result = array(
                "status" => "correcto",
                "usuario_id" => $id_usuario
            );

            echo json_encode($result);
        }     

    } catch(PDOException $e){
        echo '{"error": {"text": '.$consulta.'}';
    }
});

$app->post('/usuarios/activar/{cod_act}', function(Request $request, Response $response){

    $codigo = $request->getAttribute('cod_act');

    $db = new db();
    $db = $db->conectar();

    $con1 = "SELECT * FROM usuario WHERE urlAct='$codigo'";
    $excon1 = $db->query($con1);
    $regcon1 = $excon1->rowCount();
    $usuario = $excon1->fetchAll(PDO::FETCH_OBJ);

    if ($regcon1 > 0) {
    	$consulta = "UPDATE usuario SET urlAct='', id_estatus='1' WHERE urlAct='$codigo'";
	    try{	       

	        $stmt = $db->prepare($consulta);
	        $stmt->bindParam(':cod_act', $codigo);
	        if ($stmt->execute()){     
	            $result = array(
	                "status" => "correcto",
	                "usuario" => $usuario,
	                "codigo" => $codigo
	            );
	            echo json_encode($result);
	        }     

	    } catch(PDOException $e){
	        echo '{"error": {"text": '.$consulta.'}';
	    }
    } else {
    	$result = array(
            "status" => "No existe",
            "mensaje" => "No existe usuario relacionado con este código $codigo",
            "codigo" => $codigo
        );
        echo json_encode($result);
    }    
});

$app->get('/caballos/ver/todos', function(Request $request, Response $response){
    try{
        $db = new db();
        $i = 0;
        $db = $db->conectar();
        
        $consulta = "SELECT * FROM caballo WHERE tipo_caballo = '3' ORDER BY nombre";
        $ejecutar = $db->query($consulta);

        while($fila = $ejecutar->fetch(PDO::FETCH_ASSOC)) {
            $caballos[] = $fila;

            if( $caballos[$i]['sexo'] == "1") {
				$sexo = "Caballo";
			} elseif ($caballos[$i]['sexo'] == "2") {
				$sexo = "Yegua";
			}

			$caballos[$i]['sexo'] = $sexo;

			$ejecutar2 = $db->query("SELECT * FROM caballo WHERE id_caballo = '".$caballos[$i]['padre']."'");
            $j = 0;

            while ($fila2 = $ejecutar2->fetch(PDO::FETCH_ASSOC)) {
            	$caballos[$i]['padre'] = $fila2;
            	$j++;
            }   

            $ejecutar3 = $db->query("SELECT * FROM caballo WHERE id_caballo = '".$caballos[$i]['madre']."'");
            $k = 0;

            while ($fila3 = $ejecutar3->fetch(PDO::FETCH_ASSOC)) {
            	$caballos[$i]['madre'] = $fila3;
            	$k++;
            }      

            $ejecutar4 = $db->query("SELECT * FROM caballo WHERE id_caballo = '".$caballos[$i]['abuelo']."'");
            $l = 0;

            while ($fila4 = $ejecutar4->fetch(PDO::FETCH_ASSOC)) {
            	$caballos[$i]['abuelo'] = $fila4;
            	$l++;
            } 

            $ejecutar5 = $db->query("SELECT * FROM haras WHERE id_haras = '".$caballos[$i]['id_haras']."'");
            $m = 0;

            while ($fila5 = $ejecutar5->fetch(PDO::FETCH_ASSOC)) {
            	$caballos[$i]['id_haras'] = $fila5;
            	$m++;
            }   

            $datetime1 = date_create($caballos[$i]['nacimiento']);
			$datetime2 = date_create(date("Y-m-d"));

			$interval = date_diff($datetime1, $datetime2);

			$descripcion = $interval->format('%y años');

			if ($interval->format('%m') == "0") {
				$descripcion2 = "exactos";
			} elseif ($interval->format('%m') == "1") {
				$descripcion2 = "y ".$interval->format('%m mes');
			} else {
				$descripcion2 = "y ".$interval->format('%m meses');
			}    

			$caballos[$i]['edad'] = $descripcion." ".$descripcion2;

            $i++;
        } 
        
        $result = array(
            "status" => "correcto",
            "caballos" => $caballos
        );

        $db = null;

        echo json_encode($result);

    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}';
    }
});

$app->post('/caballos/crear', function(Request $request, Response $response){
	try{ 
	$db = new db();
    $db = $db->conectar();

    $nombre = addslashes($request->getParam('nombre'));
    $sexo = $request->getParam('sexo');

    if ($sexo == 1) {
    	$s1 = " SELECT * FROM caballo WHERE (tipo_caballo = '3') AND (sexo = '1') ORDER BY id_caballo DESC";
		$es1 = $db->query($s1);
		$ns1 = $es1->rowCount();

		if ($ns1 > 0) {
			$rs1 = $es1->fetch(PDO::FETCH_ASSOC);

			$codigo_ult_cab = $rs1["codigo"];
			$solo_n_c_u_c = $codigo_ult_cab[3].$codigo_ult_cab[4].$codigo_ult_cab[5];
			$nuevo_s_c_u_c = $solo_n_c_u_c + 1;

			if ($nuevo_s_c_u_c < 10) {
				$nuevo_s_c_u_c = "00".$nuevo_s_c_u_c;
			} elseif ($nuevo_s_c_u_c < 100) {
				$nuevo_s_c_u_c = "0".$nuevo_s_c_u_c;
			}

			$codigo_final_cab = $codigo_ult_cab[0].$codigo_ult_cab[1].$codigo_ult_cab[2].$nuevo_s_c_u_c;
		} else {
			$codigo_final_cab = "CAB001";
		}
    } elseif ($sexo == 2) {
    	$s1 = " SELECT * FROM caballo WHERE (tipo_caballo = '3') AND (sexo = '2') ORDER BY id_caballo DESC";
		$es1 = $db->query($s1);
		$ns1 = $es1->rowCount();

		if ($ns1 > 0) {
			$rs1 = $es1->fetch(PDO::FETCH_ASSOC);

			$codigo_ult_cab = $rs1["codigo"];
			$solo_n_c_u_c = $codigo_ult_cab[3].$codigo_ult_cab[4].$codigo_ult_cab[5];
			$nuevo_s_c_u_c = $solo_n_c_u_c + 1;

			if ($nuevo_s_c_u_c < 10) {
				$nuevo_s_c_u_c = "00".$nuevo_s_c_u_c;
			} elseif ($nuevo_s_c_u_c < 100) {
				$nuevo_s_c_u_c = "0".$nuevo_s_c_u_c;
			}

			$codigo_final_cab = $codigo_ult_cab[0].$codigo_ult_cab[1].$codigo_ult_cab[2].$nuevo_s_c_u_c;
		} else {
			$codigo_final_cab = "YEG001";
		}
    }


    $nacimiento = $request->getParam('nacimiento');

    $n_padre = addslashes($request->getParam('padre'));

    $c1 = "SELECT * FROM caballo WHERE (tipo_caballo = '1') AND (sexo = '1') AND (nombre = '$n_padre')";
    $e1 = $db->query($c1);
    $n1 = $e1->rowCount();

    if ($n1 == 0) {
        $c2 = "SELECT * FROM caballo WHERE (tipo_caballo = '1') AND (sexo = '1') ORDER BY id_caballo DESC";
	    $e2 = $db->query($c2);
	    $n2 = $e2->rowCount();

	    if ($n2 > 0) {
	    	$r2 = $e2->fetch(PDO::FETCH_ASSOC);

	    	$codigo_ult_mad = $r2["codigo"];
			$solo_n_c_u_m = $codigo_ult_mad[3].$codigo_ult_mad[4].$codigo_ult_mad[5];
			$nuevo_s_c_u_m = $solo_n_c_u_m + 1;

			if ($nuevo_s_c_u_m < 10) {
				$nuevo_s_c_u_m = "00".$nuevo_s_c_u_m;
			} elseif ($nuevo_s_c_u_m < 100) {
				$nuevo_s_c_u_m = "0".$nuevo_s_c_u_m;
			}

			$codigo_final_pad = $codigo_ult_mad[0].$codigo_ult_mad[1].$codigo_ult_mad[2].$nuevo_s_c_u_m;
	    } else {
	    	$codigo_final_pad = "PAD001";
	    }

	    $cp = "INSERT INTO caballo (codigo, nombre, tipo_caballo, sexo) VALUES ('$codigo_final_pad','$n_padre','1','1')";
	    if($ecp = $db->query($cp)){
			$id_padre = $db->lastInsertId();
		}

    } elseif ($n1 > 0) {
		$r1 = $e1->fetch(PDO::FETCH_ASSOC);
		$id_padre = $r1["id_caballo"];
		$codigo_ex_padre = $r1["codigo"];
	}

    $n_madre = addslashes($request->getParam('madre'));

    $c1 = "SELECT * FROM caballo WHERE (tipo_caballo = '1') AND (sexo = '2') AND (nombre = '$n_madre')";
    $e1 = $db->query($c1);
    $n1 = $e1->rowCount();

    if ($n1 == 0) {
        $c2 = "SELECT * FROM caballo WHERE (tipo_caballo = '1') AND (sexo = '2') ORDER BY id_caballo DESC";
	    $e2 = $db->query($c2);
	    $n2 = $e2->rowCount();

	    if ($n2 > 0) {
	    	$r2 = $e2->fetch(PDO::FETCH_ASSOC);

	    	$codigo_ult_mad = $r2["codigo"];
			$solo_n_c_u_m = $codigo_ult_mad[3].$codigo_ult_mad[4].$codigo_ult_mad[5];
			$nuevo_s_c_u_m = $solo_n_c_u_m + 1;

			if ($nuevo_s_c_u_m < 10) {
				$nuevo_s_c_u_m = "00".$nuevo_s_c_u_m;
			} elseif ($nuevo_s_c_u_m < 100) {
				$nuevo_s_c_u_m = "0".$nuevo_s_c_u_m;
			}

			$codigo_final_pad = $codigo_ult_mad[0].$codigo_ult_mad[1].$codigo_ult_mad[2].$nuevo_s_c_u_m;
	    } else {
	    	$codigo_final_pad = "MAD001";
	    }

	    $cm = "INSERT INTO caballo (codigo, nombre, tipo_caballo, sexo) VALUES ('$codigo_final_pad','$n_madre','1','2')";
	    if($ecp = $db->query($cm)){
			$id_madre = $db->lastInsertId();
		}

    } elseif ($n1 > 0) {
		$r1 = $e1->fetch(PDO::FETCH_ASSOC);
		$id_madre = $r1["id_caballo"];
		$codigo_ex_madre = $r1["codigo"];
	}

    $n_haras = $request->getParam('id_haras');

    $c3 = "SELECT * FROM haras WHERE descripcion = '$n_haras'";
    $e3 = $db->query($c3);
    $n3 = $e3->rowCount();

    if ($n3 == 0) {
		$c4 = "INSERT INTO haras (descripcion) VALUES ('$n_haras')";	

		if($e4 = $db->query($c4)){ 
			$id_haras = $db->lastInsertId();
		}
	} elseif ($n3 > 0) {
		$r3 = $e3->fetch(PDO::FETCH_ASSOC);
		$id_haras = $r3["id_haras"];
	}

	$c0 =  "SELECT * FROM caballo WHERE nombre = '$nombre'";
	$e0 = $db->query($c0);
    $n0 = $e0->rowCount();

    if ($n0 == 0) {
       	$consulta = "INSERT INTO caballo (codigo, nombre, sexo, tipo_caballo, padre, madre, nacimiento, id_haras) VALUES ('$codigo_final_cab','$nombre','$sexo','3','$id_padre','$id_madre','$nacimiento','$id_haras')";
         

        $stmt = $db->prepare($consulta);

        if ($stmt->execute()){
            $result = array(
                "status" => "correcto"
            );

            echo json_encode($result);
        } 
    } else {
    	$result = array(
                "status" => "error",
                "mensaje" => "Nombre ya existente"
            );

            echo json_encode($result);
    }      

    } catch(PDOException $e){
        echo '{"error": {"text": '.$consulta.'}';
    }
});

$app->get('/jinetes/ver/todos', function(Request $request, Response $response){
    try{
        $db = new db();
        $i = 0;
        $db = $db->conectar();
        
        $consulta = "SELECT * FROM jinete ORDER BY nombre";
        $ejecutar = $db->query($consulta);

        while($fila = $ejecutar->fetch(PDO::FETCH_ASSOC)) {
            $jinetes[] = $fila;

            $nombre_fichero = 'imagenes/jinetes/'.$jinetes[$i]['id_jinete'].'.png';

            if (file_exists($nombre_fichero)) {
                $jinetes[$i]['img'] = $jinetes[$i]['id_jinete'].'.png';
            } else {
                $jinetes[$i]['img'] = null;
            } 

            $i++;
        } 
        
        $result = array(
            "status" => "correcto",
            "jinetes" => $jinetes
        );

        $db = null;

        echo json_encode($result);

    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}';
    }
});

$app->post('/jinetes/crear', function(Request $request, Response $response){
    $nombre = addslashes($request->getParam('nombre'));
    $nacionalidad = $request->getParam('nacionalidad');
    $estatura = $request->getParam('estatura');
    $peso = $request->getParam('peso');

    $db = new db();
	        $db = $db->conectar();

    $c1 = "SELECT * FROM jinete WHERE nombre = '$nombre'";
    $e1 = $db->query($c1);
	$n1 = $e1->rowCount();

	if ($n1 == 0) {
		$consulta = "INSERT INTO jinete (nombre, estatura, peso, nacionalidad) VALUES ('".$nombre."','".$estatura."','".$peso."','".$nacionalidad."')";
	    try{        

	        $stmt = $db->prepare($consulta);
	        $stmt->bindParam(':nombre', $nombre);
	        if ($stmt->execute()){
	            $result = array(
	                "status" => "correcto"
	            );

	            echo json_encode($result);
	        }     

	    } catch(PDOException $e){
	        echo '{"error": {"text": '.$consulta.'}';
	    }
	} else {
		$result = array(
            "status" => "error",
            "mensaje" => "Nombre ya existente"
        );

        echo json_encode($result);
	}

    
});

$app->get('/entrenadores/ver/todos', function(Request $request, Response $response){
    try{
        $db = new db();
        $i = 0;
        $db = $db->conectar();
        
        $consulta = "SELECT * FROM entrenador ORDER BY nombre";
        $ejecutar = $db->query($consulta);

        while($fila = $ejecutar->fetch(PDO::FETCH_ASSOC)) {
            $entrenadores[] = $fila;

            $nombre_fichero = 'imagenes/entrenadores/'.$entrenadores[$i]['id_entrenador'].'.png';

            if (file_exists($nombre_fichero)) {
                $entrenadores[$i]['img'] = $entrenadores[$i]['id_entrenador'].'.png';
            } else {
                $entrenadores[$i]['img'] = null;
            } 

            $i++;
        } 
        
        $result = array(
            "status" => "correcto",
            "entrenadores" => $entrenadores
        );

        $db = null;

        echo json_encode($result);

    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}';
    }
});

$app->post('/entrenadores/crear', function(Request $request, Response $response){
    $nombre = addslashes($request->getParam('nombre'));
    $nacionalidad = $request->getParam('nacionalidad');

    $db = new db();
    $db = $db->conectar();

    $c1 = "SELECT * FROM entrenador WHERE nombre = '$nombre'";
    $e1 = $db->query($c1);
	$n1 = $e1->rowCount();

	if ($n1 == 0) {
		$consulta = "INSERT INTO entrenador (nombre, nacionalidad) VALUES ('".$nombre."','".$nacionalidad."')";
	    try{        

	        $stmt = $db->prepare($consulta);
	        $stmt->bindParam(':nombre', $nombre);
	        if ($stmt->execute()){
	            $result = array(
	                "status" => "correcto"
	            );

	            echo json_encode($result);
	        }     

	    } catch(PDOException $e){
	        echo '{"error": {"text": '.$consulta.'}';
	    }
	} else {
		$result = array(
            "status" => "error",
            "mensaje" => "Nombre ya existente"
        );

        echo json_encode($result);
	}    
});


$app->get('/haras/ver/todos', function(Request $request, Response $response){
    try{
        $db = new db();
        $i = 0;
        $db = $db->conectar();
        
        $consulta = "SELECT * FROM haras ORDER BY descripcion";
        $ejecutar = $db->query($consulta);

        while($fila = $ejecutar->fetch(PDO::FETCH_ASSOC)) {
            $haras[] = $fila;

            $nombre_fichero = 'imagenes/haras/'.$haras[$i]['id_haras'].'.png';

            if (file_exists($nombre_fichero)) {
                $haras[$i]['img'] = $haras[$i]['id_haras'].'.png';
            } else {
                $haras[$i]['img'] = null;
            } 

            $i++;
        } 
        
        $result = array(
            "status" => "correcto",
            "haras" => $haras
        );

        $db = null;

        echo json_encode($result);

    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}';
    }
});

$app->post('/haras/crear', function(Request $request, Response $response){
    $descripcion = addslashes($request->getParam('descripcion'));
    $ubicacion = $request->getParam('ubicacion');

    $db = new db();
    $db = $db->conectar();

    $c1 = "SELECT * FROM haras WHERE descripcion = '$descripcion'";
    $e1 = $db->query($c1);
    $n1 = $e1->rowCount();

    if ($n1 == 0) {
        $consulta = "INSERT INTO haras (descripcion, ubicacion) VALUES ('".$descripcion."','".$ubicacion."')";
        try{        

            $stmt = $db->prepare($consulta);
            $stmt->bindParam(':descripcion', $descripcion);
            if ($stmt->execute()){
                $result = array(
                    "status" => "correcto"
                );

                echo json_encode($result);
            }     

        } catch(PDOException $e){
            echo '{"error": {"text": '.$consulta.'}';
        }
    } else {
        $result = array(
            "status" => "error",
            "mensaje" => "Nombre ya existente"
        );

        echo json_encode($result);
    }    
});

$app->get('/studs/ver/todos', function(Request $request, Response $response){
    try{
        $db = new db();
        $i = 0;
        $db = $db->conectar();
        
        $consulta = "SELECT * FROM stud ORDER BY descripcion";
        $ejecutar = $db->query($consulta);

        while($fila = $ejecutar->fetch(PDO::FETCH_ASSOC)) {
            $studs[] = $fila;

            $nombre_fichero = 'imagenes/studs/'.$studs[$i]['id_stud'].'.png';

            if (file_exists($nombre_fichero)) {
                $studs[$i]['img'] = $studs[$i]['id_stud'].'.png';
            } else {
                $studs[$i]['img'] = null;
            } 

            $i++;
        } 
        
        $result = array(
            "status" => "correcto",
            "studs" => $studs
        );

        $db = null;

        echo json_encode($result);

    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}';
    }
});

$app->post('/studs/crear', function(Request $request, Response $response){
    $descripcion = addslashes($request->getParam('descripcion'));
    $ubicacion = $request->getParam('ubicacion');

    $db = new db();
    $db = $db->conectar();

    $c1 = "SELECT * FROM stud WHERE descripcion = '$descripcion'";
    $e1 = $db->query($c1);
    $n1 = $e1->rowCount();

    if ($n1 == 0) {
        $consulta = "INSERT INTO stud (descripcion, ubicacion) VALUES ('".$descripcion."','".$ubicacion."')";
        try{        

            $stmt = $db->prepare($consulta);
            $stmt->bindParam(':descripcion', $descripcion);
            if ($stmt->execute()){
                $result = array(
                    "status" => "correcto"
                );

                echo json_encode($result);
            }     

        } catch(PDOException $e){
            echo '{"error": {"text": '.$consulta.'}';
        }
    } else {
        $result = array(
            "status" => "error",
            "mensaje" => "Nombre ya existente"
        );

        echo json_encode($result);
    }    
});

$app->get('/hipodromos/ver/todos', function(Request $request, Response $response){
    try{
        $db = new db();
        $i = 0;
        $db = $db->conectar();
        
        $consulta = "SELECT * FROM hipodromo ORDER BY descripcion";
        $ejecutar = $db->query($consulta);

        while($fila = $ejecutar->fetch(PDO::FETCH_ASSOC)) {
            $hipodromos[] = $fila;

            $nombre_fichero = 'imagenes/hipodromos/'.$hipodromos[$i]['id_hipodromo'].'.png';

            if (file_exists($nombre_fichero)) {
                $hipodromos[$i]['img'] = $hipodromos[$i]['id_hipodromo'].'.png';
            } else {
                $hipodromos[$i]['img'] = null;
            } 

            $i++;
        } 
        
        $result = array(
            "status" => "correcto",
            "hipodromos" => $hipodromos
        );

        $db = null;

        echo json_encode($result);

    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}';
    }
});

$app->get('/carreras/ver/{id}', function(Request $request, Response $response){
    try{
        $id = $request->getAttribute('id');
        $db = new db();
        $i = 0;
        $db = $db->conectar();
        $indice = '';
        $indice2 = '';

        $fecha_for_1 = date("Y-m-d H:i:s");

        if ($id != 'todos') {
            $criterio = " AND id_carrera = '".$id."' ";
        } else {
            $criterio = "";
        }
        
        $consulta = "SELECT * FROM carrera WHERE fecha_hora >= '$fecha_for_1' ".$criterio." ORDER BY id_hipodromo, fecha_hora ASC";
        $ejecutar = $db->query($consulta);

        while($fila = $ejecutar->fetch(PDO::FETCH_ASSOC)) {
            $carreras[] = $fila;

            $fecha_carrera = date("d-m-Y", strtotime($fila['fecha_hora']));
            

            $c1 = "SELECT * FROM inscripcion WHERE id_carrera = '".$fila['id_carrera']."' ORDER BY numero";
            $e1 = $db->query($c1);
            $n1 = $e1->rowCount();

            if ($n1 == 0) {
                $carreras[$i]['inscritos'] = null;
                $carreras[$i]['inscripcion'] = "0 ejemplares inscritos";
            } else {
                $carreras[$i]['inscripcion'] = "$n1 ejemplares inscritos";
                $inscritos = array();
                $j = 0;

                while ($fila2 = $e1->fetch(PDO::FETCH_ASSOC)) {                    

                    $carreras[$i]['inscritos'][$j] = $fila2;

                    $c10 = "SELECT * FROM caballo WHERE id_caballo = '".$fila2['id_caballo']."'";
                    $e10 = $db->query($c10);

                    while($f10 = $e10->fetch(PDO::FETCH_ASSOC)) {
                        $carreras[$i]['inscritos'][$j]['id_caballo'] = $f10;

                        $datetime1 = date_create($f10['nacimiento']);
                        $datetime2 = date_create(date("Y-m-d"));

                        $interval = date_diff($datetime1, $datetime2);

                        $descripcion = $interval->format('%y años');  

                        $carreras[$i]['inscritos'][$j]['id_caballo']['edad'] = $descripcion;

                        $c15 = "SELECT * FROM caballo WHERE id_caballo = '".$f10['padre']."'";
                        $e15 = $db->query($c15);

                        while($f15 = $e15->fetch(PDO::FETCH_ASSOC)) {
                            $carreras[$i]['inscritos'][$j]['id_caballo']['padre'] = $f15;                                
                        } 

                        $c16 = "SELECT * FROM caballo WHERE id_caballo = '".$f10['madre']."'";
                        $e16 = $db->query($c16);

                        while($f16 = $e16->fetch(PDO::FETCH_ASSOC)) {
                            $carreras[$i]['inscritos'][$j]['id_caballo']['madre'] = $f16;                                
                        }    

                        $c17 = "SELECT * FROM haras WHERE id_haras = '".$f10['id_haras']."'";
                        $e17 = $db->query($c17);

                        while($f17 = $e17->fetch(PDO::FETCH_ASSOC)) {
                            $carreras[$i]['inscritos'][$j]['id_caballo']['id_haras'] = $f17;                                
                        }                            
                    } 

                    $c11 = "SELECT * FROM jinete WHERE id_jinete = '".$fila2['id_jinete']."'";
                    $e11 = $db->query($c11);

                    while($f11 = $e11->fetch(PDO::FETCH_ASSOC)) {
                        $carreras[$i]['inscritos'][$j]['id_jinete'] = $f11;                                
                    }

                    $c12 = "SELECT * FROM entrenador WHERE id_entrenador = '".$fila2['id_entrenador']."'";
                    $e12 = $db->query($c12);

                    while($f12 = $e12->fetch(PDO::FETCH_ASSOC)) {
                        $carreras[$i]['inscritos'][$j]['id_entrenador'] = $f12;                                
                    } 

                    $c13 = "SELECT * FROM stud WHERE id_stud = '".$fila2['id_stud']."'";
                    $e13 = $db->query($c13);

                    while($f13 = $e13->fetch(PDO::FETCH_ASSOC)) {
                        $carreras[$i]['inscritos'][$j]['id_stud'] = $f13;                                
                    }

                    $c14 = "SELECT * FROM stud WHERE id_stud = '".$fila2['id_stud2']."'";
                    $e14 = $db->query($c14);

                    while($f14 = $e14->fetch(PDO::FETCH_ASSOC)) {
                        $carreras[$i]['inscritos'][$j]['id_stud2'] = $f14;                                
                    } 

                    $j++;
                }  
            }

            $nombre_fichero = 'imagenes/carreras/'.$carreras[$i]['id_carrera'].'.png';

            if (file_exists($nombre_fichero)) {
                $carreras[$i]['img'] = $carreras[$i]['id_carrera'].'.png';
            } else {
                $carreras[$i]['img'] = null;
            } 

            $k = 0;

            $consulta3 = "SELECT * FROM hipodromo WHERE id_hipodromo = '".$fila['id_hipodromo']."'";
            $ejecutar3 = $db->query($consulta3);

            while($fila = $ejecutar3->fetch(PDO::FETCH_ASSOC)) {
                $hipodromos[] = $fila;                
                $k++;
            } 

            $carreras[$i]['id_hipodromo'] = $hipodromos;

            if ($fila['id_hipodromo'] != $indice OR $fecha_carrera != $indice2) {

                $indice = $fila['id_hipodromo'];
                $indice2 = $fecha_carrera;

                $carreras[$i]['div'] = $hipodromos[0]['descripcion']." - ".$fecha_carrera;
;
            }

            $i++;
        } 
        
        $result = array(
            "status" => "correcto",
            "carreras" => $carreras
        );

        $db = null;

        echo json_encode($result);

    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}';
    }
});

$app->post('/carreras/crear', function(Request $request, Response $response){
    
    $codigo = addslashes($request->getParam('codigo'));
    $id_hipodromo = $request->getParam('id_hipodromo');
    $fecha_hora = date_create($request->getParam('fecha_hora'));
    $distancia = $request->getParam('distancia');
    $superficie = $request->getParam('superficie');
    $numero = $request->getParam('numero');
    $valida = $request->getParam('valida');
    $descripcion = addslashes($request->getParam('descripcion'));
    $titulo = addslashes($request->getParam('titulo'));

    $fecha_hora = date_format($fecha_hora, 'Y-m-d H:i:s');

    $db = new db();
    $db = $db->conectar();

    $c1 = "SELECT * FROM carrera WHERE codigo = '$codigo'";
    $e1 = $db->query($c1);
    $n1 = $e1->rowCount();

    if ($n1 == 0) {
        $consulta = "INSERT INTO carrera (codigo, id_hipodromo, fecha_hora, distancia, superficie, numero, valida, descripcion, titulo) VALUES ('$codigo','$id_hipodromo','$fecha_hora','$distancia','$superficie','$numero','$valida','$descripcion','$titulo')";
        try{        

            $stmt = $db->prepare($consulta);
            $stmt->bindParam(':descripcion', $descripcion);
            if ($stmt->execute()){
                $result = array(
                    "status" => "correcto"
                );

                echo json_encode($result);
            }     

        } catch(PDOException $e){
            echo '{"error": {"text": '.$consulta.'}';
        }
    } else {
        $result = array(
            "status" => "error",
            "mensaje" => "Carrera ya existente"
        );

        echo json_encode($result);
    }    
});


$app->get('/caballos/caballosui', function(Request $request, Response $response){
    $db = new db();
    $db = $db->conectar();

    $c_1 = "SELECT * FROM caballo WHERE (tipo_caballo = '3') ORDER BY nombre ASC";
    $e_c_1 = $db->query($c_1);
    while ($r_c_1 = $e_c_1->fetch(PDO::FETCH_ASSOC)){ $caballos[] = $r_c_1['nombre']; }

    $result = array(
        "caballosui" => $caballos
    );

    echo json_encode($result);
});

$app->get('/caballos/padrillosui', function(Request $request, Response $response){
    $db = new db();
    $db = $db->conectar();

    $c_1 = "SELECT * FROM caballo WHERE (tipo_caballo = '1') AND (sexo = '1') ORDER BY nombre ASC";
    $e_c_1 = $db->query($c_1);
    while ($r_c_1 = $e_c_1->fetch(PDO::FETCH_ASSOC)){ $caballos[] = $r_c_1['nombre']; }

    $result = array(
        "padrillosui" => $caballos
    );

    echo json_encode($result);
});

$app->get('/caballos/madrillasui', function(Request $request, Response $response){
    $db = new db();
    $db = $db->conectar();

    $c_1 = "SELECT * FROM caballo WHERE (tipo_caballo = '1') AND (sexo = '2') ORDER BY nombre ASC";
    $e_c_1 = $db->query($c_1);
    while ($r_c_1 = $e_c_1->fetch(PDO::FETCH_ASSOC)){ $caballos[] = $r_c_1['nombre']; }

    $result = array(
        "madrillasui" => $caballos
    );

    echo json_encode($result);
});

$app->get('/caballos/jinetesui', function(Request $request, Response $response){
    $db = new db();
    $db = $db->conectar();

    $c_1 = "SELECT * FROM jinete ORDER BY nombre ASC";
    $e_c_1 = $db->query($c_1);
    while ($r_c_1 = $e_c_1->fetch(PDO::FETCH_ASSOC)){ $jinetes[] = $r_c_1['nombre']; }

    $result = array(
        "jinetesui" => $jinetes
    );

    echo json_encode($result);
});

$app->get('/caballos/entrenadoresui', function(Request $request, Response $response){
    $db = new db();
    $db = $db->conectar();

    $c_1 = "SELECT * FROM entrenador ORDER BY nombre ASC";
    $e_c_1 = $db->query($c_1);
    while ($r_c_1 = $e_c_1->fetch(PDO::FETCH_ASSOC)){ $entrenadores[] = $r_c_1['nombre']; }

    $result = array(
        "entrenadoresui" => $entrenadores
    );

    echo json_encode($result);
});

$app->get('/caballos/harasui', function(Request $request, Response $response){
    $db = new db();
    $db = $db->conectar();

    $c_1 = "SELECT * FROM haras ORDER BY descripcion ASC";
    $e_c_1 = $db->query($c_1);
    while ($r_c_1 = $e_c_1->fetch(PDO::FETCH_ASSOC)){ $haras[] = $r_c_1['descripcion']; }

    $result = array(
        "harasui" => $haras
    );

    echo json_encode($result);
});

$app->get('/caballos/studsui', function(Request $request, Response $response){
    $db = new db();
    $db = $db->conectar();

    $c_1 = "SELECT * FROM stud ORDER BY descripcion ASC";
    $e_c_1 = $db->query($c_1);
    while ($r_c_1 = $e_c_1->fetch(PDO::FETCH_ASSOC)){ $studs[] = $r_c_1['descripcion']; }

    $result = array(
        "studsui" => $studs
    );

    echo json_encode($result);
});

$app->post('/inscripcion/crear', function(Request $request, Response $response){

    $db = new db();
    $db = $db->conectar();

    $carrera = addslashes($request->getParam('id_carrera'));

    $c1 = "SELECT * FROM carrera WHERE codigo = '$carrera'";
    $e1 = $db->query($c1);
    $n1 = $e1->rowCount();

    if ($n1 > 0) {
        while ($r1 = $e1->fetch(PDO::FETCH_ASSOC)){ $id_carrera = $r1['id_carrera']; }
    } else {

    }

    $caballo = addslashes($request->getParam('id_caballo'));  

    $c2 = "SELECT * FROM caballo WHERE nombre = '$caballo'";
    $e2 = $db->query($c2);
    $n2 = $e2->rowCount();

    if ($n2 > 0) {
        while ($r2 = $e2->fetch(PDO::FETCH_ASSOC)){ $id_caballo = $r2['id_caballo']; }
    } else {

    }    

    $jinete = addslashes($request->getParam('id_jinete'));

    $c3 = "SELECT * FROM jinete WHERE nombre = '$jinete'";
    $e3 = $db->query($c3);
    $n3 = $e3->rowCount();

    if ($n3 > 0) {
        while ($r3 = $e3->fetch(PDO::FETCH_ASSOC)){ $id_jinete = $r3['id_jinete']; }
    } else {

    }  

    $entrenador = addslashes($request->getParam('id_entrenador'));

    $c4 = "SELECT * FROM entrenador WHERE nombre = '$entrenador'";
    $e4 = $db->query($c4);
    $n4 = $e4->rowCount();

    if ($n4 > 0) {
        while ($r4 = $e4->fetch(PDO::FETCH_ASSOC)){ $id_entrenador = $r4['id_entrenador']; }
    } else {

    } 

    $stud1 = addslashes($request->getParam('id_stud'));

    $c5 = "SELECT * FROM stud WHERE descripcion = '$stud1'";
    $e5 = $db->query($c5);
    $n5 = $e5->rowCount();

    if ($n5 > 0) {
        while ($r5 = $e5->fetch(PDO::FETCH_ASSOC)){ $id_stud = $r5['id_stud']; }
    } else {

    } 

    if ($request->getParam('id_stud2') != null) {
        $stud2 = addslashes($request->getParam('id_stud2'));

        $c6 = "SELECT * FROM stud WHERE descripcion = '$stud2'";
        $e6 = $db->query($c6);
        $n6 = $e6->rowCount();

        if ($n6 > 0) {
            while ($r6 = $e6->fetch(PDO::FETCH_ASSOC)){ $id_stud2 = $r6['id_stud']; }
        } else {

        }
    } else {
        $id_stud2 = '';
    }    

    $peso = addslashes($request->getParam('peso'));
    $numero = addslashes($request->getParam('numero'));
    $puesto = addslashes($request->getParam('puesto'));

    $cs1 = "SELECT * FROM inscripcion WHERE id_carrera = '$id_carrera' AND id_caballo = '$id_caballo'";
    $es1 = $db->query($cs1);
    $ns1 = $es1->rowCount();

    if ($ns1 == 0) {
        $cs2 = "INSERT INTO inscripcion (id_caballo, id_jinete, id_entrenador, id_stud, id_stud2, peso, numero, puesto, id_carrera) VALUES ('$id_caballo','$id_jinete','$id_entrenador','$id_stud','$id_stud2','$peso','$numero','$puesto','$id_carrera')";

        $stmt = $db->prepare($cs2);
        $stmt->bindParam(':id_caballo', $id_caballo);

        if ($stmt->execute()){
            $result = array(
                "id_carrera" => $id_carrera,
                "id_caballo" => $id_caballo,
                "id_jinete" => $id_jinete,
                "id_entrenador" => $id_entrenador,
                "id_stud1" => $id_stud,
                "id_stud2" => $id_stud2,
                "numero" => $numero,
                "peso" => $peso,
                "puesto" => $puesto,
                "status" => "correcto"
            );

            echo json_encode($result);
        }     
    }
    
});

$app->get('/categorias/juegos', function(Request $request, Response $response){
    $db = new db();
    $db = $db->conectar();

    $i = 0;

    $fecha_for_1 = date("Y-m-d H:i:s");

    $fecha_manana = date_create($fecha_for_1);
    date_add($fecha_manana, date_interval_create_from_date_string('1 days'));
    $fecha_de_manana = date_format($fecha_manana, 'd-m-Y');
    $fecha_manana = date_format($fecha_manana, 'Y-m-d H:i:s');

    $c1 = "SELECT * FROM categoria ORDER BY descripcion ASC";
    $e1 = $db->query($c1);
    while ($r1 = $e1->fetch(PDO::FETCH_ASSOC)){ 
        
        if ($r1['id_categoria'] == '27') {
            $c2 = "SELECT * FROM carrera WHERE fecha_hora >= '$fecha_for_1'";
            $e2 = $db->query($c2);
            $n2 = $e2->rowCount();

            $juegos[$i]['categoria'] = $r1['descripcion'];
            $juegos[$i]['id_categoria'] = $r1['id_categoria'];
            $juegos[$i]['juegos'] = $n2;
        } else {
            $c3 = "SELECT * FROM p_futbol INNER JOIN liga ON p_futbol.id_liga = liga.id_liga WHERE liga.id_categoria = '".$r1['id_categoria']."' AND p_futbol.fecha_inicio >= '$fecha_for_1' AND p_futbol.fecha_inicio < '$fecha_manana'";
            $e3 = $db->query($c3);
            $n3 = $e3->rowCount();

            $juegos[$i]['categoria'] = $r1['descripcion'];
            $juegos[$i]['id_categoria'] = $r1['id_categoria'];
            $juegos[$i]['juegos'] = $n3;
        }

        $i++;

    }

    $result = array(
        "juegos" => $juegos
    );

    echo json_encode($result);
});

$app->post('/seleccion/agregarh/{id_apuesta}', function(Request $request, Response $response){
    $id_apuesta = $request->getAttribute('id_apuesta');
    $id_usuario = $request->getParam('id_usuario');

    $tipo = '';
    $selecciones = [];

    $db = new db();
    $db = $db->conectar();

    $c1 = "SELECT * FROM seleccion WHERE id_select = '$id_apuesta' AND id_usuario = '$id_usuario' AND (id_ticket = '0' OR id_ticket = '')";
    $e1 = $db->query($c1);
    $n1 = $e1->rowCount();

    if ($n1 == 1) {
        $c2 = "DELETE FROM seleccion WHERE id_select = '$id_apuesta' AND id_usuario = '$id_usuario' AND (id_ticket = '0' OR id_ticket = '')";
        if ($db->query($c2)) { $status= "Borrado"; $mstatus = "Ejemplar borrado"; }
    } else {
        $c1_1 = "SELECT * FROM seleccion WHERE (id_ticket = '0' OR id_ticket = '') AND id_usuario = '$id_usuario' ORDER BY id_seleccion DESC";
        $e1_1 = $db->query($c1_1);
        $n1_1 = $e1_1->rowCount();

        $r1_1 = $e1_1->fetch(PDO::FETCH_ASSOC);

        if ($r1_1['id_deporte'] != '27' && $n1_1 > 0) {
            $mstatus = "No se pueden combinar selecciones de deporte e hipismo";
            $status = 'incorrecto';
            $selecciones = [];
            $tipo = '2x';
        } else {
            $c3 = "SELECT * FROM inscripcion WHERE id_inscripcion = '$id_apuesta'";
            $e3 = $db->query($c3);
            $n3 = $e3->rowCount();

            if ($n3 > 0) {
                $r3 = $e3->fetch(PDO::FETCH_ASSOC);

                $id_carrera = $r3['id_carrera'];

                $c4 = "SELECT * FROM seleccion WHERE (muestra = '$id_carrera') AND id_usuario = '$id_usuario' AND (id_ticket = '0' OR id_ticket = '') ";
                $e4 = $db->query($c4);
                $n4 = $e4->rowCount();

                if ($n4 == 0) {
                    $c5 = "INSERT seleccion (muestra, tipo, id_usuario, id_deporte, id_select) VALUES ('$id_carrera', '1', '$id_usuario','27','$id_apuesta')";
                    if ($db->query($c5)) { $status = "Correcto"; $mstatus = "Ejemplar agregado"; }
                } else {
                    $status = "Igual"; $mstatus = "Ya tiene un ejemplar seleccionado en esta carrera";
                }
            }   
        }               
    } 

    if ($tipo != '2x') {
        $c6 = "SELECT * FROM seleccion WHERE id_usuario = '$id_usuario' AND (id_ticket = '0' OR id_ticket = '') ORDER BY id_seleccion";
        $e6 = $db->query($c6);
        $n6 = $e6->rowCount();

        if ($n6 > 0) {
            $i = 0;

            while($f6 = $e6->fetch(PDO::FETCH_ASSOC)) {
                $c7 = "SELECT * FROM inscripcion WHERE id_inscripcion = '".$f6['id_select']."'";
                $e7 = $db->query($c7);
                $n7 = $e7->rowCount();

                if ($n7 > 0) {
                    $r7 = $e7->fetch(PDO::FETCH_ASSOC);

                    $id_part_2 = $r7["id_inscripcion"];
                    
                    $j = 0;

                    $c8 = "SELECT * FROM caballo WHERE id_caballo = '".$r7['id_caballo']."'";
                    $e8 = $db->query($c8);
                    $n8 = $e8->rowCount();

                    if ($n8 > 0) {
                        $r8 = $e8->fetch(PDO::FETCH_ASSOC);
                                           
                    }   

                    $c9 = "SELECT * FROM carrera WHERE id_carrera = '".$r7['id_carrera']."'";
                    $e9 = $db->query($c9);
                    $n9 = $e9->rowCount();

                    if ($n9 > 0) {
                        $r9 = $e9->fetch(PDO::FETCH_ASSOC);

                        $date_actual = date("Y-m-d H:i:s");
                        $date_juego = $r9["fecha_hora"];

                        if ($date_actual > $date_juego) {
                            $control2 = "mayor";
                        } else {
                            $control2 = "";
                            $selecciones[$i] = $f6;  
                            $selecciones[$i]['id_select'] = $r7; 
                            $selecciones[$i]['id_select']['id_caballo'] = $r8;  
                            $selecciones[$i]['id_select']['id_carrera'] = $r9;
                        }                                             
                    }

                    $c10 = "SELECT * FROM hipodromo WHERE id_hipodromo = '".$r9['id_hipodromo']."'";
                    $e10 = $db->query($c10);
                    $n10 = $e10->rowCount();

                    if ($n10 > 0) {
                        $r10 = $e10->fetch(PDO::FETCH_ASSOC);
                        $selecciones[$i]['id_select']['id_carrera']['id_hipodromo'] = $r10;                     
                    }

                    if ($control2 == "mayor") {
                        $con8 = "DELETE FROM seleccion WHERE id_select = '$id_part_2' and id_usuario = '$id_usuario'";
                        if ($econ8 = $db->query($con8)) {
                            
                        }
                    }
                }   

                $i++;                         
            }
        }
    }   

    $result = array(
        "status" => $status,
        "mstatus" => $mstatus,
        "selecciones" => $selecciones
    );

    echo json_encode($result);
});

$app->post('/seleccion/agregard/{id_apuesta}', function(Request $request, Response $response){
    $id_apuesta = $request->getAttribute('id_apuesta');
    $id_usuario = $request->getParam('id_usuario');
    $id_deporte = $request->getParam('id_categoria');
    $cuota = 1;
    $decim_tot = 1;
    $status = '';
    $mstatus = '';
    $selecciones = [];
    $tipo = '';

    $db = new db();
    $db = $db->conectar();

    $c1 = "SELECT * FROM seleccion WHERE id_select = '$id_apuesta' AND (id_ticket = '0' OR id_ticket = '') AND id_usuario = '$id_usuario' ORDER BY id_seleccion DESC";
    $e1 = $db->query($c1);
    $n1 = $e1->rowCount();

    if ($n1 > 0) {
        $c2 = "DELETE FROM seleccion WHERE id_select = '$id_apuesta' AND id_usuario = '$id_usuario' AND (id_ticket = '0' OR id_ticket = '')";
        if ($db->query($c2)) { $status= "Borrado"; $mstatus = "Selección borrada"; }
    } else {

        $c1_1 = "SELECT * FROM seleccion WHERE (id_ticket = '0' OR id_ticket = '') AND id_usuario = '$id_usuario' ORDER BY id_seleccion DESC";
        $e1_1 = $db->query($c1_1);

        $r1_1 = $e1_1->fetch(PDO::FETCH_ASSOC);

        if ($r1_1['id_deporte'] == '27') {
            $mstatus = "No se pueden combinar selecciones de deporte e hipismo";
            $status = 'incorrecto';
            $selecciones = [];
            $tipo = '27';
        } else {
            $c3 = "SELECT * FROM participante WHERE id_participante = '$id_apuesta'";
            $e3 = $db->query($c3);
            $n3 = $e3->rowCount();

            if ($n3 > 0) {
                $r3 = $e3->fetch(PDO::FETCH_ASSOC);

                $dividendo = $r3["dividendo"];
                $id_partido = $r3["id_partido"];

                $c4 = "SELECT * FROM seleccion WHERE muestra = '$id_partido' AND id_usuario = '$id_usuario' AND (id_ticket = '' OR id_ticket = '0')";
                $e4 = $db->query($c4);
                $n4 = $e4->rowCount();

                if ($n4 == "0") {
                    $c5 = "INSERT seleccion (muestra, valor, id_usuario, id_deporte, id_select) VALUES ('$id_partido','$dividendo','$id_usuario','$id_deporte','$id_apuesta')";
                    if ($db->query($c5)) {
                        $status = "correcto";
                        $mstatus = "Selección agregada";
                    };
                } else {
                    $status = "incorrecto";
                    $mstatus = "Ya tiene una selección para este encuentro deportivo";
                }
            }
        }        
    }

    if ($tipo != '27') {
        $c6 = "SELECT * FROM seleccion WHERE id_usuario = '$id_usuario' AND (id_ticket = '0' OR id_ticket = '') ORDER BY id_seleccion";
        $e6 = $db->query($c6);
        $n6 = $e6->rowCount();

        if ($n6 > 0) {
            $i = 0;

            while($f6 = $e6->fetch(PDO::FETCH_ASSOC)) {           

                $id_select = $f6["id_select"]; 

                $c7 = "SELECT * FROM participante WHERE id_participante = '$id_select'";
                $ec7 = $db->query($c7);
                $nc7 = $ec7->rowCount();

                if ($nc7 > 0) {
                    $f7 = $ec7->fetch(PDO::FETCH_ASSOC);

                    $id_part_2 = $f7["id_participante"];

                    $id_equipo = $f7["id_equipo1"];

                    $div_equipo_part1 = $f7["dividendo"];

                    $div_div = explode("/", $div_equipo_part1);

                    if (!isset($div_div[1])) {
                        $div_div[1] = 1;
                    }                

                    $c8 = "SELECT * FROM equipo WHERE id_equipo = '$id_equipo'";
                    $e8 = $db->query($c8);
                    $f8 = $e8->fetch(PDO::FETCH_ASSOC);

                    

                    $id_partido = $f7["id_partido"];

                    $c9 = "SELECT * FROM p_futbol WHERE id_partido = '$id_partido'";
                    $e9 = $db->query($c9);
                    $f9 = $e9->fetch(PDO::FETCH_ASSOC);

                    $name_partido =  $f9["id_wihi_partido"];

                    $date_actual = date("Y-m-d H:i:s");
                    $date_juego = $f9["fecha_inicio"];

                    if ($date_actual > $date_juego) {
                        $control2 = "mayor";
                    } else {
                        $control2 = "";
                        $selecciones[$i] = $f6;  
                        $selecciones[$i]['equipo'] = $f8['nombre_equipo'];
                    }

                    $decimal_odd = (intval($div_div[0]) / intval($div_div[1])) + 1;

                    if ($control2 != "mayor") {
                        $decim_tot = $decimal_odd * $decim_tot;                         
                    }

                    $name_d1 = explode("!", $name_partido);
                    $name_d2 = explode(".", $name_d1[1]);

                    $l1 = "SELECT * FROM equipo WHERE id_equipo='$name_d2[0]'";
                    $el1 = $db->query($l1);
                    $rl1 = $el1->fetch(PDO::FETCH_ASSOC);

                    $name_equipo1 = $rl1["nombre_equipo"];                      

                    $l2 = "SELECT * FROM equipo WHERE id_equipo='$name_d2[1]'";
                    $el2 = $db->query($l2);
                    $rl2 = $el2->fetch(PDO::FETCH_ASSOC);

                    $name_equipo2 = $rl2["nombre_equipo"];

                    $encuentro = "$name_equipo1 vs $name_equipo2";

                    $selecciones[$i]['encuentro'] = $encuentro;

                    if ($control2 == "mayor") {
                        $con8 = "DELETE FROM seleccion WHERE id_select = '$id_part_2' and id_usuario = '$id_usuario'";
                        if ($econ8 = $db->query($con8)) {
                            
                        }
                    }

                }

                $i++;
            }
        }
    }

    $result = array(
        "status" => $status,
        "mstatus" => $mstatus,
        "selecciones" => $selecciones,
        "cuota" => $decim_tot
    );

    echo json_encode($result);
});

$app->get('/seleccion/obtener/{id_usuario}', function(Request $request, Response $response){
    $db = new db();
    $db = $db->conectar();

    $id_usuario = $request->getAttribute('id_usuario');
    $tipo = '';
    $decim_tot = 1;

    $c6 = "SELECT * FROM seleccion WHERE id_usuario = '$id_usuario' AND (id_ticket = '0' OR id_ticket = '') ORDER BY id_seleccion";
    $e6 = $db->query($c6);
    $n6 = $e6->rowCount();

    if ($n6 > 0) {
        $i = 0;

        while($f6 = $e6->fetch(PDO::FETCH_ASSOC)) {           

            $id_select = $f6["id_select"]; 

            if ($f6['id_deporte'] == '27') {

                $tipo = "27";

                $c7 = "SELECT * FROM inscripcion WHERE id_inscripcion = '".$f6['id_select']."'";
                $e7 = $db->query($c7);
                $n7 = $e7->rowCount();

                if ($n7 > 0) {
                    $r7 = $e7->fetch(PDO::FETCH_ASSOC);

                    $id_part_2 = $r7["id_inscripcion"];
                    
                    $j = 0;

                    $c8 = "SELECT * FROM caballo WHERE id_caballo = '".$r7['id_caballo']."'";
                    $e8 = $db->query($c8);
                    $n8 = $e8->rowCount();

                    if ($n8 > 0) {
                        $r8 = $e8->fetch(PDO::FETCH_ASSOC);
                                           
                    }   

                    $c9 = "SELECT * FROM carrera WHERE id_carrera = '".$r7['id_carrera']."'";
                    $e9 = $db->query($c9);
                    $n9 = $e9->rowCount();

                    if ($n9 > 0) {
                        $r9 = $e9->fetch(PDO::FETCH_ASSOC);

                        $date_actual = date("Y-m-d H:i:s");
                        $date_juego = $r9["fecha_hora"];

                        if ($date_actual > $date_juego) {
                            $control2 = "mayor";
                        } else {
                            $control2 = "";
                            $selecciones[$i] = $f6;  
                            $selecciones[$i]['id_select'] = $r7; 
                            $selecciones[$i]['id_select']['id_caballo'] = $r8;  
                            $selecciones[$i]['id_select']['id_carrera'] = $r9;
                        }                                             
                    }

                    $c10 = "SELECT * FROM hipodromo WHERE id_hipodromo = '".$r9['id_hipodromo']."'";
                    $e10 = $db->query($c10);
                    $n10 = $e10->rowCount();

                    if ($n10 > 0) {
                        $r10 = $e10->fetch(PDO::FETCH_ASSOC);
                        $selecciones[$i]['id_select']['id_carrera']['id_hipodromo'] = $r10;                     
                    }

                    if ($control2 == "mayor") {
                        $con8 = "DELETE FROM seleccion WHERE id_select = '$id_part_2' and id_usuario = '$id_usuario'";
                        if ($econ8 = $db->query($con8)) {
                            
                        }
                    }
                }
            } else {
                $tipo = "2x";

                $c7 = "SELECT * FROM participante WHERE id_participante = '$id_select'";
                $ec7 = $db->query($c7);
                $nc7 = $ec7->rowCount();

                if ($nc7 > 0) {
                    $f7 = $ec7->fetch(PDO::FETCH_ASSOC);

                    $id_part_2 = $f7["id_participante"];

                    $id_equipo = $f7["id_equipo1"];

                    $div_equipo_part1 = $f7["dividendo"];

                    $div_div = explode("/", $div_equipo_part1);

                    if (!isset($div_div[1])) {
                        $div_div[1] = 1;
                    }                

                    $c8 = "SELECT * FROM equipo WHERE id_equipo = '$id_equipo'";
                    $e8 = $db->query($c8);
                    $f8 = $e8->fetch(PDO::FETCH_ASSOC);                    

                    $id_partido = $f7["id_partido"];

                    $c9 = "SELECT * FROM p_futbol WHERE id_partido = '$id_partido'";
                    $e9 = $db->query($c9);
                    $f9 = $e9->fetch(PDO::FETCH_ASSOC);

                    $name_partido =  $f9["id_wihi_partido"];

                    $date_actual = date("Y-m-d H:i:s");
                    $date_juego = $f9["fecha_inicio"];

                    if ($date_actual > $date_juego) {
                        $control2 = "mayor";
                    } else {
                        $control2 = "";
                        $selecciones[$i] = $f6;  
                        $selecciones[$i]['equipo'] = $f8['nombre_equipo'];
                    }

                    $decimal_odd = (intval($div_div[0]) / intval($div_div[1])) + 1;

                    if ($control2 != "mayor") {
                        $decim_tot = $decimal_odd * $decim_tot;                         
                    }

                    $name_d1 = explode("!", $name_partido);
                    $name_d2 = explode(".", $name_d1[1]);

                    $l1 = "SELECT * FROM equipo WHERE id_equipo='$name_d2[0]'";
                    $el1 = $db->query($l1);
                    $rl1 = $el1->fetch(PDO::FETCH_ASSOC);

                    $name_equipo1 = $rl1["nombre_equipo"];                      

                    $l2 = "SELECT * FROM equipo WHERE id_equipo='$name_d2[1]'";
                    $el2 = $db->query($l2);
                    $rl2 = $el2->fetch(PDO::FETCH_ASSOC);

                    $name_equipo2 = $rl2["nombre_equipo"];

                    $encuentro = "$name_equipo1 vs $name_equipo2";

                    $selecciones[$i]['encuentro'] = $encuentro;

                    if ($control2 == "mayor") {
                        $con8 = "DELETE FROM seleccion WHERE id_select = '$id_part_2' and id_usuario = '$id_usuario'";
                        if ($econ8 = $db->query($con8)) {
                            
                        }
                    }

                }
            }            

            $i++;
        }
    } else {
        $selecciones = [];
    }


    $result = array(
        "tipo" => $tipo,
        "selecciones" => $selecciones,
        "cuota" => $decim_tot
    );

    echo json_encode($result);
});

$app->get('/resultados/deporte/{id_categoria}', function(Request $request, Response $response){

    $id_categoria = $request->getAttribute('id_categoria');
    $fecha_actual = date("Y-m-d H:i:s");

    $db = new db();
    $db = $db->conectar();

    if ($id_categoria == '27') {
        $c1 =  "SELECT DISTINCT a.id_hipodromo, b.descripcion FROM carrera a INNER JOIN hipodromo b on a.id_hipodromo = b.id_hipodromo WHERE a.fecha_hora < '".$fecha_actual."' ORDER BY b.descripcion ASC";
        $ec1 = $db->query($c1);
        $nc1 = $ec1->rowCount();

        if ($nc1 > 0) {
            while($f1 = $ec1->fetch(PDO::FETCH_ASSOC)) {  
                $ligas[] = $f1;
            }
        }
    } else {
        $c1 = "SELECT DISTINCT a.id_liga, b.nombre_liga FROM p_futbol a INNER JOIN liga b on a.id_liga = b.id_liga INNER JOIN categoria c on b.id_categoria = c.id_categoria WHERE a.fecha_inicio < '".$fecha_actual."' AND c.id_categoria = '".$id_categoria."' ORDER BY b.nombre_liga ASC";
        $ec1 = $db->query($c1);
        $nc1 = $ec1->rowCount();

        if ($nc1 > 0) {
            while($f1 = $ec1->fetch(PDO::FETCH_ASSOC)) {  
                $ligas[] = $f1;
            }
        }
    }

    $result = array(
        "ligas" => $ligas
    );

    echo json_encode($result);
});

$app->get('/resultados/liga/{id_liga}', function(Request $request, Response $response){

    $id_liga = $request->getAttribute('id_liga');
    $fecha_actual = date("Y-m-d H:i:s");

    $db = new db();
    $db = $db->conectar();

    $i = 0;

    $c1 = "SELECT a.id_partido, a.id_liga, a.fecha_inicio, b.nombre_liga, b.id_categoria FROM p_futbol a INNER JOIN liga b on a.id_liga = b.id_liga INNER JOIN categoria c on b.id_categoria = c.id_categoria WHERE a.fecha_inicio < '$fecha_actual' AND a.id_liga = $id_liga ORDER BY a.fecha_inicio ASC";
    $ec1 = $db->query($c1);
    $nc1 = $ec1->rowCount();

    if ($nc1 > 0) {
        while($f1 = $ec1->fetch(PDO::FETCH_ASSOC)) {  
            $partidos[$i] = $f1;

            $c2 = "SELECT * FROM participante WHERE id_partido = '".$f1['id_partido']."' AND id_equipo1 != '35'";
            $ec2 = $db->query($c2);

            $j = 0;

            while ($f2 = $ec2->fetch(PDO::FETCH_ASSOC)) {
                $ec3 = $db->query("SELECT * FROM equipo WHERE id_equipo = '". $f2['id_equipo1'] . "'");
                $f3 = $ec3->fetch(PDO::FETCH_ASSOC);

                $partidos[$i]['equipos'][$j]['id_participante'] = $f2['id_participante'];
                $partidos[$i]['equipos'][$j]['id_equipo'] = $f2['id_equipo1'];
                $partidos[$i]['equipos'][$j]['nombre'] = $f3['nombre_equipo'];
                $partidos[$i]['equipos'][$j]['dividendo'] = $f2['dividendo'];
                $partidos[$i]['equipos'][$j]['id_ta'] = $f2['id_ta'];

                $j++;
            }

            $c4 = "SELECT * FROM rl_mx_9483 WHERE id_partido = '".$f1['id_partido']."'";
            $ec4 = $db->query($c4);
            $nc4 = $ec4->rowCount();

            if ($nc4 > 0) {
                $f4 = $ec4->fetch(PDO::FETCH_ASSOC);
                $res = $f4['resultado'];

                $result = explode('!', $res);

                $partidos[$i]['equipos'][0]['resultado'] = $result[0];
                $partidos[$i]['equipos'][1]['resultado'] = $result[1];
            }

            $i++;
        }
        
    }

    $result = array(
        "partidos" => $partidos
    );

    echo json_encode($result);
});

$app->get('/resultados/hipodromo/{id_hipodromo}', function(Request $request, Response $response){

    $id_hipodromo = $request->getAttribute('id_hipodromo');
    $fecha_actual = date("Y-m-d H:i:s");

    $db = new db();
    $db = $db->conectar();

    $i = 0;

    $c1 = "SELECT DISTINCT a.id_hipodromo, b.descripcion, a.id_carrera, a.fecha_hora, a.numero FROM carrera a INNER JOIN hipodromo b on a.id_hipodromo = b.id_hipodromo WHERE a.fecha_hora < '".$fecha_actual."' AND a.id_hipodromo = '".$id_hipodromo."' ORDER BY a.codigo ASC";
    $ec1 = $db->query($c1);
    $nc1 = $ec1->rowCount();

    if ($nc1 > 0) {
        while($f1 = $ec1->fetch(PDO::FETCH_ASSOC)) {  
            $carreras[$i] = $f1;

            $c2 = "SELECT * FROM inscripcion WHERE id_carrera = '".$f1['id_carrera']."'";
            $ec2 = $db->query($c2);

            $j = 0;

            while ($f2 = $ec2->fetch(PDO::FETCH_ASSOC)) {
                $ec3 = $db->query("SELECT * FROM caballo WHERE id_caballo = '". $f2['id_caballo'] . "'");
                $f3 = $ec3->fetch(PDO::FETCH_ASSOC);

                $carreras[$i]['caballos'][$j]['id_inscripcion'] = $f2['id_inscripcion'];
                $carreras[$i]['caballos'][$j]['id_caballo'] = $f2['id_caballo'];
                $carreras[$i]['caballos'][$j]['numero'] = $f2['numero'];
                $carreras[$i]['caballos'][$j]['nombre'] = $f3['nombre'];

                $j++;
            }

            $c4 = "SELECT * FROM rl_mx_9483 WHERE id_partido = '".$f1['id_carrera']."'";
            $ec4 = $db->query($c4);
            $nc4 = $ec4->rowCount();

            if ($nc4 > 0) {
                $f4 = $ec4->fetch(PDO::FETCH_ASSOC);
                $res = $f4['resultado'];

                $result = explode('!', $res);

                $ganador = explode("#", $result[0]);
                $place = explode("#", $result[1]);
                $third = explode("#", $result[2]);

                $carreras[$i]['cuadro'][0]['ejemplar'] = $ganador[0];
                $carreras[$i]['cuadro'][0]['cuota'] = $ganador[1];

                $carreras[$i]['cuadro'][1]['ejemplar'] = $place[0];
                $carreras[$i]['cuadro'][1]['cuota'] = $place[1];

                $carreras[$i]['cuadro'][2]['ejemplar'] = $third[0];
                $carreras[$i]['cuadro'][2]['cuota'] = $third[1];
                
            } else {
                $carreras[$i]['cuadro'][0]['ejemplar'] = null;
                $carreras[$i]['cuadro'][0]['cuota'] = null;

                $carreras[$i]['cuadro'][1]['ejemplar'] = null;
                $carreras[$i]['cuadro'][1]['cuota'] = null;

                $carreras[$i]['cuadro'][2]['ejemplar'] = null;
                $carreras[$i]['cuadro'][2]['cuota'] = null;
            }

            $i++;
        }
        
    }

    $result = array(
        "carreras" => $carreras
    );

    echo json_encode($result);



});

$app->post('/resultados/agregar', function(Request $request, Response $response){
   
    $id_partido = $request->getParam('id_partido');
    $id_categoria = $request->getParam('id_categoria');
    $id_ta = $request->getParam('id_ta');
    $resultado = $request->getParam('resultado');

    $db = new db();
    $db = $db->conectar();

    $c1 = "SELECT * FROM rl_mx_9483 WHERE id_partido = '$id_partido'";
    $e1 = $db->query($c1);
    $n1 = $e1->rowCount();

    if ($n1 == 0) {
        $consulta = "INSERT INTO rl_mx_9483 (id_partido, tipo_apuesta, id_categoria, resultado) VALUES ('".$id_partido."','".$id_ta."','".$id_categoria."','".$resultado."')";
        try{        

            $stmt = $db->prepare($consulta);

            if ($stmt->execute()){

                $res = explode('!', $resultado);

                if ($res[0] > $res[1]) {

                    $s1 = "SELECT id_participante, id_partido FROM participante WHERE id_partido = $id_partido ORDER BY id_participante ASC";
                    $es1 = $db->query($s1);
                    $k = 0;
                    while($fs1 = $es1->fetch(PDO::FETCH_ASSOC)) {

                        $id_participante = $fs1['id_participante'];

                        if ($k == 0) {
                            $s3 = "UPDATE participante SET status='1' WHERE id_participante='$id_participante'";
                            $es3 = $db->prepare($s3);
                            if ($es3->execute()) {
                                                                
                            }
                        } else {
                            $s2 = "UPDATE participante SET status='3' WHERE id_participante='$id_participante'";
                            $es2 = $db->prepare($s2);
                            if ($es2->execute()) {
                                
                            }
                        }

                        $k++;
                    }

                } elseif ($res[0] == $res[1]) {
                    
                    $s1 = "SELECT id_participante, id_partido, id_equipo1 FROM participante WHERE id_partido = $id_partido ORDER BY id_participante";
                    $es1 = $db->query($s1);

                    while($fs1 = $es1->fetch(PDO::FETCH_ASSOC)) {

                        $id_participante = $fs1['id_participante'];
                        $id_equipo1 = $fs1['id_equipo1'];

                        if ($id_equipo1 == '35') {
                            $s3 = "UPDATE participante SET status='1' WHERE id_participante='$id_participante'";
                            $es3 = $db->prepare($s3);
                            if ($es3->execute()) {
                                                                
                            }
                        } else {
                            $s2 = "UPDATE participante SET status='3' WHERE id_participante='$id_participante'";
                            $es2 = $db->prepare($s2);
                            if ($es2->execute()) {
                                
                            }
                        }                        
                    }

                } elseif ($res[0] < $res[1]) {
                    $s1 = "SELECT id_participante, id_partido FROM participante WHERE id_partido = $id_partido ORDER BY id_participante DESC";                    
                    $es1 = $db->query($s1);
                    $k = 0;
                    while($fs1 = $es1->fetch(PDO::FETCH_ASSOC)) {

                        $id_participante = $fs1['id_participante'];

                        if ($k == 0) {
                            $s3 = "UPDATE participante SET status='1' WHERE id_participante='$id_participante'";
                            $es3 = $db->prepare($s3);
                            if ($es3->execute()) {

                            }
                        } else {
                            $s2 = "UPDATE participante SET status='3' WHERE id_participante='$id_participante'";
                            $es2 = $db->prepare($s2);
                            if ($es2->execute()) {
                                
                            }
                        } 

                        $k++;
                    }
                } 

                $s4 = "SELECT * FROM seleccion WHERE muestra = '$id_partido' AND id_ticket != ''";
                $es4 = $db->query($s4);
                $ns4 = $es4->rowCount();
                if ($ns4 > 0) {
                    while($fs4 = $es4->fetch(PDO::FETCH_ASSOC)){
                        $codigo = $fs4['id_ticket'];

                        $s5 = "SELECT * FROM seleccion WHERE id_ticket = '$codigo'";
                        $es5 = $db->query($s5);
                        $full = 'true';
                        while($fs5 = $es5->fetch(PDO::FETCH_ASSOC)) {
                            $id_p = $fs5['id_select'];

                            $s6 = "SELECT * FROM participante WHERE id_participante = '$id_p'";
                            $es6 = $db->query($s6);

                            while($fs6 = $es6->fetch(PDO::FETCH_ASSOC)) {
                                if ($fs6['status'] == '1') { } 
                                elseif ($fs6['status'] == '2') { } 
                                elseif ($fs6['status'] == '3') { 
                                    $full = 'false';

                                    $s7 = "UPDATE 1_x_34PRLY SET estatus='3' WHERE cod_seguridad='$codigo'";
                                    $es7 = $db->prepare($s7);
                                    if ($es7->execute()) {}
                                } elseif ($fs6['status'] == '0') { $full = 'pendiente'; }
                            }                                            
                        }

                        if ($full == 'true') {
                            $s8 = "UPDATE 1_x_34PRLY SET estatus='1' WHERE cod_seguridad='$codigo'";
                            $es8 = $db->prepare($s8);
                            if ($es8->execute()) {
                                
                            }
                        }
                    }                                    
                }

                $result = array(
                    "status" => "correcto",
                    "full" => $s4
                );

                echo json_encode($result);
            }     

        } catch(PDOException $e){
            echo '{"error": {"text": '.$e.'}';
        }
    } else {
        $result = array(
            "status" => "error",
            "mensaje" => "Ya existente"
        );

        echo json_encode($result);
    }
    
});

$app->post('/resultados/agregar2', function(Request $request, Response $response){
   
    $id_partido = $request->getParam('id_carrera');
    $id_categoria = '27';
    $id_ta = '99';
    $resultado = $request->getParam('resultado');

    $db = new db();
    $db = $db->conectar();

    $c1 = "SELECT * FROM rl_mx_9483 WHERE id_partido = '$id_partido'";
    $e1 = $db->query($c1);
    $n1 = $e1->rowCount();

    if ($n1 == 0) {
        $consulta = "INSERT INTO rl_mx_9483 (id_partido, tipo_apuesta, id_categoria, resultado) VALUES ('".$id_partido."','".$id_ta."','".$id_categoria."','".$resultado."')";
        try{        

            $stmt = $db->prepare($consulta);

            if ($stmt->execute()){

                $res = explode('!', $resultado);

                $win = explode('#', $res[0]);
                $place = explode('#', $res[1]);
                $show = explode('#', $res[2]);

                if ($win[1] < 10) {
                    $cuota1 = 1.7;
                } elseif ($win[1] > 9.99 AND $win[1] < 11.11) {
                    $cuota1 = 2;
                } elseif ($win[1] > 11.10) {
                    $cuota1 = ($win[1] / 5) + 1.3;
                }

                $s1 = "SELECT id_inscripcion, id_carrera FROM inscripcion WHERE id_carrera = $id_partido ORDER BY id_inscripcion ASC";
                $es1 = $db->query($s1);

                while($fs1 = $es1->fetch(PDO::FETCH_ASSOC)) {

                    $id_inscripcion = $fs1['id_inscripcion'];

                    if ($id_inscripcion == $win[0]) {
                        $s3 = "UPDATE inscripcion SET status='1' WHERE id_inscripcion='$id_inscripcion'";
                        $es3 = $db->prepare($s3);
                        if ($es3->execute()) {
                                                            
                        }
                    } elseif ($id_inscripcion == $place[0]) {
                        $s3 = "UPDATE inscripcion SET status='2' WHERE id_inscripcion='$id_inscripcion'";
                        $es3 = $db->prepare($s3);
                        if ($es3->execute()) {
                                                            
                        }
                    } elseif ($id_inscripcion == $show[0]) {
                        $s3 = "UPDATE inscripcion SET status='3' WHERE id_inscripcion='$id_inscripcion'";
                        $es3 = $db->prepare($s3);
                        if ($es3->execute()) {
                                                            
                        }
                    } else {
                        $s3 = "UPDATE inscripcion SET status='99' WHERE id_inscripcion='$id_inscripcion'";
                        $es3 = $db->prepare($s3);
                        if ($es3->execute()) {
                                                            
                        }
                    }                    
                }   

                $s4 = "SELECT * FROM seleccion WHERE muestra = '$id_partido' AND id_ticket != '' AND id_deporte = '27'";
                $es4 = $db->query($s4);
                $ns4 = $es4->rowCount();
                if ($ns4 > 0) {
                    while($fs4 = $es4->fetch(PDO::FETCH_ASSOC)){
                        $codigo = $fs4['id_ticket'];

                        $s5 = "SELECT * FROM seleccion WHERE id_ticket = '$codigo'";
                        $es5 = $db->query($s5);
                        $ns5 = $es5->rowCount();

                        if ($ns5 > 0) {
                            $full = 'true';
                            while($fs5 = $es5->fetch(PDO::FETCH_ASSOC)) {
                                $selecciones[] = $fs5;
                                $id_p = $fs5['id_select'];
                                $ide = $fs5['id_seleccion'];

                                $s6 = "SELECT * FROM inscripcion WHERE id_inscripcion = '$id_p'";
                                $es6 = $db->query($s6);

                                while($fs6 = $es6->fetch(PDO::FETCH_ASSOC)) {

                                    if ($fs6['status'] == '1' && $fs5['tipo'] == 1) {

                                        $s9 = "UPDATE seleccion SET valor='$cuota1' WHERE id_seleccion='$ide'";
                                        $es9 = $db->prepare($s9);
                                        if ($es9->execute()) {
                                            
                                        }

                                    } elseif ($fs6['status'] == '2' && $fs5['tipo'] == 2) {
                                        
                                    } elseif ($fs6['status'] == '3' && $fs5['tipo'] == 3) {

                                        $full = 'true';
                                        
                                    } elseif ($fs6['status'] == '0') {
                                        $full = 'pendiente';
                                    } else {
                                        $full = 'false';

                                        $s7 = "UPDATE 1_x_34PRLY SET estatus='3' WHERE cod_seguridad='$codigo'";
                                        $es7 = $db->prepare($s7);
                                        if ($es7->execute()) {
                                            
                                        }
                                    } 
                                }                                            
                            }

                            if ($full == 'true') {
                                $s8 = "UPDATE 1_x_34PRLY SET estatus='1' WHERE cod_seguridad='$codigo'";
                                $es8 = $db->prepare($s8);
                                if ($es8->execute()) {
                                    
                                }
                            }
                        }                        
                    }                                    
                }          

                $result = array(
                    "status" => "correcto",
                    "selecciones" => $selecciones
                );

                echo json_encode($result);
            }     

        } catch(PDOException $e){
            echo '{"error": {"text": '.$e.'}';
        }
    } else {
        $result = array(
            "status" => "error",
            "mensaje" => "Ya existente"
        );

        echo json_encode($result);
    }
    
});