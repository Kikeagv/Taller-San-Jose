USE taller_san_jose;

INSERT INTO usuarios (nombre, correo, password_hash, rol) VALUES
('Administrador General', 'admin@taller.test', '$2y$12$dPsh9KWQAx57wwpWqgIb.uQXj.bIQ5sYmM1aRCkIxqSVNOe73zMuK', 'admin'),
('Encargado de Bodega', 'bodega@taller.test', '$2y$12$dPsh9KWQAx57wwpWqgIb.uQXj.bIQ5sYmM1aRCkIxqSVNOe73zMuK', 'bodega'),
('Recepcionista Taller', 'recepcion@taller.test', '$2y$12$dPsh9KWQAx57wwpWqgIb.uQXj.bIQ5sYmM1aRCkIxqSVNOe73zMuK', 'recepcion'),
('Propietario Taller', 'propietario@taller.test', '$2y$12$dPsh9KWQAx57wwpWqgIb.uQXj.bIQ5sYmM1aRCkIxqSVNOe73zMuK', 'propietario');

INSERT INTO proveedores (nombre, contacto, telefono, correo, direccion, productos_ofrecidos) VALUES
('Repuestos Centro', 'Mario López', '2222-1001', 'ventas@repuestoscentro.test', 'San Salvador', 'Filtros, bujías, aceites'),
('AutoPartes Express', 'Claudia Rivas', '2222-1002', 'contacto@autopartes.test', 'Soyapango', 'Frenos, pastillas, discos'),
('Lubricantes del Norte', 'René Salazar', '2222-1003', 'pedidos@lubrinorte.test', 'Mejicanos', 'Aceites y aditivos'),
('Importadora MotorMax', 'Patricia Méndez', '2222-1004', 'ventas@motormax.test', 'Santa Tecla', 'Sensores, bombas, correas'),
('Frenos y Más', 'Sofía Hernández', '2222-1005', 'info@frenosymas.test', 'San Miguel', 'Sistema de frenos'),
('Baterías El Volante', 'Óscar Peña', '2222-1006', 'ventas@elvolante.test', 'Ilopango', 'Baterías y accesorios'),
('Suspensiones San José', 'Ana Duarte', '2222-1007', 'contacto@suspensiones.test', 'Apopa', 'Amortiguadores, bujes'),
('ElectroAuto SV', 'Héctor Molina', '2222-1008', 'servicio@electroauto.test', 'Antiguo Cuscatlán', 'Componentes eléctricos');

