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

$app->post('/equipos/actualizar', function(Request $request, Response $response){
    $nombre = addslashes($request->getParam('nombre_equipo'));
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

$app->get('/ticket/ver/{id_usuario}/{estatus}', function(Request $request, Response $response){

    $id_usuario = $request->getAttribute('id_usuario');
    $estatus = $request->getAttribute('estatus');
    $ticketes = [];

    if ($estatus != 'todos') {
        if ($estatus == '1') {
            $criterio = "";
        } elseif ($estatus == '2') {
            $criterio = " AND estatus = '0' ";
        } elseif ($estatus = '3') {
            $criterio = " AND estatus > 0 ";
        }        
    } else {
        $criterio = "";
    }

    $decim_tot = 1;

    $cod_temp = '';

    $i = 0;
    $n = 0;
    $vuelta = 0;

    $db = new db();
    $db = $db->conectar();
    
    $s1 = "SELECT * FROM 1_x_34prly WHERE id_usuario = '$id_usuario'".$criterio." ORDER BY id_1_x_34prly DESC";
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
                        $c7 = "SELECT * FROM participante WHERE id_participante = '".$f6['id_select']."' ORDER BY id_participante ASC";
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

                            if ($f7['status'] != '2') {
                                $decim_tot = $decimal_odd * $decim_tot;     
                            } else {
                                $decim_tot = 1 * $decim_tot;    
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



                $monto = str_replace(",", ".", $monto);

                $a_ganar = $decim_tot * $monto;
              

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

    $d1 = "SELECT disponible, en_juego, id_usuario, email FROM usuario WHERE id_usuario = '$id_usuario'";
    $ed1 = $db->query($d1);
    $nd1 = $ed1->rowCount();

    if ($nd1 == 0) {

    } else {
        $apostador = $ed1->fetch(PDO::FETCH_ASSOC);

        $disponible = floatval($apostador['disponible']);

        if ($disponible >= $monto[$m]) {
            $s1 = "SELECT * FROM seleccion WHERE id_usuario = '$id_usuario' AND (id_ticket = '0' OR id_ticket = '')";
            $es1 = $db->query($s1);
            $ns1 = $es1->rowCount();

            if ($ns1 > 0) {
                while($fila = $es1->fetch(PDO::FETCH_ASSOC)) {
                    $cod_serial = substr(md5(rand()),0,10);
                    $selecciones[] = $fila;

                    if ($disponible >= $monto[$m]) {
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

                            $monto_a = floatval($monto[$m]);

                            $s2 = "UPDATE seleccion SET id_ticket='$cod_serial' WHERE id_usuario='$id_usuario' AND id_seleccion = '".$fila['id_seleccion']."'"; 
                            $es2 = $db->prepare($s2);
                            if ($es2->execute()) {
                                $s3 = "INSERT INTO 1_x_34prly (cod_seguridad, id_usuario, fecha_hora, monto, a_ganar, estatus) VALUES ('$cod_serial', '$id_usuario', '$fecha', '$monto_a', '0', '0')";
                                $es3 = $db->prepare($s3);
                                if ($es3->execute()) {
                                   $ticketes[$i]['id_usuario'] = $id_usuario;
                                   $ticketes[$i]['cod_seguridad'] = $cod_serial;
                                   $ticketes[$i]['fecha_hora'] = $fecha;
                                   $ticketes[$i]['monto'] = $monto_a;
                                   $ticketes[$i]['a_ganar'] = 'Según dividendo';
                                   $ticketes[$i]['id_seleccion'] = $fila['id_seleccion'];

                                   $nuevo_d = $disponible - $monto_a;                                   

                                    $cp2 = "UPDATE usuario SET disponible='$nuevo_d' WHERE id_usuario = '$id_usuario'";
                                    $ecp2 = $db->prepare($cp2);
                                    if ($ecp2->execute()) {
                                        $disponible = floatval($nuevo_d);
                                    }
                                }
                            }
                        }

                        $i++; $m++;
                    } else {
                        break;
                    }                    
                }

                $result = array(
                    "status" => "success",
                    "ticketes" => $ticketes,
                    "disponible" => $disponible,
                    "mstatus" => "Ticket generado correctamente"
                );

                echo json_encode($result);

            } 
        } else {
            $result = array(
                "status" => "error",
                "ticketes" => null,
                "mstatus" => "No tiene saldo suficiente para hacer esta apuesta"
            );

            echo json_encode($result);
        }

        
    }


       

    
  
});

$app->post('/ticket/agregard', function(Request $request, Response $response){
    $id_usuario = $request->getParam('id_usuario');
    $monto = floatval($request->getParam('montos'));
    $i = 0;
    $j = 0;   
    $decim_tot = 1;
    $cod_serial = substr(md5(rand()),0,10);
    $fecha = date("Y-m-d H:i:s");

    $db = new db();
    $db = $db->conectar();

    $d1 = "SELECT disponible, en_juego, id_usuario, email FROM usuario WHERE id_usuario = '$id_usuario'";
    $ed1 = $db->query($d1);
    $nd1 = $ed1->rowCount();

    if ($nd1 > 0) {

        $s1 = "SELECT * FROM seleccion WHERE id_usuario = '$id_usuario' AND (id_ticket = '0' OR id_ticket = '')";
        $es1 = $db->query($s1);
        $ns1 = $es1->rowCount();

        $ticketes[0] = []; 

        if ($ns1 > 0) {
            $apostador = $ed1->fetch(PDO::FETCH_ASSOC);

            $disponible = floatval($apostador['disponible']);

            if ($disponible >= $monto) {
                while($fila = $es1->fetch(PDO::FETCH_ASSOC)) {                  

                    $c7 = "SELECT a.id_partido, a.id_liga, a.fecha_inicio, b.nombre_liga, b.id_categoria, b.id_pais, c.descripcion  FROM p_futbol a INNER JOIN liga b on a.id_liga = b.id_liga INNER JOIN categoria c on b.id_categoria = c.id_categoria WHERE a.id_partido = '".$fila['muestra']."'";
                    $e7 = $db->query($c7);
                    $n7 = $e7->rowCount();

                    if ($n7 > 0) {
                        $filap = $e7->fetch(PDO::FETCH_ASSOC);
                        $ticketes[0]['selecciones'][$i] = $filap;
                        $ticketes[0]['selecciones'][$i]['dividendo'] = $fila['valor'];

                        $id_select = $fila["id_select"]; 

                        $c7 = "SELECT * FROM participante WHERE id_participante = '$id_select' ORDER BY id_participante ASC";
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

                   $nuevo_d = $disponible - $monto;                                   

                    $cp2 = "UPDATE usuario SET disponible='$nuevo_d' WHERE id_usuario = '$id_usuario'";
                    $ecp2 = $db->prepare($cp2);
                    if ($ecp2->execute()) {
                        $disponible = $nuevo_d;
                        $result = array(
                            "status" => "success",
                            "ticketes" => $ticketes,
                            "disponible" => $disponible,
                            "numero" => $c7,
                            "mstatus" => "Ticket generado correctamente"
                        );
                    }

                }
            } else {
                $result = array(
                    "status" => "error",
                    "ticketes" => null,
                    "mstatus" => "No tiene saldo suficiente para hacer esta apuesta"
                );
            }            
        }        
    }       

    echo json_encode($result);  
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

$app->get('/partidos/ver/live', function(Request $request, Response $response){

    $db = new db();
    $db = $db->conectar();

    $partidos = [];

    $i = 0;

    $c1 = "SELECT * FROM p_futbol WHERE status_lid = '1'";
    $e1 = $db->query($c1);
    $nc1 = $e1->rowCount();

    if ($nc1 > 0) {
        while($f1 = $e1->fetch(PDO::FETCH_ASSOC)) {
            $partidos[] = $f1;

     

            if ($f1['status_lid'] == '1') {
                $partidos[$i]['status_lid'] = true;
            } elseif ($f1['status_lid'] == '0') {
                $partidos[$i]['status_lid'] = false;
            }



            $ejecutar2 = $db->query("SELECT * FROM participante WHERE id_partido = '". $f1['id_partido'] . "' ORDER BY id_participante ASC");
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

        $result = array(
            "status" => "correcto",
            "partidos" => $partidos
        );        
    } else {
        $result = array(
            "status" => "error",
            "partidos" => $partidos
        );
    }

    

    $db = null;

    echo json_encode($result);
});



$app->get('/partidos/ver/{pagina}/{criterio}', function(Request $request, Response $response){
    $pagina = $request->getAttribute('pagina');
    $criterio = $request->getAttribute('criterio');
    $today = date("Y-m-d H:i:s");

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
                $criterios = " WHERE c.id_partido = '$crites[1]' ";
            } elseif ($criterio[0] == '-') {
                $crites = explode("-", $criterio);
                $criterios = " WHERE liga.id_liga = '$crites[1]' ";
            } else {
                $criterios = "WHERE (upper(e.nombre_equipo) LIKE upper('%" . $criterio . "%') OR upper(e.id_wihi_equipo) LIKE upper('%" . $criterio . "%') OR upper(e.acro) LIKE upper('%" . $criterio . "%')) ";
            }            
        } else {
            $criterios = "";
        }
        
        $consulta = "SELECT DISTINCT c.id_partido, c.url, b.nombre_liga, b.id_categoria, c.fecha_inicio, c.destacado, c.live_id, c.status_lid, c.disponibilidad FROM p_futbol c INNER JOIN liga b ON c.id_liga = b.id_liga INNER JOIN participante d on d.id_partido = c.id_partido INNER JOIN equipo e on e.id_equipo = d.id_equipo1 ".$criterios." ORDER BY id_partido DESC LIMIT ".$inicio.",".$TAMANO_PAGINA; 
        $ejecutar = $db->query($consulta);

        while($fila = $ejecutar->fetch(PDO::FETCH_ASSOC)) {
            $partidos[$i] = $fila;

            if ($fila['status_lid'] == '1') {
                $partidos[$i]['status_lid'] = true;
            } elseif ($fila['status_lid'] == '0') {
                $partidos[$i]['status_lid'] = false;
            }

            if ($fila['disponibilidad'] < $today) {
                $evento1 = "Activo";
            } else {
                $evento1 = "Cancelado";
            }         

            if ($fila['fecha_inicio'] > $today) {
                $evento2 = "Por iniciar";
            } else {
                $evento2 = "Iniciado";
            }

            $partidos[$i]['eventos'][0] = $evento1;
            $partidos[$i]['eventos'][1] = $evento2;

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

            $ejecutar2 = $db->query("SELECT * FROM participante WHERE id_partido = '". $fila['id_partido'] . "' ORDER BY id_participante ASC");
            $ligas = array();
            $j = 0;

            while ($fila2 = $ejecutar2->fetch(PDO::FETCH_ASSOC)) {
                $ejecutar3 = $db->query("SELECT * FROM equipo WHERE id_equipo = '". $fila2['id_equipo1'] . "'");
                $fila3 = $ejecutar3->fetch(PDO::FETCH_ASSOC);

                $nombre_fichero_eq = 'imagenes/equipos/'.$fila2['id_equipo1'].'.png';

                if (file_exists($nombre_fichero_eq)) {
                    $partidos[$i]['equipos'][$j]['img'] = $fila2['id_equipo1'].'.png';
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

        $consulta = "SELECT a.id_partido, a.id_liga, a.fecha_inicio, a.destacado, a.importancia, b.nombre_liga, b.id_categoria, c.descripcion  FROM p_futbol a INNER JOIN liga b on a.id_liga = b.id_liga INNER JOIN categoria c on b.id_categoria = c.id_categoria WHERE a.fecha_inicio >= '$fecha_compuesta' AND a.fecha_inicio < '$fecha_manana' AND a.destacado = '1' AND a.disponibilidad < '2099-01-01'  ORDER BY a.fecha_inicio ASC, b.nombre_liga";

        $ejecutar = $db->query($consulta);

        $i = 0;
        $id_temp = 0;

        while($fila = $ejecutar->fetch(PDO::FETCH_ASSOC)) {
            $partidos[] = $fila;

            $nombre_fichero = 'imagenes/partidos/'.$partidos[$i]['id_partido'].'.png';

            if (file_exists($nombre_fichero)) {
                $partidos[$i]['img'] = $partidos[$i]['id_partido'].'.png';
            } else {
                $partidos[$i]['img'] = null;
            } 

            $ejecutar2 = $db->query("SELECT * FROM participante WHERE id_partido = '". $fila['id_partido'] . "' ORDER BY id_participante ASC");
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

$app->post('/partidos/crear', function(Request $request, Response $response){
    $id_categoria = $request->getParam('id_categoria');
    $id_liga = $request->getParam('id_liga');
    $fecha_inicio = $request->getParam('fecha_inicio');
    $equipos = $request->getParam('equipos');
    $dividendos = $request->getParam('descripcion');
    $mensaje = '';
    $estatus = 'error';

    $db = new db();
    $db = $db->conectar();

    if ($id_categoria != '0') {

        $c0 = "SELECT * FROM tipo_apuesta WHERE id_categoria = '$id_categoria'";
        $ec0 = $db->query($c0);
        $p0 = $ec0->fetch(PDO::FETCH_ASSOC);
        $id_tipo_apuesta = $p0['id_ta'];

        if ($id_liga != '0') {
            if ($fecha_inicio != '') {
                if ($equipos[0] != '') {

                    $c1 = "SELECT * FROM equipo WHERE nombre_equipo = '$equipos[0]' OR id_wihi_equipo = '$equipos[0]'";
                    $ec1 = $db->query($c1);
                    $nc1 = $ec1->rowCount();

                    if ($nc1 == 0) {

                        $cp = "INSERT INTO equipo (nombre_equipo, id_wihi_equipo) VALUES ('$equipos[0]','$equipos[0]')";
                        if($ecp = $db->query($cp)){
                            $id_equipo1 = $db->lastInsertId();

                            $ccp1 = "INSERT INTO equipo_liga (id_equipo, id_liga) VALUES ('$id_equipo1', '$id_liga')";
                            if($eccp = $db->query($ccp1)){
                                                  
                            }                
                        }
                    } elseif ($nc1 > 0) {
                        $p1 = $ec1->fetch(PDO::FETCH_ASSOC);
                        $id_equipo1 = $p1['id_equipo'];

                        $cc1 = "SELECT * FROM equipo_liga WHERE id_equipo = '$id_equipo1' AND id_liga = '$id_liga'";
                        $ecc1 = $db->query($cc1);
                        $ncc1 = $ecc1->rowCount();

                        if ($ncc1 == 0) {
                            $ccp1 = "INSERT INTO equipo_liga (id_equipo, id_liga) VALUES ('$id_equipo1', '$id_liga')";
                            if($eccp = $db->query($ccp1)){
                                                  
                            }
                        }
                    }

                    if ($equipos[1] != '') {

                        $c1 = "SELECT * FROM equipo WHERE nombre_equipo = '$equipos[1]' OR id_wihi_equipo = '$equipos[1]'";
                        $ec1 = $db->query($c1);
                        $nc1 = $ec1->rowCount();

                        if ($nc1 == 0) {

                            $cp = "INSERT INTO equipo (nombre_equipo, id_wihi_equipo) VALUES ('$equipos[1]','$equipos[1]')";
                            if($ecp = $db->query($cp)){
                                $id_equipo2 = $db->lastInsertId();

                                $ccp1 = "INSERT INTO equipo_liga (id_equipo, id_liga) VALUES ('$id_equipo2', '$id_liga')";
                                if($eccp = $db->query($ccp1)){
                                                      
                                }                
                            }
                        } elseif ($nc1 > 0) {
                            $p1 = $ec1->fetch(PDO::FETCH_ASSOC);
                            $id_equipo2 = $p1['id_equipo'];

                            $cc1 = "SELECT * FROM equipo_liga WHERE id_equipo = '$id_equipo2' AND id_liga = '$id_liga'";
                            $ecc1 = $db->query($cc1);
                            $ncc1 = $ecc1->rowCount();

                            if ($ncc1 == 0) {
                                $ccp1 = "INSERT INTO equipo_liga (id_equipo, id_liga) VALUES ('$id_equipo2', '$id_liga')";
                                if($eccp = $db->query($ccp1)){
                                                      
                                }
                            }
                        }

                        if ($dividendos[0] != '') {
                            if ($dividendos[1] != '') {
                                if (isset($equipos[2])) {

                                    if (isset($dividendos[2])) {
                                        $c1 = "SELECT * FROM equipo WHERE nombre_equipo = '$equipos[2]' OR id_wihi_equipo = '$equipos[2]'";
                                        $ec1 = $db->query($c1);
                                        $nc1 = $ec1->rowCount();

                                        if ($nc1 == 0) {

                                            $cp = "INSERT INTO equipo (nombre_equipo, id_wihi_equipo) VALUES ('$equipos[2]','$equipos[2]')";
                                            if($ecp = $db->query($cp)){
                                                $id_equipo3 = $db->lastInsertId();

                                                $ccp1 = "INSERT INTO equipo_liga (id_equipo, id_liga) VALUES ('$id_equipo3', '$id_liga')";
                                                if($eccp = $db->query($ccp1)){
                                                                      
                                                }                
                                            }
                                        } elseif ($nc1 > 0) {
                                            $p1 = $ec1->fetch(PDO::FETCH_ASSOC);
                                            $id_equipo3 = $p1['id_equipo'];

                                            $cc1 = "SELECT * FROM equipo_liga WHERE id_equipo = '$id_equipo3' AND id_liga = '$id_liga'";
                                            $ecc1 = $db->query($cc1);
                                            $ncc1 = $ecc1->rowCount();

                                            if ($ncc1 == 0) {
                                                $ccp1 = "INSERT INTO equipo_liga (id_equipo, id_liga) VALUES ('$id_equipo3', '$id_liga')";
                                                if($eccp = $db->query($ccp1)){
                                                                      
                                                }
                                            }
                                        }
                                    } else {
                                        $mensaje = "Escriba un dividendo 3 válido";
                                    }
                                }

                                if ($mensaje == '') {
                                    $fecha = date("Y-m-d", strtotime($fecha_inicio));
                                    $hora = date("H:i:s", strtotime($fecha_inicio));

                                    $id_wihi_partido = $fecha.'!'.$id_equipo1.'.'.$id_equipo2.'!'.$hora;

                                    if ( isset($dividendos[2]) ){
                                        $id_wihi_partido = $fecha.'!'.$id_equipo1.'.'.$id_equipo3.'!'.$hora;
                                    } 

                                    $c99 = "SELECT * FROM p_futbol WHERE id_wihi_partido = '$id_wihi_partido'";
                                    $ec99 = $db->query($c99);
                                    $nc99 = $ec99->rowCount();

                                    if ($nc99 > 0) {
                                        $mensaje = "Este partido ya se encuentra registrado";
                                        $estatus = "existe";
                                    } else {
                                        $c100 = "INSERT INTO p_futbol (id_wihi_partido, id_liga, fecha_inicio, disponibilidad, url) VALUES ('$id_wihi_partido', '$id_liga', '$fecha_inicio', '2018-01-01', '')";
                                        if($ec100 = $db->query($c100)){
                                            $id_p_futbol = $db->lastInsertId();

                                            $id_wihi_part1 = $id_equipo1.'!'.$id_p_futbol.'!'.$id_tipo_apuesta;
                                            $id_wihi_part2 = $id_equipo2.'!'.$id_p_futbol.'!'.$id_tipo_apuesta;

                                             if (isset($dividendos[2])) {
                                                $id_wihi_part3 = $id_equipo3.'!'.$id_p_futbol.'!'.$id_tipo_apuesta;
                                            }

                                            $c10 = "INSERT INTO participante (id_wihi_participante, id_partido, id_equipo1, id_ta, dividendo, vinculo) VALUES ('$id_wihi_part1', '$id_p_futbol', '$id_equipo1', '$id_tipo_apuesta', '$dividendos[0]', '1')";

                                            if($ec10 = $db->query($c10)){
                                                $c11 = "INSERT INTO participante (id_wihi_participante, id_partido, id_equipo1, id_ta, dividendo, vinculo) VALUES ('$id_wihi_part2', '$id_p_futbol', '$id_equipo2', '$id_tipo_apuesta', '$dividendos[1]', '1')";

                                                if($ec11 = $db->query($c11)){

                                                    if (isset($dividendos[2])) {
                                                        $c12 = "INSERT INTO participante (id_wihi_participante, id_partido, id_equipo1, id_ta, dividendo, vinculo) VALUES ('$id_wihi_part3', '$id_p_futbol', '$id_equipo3', '$id_tipo_apuesta', '$dividendos[2]', '1')";
                                                        if($ec12 = $db->query($c12)){

                                                        }
                                                    }

                                                    $mensaje = "Se creó el partido correctamente";
                                                    $estatus = "correcto";
                                                }
                                            }
                                        }
                                    }

                                    
                                }                                 
                                
                            } else {
                                $mensaje = "Escriba un dividendo 2 válido";
                            }
                        } else {
                            $mensaje = "Escriba un dividendo 1 válido";
                        }
                    } else {
                        $mensaje = "Escriba un equipo 2 válido";
                    }
                } else {
                    $mensaje = "Escriba un equipo 1 válido";
                }
            } else {
                $mensaje = 'Escriba una fecha válida para el partido'; 
            }
        } else {
            $mensaje = 'Seleccione una liga válida';
        }
    } else {
        $mensaje = 'Seleccione un deporte válido';
    }

    
    
    $result = array(
        "status" => $estatus,
        "mensaje" => $mensaje,
        "id_categoria" => $id_categoria,
        "id_liga" => $id_liga,
        "fecha_inicio" => $fecha_inicio,
        "id_wihi_partido" => $id_wihi_partido,
        "equipos" => $equipos,
        "tipo_apuesta" => $id_tipo_apuesta,
        "dividendos" => $dividendos
    );

    echo json_encode($result);
        
});

$app->post('/partidos/actualizar', function(Request $request, Response $response){
    $destacado = $request->getParam('destacado');
    $importancia = $request->getParam('importancia');
    $id_partido = $request->getParam('id_partido');
    $status_lid = $request->getParam('status_lid');

    if ($status_lid == false) {
       $status_lid = 0;
    } elseif ($status_lid == true) {
        $status_lid = 1;
    }
    $live_id = $request->getParam('live_id');

    $consulta = "UPDATE p_futbol SET 
                        destacado = '".$destacado."',
                        importancia = '".$importancia."',
                        live_id = '".$live_id."',
                        status_lid = '".$status_lid."'
                        WHERE id_partido = ".$id_partido;
    try{
        $db = new db();
        $db = $db->conectar();

        $stmt = $db->prepare($consulta);
        $stmt->bindParam(':id_partido', $id_partido);
        if ($stmt->execute()){
            $result = array(
                "status" => "correcto",
                "consulta" => $consulta
            );

            echo json_encode($result);
        }     

    } catch(PDOException $e){
        echo '{"error": {"text": '.$consulta.'}';
    }
});

$app->post('/partidos/anular', function(Request $request, Response $response){
    $id_partido = $request->getParam('id_partido');

    $nueva_fecha = "2100-12-31 23:59";

    $consulta = "UPDATE p_futbol SET 
                        disponibilidad = '".$nueva_fecha."'
                        WHERE id_partido = ".$id_partido;
    try{
        $db = new db();
        $db = $db->conectar();

        $stmt = $db->prepare($consulta);
        $stmt->bindParam(':id_partido', $id_partido);
        if ($stmt->execute()){
            $cp2 = "UPDATE participante SET status = '2' WHERE id_partido = ".$id_partido;
            $ecp2 = $db->prepare($cp2);
            if ($ecp2->execute()) {
                $s4 = "SELECT * FROM seleccion WHERE muestra = '$id_partido' AND id_ticket != ''";
                $es4 = $db->query($s4);
                $ns4 = $es4->rowCount();
                if ($ns4 > 0) {
                    while($fs4 = $es4->fetch(PDO::FETCH_ASSOC)){
                        $codigo = $fs4['id_ticket'];
                        $disponible = 0.0001;
                        $acumulado = 1;

                        $s5 = "SELECT * FROM seleccion WHERE id_ticket = '$codigo'";
                        $es5 = $db->query($s5);
                        $full = 'true';
                        while($fs5 = $es5->fetch(PDO::FETCH_ASSOC)) {
                            $id_p = $fs5['id_select'];

                            $s6 = "SELECT * FROM participante WHERE id_participante = '$id_p' ORDER BY id_participante ASC";
                            $es6 = $db->query($s6);

                            $fs6 = $es6->fetch(PDO::FETCH_ASSOC);

                            $div_equipo_part1 = $fs6["dividendo"];

                            $div_div = explode("/", $div_equipo_part1);

                            if (!isset($div_div[1])) {
                                $div_div[1] = 1;
                            }                                       

                            $decimal_odd = (intval($div_div[0]) / intval($div_div[1])) + 1;

                            if ($fs6['status'] == '1') { $acumulado = $acumulado * $decimal_odd; } 
                            elseif ($fs6['status'] == '2') { } 
                            elseif ($fs6['status'] == '3') { 
                                $full = 'false';

                                $s7 = "UPDATE 1_x_34prly SET estatus='3' WHERE cod_seguridad='$codigo'";
                                $es7 = $db->prepare($s7);
                                if ($es7->execute()) {}
                            } elseif ($fs6['status'] == '0') { $full = 'pendiente'; }
                                                                        
                        }

                        if ($full == 'true') {
                            $s8 = "UPDATE 1_x_34prly SET estatus='1' WHERE cod_seguridad='$codigo'";
                            $es8 = $db->prepare($s8);
                            if ($es8->execute()) {
                                $ss8 = "SELECT id_usuario, cod_seguridad, monto FROM 1_x_34prly WHERE cod_seguridad = '$codigo'";
                                $ess8 = $db->query($ss8);
                                $fss8 = $ess8->fetch(PDO::FETCH_ASSOC);

                                $id_usuario =  $fss8['id_usuario'];
                                $monto_pagar = $fss8['monto'] * $acumulado;

                                $ss9 = "SELECT id_usuario, disponible FROM usuario WHERE id_usuario = '$id_usuario'";
                                $ess9 = $db->query($ss9);
                                $fss9 = $ess9->fetch(PDO::FETCH_ASSOC);

                                $saldo = $fss9['disponible'];

                                $nuevo_saldo =  $saldo + $monto_pagar;

                                $cp2 = "UPDATE usuario SET disponible='$nuevo_saldo' WHERE id_usuario = '$id_usuario'";
                                $ecp2 = $db->prepare($cp2);
                                if ($ecp2->execute()) {
                                    $disponible = floatval($nuevo_saldo);
                                }
                            }
                        }
                    }                                    
                }
            }
            $result = array(
                    "status" => "correcto",
                    "disponible" => $disponible,
                    "acumulado" => $acumulado
                );

            echo json_encode($result);
        }     

    } catch(PDOException $e){
        echo '{"error": {"text": '.$consulta.'}';
    }
});

$app->post('/partidos/agregarDatos', function(Request $request, Response $response){
    $pitchers = $request->getParam('pitchers');
    $partido = $request->getParam('partido'); 

    $url = addslashes($partido[0]['url']);
    $fecha_pdo = $partido[0]['fecha_inicio'];
    $id_partido = $partido[0]['id_partido'];

    $equipos = $partido[0]['equipos'];

    $db = new db();
    $db = $db->conectar();

    try{

        for ($i=0; $i < count($pitchers) ; $i++) { 

            if ($pitchers[$i] !== []) {
                $nombre = $pitchers[$i]['nombre'];
                $era = $pitchers[$i]['era'];

                $id_equipo = $equipos[$i]['id_equipo'];

                $id_participante = $equipos[$i]['id_participante'];

                if ($nombre != '') {
                     $c1 = "SELECT * FROM pitcher WHERE nombre = '$nombre' AND id_equipo = '$id_equipo'";
                    $ec1 = $db->query($c1);
                    $nc1 = $ec1->rowCount();

                    if ($nc1 == 0) {
                        $cp = "INSERT INTO pitcher (nombre, era, id_equipo) VALUES ('$nombre','$era','$id_equipo')";
                        if($ecp = $db->query($cp)){
                            $id_pitcher = $db->lastInsertId();                    
                        }
                    } elseif ($nc1 > 0) {
                        $p1 = $ec1->fetch(PDO::FETCH_ASSOC);

                        $id_pitcher = $p1['id_pitcher'];

                        $cp2 = "UPDATE pitcher SET era='$era' WHERE nombre='$nombre' AND id_equipo = '$id_equipo'";
                        $ecp2 = $db->prepare($cp2);
                        if ($ecp2->execute()) {

                        }
                    }

                    $cp31 = "UPDATE participante SET dato='$id_pitcher' WHERE id_participante='$id_participante'";
                    $ecp31 = $db->prepare($cp31);
                    if ($ecp31->execute()) {
                        $sta_p1 = "success";
                    }
                } else {
                    $sta_p1 = "success";
                }      
            }                 
        }

        $s3 = "UPDATE p_futbol SET url='$url', fecha_inicio = '$fecha_pdo' WHERE id_partido='$id_partido'";
        $es3 = $db->prepare($s3);
        if ($es3->execute()) {

            for ($i=0; $i < count($equipos); $i++) { 
                $id_participante = $equipos[$i]['id_participante'];
                $dividendo = $equipos[$i]['dividendo'];

                if ($id_participante != '' AND $dividendo != '') {
                    $s33 = "UPDATE participante SET dividendo='$dividendo' WHERE id_participante='$id_participante'";
                    $es33 = $db->prepare($s33);
                    if ($es33->execute()) {
                        $sta_p1 = "success";
                    }
                }
            }

            if ($sta_p1 == "success") {
                $result = array(
                    "status" => $sta_p1
                );
            } else {
                $result = array(
                    "status" => 'error'
                );
            }                           
        }       

        $db = null;

        echo json_encode($result);                   

    } catch(PDOException $e){
        echo $e;
    }
});

$app->get('/partidos/categoria/{id_categoria}', function(Request $request, Response $response){
    $id_categoria = $request->getAttribute('id_categoria');
    $fecha_manana = "";

    $estatus = 'correcto';

    if (isset($_GET["fecha"])) {
        $dato_fecha = $_GET["fecha"];
    } else {
        $dato_fecha = "";
    }

    if (isset($_GET["busqueda"])) {
        $criterio = $_GET["busqueda"];
    } else {
        $criterio = "";
    }
    
    try{
        $db = new db();
        $db = $db->conectar();

        $fecha_for_1 = date("Y-m-d H:i:s");

		$fecha_manana = date_create($fecha_for_1);
		date_add($fecha_manana, date_interval_create_from_date_string('1 days'));
        $fecha_manana_c = date_format($fecha_manana, 'd-m-Y');
		$fecha_de_manana = date_format($fecha_manana, 'Y-m-d H:i:s');		

		$fecha_for_2 = date("H:i:s");



        $ligatemp = '';   

            if ($criterio != '') {
                $criterios = "WHERE a.fecha_inicio >= '$fecha_for_1' AND (upper(e.nombre_equipo) LIKE upper('%" . $criterio . "%') OR upper(e.id_wihi_equipo) LIKE upper('%" . $criterio . "%') OR upper(e.acro) LIKE upper('%" . $criterio . "%')) ";
            } else {
                $criterios = "WHERE c.id_categoria = '$id_categoria' AND a.fecha_inicio >= '$fecha_for_1' AND a.fecha_inicio < '$fecha_de_manana' ";
            }

        if ($dato_fecha == "hoy") {
               $fecha_manana = date_format($fecha_manana, 'Y-m-d');
           }   else {
                $fecha_manana = date_format($fecha_manana, 'Y-m-d H:i:s');
           }

        $consulta = "SELECT DISTINCT a.id_partido, a.id_liga, a.disponibilidad, a.fecha_inicio, b.nombre_liga, b.id_categoria, b.id_pais, c.descripcion FROM p_futbol a INNER JOIN liga b on a.id_liga = b.id_liga INNER JOIN categoria c on b.id_categoria = c.id_categoria INNER JOIN participante d on d.id_partido = a.id_partido INNER JOIN equipo e on e.id_equipo = d.id_equipo1 ".$criterios." AND a.disponibilidad < '2099-01-01' ORDER BY b.importancia DESC, b.nombre_liga, a.fecha_inicio ASC, d.id_participante ASC";

        $ejecutar = $db->query($consulta);

        $i = 0;
        $id_temp = 0;

        $revision = '0';

        while($fila = $ejecutar->fetch(PDO::FETCH_ASSOC)) {
            $partidos[] = $fila;  

            $ejecutar2 = $db->query("SELECT * FROM participante WHERE id_partido = '". $fila['id_partido'] . "' ORDER BY id_participante ASC");
            $registros = $ejecutar2->rowCount(); 

            if ($registros > 0) {

                if ($fila['nombre_liga'] == $ligatemp) {
                    $partidos[$i]['nombre_liga'] = null;
                } else {
                    $ligatemp = $fila['nombre_liga'];
                    $revision = '0';
                }

                $newDia_partido = date("d-m-Y", strtotime($fila['fecha_inicio']));

                if ($newDia_partido == $fecha_manana_c AND $revision == '0') {
                    $partidos[$i]['manana'] = true;
                    $revision = '1';
                }

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

                    $ejecutar3 = $db->query("SELECT * FROM pitcher WHERE id_pitcher = '". $fila2['dato'] . "'");
                    $fila44 = $ejecutar3->fetch(PDO::FETCH_ASSOC);

                    $partidos[$i]['equipos'][$j]['pitcher'] = $fila44;

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
            } else {
                
            }           

            $i++;
        }  
        
        if ( $i == '0' ) {
            $partidos[] = "";
            $estatus = 'incompleto';
        }
        
        $result = array(
            "status" => $estatus,
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
    $tipo =  $request->getParam('tipoken');
    $menu = [];
    $continua = 0;

	$db = new db();
    $db = $db->conectar();

    if ($tipo == 'token') {
        $consulta = "SELECT * FROM usuario WHERE usuario = '$usuario'";
        $ejecutar = $db->query($consulta);
        $registros = $ejecutar->rowCount(); 

        $token = explode("###", $pass);

        if ($registros > 0){
            $fila = $ejecutar->fetch(PDO::FETCH_ASSOC);
            
            $pass = md5($fila['password'].$fila['usuario']);

            if ($token[0] == $pass) {
                $continua = 1;
            }
        } else {

        }


    } else {
        $consulta = "SELECT * FROM usuario WHERE usuario = '$usuario' AND password = '$pass'";
        $ejecutar = $db->query($consulta);
        $registros = $ejecutar->rowCount();

        if ($registros > 0){
            $fila = $ejecutar->fetch(PDO::FETCH_ASSOC);
            $pass = md5($fila['password'].$fila['usuario']);
            $continua = 1;
        } else {

        }
    }    
       
        if ($continua > 0){            

            $usuario = $fila;

            $time = strtotime("now");

            $usuario['password'] = ':D';
            $usuario['token'] = $pass."###".$time;

            $token = $usuario['token'];

            if ($fila['id_rol'] == '1') {
                $o = 0;
                $menu[$o] = array(
                    'titulo' => 'Usuarios',
                    'icono' => 'fas fa-users-cog',
                    'data' => 'Ir a Usuarios',
       
                    'link' => 'usuarios'
                ); $o++;
                $menu[$o] = array(
                    'titulo' => 'Depósitos',
                    'icono' => 'fas fa-funnel-dollar',
                    'data' => 'Ir a Depósitos',
                    'link' => 'adm-depositos'
                ); $o++;
                $menu[$o] = array(
                    'titulo' => 'Resultados',
                    'icono' => 'fas fa-flag-checkered',
                    'data' => 'Ir a Resultados',
                    'link' => 'resultados'
                ); $o++;
                $menu[$o] = array(
                    'titulo' => 'Caballos',
                    'icono' => 'fas fa-chess-knight',
                    'data' => 'Ir a Caballos',
                    'link' => 'caballos'
                ); $o++;
                $menu[$o] = array(
                    'titulo' => 'Partidos',
                    'icono' => 'fab fa-patreon',
                    'data' => 'Ir a Partidos',
                    'link' => 'partidos'
                ); $o++;
                $menu[$o] = array(
                    'titulo' => 'Actualizaciones',
                    'icono' => 'fas fa-redo',
                    'data' => 'Ir a Actualizaciones',
                    'link' => 'actualizaciones'
                ); $o++;
                $menu[$o] = array(
                    'titulo' => 'TipoApuestas',
                    'icono' => 'fas fa-list-ul',
                    'data' => 'Ir a Tipos de Apuesta',
                    'link' => 'tipoApuestas'
                ); $o++;
                $menu[$o] = array(
                    'titulo' => 'Nacionalidades',
                    'icono' => 'far fa-flag',
                    'data' => 'Ir a Nacionalidades',
                    'link' => 'nacionalidades'
                ); $o++;
                $menu[$o] = array(
                    'titulo' => 'Ligas',
                    'icono' => 'fas fa-trophy',
                    'data' => 'Ir a Ligas',
                    'link' => 'ligas'
                ); $o++;
                $menu[$o] = array(
                    'titulo' => 'Equipos',
                    'icono' => 'fab fa-first-order',
                    'data' => 'Ir a Equipos',
                    'link' => 'equipos'
                ); $o++;
                $menu[$o] = array(
                    'titulo' => 'Mensajes',
                    'icono' => 'fa fa-mail-bulk',
                    'data' => 'Ir a Mensajes',
                    'link' => 'mensajes/'.$token
                ); $o++;

                $menu[$o] = array(
                    'titulo' => 'Promociones',
                    'icono' => 'fa fa-gift',
                    'data' => 'Ir a Promociones',
                    'link' => 'promociones'
                ); $o++;

                $menu[$o] = array(
                    'titulo' => 'Noticias',
                    'icono' => 'fa fa-newspaper',
                    'data' => 'Ir a Noticias',
                    'link' => 'noticias'
                ); $o++;

                $menu[$o] = array(
                    'titulo' => 'Bancos',
                    'icono' => 'fa fa-university',
                    'data' => 'Ir a Bancos',
                    'link' => 'bancos'
                ); $o++;

                $menu[$o] = array(
                    'titulo' => 'Estadísticas',
                    'icono' => 'fa fa-percent',
                    'data' => 'Ir a Estadísticas',
                    'link' => 'estadisticas/'.$token
                ); $o++;

                $menu[$o] = array(
                    'titulo' => 'Versiones',
                    'icono' => 'fas fa-laptop-code',
                    'data' => 'Ir a Versiones',
                    'link' => 'changelog'
                ); $o++;

            } elseif ($fila['id_rol'] == '2') {                
                $menu[0] = array(
                    'titulo' => 'Mi Cuenta',
                    'icono' => 'fa fa-universal-access',
                    'data' => 'Ir a Mi Cuenta',
                    'link' => 'miCuenta'
                );

                $menu[1] = array(
                    'titulo' => 'Historial',
                    'icono' => 'fa fa-history',
                    'data' => 'Ir a Historial',
                    'link' => 'historial/'.$token
                );

                $menu[2] = array(
                    'titulo' => 'Mensajes',
                    'icono' => 'fa fa-mail-bulk',
                    'data' => 'Ir a Mensajes',
                    'link' => 'mensajes/'.$token
                );

                $menu[3] = array(
                    'titulo' => 'Promociones',
                    'icono' => 'fa fa-gift',
                    'data' => 'Ir a Promociones',
                    'link' => 'promociones'
                );

                $menu[4] = array(
                    'titulo' => 'Noticias',
                    'icono' => 'fa fa-newspaper',
                    'data' => 'Ir a Noticias',
                    'link' => 'noticias'
                );

                $menu[5] = array(
                    'titulo' => 'Bancos',
                    'icono' => 'fa fa-university',
                    'data' => 'Ir a Bancos',
                    'link' => 'bancos'
                );

                $menu[6] = array(
                    'titulo' => 'Estadísticas',
                    'icono' => 'fa fa-percent',
                    'data' => 'Ir a Estadísticas',
                    'link' => 'estadisticas/'.$token
                );
                 $menu[7] = array(
                    'titulo' => 'Resultados',
                    'icono' => 'fas fa-flag-checkered',
                    'data' => 'Ir a Resultados',
                    'link' => 'verResultados'
                );
            }

            $result = array(
                "status" => "correcto",
                "usuario" => $usuario,
                "menu" => $menu
            );


            
        } else {
            	$result = array(
                "status" => "incorrecto",
                "usuario" => $usuario,
                "tipo" => $tipo,
                "token" => $token
            );

            
            }

echo json_encode($result);
    
});

// Agregar Cliente
$app->post('/usuarios/crear', function(Request $request, Response $response){
    $db = new db();
    $db = $db->conectar();

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
    $fecha = date("Y-m-d H:i:s");

    $ipaddress = '';

    if (getenv('HTTP_CLIENT_IP')) {
        $ipaddress = getenv('HTTP_CLIENT_IP');
    }
    else if(getenv('HTTP_X_FORWARDED_FOR')) {
        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    }
    else if(getenv('HTTP_X_FORWARDED')) {
        $ipaddress = getenv('HTTP_X_FORWARDED');
    }
    else if(getenv('HTTP_FORWARDED_FOR')) {
        $ipaddress = getenv('HTTP_FORWARDED_FOR');
    }
    else if(getenv('HTTP_FORWARDED')) {
       $ipaddress = getenv('HTTP_FORWARDED');
    }
    else if(getenv('REMOTE_ADDR')) {
        $ipaddress = getenv('REMOTE_ADDR');
    }
    else {
        $ipaddress = 'UNKNOWN';
    }

    function ip_info($ip = NULL, $purpose = "location", $deep_detect = TRUE) {
        $output = NULL;
        if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
            $ip = $_SERVER["REMOTE_ADDR"];
            if ($deep_detect) {
                if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP))
                    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
                if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP))
                    $ip = $_SERVER['HTTP_CLIENT_IP'];
            }
        }
        $purpose    = str_replace(array("name", "\n", "\t", " ", "-", "_"), NULL, strtolower(trim($purpose)));
        $support    = array("country", "countrycode", "state", "region", "city", "location", "address");
        $continents = array(
            "AF" => "Africa",
            "AN" => "Antarctica",
            "AS" => "Asia",
            "EU" => "Europe",
            "OC" => "Australia (Oceania)",
            "NA" => "North America",
            "SA" => "South America"
        );
        
        if (filter_var($ip, FILTER_VALIDATE_IP) && in_array($purpose, $support)) {
            $ipdat = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=" . $ip));
            if (@strlen(trim($ipdat->geoplugin_countryCode)) == 2) {
                switch ($purpose) {
                    case "location":
                        $output = array(
                            "city"           => @$ipdat->geoplugin_city,
                            "state"          => @$ipdat->geoplugin_regionName,
                            "country"        => @$ipdat->geoplugin_countryName,
                            "country_code"   => @$ipdat->geoplugin_countryCode,
                            "continent"      => @$continents[strtoupper($ipdat->geoplugin_continentCode)],
                            "continent_code" => @$ipdat->geoplugin_continentCode
                        );
                        break;
                    case "address":
                        $address = array($ipdat->geoplugin_countryName);
                        if (@strlen($ipdat->geoplugin_regionName) >= 1)
                            $address[] = $ipdat->geoplugin_regionName;
                        if (@strlen($ipdat->geoplugin_city) >= 1)
                            $address[] = $ipdat->geoplugin_city;
                        $output = implode(", ", array_reverse($address));
                        break;
                    case "city":
                        $output = @$ipdat->geoplugin_city;
                        break;
                    case "state":
                        $output = @$ipdat->geoplugin_regionName;
                        break;
                    case "region":
                        $output = @$ipdat->geoplugin_regionName;
                        break;
                    case "country":
                        $output = @$ipdat->geoplugin_countryName;
                        break;
                    case "countrycode":
                        $output = @$ipdat->geoplugin_countryCode;
                        break;
                }
            }
        }
        return $output;
    }

    // $location = ip_info($ipaddress, "Country");

    $v1 = "SELECT * FROM usuario WHERE usuario = '$usuario'";
    $ev1 = $db->query($v1);
    $nv1 = $ev1->rowCount();

    if ($nv1 > 0) {
        $encab = "Error durante el registro";
        $status = "warning";
        $mstatus = "Ya existe este nombre de usuario en nuestra base de clientes";
    } else {
        $consulta = "INSERT INTO usuario (nacimento, usuario, password, numerico, disponible, en_juego, puntos, id_rol, id_estatus, nombres, apellidos, cedula, telefono, email, id_pais, tratamiento, creacion, ip) VALUES ('$nacimiento','$usuario','$password','$numerico', '200', '$en_juego','5','$id_rol','$id_estatus','$nombres','$apellidos','$cedula','$telefono','$email','$id_pais','$tratamiento', '$fecha', '$ipaddress')";

        $stmt = $db->prepare($consulta);
        $stmt->bindParam(':usuario', $usuario);
        if ($stmt->execute()){

            $encab = "Usuario creado correctamente";
            $status = "success";
            $mstatus = "Tiene un regalo de Bss. 200,00 para que disfrute de la pasión de las apuestas.";

            $cod_serial = substr(md5(rand()),0,10);

            $id_usuario = $db->lastInsertId();

            $linke = "http://betzone.com.ve/#/activacion/".$cod_serial;
            $linke_o = $cod_serial;

            $para      = $email;
            $titulo    = 'Activación de Cuenta BetZone';
            $preliminar = 'Estimado(a): '.$nombres.' '.$apellidos.', gracias por seleccionarnos como su página de apuestas. Queremos que tenga una grata bienvenida, es por ello que hemos agregado a tu balance de cuenta Bss. 200,00 totalmente gratis para que puedas apostar como gustes.';
            $mensaje_msj = '<br>Estimado(a): '.$nombres.' '.$apellidos.', gracias por seleccionarnos como su página de apuestas.<br><br>Queremos que tenga una grata bienvenida, es por ello que hemos agregado a tu balance de cuenta Bss. 200,00 totalmente gratis para que puedas apostar como gustes.<br><br>También le recordamos que puede realizar depósitos bancarios en nuestras cuentas para disfrutar de más beneficios apostando y ganando.<br><br><span><b>¡Disfrute de la acción!</b></span><br><span><b class="bolde">El Equipo de BetZone</b></span><br><br>Si necesita ayuda, puede contactarse con nosotros a través de una sesión de chat o por nuestro correo electrónico. Es posible que se le pida el número de seguridad de 4 dígitos.';
            $titulo_msj = "Bienvenido a BetZone.com.ve";
            $mensaje = '
                <div style="border: 1px #CCCCCC solid; width: 550px; overflow: hidden; color:black; margin: 0 auto;">
                <div style="background: #14805E;">
                <div style="width: 300px; display: inline-block; vertical-align: middle; text-align: center;"><img src="http://i65.tinypic.com/qmytmt.png" height="70px"></div>
                <div style="display: inline-block; vertical-align: middle; text-align: center;"><a href="betzone.com.ve"><button style="width: 200px; margin: 0 auto; height: 37px;">Ir a la página</button></a></div>
                </div>
                <div style="background: white; border-top: #CCCCCC 1px solid; border-bottom: #CCCCCC 1px solid; height: 25px; text-align: center; padding-top: 6px;">Activación de cuenta nueva en BetZone</div>
                <div style="padding: 10px;">
                <p>Estimado(a):<b>'.$nombres.' '.$apellidos.'</b></p>
                <p>Gracias por preferirnos como su página de apuestas. Bienvenido a BetZone.com.ve; Para activar tu usuario en el portal, ingresa al siguiente enlace: <a href="'.$linke.'">'.$linke.'</a></p>
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

            $cod_serial = substr(md5(rand()),0,10);

            $host= $_SERVER["HTTP_HOST"];

            if ($host == 'localhost') {

                $em = "INSERT INTO mensajes (titulo, para, texto, preliminar, cabeceras, desde, fecha_hora, id_usuario, serial, estatus) VALUES ('$titulo_msj','$para','$mensaje_msj','$preliminar','$cabeceras', 'Captación BetZone', '$fecha', '$id_usuario','$cod_serial','0')";
                if($ecm = $db->query($em)){
                   
                }
                
            } elseif ($host == 'betzone.com.ve') {
                if (mail($para, $titulo, $mensaje, $cabeceras)) {                       
                    $c5 = "UPDATE usuario SET urlAct='$linke_o' WHERE id_usuario='$id_usuario'";
                    $ec5 = $db->prepare($c5);
                    if ($ec5->execute()) {
                        $em = "INSERT INTO mensajes (titulo, para, texto, preliminar, cabeceras, desde, fecha_hora, id_usuario, serial, estatus) VALUES ('$titulo_msj','$para','$mensaje_msj','$preliminar','$cabeceras', 'Captación BetZone', '$fecha', '$id_usuario','$cod_serial','0')";
                        if($ecm = $db->query($em)){
                           
                        }
                    }
                }
            }
        } 
    }       

    $result = array(
        "status" => $status,
        "mstatus" => $mstatus,
        "ip" => $ipaddress,
        "titulo" => $encab
    );

    echo json_encode($result); 

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

        $actualizacion = date("Y-m-d H:i:s");
        
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

            if ($caballos[$i]['nacimiento'] != '0000-00-00') {
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
            } else {
                $caballos[$i]['edad'] = "0";
            }

            

            $i++;
        } 
        
        $result = array(
            "status" => "correcto",
            "actualizacion" => $actualizacion,
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
            $padre_a = array('codigo' => $codigo_final_pad, 'nombre' => $n_padre );
		}

    } elseif ($n1 > 0) {
		$r1 = $e1->fetch(PDO::FETCH_ASSOC);
        $padre_a = $r1;
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
            $madre_a = array('codigo' => $codigo_final_pad, 'nombre' => $n_madre );
		}

    } elseif ($n1 > 0) {
		$r1 = $e1->fetch(PDO::FETCH_ASSOC);
        $madre_a = $r1;
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
            $haras_a = array('descripcion' => $n_haras);
		}
	} elseif ($n3 > 0) {
		$r3 = $e3->fetch(PDO::FETCH_ASSOC);
        $haras_a = $r3;
		$id_haras = $r3["id_haras"];
	}

	$c0 =  "SELECT * FROM caballo WHERE nombre = '$nombre'";
	$e0 = $db->query($c0);
    $n0 = $e0->rowCount();


    if ($n0 == 0) {
       	$consulta = "INSERT INTO caballo (codigo, nombre, sexo, tipo_caballo, padre, madre, nacimiento, id_haras) VALUES ('$codigo_final_cab','$nombre','$sexo','3','$id_padre','$id_madre','$nacimiento','$id_haras')";
         

        $stmt = $db->prepare($consulta);

        if ($stmt->execute()){

            if( $sexo == "1") {
                $sexo_f = "Caballo";
            } elseif ($sexo == "2") {
                $sexo_f = "Yegua";
            }

            $id_caballo_a = $db->lastInsertId(); 

            $caballo_array = array(
                'id_caballo' => $id_caballo_a,
                'codigo' => $codigo_final_cab,
                'nombre' => $nombre,
                'sexo' => $sexo_f,
                'tipo_caballo' => '3',
                'padre' => $padre_a,
                'madre' => $madre_a,
                'nacimiento' => $nacimiento,
                'id_haras' => $haras_a
            );

            $result = array(
                "status" => "correcto",
                "caballo" => $caballo_array
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

$app->post('/caballos/actualizar', function(Request $request, Response $response){


    $nombre = $request->getParam('nombre');
    $id_caballo = $request->getParam('id_caballo');
    $nacimiento = $request->getParam('nacimiento');

    $haras =  $request->getParam('id_haras');
    if ($haras != '0') {
        $id_haras = $haras['id_haras'];
    } else {
        $id_haras = '0';
    }
    

    $padre = $request->getParam('padre');
    if ($padre != '0') {
        $id_padre = $padre['id_caballo'];
    } else {
        $id_padre = '0';
    }
    

    $madre = $request->getParam('madre');
    if ($madre != '0') {
        $id_madre = $madre['id_caballo'];
    } else {
        $id_madre = '0';
    }
   

    $consulta = "UPDATE caballo SET 
                        nombre = '".$nombre."',
                        id_haras = '".$id_haras."',
                        padre = '".$id_padre."',
                        madre = '".$id_madre."',
                        nacimiento = '".$nacimiento."'
                        WHERE id_caballo = ".$id_caballo;
    try{
        $db = new db();
        $db = $db->conectar();

        $stmt = $db->prepare($consulta);
        $stmt->bindParam(':nombre', $nombre);
        if ($stmt->execute()){



            $datetime1 = date_create($nacimiento);
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

            $edad = $descripcion." ".$descripcion2;

            $result = array(
                "edad" => $edad,
                "status" => "correcto",
                "mensaje" => "Ejemplar $nombre actualizado correctamente"
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

        $actualizacion = date("Y-m-d H:i:s");
        
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
            "jinetes" => $jinetes,
            "actualizacion" => $actualizacion
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
                $jinete_a = array(
                    'id_jinete' => $db->lastInsertId(),
                    'nombre' => $nombre,
                    'estatura' => $estatura,
                    'peso' => $peso,
                    'nacionalidad' => $nacionalidad
                 );
	            $result = array(
	                "status" => "correcto",
                    "jinete" => $jinete_a
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

        $actualizacion = date("Y-m-d H:i:s");
        
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
            "actualizacion" => $actualizacion,
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
                $entrenador_a = array(
                    'id_entrenador' => $db->lastInsertId(),
                    'nombre' => $nombre, 
                    'nacionalidad' => $nacionalidad
                );
	            $result = array(
	                "status" => "correcto",
                    "entrenador" => $entrenador_a
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

        $actualizacion = date("Y-m-d H:i:s");
        
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
            "actualizacion" => $actualizacion,
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
                $haras_a = array(
                    'id_haras' => $db->lastInsertId(),
                    'descripcion' => $descripcion,
                    'ubicacion' => $ubicacion 
                );
                $result = array(
                    "status" => "correcto",
                    "haras" => $haras_a
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

        $actualizacion = date("Y-m-d H:i:s");
        
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
            "actualizacion" => $actualizacion,
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
                $stud_a = array(
                    'id_stud' => $db->lastInsertId(),
                    'descripcion' => $descripcion,
                    'ubicacion' => $ubicacion
                 );
                $result = array(
                    "status" => "correcto",
                    "stud" => $stud_a
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

$app->post('/studs/actualizar', function(Request $request, Response $response){
    $descripcion = $request->getParam('descripcion');
    $id_stud = $request->getParam('id_stud');

    $consulta = "UPDATE stud SET 
                        descripcion = '".$descripcion."'
                        WHERE id_stud = ".$id_stud;
    try{
        $db = new db();
        $db = $db->conectar();

        $stmt = $db->prepare($consulta);
        $stmt->bindParam(':descripcion', $descripcion);
        if ($stmt->execute()){
            $result = array(
                "status" => "correcto",
                "mensaje" => "Stud $descripcion actualizado correctamente"
            );

            echo json_encode($result);
        }     

    } catch(PDOException $e){
        echo '{"error": {"text": '.$consulta.'}';
    }
});

$app->get('/hipodromos/ver/todos', function(Request $request, Response $response){
    try{
        $db = new db();
        $i = 0;
        $db = $db->conectar();

        $actualizacion = date("Y-m-d H:i:s");
        
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
            "actualizacion" => $actualizacion,
            "hipodromos" => $hipodromos
        );

        $db = null;

        echo json_encode($result);

    } catch(PDOException $e){
        echo '{"error": {"text": '.$e->getMessage().'}';
    }
});

$app->post('/hipodromos/crear', function(Request $request, Response $response){
    $descripcion = addslashes($request->getParam('descripcion'));
    $ubicacion = $request->getParam('ubicacion');
    $acro = $request->getParam('acro');

    $db = new db();
    $db = $db->conectar();

    $c1 = "SELECT * FROM hipodromo WHERE descripcion = '$descripcion'";
    $e1 = $db->query($c1);
    $n1 = $e1->rowCount();

    if ($n1 == 0) {
        $consulta = "INSERT INTO hipodromo (descripcion, ubicacion, acro) VALUES ('".$descripcion."','".$ubicacion."','".$acro."')";
        try{        

            $stmt = $db->prepare($consulta);
            $stmt->bindParam(':descripcion', $descripcion);
            if ($stmt->execute()){
                $hipo_a = array(
                    'id_hipodromo' => $db->lastInsertId(),
                    'descripcion' => $descripcion,
                    'ubicacion' => $ubicacion,
                    'acro' => $acro
                 );
                $result = array(
                    "status" => "correcto",
                    "hipodromo" => $hipo_a
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

$app->get('/carreras/ver/{id}', function(Request $request, Response $response){
    try{
        $id = $request->getAttribute('id');
        $db = new db();
        $i = 0;
        $db = $db->conectar();
        $indice = '';
        $indice2 = '';

        $carreras = [];

        $fecha_for_1 = date("Y-m-d H:i:s");

        if ($id != 'todas') {
            $criterio = " AND id_carrera = '".$id."' ";
        } else {
            $criterio = "";
        }
        
        $consulta = "SELECT * FROM carrera WHERE fecha_hora >= '$fecha_for_1' ".$criterio." ORDER BY id_hipodromo, fecha_hora ASC";
        $ejecutar = $db->query($consulta);

        while($fila = $ejecutar->fetch(PDO::FETCH_ASSOC)) {
            $carreras[] = $fila;

            $fecha_carrera = date("d-m-Y", strtotime($fila['fecha_hora']));

            $dia = date('l', strtotime($fila['fecha_hora']));

            if ($dia == 'Monday') { $dia = "Lunes"; }
            if ($dia == 'Tuesday') { $dia = "Martes"; }
            if ($dia == 'Wednesday') { $dia = "Miércoles"; }
            if ($dia == 'Thursday') { $dia = "Jueves"; }
            if ($dia == 'Friday') { $dia = "Viernes"; }
            if ($dia == 'Saturday') { $dia = "Sábado"; }
            if ($dia == 'Sunday') { $dia = "Domingo"; }

            $carreras[$i]['dia'] = $dia;
            

            $c1 = "SELECT * FROM inscripcion WHERE id_carrera = '".$fila['id_carrera']."' ORDER BY puesto";
            $e1 = $db->query($c1);
            $n1 = $e1->rowCount();

            if ($n1 == 0) {
                $carreras[$i]['inscritos'] = null;
                $carreras[$i]['inscripcion'] = "0 inscritos";
            } else {
                if ($n1 == 1) {
                    $carreras[$i]['inscripcion'] = "$n1 inscrito";
                } else {
                    $carreras[$i]['inscripcion'] = "$n1 ejemplares inscritos";
                }
                
                $inscritos = array();
                $j = 0;

                while ($fila2 = $e1->fetch(PDO::FETCH_ASSOC)) {                    

                    $carreras[$i]['inscritos'][$j] = $fila2;

                    $c10 = "SELECT * FROM caballo WHERE id_caballo = '".$fila2['id_caballo']."'";
                    $e10 = $db->query($c10);

                    while($f10 = $e10->fetch(PDO::FETCH_ASSOC)) {
                        $carreras[$i]['inscritos'][$j]['id_caballo'] = $f10;

                        if ($f10['nacimiento'] != '0000-00-00') {
                            $datetime1 = date_create($f10['nacimiento']);
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

                            $carreras[$i]['inscritos'][$j]['id_caballo']['edad'] = $descripcion;
                        } else {
                            $carreras[$i]['inscritos'][$j]['id_caballo']['edad'] = "0";
                        } 

                       

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

            $filahip = $ejecutar3->fetch(PDO::FETCH_ASSOC);              

            $carreras[$i]['id_hipodromo'] = $filahip;

            if ($fila['id_hipodromo'] != $indice OR $fecha_carrera != $indice2) {

                $indice = $fila['id_hipodromo'];
                $indice2 = $fecha_carrera;

                $carreras[$i]['div'] = $filahip['descripcion']." > ".$fecha_carrera;

                $fecha[] = array(
                    'dia' => $dia,
                    'hip' => $filahip['descripcion']
                 );
            }

            $i++;
        } 
        
        $result = array(
            "status" => "correcto",
            "carreras" => $carreras,
            "dias" => $fecha
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

$app->post('/carreras/inscribir/{id_carrera}/{numero}', function(Request $request, Response $response){
    $id_carrera = $request->getAttribute('id_carrera');
    $inscritos = $request->getAttribute('numero');
    $puesto = 0;
    $numero = 0;
    $mensaje = '';

    $db = new db();
    $db = $db->conectar();

    $c1 = "SELECT * FROM inscripcion WHERE id_carrera = '$id_carrera' ORDER BY puesto DESC";
    $e1 = $db->query($c1);
    $n1 = $e1->rowCount();

    if ($n1 > 0) {
        $r1 = $e1->fetch(PDO::FETCH_ASSOC);

        $numero = $r1['numero'];
        $puesto = $r1['puesto'];
    } 

    $numero++;
    $puesto++;

    for ($i=0; $i < $inscritos; $i++) { 
        $cg = "INSERT inscripcion (id_carrera, numero, puesto) VALUES ('$id_carrera', '$numero', '$puesto')";
        
        if ($db->query($cg)) { $numero++; $puesto++; $mensaje = 'correcto'; }
    }

    $result = array(
        "mensaje" => $mensaje,
        "numero" => $numero,
        "puesto" => $puesto,
        "inscritos" => $inscritos
    );

    echo json_encode($result);

});

$app->post('/carreras/enviada', function(Request $request, Response $response){
    $carrera = $request->getParam('carrera');

    $inscritos = $carrera['inscritos'];

    $vuelta = 0;

    $db = new db();
    $db = $db->conectar();


    $fecha_hora = $carrera['fecha_hora'];
    $distancia = $carrera['distancia'];
    $numero_carr = $carrera['numero'];
    $valida = $carrera['valida'];
    $titulo = $carrera['titulo'];
    $descripcion = $carrera['descripcion'];
    $id_carrera = $carrera['id_carrera'];

    if ($fecha_hora != '' AND $distancia != '' AND $numero_carr != '' AND $valida != '') {
        $consulta = "UPDATE carrera SET 
                fecha_hora = '$fecha_hora',
                distancia = '$distancia',
                numero = '$numero_carr',
                valida = '$valida',
                titulo = '$titulo',
                descripcion = '$descripcion'
            WHERE id_carrera = '$id_carrera'";
        $econsulta = $db->prepare($consulta);
        if ($econsulta->execute()) {

        }
    }    

    foreach ($inscritos as $ins) {

        $id_caballo = 0; $id_jinete = 0; $id_entrenador = 0; $id_stud = 0; $id_stud2 = 0; $peso = ''; $numero = ''; $puesto = 0;

        if (is_array($ins['id_caballo'])) {
            $id_caballo = $ins['id_caballo']['id_caballo'];
        }

        if (is_array($ins['id_jinete'])) {
            $id_jinete = $ins['id_jinete']['id_jinete'];
        }

        if (is_array($ins['id_entrenador'])) {
            $id_entrenador = $ins['id_entrenador']['id_entrenador'];
        }

        if (is_array($ins['id_stud'])) {
            $id_stud = $ins['id_stud']['id_stud'];
        }

        if (is_array($ins['id_stud2'])) {
            $id_stud2 = $ins['id_stud2']['id_stud'];
        }

        $id_inscripcion = $ins['id_inscripcion'];

        $peso = $ins['peso'];
        $numero = $ins['numero'];
        $puesto = $ins['puesto'];

        $con = "UPDATE inscripcion SET 
                id_caballo = '$id_caballo',
                id_jinete = '$id_jinete',
                id_entrenador = '$id_entrenador',
                id_stud = '$id_stud',
                id_stud2 = '$id_stud2',
                peso = '$peso',
                puesto = '$puesto',
                numero = '$numero'
            WHERE id_inscripcion = '$id_inscripcion'";
        $econ = $db->prepare($con);
        if ($econ->execute()) {

        }
    }

    $result = array(
        "carrera" => $carrera,
        "inscritos" => $inscritos
    );

    echo json_encode($result);
});
    


$app->get('/equipos/equiposui/{liga}', function(Request $request, Response $response){

    $liga = $request->getAttribute('liga');
    $equiposui = [];
    $i = 0;

    $db = new db();
    $db = $db->conectar();

    $criterios = " WHERE equipo_liga.id_liga=".$liga;

    $consulta = "SELECT DISTINCT equipo_liga.id_equipo, equipo.nombre_equipo FROM equipo_liga INNER JOIN equipo ON equipo_liga.id_equipo = equipo.id_equipo ". $criterios ." ORDER BY equipo.nombre_equipo ASC";  
    $ejecutar = $db->query($consulta);

    while($fila = $ejecutar->fetch(PDO::FETCH_ASSOC)) {
        $equiposui[$i] = $fila;

        $nombre_fichero = 'imagenes/equipos/'.$equiposui[$i]['id_equipo'].'.png';

        if (file_exists($nombre_fichero)) {
            $equiposui[$i]['img'] = $equiposui[$i]['id_equipo'].'.png';
        } else {
            $equiposui[$i]['img'] = 'sinimagen.png';
        }

        $i++;
    }

    $result = array(
        "equiposui" => $equiposui
    );

    echo json_encode($result);
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
    while ($r_c_1 = $e_c_1->fetch(PDO::FETCH_ASSOC)){ $caballos[] = $r_c_1; }

    $result = array(
        "padrillosui" => $caballos
    );

    echo json_encode($result);
});

$app->post('/caballos/madrilla/agregar', function(Request $request, Response $response){
    
    $nombre = addslashes($request->getParam('nombre'));
    $caballo =  $request->getParam('caballo');
    $id_caballo = $caballo['id_caballo'];

    $db = new db();
    $db = $db->conectar();

    $c1 = "SELECT * FROM caballo WHERE (tipo_caballo = '1') AND (sexo = '2') AND (nombre = '$nombre')";
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

        $cm = "INSERT INTO caballo (codigo, nombre, tipo_caballo, sexo) VALUES ('$codigo_final_pad','$nombre','1','2')";
        if($ecp = $db->query($cm)){
            $id_madre = $db->lastInsertId();
            $madre_a = array('id_caballo' => $id_madre, 'codigo' => $codigo_final_pad, 'nombre' => $nombre );
            $status = "success";
            $mstatus = "Yegua madre agregada correctamente";

            $consulta = "UPDATE caballo SET 
                        madre = '".$id_madre."'
                        WHERE id_caballo = ".$id_caballo;

            $stmt = $db->prepare($consulta);
            $stmt->bindParam(':nombre', $nombre);
            if ($stmt->execute()){
                $status2 = "success";
                $mstatus2 = "Se actualizó la yegua madre del ejemplar ".$caballo['nombre'];
            } else {
                $status2 = "error";
                $mstatus2 = "No se pudo actualizar la yegua madre del ejemplar ".$caballo['nombre'].". Debe hacerlo manualmente";
            }
        }

    } elseif ($n1 > 0) {
        $r1 = $e1->fetch(PDO::FETCH_ASSOC);
        $madre_a = $r1;
    }

    $result = array(
        "status" => $status,
        "mstatus" => $mstatus,
        "status2" => $status2,
        "mstatus2" => $mstatus2,
        "madre" => $madre_a
    );

    echo json_encode($result);

    
});

$app->post('/caballos/padrillo/agregar', function(Request $request, Response $response){
    
    $nombre = addslashes($request->getParam('nombre'));
    $caballo =  $request->getParam('caballo');
    $id_caballo = $caballo['id_caballo'];

    $db = new db();
    $db = $db->conectar();

    $c1 = "SELECT * FROM caballo WHERE (tipo_caballo = '1') AND (sexo = '1') AND (nombre = '$nombre')";
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

        $cm = "INSERT INTO caballo (codigo, nombre, tipo_caballo, sexo) VALUES ('$codigo_final_pad','$nombre','1','1')";
        if($ecp = $db->query($cm)){
            $id_padre = $db->lastInsertId();
            $padre_a = array('id_caballo' => $id_padre, 'codigo' => $codigo_final_pad, 'nombre' => $nombre );
            $status = "success";
            $mstatus = "Padrillo agregado correctamente";

            $consulta = "UPDATE caballo SET 
                        padre = '".$id_padre."'
                        WHERE id_caballo = ".$id_caballo;

            $stmt = $db->prepare($consulta);
            $stmt->bindParam(':nombre', $nombre);
            if ($stmt->execute()){
                $status2 = "success";
                $mstatus2 = "Se actualizó el padrillo del ejemplar ".$caballo['nombre'];
            } else {
                $status2 = "error";
                $mstatus2 = "No se pudo actualizar el padrillo del ejemplar ".$caballo['nombre'].". Debe hacerlo manualmente";
            }
        }

    } elseif ($n1 > 0) {
        $r1 = $e1->fetch(PDO::FETCH_ASSOC);
        $padre_a = $r1;
    }

    $result = array(
        "status" => $status,
        "mstatus" => $mstatus,
        "status2" => $status2,
        "mstatus2" => $mstatus2,
        "padre" => $padre_a
    );

    echo json_encode($result);

    
});

$app->get('/caballos/madrillasui', function(Request $request, Response $response){
    $db = new db();
    $db = $db->conectar();

    $c_1 = "SELECT * FROM caballo WHERE (tipo_caballo = '1') AND (sexo = '2') ORDER BY nombre ASC";
    $e_c_1 = $db->query($c_1);
    while ($r_c_1 = $e_c_1->fetch(PDO::FETCH_ASSOC)){ $caballos[] = $r_c_1; }

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

$app->post('/inscripcion/retirar', function(Request $request, Response $response){

    $db = new db();
    $db = $db->conectar();

    $id_inscripcion = $request->getParam('id_inscripcion');

    $cs1 = "SELECT * FROM inscripcion WHERE id_inscripcion = '$id_inscripcion'";
    $es1 = $db->query($cs1);
    $ns1 = $es1->rowCount();

    if ($ns1 > 0) {
        $s3 = "UPDATE inscripcion SET status='2' WHERE id_inscripcion='$id_inscripcion'";
        $es3 = $db->prepare($s3);
        if ($es3->execute()) {
            $result = array(
                "id_inscripcion" => $id_inscripcion,
                "mensaje" => "El ejemplar seleccionado fue retirado",
                "status" => "correcto"
            );                          
        }
    } else {
        $result = array(
            "id_inscripcion" => $id_inscripcion,
            "mensaje" => "El ejemplar seleccionado a retirar no existe",
            "status" => "error"
        );       
    }

    echo json_encode($result);


});

$app->delete('/inscripcion/eliminar/{id_sel}', function(Request $request, Response $response){
    $id_inscripcion = $request->getAttribute('id_sel');
          
    $db = new db();
    $db = $db->conectar();
    
    $cs1 = "SELECT * FROM inscripcion WHERE id_inscripcion = '$id_inscripcion'";
    $es1 = $db->query($cs1);
    $ns1 = $es1->rowCount();

    if ($ns1 > 0) {
        $c2 = "DELETE FROM inscripcion WHERE id_inscripcion = '$id_inscripcion'";
        if ($db->query($c2)) {
            $result = array(
                "id_inscripcion" => $id_inscripcion,
                "mensaje" => "La inscripción fue eliminada",
                "status" => "correcto"
            );                          
        }
    } else {
        $result = array(
            "id_inscripcion" => $id_inscripcion,
            "mensaje" => "El ejemplar seleccionado a retirar no existe",
            "status" => "error"
        );       
    }

    echo json_encode($result);
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

    if ($id_usuario == '') {
        $status = "warning"; $mstatus = "Debe iniciar sesión para seleccionar cuotas";
    } else {
        $db = new db();
        $db = $db->conectar();

        $c1 = "SELECT * FROM seleccion WHERE id_select = '$id_apuesta' AND id_usuario = '$id_usuario' AND (id_ticket = '0' OR id_ticket = '')";
        $e1 = $db->query($c1);
        $n1 = $e1->rowCount();

        if ($n1 == 1) {
            $c2 = "DELETE FROM seleccion WHERE id_select = '$id_apuesta' AND id_usuario = '$id_usuario' AND (id_ticket = '0' OR id_ticket = '')";
            if ($db->query($c2)) { $status= "success"; $mstatus = "Ejemplar borrado"; }
        } else {
            $c1_1 = "SELECT * FROM seleccion WHERE (id_ticket = '0' OR id_ticket = '') AND id_usuario = '$id_usuario' ORDER BY id_seleccion DESC";
            $e1_1 = $db->query($c1_1);
            $n1_1 = $e1_1->rowCount();

            $r1_1 = $e1_1->fetch(PDO::FETCH_ASSOC);

            if ($r1_1['id_deporte'] != '27' && $n1_1 > 0) {
                $mstatus = "No se pueden combinar selecciones de deporte e hipismo";
                $status = 'warning';
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
                        if ($db->query($c5)) { $status = "success"; $mstatus = "Ejemplar agregado"; }
                    } else {
                        $status = "info"; $mstatus = "Ya tiene un ejemplar seleccionado en esta carrera";
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

    if ($id_usuario == '') {
         $status = "warning"; $mstatus = "Debe iniciar sesión para seleccionar cuotas";
    } else {
         $db = new db();
    $db = $db->conectar();

    $c1 = "SELECT * FROM seleccion WHERE id_select = '$id_apuesta' AND (id_ticket = '0' OR id_ticket = '') AND id_usuario = '$id_usuario' ORDER BY id_seleccion DESC";
    $e1 = $db->query($c1);
    $n1 = $e1->rowCount();

    if ($n1 > 0) {
        $c2 = "DELETE FROM seleccion WHERE id_select = '$id_apuesta' AND id_usuario = '$id_usuario' AND (id_ticket = '0' OR id_ticket = '')";
        if ($db->query($c2)) { $status= "success"; $mstatus = "Selección borrada"; }
    } else {

        $c1_1 = "SELECT * FROM seleccion WHERE (id_ticket = '0' OR id_ticket = '') AND id_usuario = '$id_usuario' ORDER BY id_seleccion DESC";
        $e1_1 = $db->query($c1_1);

        $r1_1 = $e1_1->fetch(PDO::FETCH_ASSOC);

        if ($r1_1['id_deporte'] == '27') {
            $mstatus = "No se pueden combinar selecciones de deporte e hipismo";
            $status = 'warning';
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
                        $status = "success";
                        $mstatus = "Selección agregada";
                    };
                } else {
                    $status = "info";
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

    $selecciones = [];

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

$app->delete('/seleccion/{id_sel}', function(Request $request, Response $response){
    $id_seleccion = $request->getAttribute('id_sel');

    $sql = "DELETE FROM seleccion WHERE id_seleccion = $id_seleccion";

    try{
        $db = new db();
        $db = $db->conectar();

        $stmt = $db->prepare($sql);
        if ($stmt->execute()) {
            $result = array(
                "status" => 'success',
                "mstatus" => 'Selección eliminada'
            );    
        } else {
            $result = array(
                "status" => 'error',
                "mstatus" => 'No se puede eliminar la selección'
            );
        }

        $db = null;

        
    } catch(PDOException $e){
        $result = array(
            "status" => 'error',
            "mstatus" => $e->getMessage()
        );
    }

    echo json_encode($result);
});

$app->get('/deportes/categoria/{id_categoria}', function(Request $request, Response $response){

    $id_categoria = $request->getAttribute('id_categoria');

    $db = new db();
    $db = $db->conectar();

    $consulta = "SELECT * FROM liga WHERE id_categoria = '$id_categoria' ORDER BY nombre_liga";
    $ejecutar = $db->query($consulta);

    while($fila = $ejecutar->fetch(PDO::FETCH_ASSOC)) {
        $ligas[] = $fila;
    }

    $result = array(
        "ligas" => $ligas
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

            $c2 = "SELECT * FROM participante WHERE id_partido = '".$f1['id_partido']."' AND id_equipo1 != '35' ORDER BY id_participante ASC";
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

    $c1 = "SELECT DISTINCT a.id_hipodromo, b.descripcion, a.id_carrera, a.fecha_hora, a.numero FROM carrera a INNER JOIN hipodromo b on a.id_hipodromo = b.id_hipodromo WHERE a.fecha_hora < '".$fecha_actual."' AND a.id_hipodromo = '".$id_hipodromo."' ORDER BY a.fecha_hora ASC";
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

            $c4 = "SELECT * FROM rl_mx_9483 WHERE id_partido = '".$f1['id_carrera']."' AND id_categoria = '27'";
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
    $disponible = null;

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
                        $disponible = 0.0001;
                        $acumulado = 1;

                        $s5 = "SELECT * FROM seleccion WHERE id_ticket = '$codigo'";
                        $es5 = $db->query($s5);
                        $full = 'true';
                        while($fs5 = $es5->fetch(PDO::FETCH_ASSOC)) {
                            $id_p = $fs5['id_select'];

                            $s6 = "SELECT * FROM participante WHERE id_participante = '$id_p' ORDER BY id_participante ASC";
                            $es6 = $db->query($s6);

                            $fs6 = $es6->fetch(PDO::FETCH_ASSOC);

                            $div_equipo_part1 = $fs6["dividendo"];

                            $div_div = explode("/", $div_equipo_part1);

                            if (!isset($div_div[1])) {
                                $div_div[1] = 1;
                            }                                       

                            $decimal_odd = (intval($div_div[0]) / intval($div_div[1])) + 1;

                            if ($fs6['status'] == '1') { $acumulado = $acumulado * $decimal_odd; } 
                            elseif ($fs6['status'] == '2') { } 
                            elseif ($fs6['status'] == '3') { 
                                $full = 'false';

                                $s7 = "UPDATE 1_x_34prly SET estatus='3' WHERE cod_seguridad='$codigo'";
                                $es7 = $db->prepare($s7);
                                if ($es7->execute()) {}
                            } elseif ($fs6['status'] == '0') { $full = 'pendiente'; }
                                                                        
                        }

                        if ($full == 'true') {
                            $s8 = "UPDATE 1_x_34prly SET estatus='1' WHERE cod_seguridad='$codigo'";
                            $es8 = $db->prepare($s8);
                            if ($es8->execute()) {
                                $ss8 = "SELECT id_usuario, cod_seguridad, monto FROM 1_x_34prly WHERE cod_seguridad = '$codigo'";
                                $ess8 = $db->query($ss8);
                                $fss8 = $ess8->fetch(PDO::FETCH_ASSOC);

                                $id_usuario =  $fss8['id_usuario'];
                                $monto_pagar = $fss8['monto'] * $acumulado;

                                $ss9 = "SELECT id_usuario, disponible FROM usuario WHERE id_usuario = '$id_usuario'";
                                $ess9 = $db->query($ss9);
                                $fss9 = $ess9->fetch(PDO::FETCH_ASSOC);

                                $saldo = $fss9['disponible'];

                                $nuevo_saldo =  $saldo + $monto_pagar;

                                $cp2 = "UPDATE usuario SET disponible='$nuevo_saldo' WHERE id_usuario = '$id_usuario'";
                                $ecp2 = $db->prepare($cp2);
                                if ($ecp2->execute()) {
                                    $disponible = floatval($nuevo_saldo);
                                }
                            }
                        }
                    }                                    
                }

                $result = array(
                    "status" => "correcto",
                    "disponible" => $disponible,
                    "acumulado" => $acumulado,
                    "full" => $ss9
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

                if ($win[1] < 150.01) {
                    $cuota1 = 1.7;
                } elseif ($win[1] > 150 AND $win[1] < 170.01) {
                    $cuota1 = 1.85;
                } elseif ($win[1] > 170 AND $win[1] < 200.01) {
                    $cuota1 = 2;
                } elseif ($win[1] > 200) {
                    $cuota1 = ($win[1] / 100) + 1.2;
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

                                        $s7 = "UPDATE 1_x_34prly SET estatus='3' WHERE cod_seguridad='$codigo'";
                                        $es7 = $db->prepare($s7);
                                        if ($es7->execute()) {
                                            
                                        }
                                    } 
                                }                                            
                            }

                            if ($full == 'true') {
                                $s8 = "UPDATE 1_x_34prly SET estatus='1' WHERE cod_seguridad='$codigo'";
                                $es8 = $db->prepare($s8);
                                if ($es8->execute()) {
                                    $ss8 = "SELECT id_usuario, cod_seguridad, monto FROM 1_x_34prly WHERE cod_seguridad = '$codigo'";
                                    $ess8 = $db->query($ss8);
                                    $fss8 = $ess8->fetch(PDO::FETCH_ASSOC);

                                    $id_usuario =  $fss8['id_usuario'];
                                    $monto_pagar = $fss8['monto'] * $cuota1;

                                    $ss9 = "SELECT id_usuario, disponible FROM usuario WHERE id_usuario = '$id_usuario'";
                                    $ess9 = $db->query($ss9);
                                    $fss9 = $ess9->fetch(PDO::FETCH_ASSOC);

                                    $saldo = $fss9['disponible'];

                                    $nuevo_saldo =  $saldo + $monto_pagar;

                                    $cp2 = "UPDATE usuario SET disponible='$nuevo_saldo' WHERE id_usuario = '$id_usuario'";
                                    $ecp2 = $db->prepare($cp2);
                                    if ($ecp2->execute()) {
                                        $disponible = floatval($nuevo_saldo);
                                    }
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

$app->get('/changelog/ver/{dato}', function(Request $request, Response $response){
    $db = new db();
    $db = $db->conectar();

    $dato = $request->getAttribute('dato');
    $changelog = [];
    $i = 0;

    if ($dato == 'todos') {
        $criterio = '';
    }

    $c_1 = "SELECT * FROM retos ORDER BY fecha_hora DESC";
    $e_c_1 = $db->query($c_1);
    while ($r_c_1 = $e_c_1->fetch(PDO::FETCH_ASSOC)){ 
        $changelog[$i] = $r_c_1;

        $id_usuario = $r_c_1['id_usuario'];

        $consulta = "SELECT usuario, id_usuario FROM usuario WHERE id_usuario = '$id_usuario'";
        $ejecutar = $db->query($consulta);
        $registros = $ejecutar->rowCount(); 

        if ($registros > 0){
            $fila = $ejecutar->fetch(PDO::FETCH_ASSOC);
            
            $changelog[$i]['id_usuario'] = $fila;
        }

        $i++;
    }

    $result = array(
        "changelog" => $changelog
    );

    echo json_encode($result);
});

$app->post('/changelog/agregar', function(Request $request, Response $response){
    
    $texto = addslashes($request->getParam('tarea'));
    $id_usuario = $request->getParam('id_usuario');
    $fecha_actual = date("Y-m-d H:i:s");

    $consulta = "INSERT INTO retos (texto, id_usuario, fecha_hora) VALUES ('".$texto."','".$id_usuario."','".$fecha_actual."')";
    try{
        $db = new db();
        $db = $db->conectar();

        $stmt = $db->prepare($consulta);
        $stmt->bindParam(':texto', $texto);
        if ($stmt->execute()){
            $changelog = [];
            $i = 0;
            $c_1 = "SELECT * FROM retos ORDER BY fecha_hora DESC";
            $e_c_1 = $db->query($c_1);
            while ($r_c_1 = $e_c_1->fetch(PDO::FETCH_ASSOC)){ 
                $changelog[$i] = $r_c_1;

                $id_usuario = $r_c_1['id_usuario'];

                $consulta = "SELECT usuario, id_usuario FROM usuario WHERE id_usuario = '$id_usuario'";
                $ejecutar = $db->query($consulta);
                $registros = $ejecutar->rowCount(); 

                if ($registros > 0){
                    $fila = $ejecutar->fetch(PDO::FETCH_ASSOC);
                    
                    $changelog[$i]['id_usuario'] = $fila;
                }

                $i++;
            }
            $result = array(
                "status" => "correcto",
                "changelogs" => $changelog
            );

            echo json_encode($result);
        }     

    } catch(PDOException $e){
        echo '{"error": {"text": '.$consulta.'}';
    }
});

$app->post('/changelog/actualizar', function(Request $request, Response $response){
    $id_reto = $request->getParam('id_cl');
    $estatus = 1;

    $consulta = "UPDATE retos SET 
                        estatus = '".$estatus."'
                        WHERE id_reto = ".$id_reto;
    try{
        $db = new db();
        $db = $db->conectar();

        $stmt = $db->prepare($consulta);
        $stmt->bindParam(':id_reto', $id_reto);
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