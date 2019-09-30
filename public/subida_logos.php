<?php
    header('Access-Control-Allow-Origin: http://localhost:4200', false);
    
    $id = $_GET["id"];
    $carpeta = $_GET["c"];

	$infoFile = getimagesize($_FILES['myFile']['tmp_name']);

    if($infoFile[0]>=30 && $infoFile[1]>=30){
        $carpetero = "imagenes/".$carpeta;

        if (file_exists($carpetero)) {
            
        } else {
            mkdir($carpetero);		        
        }   

        if (copy($_FILES['myFile']['tmp_name'],"imagenes/".$carpeta."/$id.png")) { 
            $result = array(
                "status" => "correcto",
                "imagen" => $id.'.png'
            );
        } else {
            $result = array(
                "status" => "error"
            );
        }	
        
        echo json_encode($result);
    }