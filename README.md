# CONFIGURACIONES INICIALES PARA LOCALHOST#

### Configurar variable global STASIS ###

Es necesario definir bien la ruta de localhost para que los assets carguen correctamente

Para eso debemos ubicarnos en el archivo: aplicacion/config/config.php

Y dentro de la condición que corresponda a localhost, escribir nuestra ruta

Con eso quedaría la variable STASIS funcionando correctamente

### Configurar archivo .htaccess ###

Es indispensable este archivo para que los assets carguen correctamente

Basta con descargar el archivo de cualquier proyecto anterior y listo, lo copiamos a la raíz de este sitio

### Configuración de Permisos de la carpeta de sesiones ###

Se necesita configurar los permisos de la carpeta de sesiones para correr el sitio en Mac OS

* En Terminal, escribir el siguiente comando
* sudo chmod -R 777 /Applications/XAMPP/xamppfiles/htdocs/devvalcas/proveedores/data/sessions/
* Ejecutar comando
* Escribir contraseña del equipo con la que se inicia sesion (si es que existe una)
* Ejecutar contraseña
* Listo, ya no debería de aparecer el error de sessions_start()