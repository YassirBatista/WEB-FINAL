-- NEXUS STELLAR SHIPYARDS - Base de Datos
-- Ejecutar primero: CREATE DATABASE nexus_stellar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE nexus_stellar;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- 1. TABLAS BASE (sin dependencias)
-- ============================================

DROP TABLE IF EXISTS movimientos_inventario;
DROP TABLE IF EXISTS transacciones;
DROP TABLE IF EXISTS alertas;
DROP TABLE IF EXISTS ordenes;
DROP TABLE IF EXISTS reparaciones;
DROP TABLE IF EXISTS tecnicos;
DROP TABLE IF EXISTS piezas;
DROP TABLE IF EXISTS naves;
DROP TABLE IF EXISTS hangares;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS facciones;

-- Facciones
CREATE TABLE facciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    color VARCHAR(7) DEFAULT '#00D9FF',
    color_secundario VARCHAR(7) DEFAULT '#FF4D5A',
    descripcion TEXT,
    logo VARCHAR(255)
);

-- Usuarios (Admin + Clientes)
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('admin','cliente') NOT NULL DEFAULT 'cliente',
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    faccion_id INT,
    saldo DECIMAL(15,2) DEFAULT 0,
    avatar VARCHAR(255) DEFAULT 'default_avatar.png',
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (faccion_id) REFERENCES facciones(id)
);

-- Hangares
CREATE TABLE hangares (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nivel INT NOT NULL,
    nombre VARCHAR(50),
    tipo_naves VARCHAR(100),
    capacidad_total INT NOT NULL,
    ocupacion_actual INT DEFAULT 0,
    imagen VARCHAR(255),
    estado_operativo ENUM('operativo','mantenimiento','danado') DEFAULT 'operativo',
    UNIQUE KEY (nivel)
);

-- Técnicos
CREATE TABLE tecnicos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    avatar VARCHAR(255) DEFAULT 'tech_default.png',
    rango ENUM('cadete','tecnico','especialista','ingeniero_jefe') NOT NULL,
    especialidad VARCHAR(100),
    nivel INT DEFAULT 1,
    reparaciones_completadas INT DEFAULT 0,
    horas_servicio INT DEFAULT 0,
    estado ENUM('disponible','en_reparacion','en_diagnostico','descansando') DEFAULT 'disponible'
);

-- Piezas / Suministros
CREATE TABLE piezas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    categoria ENUM('motores','reactores','escudos','blindajes','navegacion','armas','bahias','especiales') NOT NULL,
    imagen VARCHAR(255),
    stock INT DEFAULT 0,
    precio DECIMAL(12,2) DEFAULT 0,
    estado ENUM('disponible','stock_bajo','agotado') DEFAULT 'disponible',
    faccion_exclusiva_id INT,
    descripcion TEXT,
    FOREIGN KEY (faccion_exclusiva_id) REFERENCES facciones(id)
);

-- Naves
CREATE TABLE naves (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(20) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    tipo ENUM('caza','fragata','acorazado','nodriza','comercial','transporte','crucero','portanaves') NOT NULL,
    imagen VARCHAR(255),
    cliente_id INT,
    hangar_nivel INT,
    estado ENUM('operativa','en_reparacion','esperando_piezas','diagnostico','en_pruebas','pendiente') DEFAULT 'pendiente',
    tiempo_restante INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id),
    FOREIGN KEY (hangar_nivel) REFERENCES hangares(nivel)
);

-- Reparaciones
CREATE TABLE reparaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nave_id INT NOT NULL,
    tecnico_id INT,
    estado ENUM('en_reparacion','esperando_piezas','diagnostico','en_pruebas','pendiente','completada') DEFAULT 'pendiente',
    tiempo_estimado INT DEFAULT 0,
    tiempo_restante INT DEFAULT 0,
    costo DECIMAL(12,2) DEFAULT 0,
    descripcion TEXT,
    fecha_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_fin TIMESTAMP NULL,
    FOREIGN KEY (nave_id) REFERENCES naves(id),
    FOREIGN KEY (tecnico_id) REFERENCES tecnicos(id)
);

-- Órdenes
CREATE TABLE ordenes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    cliente_id INT NOT NULL,
    nave_id INT,
    estado ENUM('en_reparacion','esperando_piezas','diagnostico','en_pruebas','pendiente','completada','cancelada') DEFAULT 'pendiente',
    prioridad ENUM('baja','media','alta','critica') DEFAULT 'media',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id),
    FOREIGN KEY (nave_id) REFERENCES naves(id)
);

-- Alertas
CREATE TABLE alertas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tipo ENUM('stock_bajo','hangar_lleno','tiempo_excedido','tecnico_no_disponible','pago_pendiente','sistema') NOT NULL,
    mensaje TEXT NOT NULL,
    nivel ENUM('critico','advertencia','info') DEFAULT 'info',
    leida BOOLEAN DEFAULT FALSE,
    usuario_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Inventario / Movimientos
