# Sistema Web de Trazabilidad Inteligente del Café Boliviano

**Universidad Técnica Privada Cosmos — UNITEPC**
**Carrera:** Ingeniería de Sistemas
**Postulante:** Alex Butrón López
**Año:** 2026

---

## Descripción

Sistema web que permite el mejoramiento de la producción y la verificación de origen del café boliviano durante los procesos de exportación, incorporando inteligencia artificial para la detección de plagas y generación de códigos QR de trazabilidad.

Documento completo del proyecto: [docs/requerimientos funcionales y no funcionales.md](<docs/requerimientos funcionales y no funcionales.md>)

---

## Módulos del Sistema

| Módulo | Descripción |
|---|---|
| 1. Producción | Registro de productores, parcelas (con mapa) y cosechas con código de trazabilidad único por lote |
| 2. Monitoreo | Clima real por parcela (API Open-Meteo) y observaciones del cultivo con fotos |
| 3. Calidad | Diagnóstico de plagas/enfermedades con IA (ej. roya), alertas y control de calidad del lote |
| 4. Procesamiento | Etapas de post-cosecha: despulpado, fermentación, lavado, secado, tostado, envasado y embalaje |
| 5. Inventario | Café disponible por lote y por estado (en proceso, terminado, exportado) |
| 6. Verificación | El verificador revisa cada lote y lo marca como Pendiente, Observado, Verificado o Rechazado |
| 7. Exportación | QR público por lote, historial completo, contacto por WhatsApp, certificado PDF y reclamos |
| Transversal | Autenticación y roles, aprobación de cuentas, búsqueda de lotes, reportes y auditoría |

---

## Roles del Sistema

| Rol | Qué hace |
|---|---|
| **Productor** | Registra parcelas, cosechas, monitoreo, procesamiento, calidad e inventario |
| **Verificador** | Revisa las evidencias de cada lote y lo aprueba, lo observa o lo rechaza |
| **Administrador** | Aprueba cuentas, gestiona usuarios y reclamos, autoriza la exportación, ve reportes y auditoría |
| **Comprador** | Sin cuenta: escanea el QR, ve el historial del lote, contacta al productor y registra reclamos |

Cada rol solo puede usar sus propias rutas de la API (guardia central `rol:` en `backend/routes/api.php`). Si el administrador desactiva una cuenta, su sesión se cierra en la siguiente petición.

---

## Herramientas y Tecnologías

| Parte | Herramientas |
|---|---|
| Diseño UI/UX | Figma |
| Frontend | Quasar 2 (Vue 3), Vue Router, Pinia, Axios, Leaflet + OpenStreetMap, qrcode (QR), jsPDF (certificado PDF) |
| Backend | Laravel 12 (PHP 8.2), Eloquent, Laravel Sanctum (sesiones y roles), Laravel Socialite (login con Google), Laravel Storage (fotos), L5-Swagger / OpenAPI |
| Inteligencia Artificial | Python, FastAPI + Uvicorn, TensorFlow / Keras, NumPy, Pillow. **Teachable Machine** (Google) para entrenar el modelo de detección de plagas/enfermedades |
| Clima | API Open-Meteo |
| Base de datos | MySQL / MariaDB (XAMPP), phpMyAdmin |
| Contacto | WhatsApp Click to Chat (enlace `wa.me/<número>` en la página pública del lote) |
| Pruebas y calidad | PHPUnit (pruebas automatizadas del backend), Laravel Pint, Postman |
| Documentación y gestión | Git + GitHub, Swagger, Markdown, diagramas ER y de flujo, Trello / GitHub Projects (Scrum) |

---

## Instalación y ejecución local

### Requisitos

