# Proyecto base — Evaluación Final Análisis de Sistemas I

Proyecto **Laravel 12 + Vue 3 (Vite)** con **JWT**, **Spatie Laravel Permission** y **Stancl Tenancy** (tenant identificado por cabecera `X-Tenant-ID`). Esta base se entrega para que el estudiante analice la estructura existente y desarrolle el módulo asignado por el docente.

---

## Arquitectura construida

La aplicación sigue un modelo **SPA + API REST**: el navegador carga una única vista Blade que monta Vue; el backend expone JSON bajo `/api/v1`.

### Vista general

| Capa                    | Tecnología                              | Para qué sirve                                                                                                                                                    |
| ----------------------- | --------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Backend / API**       | Laravel 12                              | Punto único de negocio, persistencia, seguridad y contratos HTTP JSON.                                                                                            |
| **Autenticación API**   | `tymon/jwt-auth`                        | Emite y valida tokens JWT en el guard `api`; no usa sesiones para el API.                                                                                         |
| **Autorización (RBAC)** | `spatie/laravel-permission`             | Roles y permisos sobre el modelo `User` (guard `api`).                                                                                                            |
| **Multitenancy base**   | `stancl/tenancy` + tabla `tenants`      | Modelo `Tenant` y columna `tenant_id` en usuarios. El tenant activo se **indica en cada petición** con `X-Tenant-ID` (sin bases de datos separadas en esta fase). |
| **Middleware propio**   | `TenantMiddleware`, `JwtAuth`           | `TenantMiddleware` resuelve y valida el tenant por cabecera; `JwtAuth` protege rutas con JWT y coherencia tenant–token.                                           |
| **Frontend**            | Vue 3 + Vue Router + Pinia              | SPA: rutas del lado cliente, estado global (p. ej. sesión / token) y pantallas como login.                                                                        |
| **Build frontend**      | Vite 7 + `@vitejs/plugin-vue`           | Empaqueta JS/CSS; alias `@` apunta a `resources/js`.                                                                                                              |
| **Cliente HTTP**        | Axios (`resources/js/plugins/axios.js`) | Llama al API con `Authorization: Bearer` y `X-Tenant-ID` según lo guardado en `localStorage`.                                                                     |
| **Vista shell**         | `resources/views/app.blade.php`         | Inyecta el bundle Vite y el `<div id="app">` donde Vue se monta.                                                                                                  |
| **Rutas web**           | `routes/web.php`                        | Cualquier ruta devuelve la misma SPA (fallback) para que Vue Router maneje `/`, `/login`, etc.                                                                    |

### Flujo típico de una petición

1. El usuario (o el formulario de login) fija el **ID del tenant**; Axios envía `X-Tenant-ID` y, si hay sesión, el **JWT** en `Authorization`.
2. Laravel aplica `TenantMiddleware` donde corresponda: si el tenant no existe, responde 404 JSON.
3. En rutas protegidas, `jwt.auth` valida el token; opcionalmente se compara el tenant del header con el del usuario del token.
4. Las respuestas del API son siempre **JSON**.

### Estructura relevante en el repo

```
app/Http/Controllers/Api/V1/AuthController.php   # registro, login, me, refresh, logout
app/Http/Middleware/TenantMiddleware.php         # cabecera X-Tenant-ID
app/Http/Middleware/JwtAuth.php                  # JWT + coherencia tenant
app/Models/User.php                              # JWT + HasRoles + tenant_id
app/Models/Tenant.php                            # modelo Stancl / tabla tenants
resources/js/                                    # Vue: router, stores, páginas, Axios
routes/api.php                                   # rutas bajo prefijo api/v1 (ver bootstrap/app.php)
```

---

## Qué se necesita para correr el proyecto

### Software instalado en tu máquina

| Requisito                      | Uso                                                                                                                        |
| ------------------------------ | -------------------------------------------------------------------------------------------------------------------------- |
| **PHP ≥ 8.2**                  | Ejecutar Laravel y Composer scripts (`artisan`, migraciones).                                                              |
| **Composer ≥ 2.x**             | Instalar dependencias PHP (`vendor/`).                                                                                     |
| **Node.js ≥ 20** y **npm**     | Instalar dependencias JS y ejecutar Vite (`npm run dev` / `npm run build`).                                                |
| **Extensiones PHP habituales** | `openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath` (según tu stack).                              |
| **Base de datos**              | **SQLite** (rápido en desarrollo, archivo `database/database.sqlite`) o **MySQL 8** en entornos más cercanos a producción. |

### Variables de entorno imprescindibles

Tras copiar `.env.example` a `.env`:

- **`APP_KEY`** — `php artisan key:generate`
- **`JWT_SECRET`** — `php artisan jwt:secret`
- **Conexión a BD** — según elijas SQLite o MySQL en `.env`
- **`VITE_API_URL`** — URL base del API que usará el frontend en desarrollo (p. ej. `http://localhost:8000/api/v1`) si el navegador sirve la SPA desde otro puerto (Vite).

