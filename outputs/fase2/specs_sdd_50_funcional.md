# Especificación SDD para implementar el 50% funcional

**Proyecto:** Sistema de Gestión de Inventario de Repuestos - Taller Mecánico San José  
**Stack obligatorio:** PHP + MySQL + Bootstrap  
**Objetivo del agente implementador:** construir una versión funcional demostrable que cubra los flujos mínimos exigidos para la Fase 2.

---

# 1. Metodología SDD

Esta especificación usa Spec-Driven Development. El agente implementador debe trabajar en este orden:

1. Leer esta especificación completa.
2. Crear la base de datos según el esquema definido.
3. Cargar datos semilla suficientes para pruebas.
4. Implementar primero los flujos críticos.
5. Validar cada pantalla contra sus criterios de aceptación.
6. Generar capturas finales para el documento y las diapositivas.

No debe empezar por “hacer pantallas bonitas”. La interfaz debe ser clara y responsiva, pero la prioridad es que los casos de uso funcionen y que los datos se guarden correctamente.

---

# 2. Alcance del 50% funcional

La versión debe incluir:

| Área | Estado esperado |
|---|---|
| Autenticación | Login funcional con sesiones |
| Roles | Administrador, bodega, recepción, propietario |
| Dashboard | Indicadores básicos desde MySQL |
| Repuestos | CRUD completo |
| Movimientos | Entradas y salidas con actualización de stock |
| Proveedores | CRUD completo |
| Compras | Crear compra, agregar detalle, recibir mercadería |
| Clientes | CRUD completo |
| Vehículos | CRUD asociado a clientes |
| Reportes | Consultas imprimibles básicas |
| Validaciones | Campos obligatorios, cantidades válidas, stock suficiente |
| Seguridad básica | Password hash, sesiones, protección de rutas |

Fuera del alcance para esta versión:

- Facturación completa.
- Exportación real a PDF.
- Integración con correo.
- Control avanzado de permisos por acción.
- Historial completo de servicios con mano de obra.
- API REST separada.

---

# 3. Reglas generales de implementación

## 3.1 Arquitectura de carpetas

Usar esta estructura:

```text
/app
  /Controllers
  /Models
  /Services
  /Repositories
  /Core
/config
  database.php
/database
  schema.sql
  seed.sql
/public
  index.php
  /assets
    /css
    /js
/views
  /auth
  /dashboard
  /repuestos
  /movimientos
  /proveedores
  /compras
  /clientes
  /vehiculos
  /reportes
```

Si el agente decide usar una estructura más simple, debe conservar la separación mínima entre conexión, lógica, vistas y consultas.

## 3.2 Convenciones

- Usar PDO para conectar con MySQL.
- Usar consultas preparadas.
- No concatenar datos del usuario dentro de SQL.
- Usar `password_hash()` y `password_verify()`.
- Validar sesión en cada pantalla privada.
- Mostrar mensajes claros de éxito y error.
- Mantener Bootstrap 5 desde CDN o archivo local.
- Evitar dependencias innecesarias.

---

# 4. Base de datos

## 4.1 Script SQL obligatorio

