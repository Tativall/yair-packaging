-- =====================================================
-- YAIR PACKAGING — Supabase SQL
-- Ejecutar en Supabase → SQL Editor → New query
-- =====================================================

-- Categorías
CREATE TABLE IF NOT EXISTS categorias (
    id     SERIAL PRIMARY KEY,
    nombre TEXT NOT NULL,
    icono  TEXT DEFAULT '📦',
    color  TEXT DEFAULT '#fff3e0',
    orden  INTEGER DEFAULT 0,
    activa BOOLEAN DEFAULT true
);

-- Productos
CREATE TABLE IF NOT EXISTS productos (
    id           SERIAL PRIMARY KEY,
    nombre       TEXT NOT NULL,
    descripcion  TEXT,
    categoria_id INTEGER REFERENCES categorias(id),
    precio       NUMERIC DEFAULT 0,
    unidad       TEXT DEFAULT 'unid',
    medidas      TEXT,
    etiqueta     TEXT DEFAULT '',
    emoji        TEXT DEFAULT '📦',
    foto         TEXT DEFAULT '',
    activo       BOOLEAN DEFAULT true,
    orden        INTEGER DEFAULT 0,
    created_at   TIMESTAMPTZ DEFAULT NOW(),
    updated_at   TIMESTAMPTZ DEFAULT NOW()
);

-- Pedidos
CREATE TABLE IF NOT EXISTS pedidos (
    id               SERIAL PRIMARY KEY,
    codigo           TEXT NOT NULL UNIQUE,
    nombre           TEXT NOT NULL,
    telefono         TEXT NOT NULL,
    email            TEXT,
    empresa          TEXT,
    producto_nombre  TEXT,
    cantidad         TEXT,
    medida           TEXT,
    notas            TEXT,
    via              TEXT DEFAULT 'web',
    estado           TEXT DEFAULT 'nuevo',
    ip               TEXT,
    created_at       TIMESTAMPTZ DEFAULT NOW(),
    updated_at       TIMESTAMPTZ DEFAULT NOW()
);

-- Configuración
CREATE TABLE IF NOT EXISTS settings (
    id         SERIAL PRIMARY KEY,
    clave      TEXT NOT NULL UNIQUE,
    valor      TEXT,
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- Datos iniciales: categorías
INSERT INTO categorias (nombre, icono, color, orden) VALUES
('Cartones',   '📦', '#fff3e0', 1),
('Plásticos',  '🧴', '#e0f2fe', 2),
('Isopor',     '🔲', '#f0fdf4', 3),
('Accesorios', '🛒', '#f3e8ff', 4)
ON CONFLICT DO NOTHING;

-- Datos iniciales: configuración
INSERT INTO settings (clave, valor) VALUES
('nombre_negocio', 'Yair Packaging'),
('slogan',         'Embalajes profesionales para tu negocio'),
('whatsapp',       '595981000000'),
('email_contacto', 'yairpackaging@gmail.com'),
('email_pedidos',  'yairpackaging@gmail.com'),
('admin_password', 'admin123'),
('direccion',      'Asunción, Paraguay'),
('horario',        'Lunes a Viernes 8:00 - 18:00')
ON CONFLICT (clave) DO NOTHING;

-- Datos iniciales: productos
INSERT INTO productos (nombre, descripcion, categoria_id, precio, unidad, medidas, etiqueta, emoji) VALUES
('Caja Corrugada Simple', 'Ideal para mudanzas, envíos y almacenamiento general.',        1, 8500,  'unid',    '30×20×20, 40×30×30, 60×40×40, A medida', 'popular', '📦'),
('Caja Doble Pared',      'Mayor resistencia para cargas pesadas.',                       1, 18000, 'unid',    '50×40×40, 80×60×60, A medida',            '',        '🗂️'),
('Caja para E-commerce',  'Diseñada para envíos. Cierre seguro, sin cinta.',              1, 7000,  'unid',    'Pequeño, Mediano, Grande',                'popular', '📬'),
('Plancha de Cartón',     'Para separadores, protección y armado de embalajes.',          1, 4000,  'unid',    '1m×1m, 1.2m×0.8m, A medida',             'oferta',  '🃏'),
('Film Stretch',          'Para palletizar y asegurar cargas. Alta extensibilidad.',      2, 65000, 'rollo',   '45cm×300m, 50cm×500m, Manual/Máquina',   'popular', '🌀'),
('Plástico Burbuja',      'Protección acolchada para artículos frágiles.',                2, 85000, 'rollo',   '50cm×50m, 100cm×50m, A medida',           '',        '🫧'),
('Bolsas de Polietileno', 'Transparentes, con cierre, autoadhesivo.',                    2, 3500,  '100 unid','10×15cm, 20×30cm, 40×60cm, A medida',    'oferta',  '🛍️'),
('Plancha de Isopor',     'Para aislación térmica, construcción y embalaje.',             3, 15000, 'unid',    '1m×0.5m×1cm, 1m×0.5m×2cm',               'popular', '⬜'),
('Caja Térmica',          'Para alimentos, medicamentos y productos refrigerados.',       3, 35000, 'unid',    '5L, 15L, 30L, 50L',                      '',        '🧊'),
('Cinta de Embalaje',     'Transparente y marrón. Para cierre de cajas.',                4, 12000, 'rollo',   '48mm×90m, 48mm×150m, 72mm×90m',          'popular', '🟨'),
('Fleje Plástico',        'Para asegurar pallets y bultos. Manual y máquina.',            4, 45000, 'caja',    '12mm, 16mm, 19mm',                        '',        '🔗')
ON CONFLICT DO NOTHING;

-- Acceso público de lectura (necesario para que el catálogo funcione)
ALTER TABLE productos  ENABLE ROW LEVEL SECURITY;
ALTER TABLE categorias ENABLE ROW LEVEL SECURITY;
ALTER TABLE pedidos    ENABLE ROW LEVEL SECURITY;
ALTER TABLE settings   ENABLE ROW LEVEL SECURITY;

CREATE POLICY "lectura_publica_productos"  ON productos  FOR SELECT USING (true);
CREATE POLICY "lectura_publica_categorias" ON categorias FOR SELECT USING (true);
CREATE POLICY "escritura_publica_pedidos"  ON pedidos    FOR INSERT WITH CHECK (true);
CREATE POLICY "lectura_publica_settings"   ON settings   FOR SELECT USING (true);
CREATE POLICY "escritura_settings"         ON settings   FOR ALL    USING (true);
CREATE POLICY "escritura_productos"        ON productos   FOR ALL    USING (true);
CREATE POLICY "escritura_pedidos"          ON pedidos     FOR ALL    USING (true);
