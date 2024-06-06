<?php
include "../inc/dbinfo.inc";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

$user = $_SESSION['usuario'];


$file ="/var/www/dockercomposes/" . $user . "-docker-compose.yml";

if (!file_exists("$file")) {
    touch("$file");
}

fopen("$file", "w");

$container_name = $user . "-server";

$docker_compose_content = "
version: '3.8'
services:
    minecraft_server:
        build: .
        image: easy-minecubos
        container_name: $container_name
        ports:
            - '25565:25565'
        environment:
";

foreach ($_POST as $key => $value) {
    $value = preg_replace("/[^a-zA-Z0-9\s]/", "", $value);
    $docker_compose_content .= "            - $key = $value\n";
}

if (file_put_contents($file, $docker_compose_content) !== false) {
    echo "Archivo $file generado correctamente.<br>";

    $pass = "/home/ec2-user/easyminecubos-servermc.pem";
    $destiny = "ec2-user@34.202.66.61:/home/ec2-user/docker";

    $comando_scp = "scp -i $pass $file $destiny";
    exec($comando_scp);

    echo exec('pwd');

    echo $comando_scp . "<br>";

    echo "archivo enviado correctamente.<br>";

    $file2 = "/home/ec2-user/docker/" . $user . "-docker-compose.yml";

    $dockercompose = "docker-compose -f $file2 up";
    
    $destiny = "ec2-user@34.202.66.61";

    $comando_ssh = "ssh -i $pass $destiny \"$dockercompose\"";

    exec($comando_ssh);

    echo $comando_ssh . "<br>";

    echo "Contenedor arrancado.<br>";

}else {
    // Si hay un error, captura el mensaje de error
    $error = error_get_last();
    echo "Error al generar el archivo $file: " . $error['message'];
}