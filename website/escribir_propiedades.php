<?php
include "../inc/dbinfo.inc";
require "vendor/autoload.php";
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SFTP;
use phpseclib3\Net\SSH2;

session_start();

//CREACION DEL DOCKER COMPOSE

$user = $_SESSION['usuario'];
$directory = "/var/www/dockercomposes/";
$file = $directory . $user . "-docker-compose.yml";

// Contenido de Docker Compose
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

// Construir el contenido basado en POST
foreach ($_POST as $key => $value) {
    $value = preg_replace("/[^a-zA-Z0-9\s]/", "", $value);
    $docker_compose_content .= "            - $key = $value\n";
}

// Escribir contenido en el archivo Docker Compose
if (file_put_contents($file, $docker_compose_content) === false) {
    throw new Exception("Error al escribir en el archivo $file");
}

//echo "Archivo $file generado correctamente.<br>";




// Crear la sesión SFTP
$host = '34.202.66.61';
$username = 'ec2-user';
$private_key_file = '/home/ec2-user/easyminecubos-servermc.pem';

$sftp = new SFTP($host);

// Leer la clave privada
$privateKey = PublicKeyLoader::load(file_get_contents($private_key_file));

// login
if (!$sftp->login($username, $privateKey)) {
    throw new Exception('sFTP login failed');
}

// Envío del archivo
// Ruta del archivo local y destino remoto
$LocalFile = $file;
$RemoteFile = "/home/ec2-user/docker/" . $user . "-docker-compose.yml";

if (!$sftp->put($RemoteFile, $LocalFile, SFTP::SOURCE_LOCAL_FILE)) {
    throw new Exception('Error al copiar archivo al servidor remoto');
}

//echo "Archivo '$LocalFile' copiado correctamente a '$RemoteFile' en el servidor remoto.<br><br><br>";


//EJECUCIÓN DEL CONTENEDOR
// Crear sesión de SSH
$ssh = new SSH2($host);
if (!$ssh->login($username, $privateKey)) {
    throw new Exception('SSH login failed');
}

// Inicialización del contenedor

$docker_command = "docker-compose -f $RemoteFile up -d";
$output = $ssh->exec($docker_command);

if ($output === false) {
    throw new Exception('Error al ejecutar comando Docker Compose');
}

echo "Comando Docker Compose ejecutado correctamente:\n";
echo $output . "\n";

// HTML para el indicador de carga básico
    echo <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Cargando...</title>
        <style>
        /* Estilos para el indicador de carga */
        .loading-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .loading-spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        </style>
        </head>
        <body>
        <div class="loading-container">
            <div class="loading-spinner"></div>
        </div>
        </body>
        </html>
    HTML;

// Esperar hasta que el contenedor esté en funcionamiento
$max_attempts = 12; // Intentos máximos (espera total de aproximadamente 2 minutos)
$wait_time = 10; // Tiempo de espera entre intentos (segundos)

$container_running = false;
for ($attempt = 1; $attempt <= $max_attempts; $attempt++) {
    sleep($wait_time); // Esperar antes de verificar nuevamente

    // verificar el estado del contenedor
    $check_command = "docker ps --filter name=$container_name --format '{{.Status}}'";
    $status_output = $ssh->exec($check_command);

    if ($status_output === false) {
        throw new Exception('Error al verificar estado del contenedor');
    }

    // verificar si el contenedor está corriendo
    if (strpos($status_output, 'Up') !== false) {
        $container_running = true;
        break;
    }
}

if (!$container_running) {
    throw new Exception("El contenedor '$container_name' no pudo ponerse en funcionamiento después de $max_attempts intentos.");
}else {
    echo "El contenedor '$container_name' está en funcionamiento.<br>Conéctate: 34.202.66.61:25565";
}