- **XAMPP** (MySQL/MariaDB). En este proyecto la base corre en el puerto **3307**.
- **PHP 8.2** o superior, con las extensiones `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `curl` y `zip`.
- **Composer**
- **Node.js** 22.12 o superior (o 24)
- **Python 3.12** (para el servicio de IA)

### 1. Base de datos

Inicia MySQL desde XAMPP y crea dos bases vacías: `cafe_trazabilidad` (la del sistema) y `cafe_trazabilidad_test` (la usan las pruebas automáticas).

### 2. Backend (Laravel) — puerto 8000

```bash
cd backend
composer install
cp .env.example .env          # luego revisa DB_*, ADMIN_EMAIL y ADMIN_PASSWORD
php artisan key:generate
php artisan migrate           # crea todas las tablas y el catálogo de variedades
php artisan db:seed           # crea el primer administrador (datos de ADMIN_* en .env)
php artisan storage:link      # para que se vean las fotos subidas
php artisan serve --host=0.0.0.0 --port=8000
```

### 3. Servicio de IA (FastAPI) — puerto 8001

```bash
cd ia-service
pip install -r requirements.txt
uvicorn main:app --host 0.0.0.0 --port 8001
```

### 4. Frontend (Quasar) — puerto 9000

```bash
cd frontend
npm install
npx quasar dev --port 9000
```

### Direcciones

| Qué | Dirección |
|---|---|
| Sistema | http://localhost:9000 |
| Documentación de la API (Swagger) | http://localhost:8000/api/documentation |
| Servicio de IA | http://localhost:8001 |
| Desde un celular en la misma red wifi | `http://<IP-de-la-computadora>:9000` |

### Pruebas automáticas

```bash
cd backend
php artisan test
```

### Notas

- Si cambias el puerto del backend, agrégalo también en la política de seguridad (CSP) de `frontend/index.html`, en `connect-src` e `img-src`. Si no, el navegador bloquea las peticiones.
- El login con Google necesita `GOOGLE_CLIENT_ID` y `GOOGLE_CLIENT_SECRET` (Google Cloud Console) en `backend/.env`. Sin ellos, el resto del sistema funciona igual.
- Las sesiones duran 7 días (`SANCTUM_TOKEN_EXPIRATION`, en minutos).
- **Respaldo de la base:** `php artisan respaldo:bd` guarda una copia en `backend/storage/app/respaldos` y conserva las últimas 14. Se hace sola todos los días a las 2:00 mientras corra `php artisan schedule:work`. En Windows con XAMPP, pon en `.env`: `MYSQLDUMP_PATH=C:/xampp2/mysql/bin/mysqldump.exe`.

---

## Despliegue en la nube (Railway)

El sistema se publica como 4 servicios de un mismo proyecto de Railway, todos desde este repositorio. Cada carpeta tiene su `Dockerfile`.

| Servicio | Root Directory | Notas |
|---|---|---|
| MySQL | (base de datos de Railway) | — |
| backend | `/backend` | Dominio público. Volume montado en `/app/storage/app/public` (fotos). |
| ia-service | `/ia-service` | Sin dominio público: el backend lo llama por la red privada. Variable `PORT=8001`. |
| frontend | `/frontend` | Dominio público. Variable `API_URL` = dominio del backend (se usa al compilar). |

Variables del **backend** (Raw Editor de Railway):

```
APP_NAME=CafeTrace
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...                      # php artisan key:generate --show
APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}
LOG_CHANNEL=stderr
DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
IA_SERVICE_URL=http://${{ia-service.RAILWAY_PRIVATE_DOMAIN}}:8001
FRONTEND_URL=https://${{frontend.RAILWAY_PUBLIC_DOMAIN}}
ADMIN_NOMBRE=Administrador
ADMIN_EMAIL=...
ADMIN_PASSWORD=...
```

Variable del **frontend**: `API_URL=https://${{backend.RAILWAY_PUBLIC_DOMAIN}}`

Al arrancar, el backend crea las tablas, el primer administrador y el enlace de las fotos (`backend/iniciar.sh`).

---

## Estructura del Proyecto

```
cafe-trazabilidad-bo/
├── frontend/               # Interfaz de usuario (Quasar / Vue 3)
├── backend/                # API y lógica de negocio (Laravel / PHP)
│   ├── database/
│   │   ├── migrations/     # Creación de tablas
│   │   └── seeders/        # Primer administrador
│   └── tests/              # Pruebas automáticas (PHPUnit)
├── ia-service/             # Servicio de Inteligencia Artificial (Python + FastAPI)
└── docs/                   # Documentación del proyecto
    ├── requerimientos funcionales y no funcionales.md
    ├── Diagrama_ER_Completo.png, Diagrama_BD_Fisico.png, Diagrama_Flujo_Maestro.png
    └── imagenes_diagramas/ # Diagramas por módulo
```
