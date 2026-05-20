# Sistema de Gestión de Inventario - Taller Mecánico San José

Aplicación web construida para el avance funcional de Fase 2 de Análisis y Diseño de Sistemas.

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

## Funcionalidad incluida

- Login con sesiones y contraseña cifrada.
- Dashboard con indicadores desde MySQL.
- CRUD de repuestos.
- Entradas y salidas de inventario con validación de stock.
- CRUD de proveedores.
- Compras con detalle y recepción de mercadería.
- CRUD de clientes.
- Vehículos asociados a clientes.
- Reportes imprimibles.
- Datos semilla con más de 50 registros para pruebas.

## Capturas requeridas para el documento

Cuando la app esté corriendo, capture:

1. Login
2. Dashboard
3. Listado de repuestos
4. Formulario de repuesto
5. Movimientos de inventario
6. Proveedores
7. Compras
8. Clientes y vehículos
9. Reportes