INSERT INTO repuestos (codigo, numero_parte, nombre, descripcion, marca, ubicacion, precio_referencia, stock_actual, stock_minimo) VALUES
('REP-001', 'OF-100', 'Filtro de aceite', 'Filtro estándar para motor gasolina', 'Mann', 'A1', 8.50, 18, 6),
('REP-002', 'AF-220', 'Filtro de aire', 'Filtro rectangular para sedán', 'Bosch', 'A2', 12.25, 9, 5),
('REP-003', 'SP-430', 'Bujía iridium', 'Bujía de alto rendimiento', 'NGK', 'A3', 6.75, 30, 10),
('REP-004', 'BP-101', 'Pastillas de freno delanteras', 'Juego de pastillas delanteras', 'Brembo', 'B1', 32.00, 4, 5),
('REP-005', 'BD-301', 'Disco de freno', 'Disco ventilado', 'Brembo', 'B2', 46.80, 8, 4),
('REP-006', 'OC-10W30', 'Aceite 10W30', 'Aceite semisintético cuarto', 'Castrol', 'C1', 7.20, 24, 12),
('REP-007', 'OC-20W50', 'Aceite 20W50', 'Aceite mineral cuarto', 'Shell', 'C2', 6.80, 20, 10),
('REP-008', 'BAT-12V', 'Batería 12V', 'Batería 12 voltios 600 CCA', 'LTH', 'D1', 95.00, 5, 3),
('REP-009', 'ALT-90A', 'Alternador 90A', 'Alternador compacto', 'Denso', 'D2', 140.00, 2, 2),
('REP-010', 'COR-001', 'Correa de distribución', 'Correa dentada', 'Gates', 'E1', 28.75, 7, 4),
('REP-011', 'BOM-AG', 'Bomba de agua', 'Bomba para sistema de enfriamiento', 'GMB', 'E2', 38.00, 6, 3),
('REP-012', 'TERM-88', 'Termostato', 'Termostato 88 grados', 'Motorad', 'E3', 11.50, 12, 5),
('REP-013', 'AMO-F', 'Amortiguador delantero', 'Amortiguador hidráulico', 'KYB', 'F1', 58.00, 6, 4),
('REP-014', 'AMO-T', 'Amortiguador trasero', 'Amortiguador hidráulico trasero', 'KYB', 'F2', 54.00, 6, 4),
('REP-015', 'SEN-O2', 'Sensor de oxígeno', 'Sensor universal', 'Bosch', 'G1', 42.00, 3, 3),
('REP-016', 'LIM-PB', 'Limpiaparabrisas', 'Par de escobillas 22 pulgadas', 'Trico', 'G2', 13.00, 14, 6),
('REP-017', 'FUS-20', 'Fusible 20A', 'Fusible automotriz', 'Generic', 'H1', 0.50, 80, 20),
('REP-018', 'REL-12V', 'Relay 12V', 'Relay universal 4 pines', 'Hella', 'H2', 4.20, 16, 8),
('REP-019', 'MANG-RAD', 'Manguera radiador', 'Manguera superior radiador', 'Gates', 'I1', 18.40, 9, 5),
('REP-020', 'TAP-RAD', 'Tapón radiador', 'Tapón 0.9 bar', 'Stant', 'I2', 5.25, 15, 6);

INSERT INTO clientes (nombre, telefono, correo, direccion) VALUES
('Carlos Mendoza', '7000-1001', 'carlos@example.test', 'San Salvador'),
('Lucía Ramírez', '7000-1002', 'lucia@example.test', 'Soyapango'),
('Jorge Pineda', '7000-1003', 'jorge@example.test', 'Santa Tecla'),
('María Aguilar', '7000-1004', 'maria@example.test', 'Mejicanos'),
('Nelson Rivera', '7000-1005', 'nelson@example.test', 'Apopa'),
('Rosa Castillo', '7000-1006', 'rosa@example.test', 'Ilopango'),
('David Escobar', '7000-1007', 'david@example.test', 'San Marcos'),
('Verónica Flores', '7000-1008', 'vero@example.test', 'Antiguo Cuscatlán'),
('Mauricio Cruz', '7000-1009', 'mauricio@example.test', 'Zaragoza'),
('Elena Torres', '7000-1010', 'elena@example.test', 'Ciudad Delgado');

INSERT INTO vehiculos (id_cliente, marca, modelo, anio, placa, tipo_motor, color) VALUES
(1, 'Toyota', 'Corolla', 2016, 'P123-456', 'Gasolina 1.8', 'Gris'),
(2, 'Nissan', 'Frontier', 2019, 'P234-567', 'Diesel 2.5', 'Blanco'),
(3, 'Hyundai', 'Accent', 2015, 'P345-678', 'Gasolina 1.6', 'Azul'),
(4, 'Kia', 'Sportage', 2018, 'P456-789', 'Gasolina 2.0', 'Negro'),
(5, 'Honda', 'Civic', 2017, 'P567-890', 'Gasolina 1.8', 'Rojo'),
(6, 'Mazda', 'BT-50', 2020, 'P678-901', 'Diesel 3.2', 'Plata'),
(7, 'Chevrolet', 'Spark', 2014, 'P789-012', 'Gasolina 1.2', 'Verde'),
(8, 'Ford', 'Ranger', 2021, 'P890-123', 'Diesel 2.2', 'Blanco'),
(9, 'Mitsubishi', 'L200', 2018, 'P901-234', 'Diesel 2.4', 'Gris'),
(10, 'Suzuki', 'Swift', 2019, 'P012-345', 'Gasolina 1.4', 'Rojo');

