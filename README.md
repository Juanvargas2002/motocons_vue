# MOTOCONS — Proyecto Completo (Backend PHP + Frontend Vue 3)

Sistema web para la gestión integral de un concesionario de motocicletas (**MOTOCONS**): ventas presenciales, créditos y financiación, taller y mantenimientos, garantías, inventario, promociones y gestión de usuarios.

La carpeta `Proyecto/` agrupa dos subproyectos que trabajan juntos:

1. **`motocons/` (Backend + Vistas tradicionales):** Aplicación en **PHP 8 sin framework** con arquitectura **MVC modular** y base de datos **MySQL**. Además de servir la aplicación web clásica, expone endpoints JSON consumidos por el frontend.
2. **`frontend/` (SPA moderna):** Aplicación en **Vue 3 + TypeScript + Vite**, enfocada actualmente en el panel administrativo y el módulo de **Ventas en tienda (`ventas_presenciales`)**, conectada al backend PHP mediante el proxy de Vite.

---

## Estructura general

```text
Proyecto/
├── motocons/                 # Backend PHP 8 + MySQL (MVC modular)
│   ├── config/               # config.ejemplo.php y config.php (local, no se sube a Git)
│   ├── core/                 # Núcleo común: Router, Controlador, Modelo, Database
│   ├── database/
│   │   └── motocons.sql      # Esquema completo de BD y datos de prueba
│   ├── modulos/              # Módulos funcionales independientes
│   │   ├── _plantilla/       # Plantilla base para crear nuevos módulos
│   │   ├── creditos/         # Créditos y financiación
│   │   ├── garantia/         # Consulta y gestión de garantías
│   │   ├── inicio/           # Página de inicio y panel principal
│   │   ├── inventario/       # Control de stock
│   │   ├── login/            # Autenticación y manejo de sesiones
│   │   ├── mantenimiento/    # Taller y mantenimientos
│   │   ├── promociones/      # Descuentos por marca, gama o modelo
│   │   └── ventas_presenciales/ # Punto de venta en tienda + API JSON
│   ├── public/               # Punto de entrada único (index.php) y assets (css/js/img)
│   ├── vistas/               # Layout compartido (encabezado.php y pie.php)
│   ├── AGENTS.md             # Reglas técnicas y de arquitectura del proyecto
│   ├── CONTRIBUTING.md       # Guía de contribución para los grupos
│   ├── DESIGN.md             # Guía de estilo visual (colores, tipografía, componentes)
│   └── README.md             # Documentación específica del backend
│
└── frontend/                 # Frontend SPA (Vue 3 + TypeScript + Vite)
    ├── public/               # Recursos estáticos (favicon, assets/img)
    ├── src/
    │   ├── api/              # Cliente HTTP Axios (ventasApi.ts)
    │   ├── components/       # Sidebar, tablas y formularios de ventas
    │   ├── layouts/          # Layout administrativo (AdminLayout.vue)
    │   ├── router/           # Rutas de Vue Router (src/router/index.ts)
    │   ├── styles/           # Estilos globales (motocons.css)
    │   ├── types/            # Tipos TypeScript (ventas.ts)
    │   ├── utils/            # Utilidades de formato (formato.ts)
    │   └── views/            # Vistas: Login, Dashboard, Listado/Nueva/Ver/Editar Venta
    ├── package.json          # Scripts y dependencias (pnpm)
    └── vite.config.ts        # Configuración de Vite y proxy hacia el backend PHP
```

---

## Cómo se conectan Backend y Frontend

- El backend PHP corre en `http://localhost:8000` sirviendo la carpeta `motocons/public/`.
- Todas las rutas del backend pasan por `index.php?r=modulo/accion` (gestionado por `motocons/core/Router.php`).
- En desarrollo, el frontend (`frontend/vite.config.ts`) configura un **proxy** que redirige todas las peticiones a `/index.php` hacia `http://localhost:8000`, manteniendo `withCredentials: true` para compartir la cookie de sesión PHP (`PHPSESSID`).
- Esto significa que **el backend debe estar encendido** para poder iniciar sesión o usar el frontend en Vue.

---

## Requisitos previos

- **PHP 8+** (con extensión `pdo_mysql` habilitada)
- **MySQL** o **MariaDB** (por ejemplo, XAMPP, Laragon o MySQL local)
- **Node.js** (`^22.18.0` o `>=24.12.0` según `frontend/package.json`)
- **pnpm** (gestor de paquetes usado en `frontend/`)

---

## Puesta en marcha en tu PC

### 1. Base de datos (MySQL)

1. Abre phpMyAdmin, MySQL Workbench o tu cliente MySQL preferido.
2. Importa o ejecuta el archivo `motocons/database/motocons.sql`.
   > **Nota:** Este script elimina y recrea la base de datos `motocons` desde cero con todas las tablas y datos de prueba.

### 2. Configurar y arrancar el Backend (`motocons/`)

1. Entra a `motocons/config/`. Si no existe `config.php`, copia `config.ejemplo.php` como `config.php` y ajusta tus credenciales de MySQL:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_PUERTO', '3306');
   define('DB_NOMBRE', 'motocons');
   define('DB_USUARIO', 'root');
   define('DB_CLAVE', '');
   define('URL_BASE', 'http://localhost:8000');
   ```
2. Abre una terminal en `Proyecto/motocons` y levanta el servidor integrado de PHP:
   ```sh
   php -S localhost:8000 -t public
   ```
3. Verifica en el navegador: `http://localhost:8000`

