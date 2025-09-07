# Cómo crear el usuario administrador para ingresar al sistema

He preparado un script para crear automáticamente un usuario administrador por defecto.

## Credenciales de Acceso

Una vez creado, podrás usar las siguientes credenciales para iniciar sesión:

- **Usuario:** `admin`
- **Contraseña:** `admin1234`

## Pasos para Crear el Usuario

1.  **Configura tu base de datos:** Asegúrate de que los archivos `database/schema.sql` y `database/stored_procedures.sql` se hayan importado en tu base de datos y que el archivo `config/config.php` tenga las credenciales correctas.

2.  **Ejecuta el siguiente comando** en la terminal, desde el directorio raíz del proyecto:

    ```bash
    php database/seed.php
    ```

Este comando insertará el usuario `admin` en tu base de datos y podrás usarlo para acceder al sistema.
