

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