INSERT INTO compras (id_proveedor, id_usuario, fecha_compra, estado, total_estimado, observaciones) VALUES
(1, 2, '2026-05-01', 'recibida', 199.25, 'Compra inicial de filtros y bujías'),
(2, 2, '2026-05-03', 'pendiente', 188.80, 'Pedido de frenos'),
(3, 2, '2026-05-05', 'recibida', 140.00, 'Aceites para bodega'),
(4, 2, '2026-05-08', 'pendiente', 224.00, 'Sensores y correas'),
(6, 2, '2026-05-10', 'recibida', 285.00, 'Baterías'),
(7, 2, '2026-05-12', 'pendiente', 336.00, 'Suspensión');

INSERT INTO detalle_compra (id_compra, id_repuesto, cantidad, precio_unitario, subtotal) VALUES
(1, 1, 10, 8.25, 82.50),
(1, 2, 5, 11.75, 58.75),
(1, 3, 8, 7.25, 58.00),
(2, 4, 4, 32.00, 128.00),
(2, 5, 2, 30.40, 60.80),
(3, 6, 10, 7.20, 72.00),
(3, 7, 10, 6.80, 68.00),
(4, 10, 4, 28.00, 112.00),
(4, 15, 2, 56.00, 112.00),
(5, 8, 3, 95.00, 285.00),
(6, 13, 3, 58.00, 174.00),
(6, 14, 3, 54.00, 162.00);

INSERT INTO movimientos_inventario (id_repuesto, id_usuario, tipo_movimiento, cantidad, motivo, referencia) VALUES
(1, 2, 'entrada', 10, 'Recepción de compra', 'Compra #1'),
(2, 2, 'entrada', 5, 'Recepción de compra', 'Compra #1'),
(3, 2, 'entrada', 8, 'Recepción de compra', 'Compra #1'),
(6, 2, 'entrada', 10, 'Recepción de compra', 'Compra #3'),
(7, 2, 'entrada', 10, 'Recepción de compra', 'Compra #3'),
(8, 2, 'entrada', 3, 'Recepción de compra', 'Compra #5'),
(1, 2, 'salida', 2, 'Servicio preventivo', 'Orden taller'),
(3, 2, 'salida', 4, 'Cambio de bujías', 'Orden taller'),
(6, 2, 'salida', 3, 'Cambio de aceite', 'Orden taller'),
(7, 2, 'salida', 2, 'Cambio de aceite', 'Orden taller'),
(17, 2, 'salida', 5, 'Reparación eléctrica', 'Orden taller'),
(18, 2, 'salida', 1, 'Reparación eléctrica', 'Orden taller'),
(16, 2, 'salida', 2, 'Mantenimiento', 'Orden taller'),
(12, 2, 'salida', 1, 'Sistema enfriamiento', 'Orden taller'),
(19, 2, 'salida', 1, 'Sistema enfriamiento', 'Orden taller'),
(20, 2, 'salida', 1, 'Sistema enfriamiento', 'Orden taller'),
(10, 2, 'salida', 1, 'Cambio distribución', 'Orden taller'),
(11, 2, 'salida', 1, 'Cambio bomba agua', 'Orden taller'),
(4, 2, 'salida', 1, 'Revisión frenos', 'Orden taller'),
(5, 2, 'salida', 1, 'Revisión frenos', 'Orden taller');

INSERT INTO servicios (id_vehiculo, fecha_servicio, descripcion, kilometraje, observaciones) VALUES
(1, '2026-05-02', 'Mantenimiento preventivo', 85000, 'Cambio de filtros y aceite'),
(3, '2026-05-04', 'Revisión de frenos', 102000, 'Ruido al frenar'),
(5, '2026-05-09', 'Reparación eléctrica', 76000, 'Falla intermitente de luces');

INSERT INTO detalle_servicio_repuesto (id_servicio, id_repuesto, cantidad_usada) VALUES
(1, 1, 1),
(1, 6, 4),
(2, 4, 1),
(2, 5, 1),
(3, 17, 5),
(3, 18, 1);
