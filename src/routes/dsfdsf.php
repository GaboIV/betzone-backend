 try{ 
	$db = new db();
    $db = $db->conectar();

    $nombre = $request->getParam('nombre');
    $sexo = $request->getParam('sexo');
    $nacimiento = $request->getParam('nacimiento');

    $n_padre = $request->getParam('padre');
    $n_padre = $db->real_escape_string($n_padre);

    $e1 = $db->query("");
    $r1 = $e1->fetch(PDO::FETCH_ASSOC);

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
    } else {
        $txt_cant_eq = "equipos";
    }	


    $n_madre = $request->getParam('madre');

    $n_haras = $request->getParam('id_haras');
   

    $consulta = "INSERT INTO caballo (codigo, nombre, tipo_caballo, sexo) VALUES ('$codigo_final_pad','$n_padre','1','1')";
         

        $stmt = $db->prepare($consulta);
        $stmt->bindParam(':padre', $nombre);
        if ($stmt->execute()){
            $result = array(
                "status" => "correcto"
            );

            echo json_encode($result);
        }   

    } catch(PDOException $e){
        echo '{"error": {"text": '.$consulta.'}';
    }