```sql
CREATE DATABASE IF NOT EXISTS taller_san_jose
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE taller_san_jose;

CREATE TABLE usuarios (
  id_usuario INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  correo VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  rol ENUM('admin','bodega','recepcion','propietario') NOT NULL,
  estado TINYINT(1) NOT NULL DEFAULT 1,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE proveedores (
  id_proveedor INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  contacto VARCHAR(120),
  telefono VARCHAR(30),
  correo VARCHAR(120),
  direccion VARCHAR(255),
  productos_ofrecidos TEXT,
  estado TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE repuestos (
  id_repuesto INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(40) NOT NULL UNIQUE,
  numero_parte VARCHAR(80),
  nombre VARCHAR(140) NOT NULL,
  descripcion TEXT,
  marca VARCHAR(80),
  ubicacion VARCHAR(80),
  precio_referencia DECIMAL(10,2) NOT NULL DEFAULT 0,
  stock_actual INT NOT NULL DEFAULT 0,
  stock_minimo INT NOT NULL DEFAULT 0,
  estado TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE movimientos_inventario (
  id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
  id_repuesto INT NOT NULL,
  id_usuario INT NOT NULL,
  tipo_movimiento ENUM('entrada','salida') NOT NULL,
  cantidad INT NOT NULL,
  motivo VARCHAR(180) NOT NULL,
  referencia VARCHAR(120),
  fecha_movimiento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_repuesto) REFERENCES repuestos(id_repuesto),
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

CREATE TABLE compras (
  id_compra INT AUTO_INCREMENT PRIMARY KEY,
  id_proveedor INT NOT NULL,
  id_usuario INT NOT NULL,
  fecha_compra DATE NOT NULL,
  estado ENUM('pendiente','recibida','anulada') NOT NULL DEFAULT 'pendiente',
  total_estimado DECIMAL(10,2) NOT NULL DEFAULT 0,
  observaciones TEXT,
  FOREIGN KEY (id_proveedor) REFERENCES proveedores(id_proveedor),
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

CREATE TABLE detalle_compra (
  id_detalle_compra INT AUTO_INCREMENT PRIMARY KEY,
  id_compra INT NOT NULL,
  id_repuesto INT NOT NULL,
  cantidad INT NOT NULL,
  precio_unitario DECIMAL(10,2) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (id_compra) REFERENCES compras(id_compra),
  FOREIGN KEY (id_repuesto) REFERENCES repuestos(id_repuesto)
);

CREATE TABLE clientes (
  id_cliente INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(140) NOT NULL,
  telefono VARCHAR(30),
  correo VARCHAR(120),
  direccion VARCHAR(255),
  estado TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE vehiculos (
  id_vehiculo INT AUTO_INCREMENT PRIMARY KEY,
  id_cliente INT NOT NULL,
  marca VARCHAR(80) NOT NULL,
  modelo VARCHAR(80) NOT NULL,
  anio INT,
  placa VARCHAR(20) NOT NULL UNIQUE,
  tipo_motor VARCHAR(80),
  color VARCHAR(50),
  estado TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente)
);

CREATE TABLE servicios (
  id_servicio INT AUTO_INCREMENT PRIMARY KEY,
  id_vehiculo INT NOT NULL,
  fecha_servicio DATE NOT NULL,
  descripcion TEXT NOT NULL,
  kilometraje INT,
  observaciones TEXT,
  FOREIGN KEY (id_vehiculo) REFERENCES vehiculos(id_vehiculo)
);

CREATE TABLE detalle_servicio_repuesto (
  id_detalle_servicio INT AUTO_INCREMENT PRIMARY KEY,
  id_servicio INT NOT NULL,
  id_repuesto INT NOT NULL,
  cantidad_usada INT NOT NULL,
  FOREIGN KEY (id_servicio) REFERENCES servicios(id_servicio),
  FOREIGN KEY (id_repuesto) REFERENCES repuestos(id_repuesto)
);
```

## 4.2 Datos semilla

Debe crear:

- 4 usuarios, uno por rol.
- 8 proveedores.
- 20 repuestos.
- 10 clientes.
- 10 vehículos.
- 6 compras con detalle.
- 20 movimientos de inventario.

Credenciales sugeridas para demo:

| Rol | Correo | Contraseña |
|---|---|---|
| admin | admin@taller.test | Admin123* |
| bodega | bodega@taller.test | Bodega123* |
| recepcion | recepcion@taller.test | Recep123* |
| propietario | propietario@taller.test | Prop123* |

Las contraseñas deben guardarse cifradas, nunca en texto plano.

---

# 5. Pantallas y criterios de aceptación

## 5.1 Login

**Ruta:** `/login`

**Debe tener:**

- Campo correo.
- Campo contraseña.
- Botón ingresar.
- Mensaje para credenciales inválidas.

**Criterios de aceptación:**

- Si las credenciales son válidas, crea sesión.
- Si las credenciales son inválidas, no crea sesión.
- Si el usuario ya inició sesión, puede entrar al dashboard.
- Si no inició sesión, no puede abrir pantallas privadas.

## 5.2 Dashboard

**Ruta:** `/dashboard`

**Debe mostrar:**

- Total de repuestos activos.
- Cantidad de repuestos bajo stock mínimo.
- Compras pendientes.
- Total de clientes.
- Últimos 5 movimientos.