### 3. Instalar y arrancar el Frontend (`frontend/`)

1. Abre **otra terminal** en `Proyecto/frontend`.
2. Instala las dependencias:
   ```sh
   pnpm install
   ```
3. Inicia el servidor de desarrollo de Vite:
   ```sh
   pnpm dev
   ```
4. Abre la URL que indique Vite en consola (normalmente `http://localhost:5173`).

### Credenciales de prueba

- **Correo:** `admin@motocons.com`
- **Contraseña:** `admin123`
- **Rol:** `admin`

*(En `frontend/`, el acceso al panel de Ventas en tienda requiere rol `admin` o `vendedor`.)*

---

## Guía para trabajar sobre el proyecto

### A. Trabajar en el Backend (`motocons/`)

Antes de escribir código en el backend, revisa los documentos internos dentro de `motocons/`:
- `motocons/CONTRIBUTING.md`: flujo de trabajo por módulos y ramas.
- `motocons/AGENTS.md`: reglas estrictas de arquitectura, seguridad y convenciones.
- `motocons/DESIGN.md`: guía visual para vistas PHP.

#### 1. ¿Cómo funciona el enrutamiento?
`core/Router.php` convierte `?r=modulo/accion` en:
- Archivo: `modulos/<modulo>/controladores/<ModuloEnPascalCase>Controlador.php`
- Método: `<accion>()`

Ejemplo: `?r=ventas_presenciales/apiListar` llama a `VentasPresencialesControlador::apiListar()`.

#### 2. Crear un nuevo módulo
1. Copia `modulos/_plantilla/` y renómbrala con el nombre de tu módulo en minúsculas (ej. `modulos/reportes/`).
2. Renombra `PlantillaControlador.php` a `ReportesControlador.php` (la clase debe llamarse igual que el archivo y extender `Controlador`).
3. Renombra o crea tus modelos en `modelos/` extendiendo `Modelo`.
4. Configura `modulo.php` con el nombre, descripción y ruta (`'ruta' => 'reportes/index'`).

#### 3. Proteger rutas y devolver JSON
En cualquier controlador que extienda `Controlador` (`core/Controlador.php`):
- **Proteger una acción por sesión y rol:**
  ```php
  $this->requiereLogin(['admin', 'vendedor']);
  ```
- **Devolver una vista HTML:**
  ```php
  $this->vista('nombre_vista', ['datos' => $datos]);
  ```
- **Devolver JSON (para el frontend Vue):**
  ```php
  $this->json(['ok' => true, 'datos' => $datos], 200);
  ```

#### 4. Base de datos compartida
- Todas las tablas viven en `motocons/database/motocons.sql`.
- Reutiliza las tablas comunes (`usuario`, `cliente`, `moto`, `venta`, `vehiculo`, `mantenimiento`) en lugar de duplicarlas.
- Si agregas tablas o `ALTER TABLE`, hazlo al final de `motocons.sql` documentando el módulo, grupo y fecha.

---

### B. Trabajar en el Frontend (`frontend/`)

#### 1. Rutas y Vistas (`src/router/index.ts` y `src/views/`)
- Las rutas administrativas usan `AdminLayout.vue` como layout padre (con `AdminSidebar.vue`).
- Actualmente existen las vistas:
  - `/login` → `LoginView.vue`
  - `/` → `DashboardView.vue`
  - `/ventas` → `VentasListadoView.vue`
  - `/ventas/nueva` → `NuevaVentaView.vue`
  - `/ventas/:id` → `VerVentaView.vue`
  - `/ventas/:id/editar` → `EditarVentaView.vue`

#### 2. Consumir nuevos endpoints del Backend
1. Define las interfaces de respuesta/payload en `src/types/` (ej. `src/types/ventas.ts`).
2. Agrega los métodos en `src/api/ventasApi.ts` (o crea un nuevo archivo de API siguiendo el mismo patrón con `axios`, `withCredentials: true` y validación de `application/json`).
3. Usa la ruta `/index.php` con el parámetro `r=modulo/accion` para que el proxy de Vite la envíe al servidor PHP en `localhost:8000`.

#### 3. Comandos útiles en `frontend/`
- **Desarrollo con recarga en caliente:** `pnpm dev`
- **Verificación de tipos TypeScript:** `pnpm type-check`
- **Compilación para producción (type-check + build):** `pnpm build`
- **Previsualizar build de producción:** `pnpm preview`

---

### C. Buenas prácticas al colaborar

1. **No subas archivos locales o sensibles:** `motocons/config/config.php` y `frontend/node_modules/` deben permanecer fuera del repositorio.
2. **Sincroniza la base de datos:** Cada vez que alguien actualice `motocons/database/motocons.sql`, vuelve a ejecutarlo en tu MySQL local para tener las tablas y datos al día.
3. **Prueba ambos lados si tocas APIs:** Si modificas un método `api*` en `VentasPresencialesControlador.php`, verifica que los tipos en `frontend/src/types/ventas.ts` sigan coincidiendo y ejecuta `pnpm type-check` en `frontend/`.
