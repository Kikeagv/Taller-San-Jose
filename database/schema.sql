CREATE DATABASE IF NOT EXISTS taller_san_jose
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE taller_san_jose;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS detalle_servicio_repuesto;
DROP TABLE IF EXISTS servicios;
DROP TABLE IF EXISTS vehiculos;
DROP TABLE IF EXISTS clientes;
DROP TABLE IF EXISTS detalle_compra;
DROP TABLE IF EXISTS compras;
DROP TABLE IF EXISTS movimientos_inventario;
DROP TABLE IF EXISTS repuestos;
DROP TABLE IF EXISTS proveedores;
DROP TABLE IF EXISTS usuarios;
SET FOREIGN_KEY_CHECKS = 1;

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
