<?php
    class db{
        // Variables de conexión
        private $host = 'sql310.epizy.com';
        private $usuario = 'epiz_22939042';
        private $password = 'fSHZaKu71W8VL';
        private $base = 'epiz_22939042_betzone';
        

        // Conectar a BD
        public function conectar(){
            $conexion_mysql = "mysql:host=$this->host;dbname=$this->base";
            $conexionDB = new PDO($conexion_mysql, $this->usuario, $this->password);
            $conexionDB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            //Esta linea arregla la codificacion sino no aparecen en la salida en JSON quedan NULL
            $conexionDB -> exec("set names utf8");
            return $conexionDB;
        }
    }