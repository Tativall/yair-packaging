# YAIR PACKAGING — Versión Supabase

## Configuración (3 pasos)

### 1. Crear tablas en Supabase
- Supabase → SQL Editor → New query
- Pegar el contenido de `config/install.sql` → Run

### 2. Crear bucket de fotos
- Supabase → Storage → New bucket
- Nombre: `productos`
- Tildá "Public bucket" → Create

### 3. Configurar credenciales
- Supabase → Settings → API
- Copiar "Project URL" y "anon public" key
- Editar `config/supabase.php` y reemplazar:
  - `TU_PROJECT_ID` con tu Project URL completo
  - `TU_ANON_KEY` con tu anon key

### 4. Subir a GitHub y Railway
- Igual que antes — Railway detecta PHP automáticamente

## URLs
- Catálogo: `tudominio/catalogo`
- Admin: `tudominio/admin/login.php` → admin / admin123
