FROM ubuntu:latest

ENV DEBIAN_FRONTEND=noninteractive

EXPOSE 25565

WORKDIR /minecraft

RUN apt-get update && apt-get install -y \
    openjdk-17-jdk \
    wget \
    && rm -rf /var/lib/apt/lists/*

# Copiar el script y otros archivos necesarios
COPY docker/entrypoint.sh /minecraft/

# Descargar el archivo del servidor de Minecraft
RUN wget --no-cache -O server.jar https://piston-data.mojang.com/v1/objects/4fb536bfd4a83d61cdbaf684b8d311e66e7d4c49/server.jar

RUN echo "eula=true" > eula.txt

# Cambiar permisos y ejecutar el script
RUN chmod +x /minecraft/entrypoint.sh

# Ejecutar el script al inicio del contenedor
CMD ["/minecraft/entrypoint.sh"]