CREATE TABLE movimientos_inventario (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pieza_id INT,
    tipo ENUM('entrada','salida','ajuste') NOT NULL,
    cantidad INT NOT NULL,
    usuario_id INT,
    descripcion TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pieza_id) REFERENCES piezas(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Finanzas / Transacciones
CREATE TABLE transacciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tipo ENUM('ingreso','egreso','pago_cliente','compra_proveedor','salario') NOT NULL,
    monto DECIMAL(15,2) NOT NULL,
    concepto TEXT,
    cliente_id INT,
    orden_id INT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id),
    FOREIGN KEY (orden_id) REFERENCES ordenes(id)
);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- 2. DATOS DEMO
-- ============================================

INSERT INTO facciones (nombre, color, color_secundario, descripcion) VALUES
('Imperio Solaris', '#FF4D5A', '#FFB800', 'La faccion dominante del sector central. Tecnologia avanzada y disciplina militar.'),
('Confederacion Umbra', '#9D4DFF', '#00D9FF', 'Alianza de sistemas perifericos. Especialistas en sigilo y tecnologia de punta.'),
('Corporaciones Independientes', '#FFB800', '#00FF9C', 'Conglomerado mercantil neutral. Controlan las rutas comerciales principales.'),
('Mercenarios Galacticos', '#00FF9C', '#FFB800', 'Fuerzas de combate independientes disponibles para el mejor postor.'),
('Liga Comercial Interestelar', '#00D9FF', '#FFB800', 'Coalicion de comerciantes y transportistas. Neutral en el conflicto.');

INSERT INTO usuarios (username, password_hash, rol, nombre, email, faccion_id, saldo, avatar) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Adm. Vasquez', 'admin@nexus.com', 1, 2458750.00, 'admin_vasquez.jpg');