Sin PHP/Composer/Node o sin BD configurada, el proyecto no podrá migrar ni compilar el frontend.

---

## Instalación y ejecución

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

Configura la base de datos en `.env` (SQLite o MySQL). Luego:

```bash
php artisan migrate
npm install
npm run dev
```

En **otra terminal**, el servidor HTTP de Laravel:

```bash
php artisan serve
```

Abre el frontend según la URL que muestre Vite (típicamente `http://localhost:5173`) y asegúrate de que `VITE_API_URL` apunte al backend (`php artisan serve` suele ser `http://127.0.0.1:8000`).

### Variables `.env` más usadas

| Variable       | Descripción                                                   |
| -------------- | ------------------------------------------------------------- |
| `APP_URL`      | URL pública del backend (p. ej. `http://localhost:8000`).     |
| `FRONTEND_URL` | URL del frontend en desarrollo (referencia / CORS si aplica). |
| `JWT_SECRET`   | Secreto de firma JWT (generado con `jwt:secret`).             |
| `JWT_TTL`      | Minutos de vida del access token (por defecto 60).            |
| `VITE_API_URL` | Base URL del API para Axios desde Vite.                       |

## API (`/api/v1`)

Todas las rutas del API requieren la cabecera **`X-Tenant-ID`** (UUID del tenant).

| Método | Ruta             | Auth                                                         |
| ------ | ---------------- | ------------------------------------------------------------ |
| POST   | `/auth/register` | No (devuelve JWT al registrar)                               |
| POST   | `/auth/login`    | No                                                           |
| GET    | `/auth/me`       | Bearer JWT                                                   |
| POST   | `/auth/refresh`  | Middleware `jwt.refresh` (renovación con ventana de refresh) |
| POST   | `/auth/logout`   | Bearer JWT                                                   |

Respuestas siempre en **JSON**.

---

## Validación recomendada

```bash
php artisan route:list --path=api
php artisan config:clear
npm run build
php artisan test
```

---

## Entrega esperada

El estudiante debe trabajar sobre su propio fork del repositorio y entregar en Canvas el enlace al repositorio forkeado, junto con una breve descripción del módulo implementado y los commits principales que evidencian su avance.

## ===================================================================

## Análisis del Proyecto y módulo e implementación inicial de Diagramas

En el primer sprint se analiza el proyecto cómo está construido y cómo funciona en general para comenzar a realizar el planteamiento y los diagramas correspondientes, inicialmente se pide con el uso de OpenCode, el análisis inicial del proyecto y cómo está construido y sugerencias para los diagramas correspondientes.

Los diagramas construidos se encuentran en este link: https://drive.google.com/drive/folders/1hvpyrPVTyI4wwWdlAG_6Gv6nBUfAiSS5?usp=sharing

En este caso se utiliza la herramienta de draw.io para el desarrollo de los diagramas

Descripción del Módulo: Prescripciones
Nombre del módulo: Módulo de Prescripciones Médicas (Prescriptions)
Objetivo: Permitir al personal médico autorizado (rol Médico) registrar, consultar, modificar y anular recetas médicas para pacientes del hospital, asegurando la trazabilidad de las prescripciones dentro del expediente clínico.
Alcance funcional:

- Registro de recetas médicas asociadas a un paciente
- Asignación de uno o varios medicamentos por receta, con detalle de dosis, frecuencia, duración e instrucciones
- Consulta de recetas por paciente, médico o rango de fechas
- Modificación y anulación de recetas existentes
- Visualización del historial de prescripciones de un paciente

Análisis de los sprint:
Sprint 1: Análisis y Diseño

1. Diseña el modelo: Prescription → Patient, con PrescriptionItem (medicamentos)
2. Crea los 3 diagramas UML (herramientas: Draw.io (https://draw.io), LucidChart, PlantUML)
3. Documenta endpoints REST y decisiones de diseño
4. Commit: docs(sprint1): add UML diagrams and module analysis for Prescriptions

Sprint 2: Backend

1. Migraciones: patients, prescriptions, prescription_items
2. Modelos: Patient, Prescription, PrescriptionItem con relaciones
3. Controlador + Form Requests + API Resource
4. Rutas en routes/api.php
5. Factory + Seeder para datos demo
6. Tests (PHPUnit) para los endpoints
7. Commit: feat(sprint2): add Prescription module backend

Sprint 3: Frontend

1. Vue componentes: PrescriptionListPage, PrescriptionFormPage, PrescriptionDetailPage
2. Pinia store prescriptions.js
3. Vue Router: rutas /prescriptions, /prescriptions/create, /prescriptions/:id
4. Autorización por roles (solo Medico puede crear/editar)
5. Commit: feat(sprint3): add Prescription frontend