**Criterios de aceptación:**

- Los datos vienen desde MySQL.
- Las tarjetas no muestran números fijos.
- Los enlaces rápidos llevan a los módulos correspondientes.

## 5.3 Repuestos

**Rutas:**

- `/repuestos`
- `/repuestos/create`
- `/repuestos/edit?id=`

**Campos:**

- Código.
- Número de parte.
- Nombre.
- Descripción.
- Marca.
- Ubicación.
- Precio referencia.
- Stock mínimo.
- Estado.

**Criterios de aceptación:**

- Permite crear repuesto.
- Permite editar repuesto.
- Permite buscar por código, nombre o número de parte.
- No permite códigos duplicados.
- Muestra alerta visual si `stock_actual <= stock_minimo`.

## 5.4 Movimientos de inventario

**Ruta:** `/movimientos/create`

**Campos:**

- Repuesto.
- Tipo: entrada o salida.
- Cantidad.
- Motivo.
- Referencia.

**Reglas:**

- Entrada suma stock.
- Salida resta stock.
- Salida no puede superar existencia.
- Todo movimiento se guarda en `movimientos_inventario`.

**Criterios de aceptación:**

- Al registrar entrada, el stock aumenta.
- Al registrar salida, el stock disminuye.
- Si no hay stock suficiente, se bloquea la operación.
- Se muestra historial por repuesto.

## 5.5 Proveedores

**Rutas:**

- `/proveedores`
- `/proveedores/create`
- `/proveedores/edit?id=`

**Campos:**

- Nombre.
- Contacto.
- Teléfono.
- Correo.
- Dirección.
- Productos ofrecidos.
- Estado.

**Criterios de aceptación:**

- Permite crear y editar proveedores.
- Permite buscar por nombre o contacto.
- No elimina físicamente; cambia estado a inactivo.

## 5.6 Compras

**Rutas:**

- `/compras`
- `/compras/create`
- `/compras/show?id=`
- `/compras/recibir?id=`

**Debe permitir:**

- Seleccionar proveedor.
- Agregar varios repuestos.
- Definir cantidad y precio unitario.
- Calcular subtotal y total.
- Guardar compra pendiente.
- Recibir compra.

**Reglas:**

- Al recibir compra, el sistema registra entradas de inventario.
- Una compra recibida no puede recibirse dos veces.
- Una compra anulada no actualiza inventario.

**Criterios de aceptación:**

- El total se calcula correctamente.
- El detalle queda asociado a la compra.
- Al recibir, aumenta el stock de cada repuesto.
- Se registran movimientos con referencia a la compra.

## 5.7 Clientes

**Rutas:**

- `/clientes`
- `/clientes/create`
- `/clientes/edit?id=`
- `/clientes/show?id=`

**Campos:**

- Nombre.
- Teléfono.
- Correo.
- Dirección.
- Estado.

**Criterios de aceptación:**

- Permite crear y editar clientes.
- Muestra los vehículos asociados.
- Permite buscar por nombre o teléfono.

## 5.8 Vehículos

**Rutas:**

- `/vehiculos/create?cliente_id=`
- `/vehiculos/edit?id=`

**Campos:**

- Cliente.
- Marca.
- Modelo.
- Año.
- Placa.
- Tipo de motor.
- Color.

**Criterios de aceptación:**

- Todo vehículo pertenece a un cliente.
- No permite placas duplicadas.
- Desde la ficha del cliente se pueden ver sus vehículos.

## 5.9 Reportes

**Ruta:** `/reportes`

**Reportes mínimos:**

1. Inventario actual.
2. Repuestos bajo stock mínimo.
3. Movimientos por fecha.
4. Compras por proveedor.
5. Clientes y vehículos registrados.

**Criterios de aceptación:**

- Los reportes tienen filtros básicos.
- Los resultados se muestran en tablas.
- Existe botón “Imprimir” con `window.print()`.
- No se muestran datos inventados.

---

# 6. Reglas de seguridad y calidad

El sistema debe cumplir:

- Proteger rutas privadas con sesión.
- Cerrar sesión correctamente.
- Cifrar contraseñas.
- Validar campos obligatorios.
- Validar cantidades numéricas.
- Validar correo cuando aplique.
- Usar consultas preparadas.
- Mostrar mensajes de error entendibles.
- No mostrar errores internos de PHP al usuario final.

Configuración recomendada para desarrollo:

```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

Configuración recomendada para demo:

```php
ini_set('display_errors', 0);
error_reporting(E_ALL);
```

---

# 7. Flujo de implementación recomendado

## Sprint 1: Base técnica

- Crear estructura de carpetas.
- Crear conexión PDO.
- Crear base de datos.
- Crear login y logout.
- Crear layout Bootstrap común.
- Crear dashboard básico.

**Resultado esperado:** usuario puede iniciar sesión y ver dashboard.

## Sprint 2: Inventario

- CRUD de repuestos.
- Búsqueda.
- Indicador de stock bajo.
- Registro de movimientos.
- Historial de movimientos.

**Resultado esperado:** entradas y salidas actualizan stock correctamente.

## Sprint 3: Proveedores y compras

- CRUD de proveedores.
- Crear compra.
- Agregar detalle.
- Recibir compra.
- Actualizar inventario desde compra recibida.

**Resultado esperado:** compra recibida genera entradas automáticas.

## Sprint 4: Clientes, vehículos y reportes

- CRUD de clientes.
- CRUD de vehículos por cliente.
- Reportes básicos.
- Botón imprimir.
- Capturas finales.

**Resultado esperado:** sistema navegable para defensa de Fase 2.

---

# 8. Capturas requeridas para documento y diapositivas

El agente implementador debe generar estas capturas:

1. `login.png`
2. `dashboard.png`
3. `repuestos-listado.png`
4. `repuesto-formulario.png`
5. `movimiento-inventario.png`
6. `proveedores.png`
7. `compras.png`
8. `clientes-vehiculos.png`
9. `reportes.png`

Las capturas deben mostrar datos reales de la base semilla. No deben estar vacías.

---

# 9. Checklist de aceptación final

Antes de entregar el sistema, verificar:

- [ ] Login funciona con los cuatro usuarios de prueba.
- [ ] Logout destruye la sesión.
- [ ] Dashboard muestra datos desde MySQL.
- [ ] Repuestos permite crear, editar y buscar.
- [ ] Stock bajo se marca visualmente.
- [ ] Entrada de inventario aumenta stock.
- [ ] Salida de inventario disminuye stock.
- [ ] No permite salida mayor a existencia.
- [ ] Proveedores permite crear, editar y buscar.
- [ ] Compra pendiente se guarda con detalle.
- [ ] Compra recibida actualiza inventario.
- [ ] Clientes permite crear, editar y buscar.
- [ ] Vehículos se asocian a clientes.
- [ ] Reportes muestran datos imprimibles.
- [ ] Hay al menos 50 registros de prueba.
- [ ] No aparecen errores PHP visibles durante la demo.
- [ ] La interfaz es responsiva en tamaño laptop.

---

# 10. Prompt para el agente implementador

Usa este prompt si vas a delegar la construcción:

```text
Debes implementar el 50% funcional del Sistema de Gestión de Inventario de Repuestos para el Taller Mecánico San José usando PHP, MySQL y Bootstrap.

Trabaja con metodología Spec-Driven Development. Antes de programar, lee el archivo specs_sdd_50_funcional.md completo y úsalo como contrato. No inventes módulos fuera del alcance. Prioriza que los flujos críticos funcionen: login, dashboard, CRUD de repuestos, movimientos de inventario, proveedores, compras con recepción, clientes, vehículos y reportes imprimibles.

Usa PDO y consultas preparadas. Las contraseñas deben guardarse con password_hash. Protege las rutas con sesión. Implementa validaciones de campos obligatorios, cantidades y stock suficiente. Carga al menos 50 registros de prueba.

Al finalizar, entrega el código funcionando, el script schema.sql, el script seed.sql y capturas reales de las pantallas requeridas: login, dashboard, repuestos, formulario de repuesto, movimientos, proveedores, compras, clientes/vehículos y reportes.

No entregues solo maquetas. El sistema debe guardar, consultar y actualizar datos en MySQL.
```