INSERT INTO usuarios (username, password_hash, rol, nombre, email, faccion_id, saldo, avatar) VALUES
('cmd_solaris', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente', 'Cmd. Kaelen', 'kaelen@imperio.sol', 1, 850000.00, 'cliente_1.jpg'),
('cmd_umbra', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente', 'Cmd. Vex', 'vex@umbra.conf', 2, 420000.00, 'cliente_2.jpg'),
('cmd_corp', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente', 'Cmd. Sterling', 'sterling@corp.ind', 3, 1200000.00, 'cliente_3.jpg');

INSERT INTO hangares (nivel, nombre, tipo_naves, capacidad_total, ocupacion_actual, imagen, estado_operativo) VALUES
(3, 'Hangar Nivel 3', 'Cazas, Interceptores, Exploradores, Comerciales ligeras, Transportes ligeros', 30, 30, 'hangar_nivel3.jpg', 'operativo'),
(2, 'Hangar Nivel 2', 'Fragatas, Corbetas, Escoltas, Comerciales pesadas, Transportes pesados, Acorazados ligeros', 15, 10, 'hangar_nivel2.jpg', 'operativo'),
(1, 'Hangar Nivel 1', 'Nodrizas, Naves insignia, Portanaves, Cruceros de batalla, Acorazados pesados', 5, 2, 'hangar_nivel1.jpg', 'operativo');

INSERT INTO tecnicos (nombre, avatar, rango, especialidad, nivel, reparaciones_completadas, horas_servicio, estado) VALUES
('J. Riker', 'tech_1.jpg', 'ingeniero_jefe', 'Propulsion Cuantica', 5, 847, 12400, 'en_reparacion'),
('M. Torres', 'tech_2.jpg', 'especialista', 'Sistemas de Armas', 4, 623, 9800, 'disponible'),
('K. O''Brien', 'tech_3.jpg', 'especialista', 'Blindajes y Escudos', 4, 591, 8900, 'en_diagnostico'),
('T. Pol', 'tech_4.jpg', 'tecnico', 'Navegacion', 3, 342, 5600, 'disponible'),
('H. Scott', 'tech_5.pjpg', 'tecnico', 'Reactores', 3, 298, 4800, 'descansando'),
('L. Carey', 'tech_6.jpg', 'cadete', 'Mantenimiento General', 1, 45, 800, 'disponible'),
('D. Kim', 'tech_7.jpg', 'especialista', 'Motores', 4, 512, 7200, 'en_reparacion');

INSERT INTO piezas (codigo, nombre, categoria, imagen, stock, precio, estado, faccion_exclusiva_id, descripcion) VALUES
('RAC-IV', 'Reactor Antimateria MK-IV', 'reactores', 'pieza_reactor.jpg', 3, 450000, 'stock_bajo', NULL, 'Reactor de alta potencia para naves capitales.'),
('MC-XR9', 'Motor Cuantico XR-9', 'motores', 'pieza_motor.jpg', 12, 125000, 'disponible', NULL, 'Propulsion cuantica para naves medianas.'),
('ED-Z12', 'Escudo Deflector Z-12', 'escudos', 'pieza_escudo.jpg', 8, 89000, 'disponible', NULL, 'Escudo de deflector de clase pesada.'),
('CL-MX7', 'Canon Laser MX-7', 'armas', 'pieza_laser.jpg', 15, 67000, 'disponible', 1, 'Armamento exclusivo Imperio Solaris.'),
('BT-T85', 'Blindaje Titanio TI-85', 'blindajes', 'pieza_blindaje.jpg', 6, 34000, 'stock_bajo', NULL, 'Blindaje reforzado de aleacion titanio.'),
('NA-SG1', 'Sistema de Navegacion SG-1', 'navegacion', 'pieza_navegacion.jpg', 20, 22000, 'disponible', NULL, 'Sistema de navegacion interestelar.'),
('BA-HK2', 'Bahia de Carga HK-2', 'bahias', 'pieza_bahia.jpg', 4, 55000, 'stock_bajo', NULL, 'Bahia modular para transportes pesados.'),
('ES-UMB', 'Cloaking Device Umbra', 'especiales', 'pieza_cloak.jpg', 2, 780000, 'stock_bajo', 2, 'Sistema de sigilo avanzado. Exclusivo Confederacion Umbra.');

INSERT INTO naves (codigo, nombre, tipo, imagen, cliente_id, hangar_nivel, estado, tiempo_restante) VALUES
('AX-221', 'AX-221 "Valkyrie"', 'acorazado', 'nave_valkyrie.jpg', 2, 2, 'en_reparacion', 2732),
('FRG-77', 'FRG-77 "Eclipse"', 'fragata', 'nave_eclipse.jpg', 2, 2, 'esperando_piezas', 4415),
('CN-514', 'CN-514 "Venture"', 'comercial', 'nave_venture.jpg', 3, 3, 'diagnostico', 1345),
('LIG-44', 'LIG-44 "Raptor"', 'caza', 'nave_raptor.jpg', 2, 3, 'en_pruebas', 2710),
('TR-88', 'TR-88 "Cargo Master"', 'transporte', 'nave_cargo.jpg', 3, 3, 'pendiente', 21600),
('INT-01', 'Interceptor MK-II', 'caza', 'nave_interceptor.jpg', 2, 3, 'operativa', 0),
('COR-15', 'Corbeta "Shadow"', 'fragata', 'nave_shadow.jpg', 3, 2, 'operativa', 0);

INSERT INTO reparaciones (nave_id, tecnico_id, estado, tiempo_estimado, tiempo_restante, costo, descripcion) VALUES
(1, 1, 'en_reparacion', 3600, 2732, 125000, 'Reparacion de sistema de propulsion y blindaje frontal.'),
(2, 7, 'esperando_piezas', 7200, 4415, 89000, 'Esperando Reactor Antimateria MK-IV.'),
(3, 3, 'diagnostico', 1800, 1345, 45000, 'Diagnostico completo de sistemas de navegacion.'),
(4, 1, 'en_pruebas', 3600, 2710, 67000, 'Pruebas de vuelo post-reparacion de motores.'),
(5, NULL, 'pendiente', 21600, 21600, 34000, 'Pendiente de asignacion de tecnico.');

INSERT INTO ordenes (codigo, cliente_id, nave_id, estado, prioridad) VALUES
('OR-2926-0091', 2, 1, 'en_reparacion', 'alta'),
('OR-2926-0090', 2, 2, 'esperando_piezas', 'media'),
('OR-2926-0089', 3, 3, 'diagnostico', 'media'),
('OR-2926-0088', 2, 4, 'en_pruebas', 'baja'),
('OR-2926-0087', 3, 5, 'pendiente', 'alta');

INSERT INTO alertas (tipo, mensaje, nivel, leida, usuario_id) VALUES
('stock_bajo', 'Stock bajo: Reactor Antimateria MK-IV (3 unidades restantes)', 'critico', FALSE, NULL),
('tecnico_no_disponible', 'Tecnico asignado no disponible para reparacion FRG-77', 'advertencia', FALSE, NULL),
('hangar_lleno', 'Hangar Nivel 2: 90% de ocupacion alcanzada', 'advertencia', FALSE, NULL),
('stock_bajo', 'Stock bajo: Blindaje Titanio TI-85 (6 unidades restantes)', 'advertencia', FALSE, NULL),
('pago_pendiente', 'Pago pendiente de orden OR-2926-0087', 'critico', FALSE, 3);

INSERT INTO transacciones (tipo, monto, concepto, fecha) VALUES
('ingreso', 1250000, 'Reparaciones y servicios - Semana 1', '2026-06-01 10:00:00'),
('ingreso', 980000, 'Reparaciones y servicios - Semana 2', '2026-06-08 10:00:00'),
('ingreso', 1450000, 'Reparaciones y servicios - Semana 3', '2026-06-15 10:00:00'),
('ingreso', 1870000, 'Reparaciones y servicios - Semana 4', '2026-06-22 10:00:00'),
('egreso', 450000, 'Compra de suministros - Reactores', '2026-06-10 14:00:00'),
('egreso', 230000, 'Salarios tecnicos', '2026-06-25 09:00:00');