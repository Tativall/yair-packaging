# YAIR PACKAGING — Sistema de Catálogo

## Las dos URLs del sistema

### Para los clientes de Yair (catálogo público)
```
https://tudominio.com/catalogo
```
- Solo pueden VER productos y hacer pedidos
- No pueden cambiar nada
- Sin botón de admin visible
- Este es el link que Yair comparte en WhatsApp, Instagram, etc.

### Para Yair (panel admin)
```
https://tudominio.com/admin
  o
https://tudominio.com/admin/login.php
```
- Solo Yair conoce este link
- Requiere usuario + contraseña
- Todo lo que Yair cambia aquí se refleja INSTANTÁNEAMENTE en el catálogo

---

## Instalación en Railway (gratis)

1. Subir esta carpeta a GitHub (nuevo repositorio)
2. railway.app → New Project → Deploy from GitHub
3. Settings → Networking → Generate Domain
4. Abrir la URL → el sistema se instala solo
5. Admin → usuario: `admin` / contraseña: `admin123`
6. **IMPORTANTE: Cambiar la contraseña en Ajustes antes de entregar al cliente**

## Instalación en hosting cPanel

1. Comprimir en ZIP → subir a public_html → extraer
2. Dar permisos 755 a `assets/uploads/`
3. Abrir el sitio → se instala solo
4. **IMPORTANTE: Asegurarse que mod_rewrite esté activado en el hosting**

---

## Cómo funciona la sincronización

El catálogo y el admin usan la MISMA base de datos (SQLite).
No hay que sincronizar nada — cuando Yair guarda un cambio,
el catálogo lo muestra en el próximo refresh del cliente.

## Archivos que puede modificar la desarrolladora

| Archivo | Para qué |
|---------|---------|
| `config/database.php` | Cambiar ruta de BD o datos iniciales |
| `assets/css/style.css` | Cambiar colores, fuentes, diseño |
| `index.php` | Modificar el catálogo público |
| `admin/*.php` | Modificar el panel admin |
| `api/*.php` | Modificar la lógica de datos |

## Backup
Copiar el archivo `data/yair_packaging.db` — contiene todo.
