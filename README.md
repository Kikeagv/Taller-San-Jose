# Sistema de Gestión de Inventario - Taller Mecánico San José

Aplicación web construida como entrega funcional final para Análisis y Diseño de Sistemas.

## Stack

- PHP 8.0 o superior
- MySQL 8 o MariaDB
- Bootstrap 5
- PDO

## Instalación

1. Cree la base de datos e importe el esquema:

```bash
mysql -u root -p < database/schema.sql
```

2. Cargue los datos de prueba:

```bash
mysql -u root -p taller_san_jose < database/seed.sql
```

3. Ajuste credenciales en `config/database.php` si su usuario de MySQL no es `root` sin contraseña.
   También puede usar variables de entorno:

```bash
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_DATABASE=taller_san_jose
export DB_USERNAME=root
export DB_PASSWORD='123456..'
export APP_TIMEZONE=America/El_Salvador
```

4. Levante el servidor desde la carpeta `public`:

```bash
php -S localhost:8000 -t public
```

5. Abra:

```text
http://localhost:8000
```

## Usuarios de prueba

Todos usan la contraseña:

```text
password
```

| Rol | Correo |
|---|---|
| Administrador | admin@taller.test |
| Bodega | bodega@taller.test |
| Recepción | recepcion@taller.test |
| Propietario | propietario@taller.test |

## Permisos por rol

| Rol | Acceso principal |
|---|---|
| Administrador | Todas las secciones y acciones del sistema. |
| Bodega | Dashboard, repuestos, movimientos, proveedores y compras. |
| Recepción | Dashboard, clientes, vehículos y servicios de taller. |
| Propietario | Dashboard y reportes en modo consulta/exportación. |

El menú se ajusta automáticamente según el rol del usuario. Las rutas y acciones sensibles también se validan del lado del servidor.

## Funcionalidad incluida

- Login con sesiones y contraseña cifrada.
- Dashboard operativo desde MySQL.
- CRUD de repuestos.
- Entradas y salidas de inventario con validación de stock.
- CRUD de proveedores.
- Compras con detalle y recepción de mercadería.
- CRUD de clientes.
- Vehículos asociados a clientes.
- Servicios de taller por vehículo, con repuestos utilizados y descuento automático de inventario.
- Trazabilidad de salidas de inventario generadas desde servicios.
- Reportes imprimibles y exportables a CSV.
- Datos semilla con más de 50 registros para pruebas.
