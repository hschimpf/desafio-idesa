# Desafio IDESA

## Instalación manual

Luego de clonar el proyecto hay que inicializar los módulos
```
git submodule update --init --recursive
```

Los directorios `logs`, `tmp`, y `conf/sqlite` deben ser writables para apache/php **(reemplazar http por www-data o correspondiente)**
```
chown `whoami` logs tmp conf/sqlite
chgrp http logs tmp conf/sqlite
chmod g+w logs tmp conf/sqlite
```

El WS tiene una consola integrada para pruebas (https://url-o-ip/console). La misma tiene un fichero de configuración que se debe vincular.
De debe crear un vinculo al fichero `conf/console.config` dentro del directorio `html/console`
```
cd html/console
ln -s ../../conf/console.config
```

Se debe crear un fichero `.htaccess` con los parametros de conexion a la base de datos en la raiz del proyecto
```
SetEnv DB_HOST "localhost"
SetEnv DB_PORT "3306"
SetEnv DB_USER "idesa"
SetEnv DB_PASS "superpass123"
SetEnv DB_DDBB "idesa-desafio"
```

En el directorio `data` se encuentra un SQL para la creacion de las tablas del WS y un usuario de pruebas.
Las credenciales del mismo son las siguientes:
```
username: root
password: 7nmFEWHeaX#qcaNj%gszxj8nkj*!3n
```

## Instalación rápida

Se puede ejecutar el script *data/init*, el mismo ejecuta los comandos anteriormente mencionados dentro del directorio del proyecto
```
./data/init
```

La creacion de la base de datos sigue siendo de forma manual, se debe ejecutar el SQL mencionado en la instalación manual.
