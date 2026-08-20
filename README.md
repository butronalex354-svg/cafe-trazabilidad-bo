# Sistema Web de Trazabilidad Inteligente del Café Boliviano

**Universidad Técnica Privada Cosmos — UNITEPC**
**Carrera:** Ingeniería de Sistemas
**Postulante:** Alex Butrón López
**Año:** 2026

---

## Descripción

Sistema web que permite el mejoramiento de la producción y la verificación de origen del café boliviano durante los procesos de exportación, incorporando inteligencia artificial para la detección de plagas y generación de códigos QR de trazabilidad.

Documento completo del proyecto: [docs/requrimiento funcionales y no funcionales.md](<docs/requrimiento funcionales y no funcionales.md>)

---

## Módulos del Sistema

| Módulo | Descripción |
|---|---|
| Producción | Registro de productores, parcelas y cosechas |
| Monitoreo | Condiciones del cultivo (clima, observaciones) |
| Elaboración | Etapas de post-cosecha (lavado, secado, tostado) |
| Calidad | Diagnóstico de plagas/enfermedades con IA |
| Exportación | Verificación de origen y generación de QR/PDF |
| Contacto | Acceso directo por WhatsApp al productor/dueño del lote mediante el QR |

---

## Herramientas y Tecnologías

### Diseño UI/UX
- **Figma** — diseño de mockups/prototipos de cada pantalla (login, dashboard, formularios por módulo) antes de programarlas

### Entorno de Desarrollo
- **XAMPP** (Apache + MySQL + PHP) — servidor local para correr el backend y la base de datos
- **Visual Studio Code** — editor de código para frontend, backend e IA

### Frontend
- Quasar Framework (Vue 3)
- Vue Router
- Pinia (estado global)
- Axios (consumo de la API REST)
- vue-qrcode / qrcode.vue (generación y lectura de códigos QR)
- VeeValidate (validación de formularios)

### Backend
- Laravel (PHP)
- Composer (gestor de dependencias PHP)
- Eloquent ORM
- Laravel Sanctum (autenticación y roles)
- simple-qrcode (generación de códigos QR en backend)
- Laravel DomPDF (generación de certificados/reportes PDF)
- Laravel Notifications (alertas al productor)
- Laravel Storage (almacenamiento de imágenes de cultivo y diagnósticos de IA)
- L5-Swagger / OpenAPI (documentación de los endpoints de la API)

### Servicio de Inteligencia Artificial
- Python 3
- FastAPI + Uvicorn (API del servicio de IA)
- **Teachable Machine** (Google) — entrenamiento del modelo de detección de plagas/enfermedades sin necesidad de programar el entrenamiento, subiendo imágenes de plantas sanas y enfermas
- TensorFlow / Keras (carga y ejecución del modelo exportado desde Teachable Machine)
- OpenCV (procesamiento de imágenes)
- NumPy, Pillow

### Base de Datos
- MySQL
- MySQL Workbench / phpMyAdmin

### Contacto vía QR (WhatsApp)
- **WhatsApp Click to Chat** (enlace `wa.me/<número>`) — al escanear el QR del lote, además del historial de trazabilidad, se genera un enlace directo a un chat de WhatsApp con el productor/dueño, usando la misma librería de QR (vue-qrcode / simple-qrcode) para codificar el enlace `wa.me`.

### Control de Versiones y Documentación
- Git + GitHub
- PlantUML (diagramas de casos de uso, actividad, secuencia)
- Markdown

### Gestión del Proyecto
- Trello / GitHub Projects (metodología ágil Scrum)

### Pruebas y Despliegue
- Postman (pruebas de API)
- PHPUnit (pruebas automatizadas del backend)
- Laravel Pint (formato y estilo de código)
- Servidor local / hosting académico / Railway-Render (demo)

---

## Estructura del Proyecto

```
cafe-trazabilidad-bo/
├── frontend/               # Interfaz de usuario (Quasar / Vue 3)
├── backend/                # API y lógica de negocio (Laravel / PHP)
├── ia-service/             # Servicio de Inteligencia Artificial (Python + FastAPI)
├── database/
│   ├── migrations/         # Creación de tablas
│   └── seeds/              # Datos iniciales
└── docs/                   # Documentación del proyecto
    └── requrimiento funcionales y no funcionales.md   # Objetivos, requerimientos funcionales y no funcionales
```

---

## Roles del Sistema

- **Productor** — Registra cosechas, parcelas y condiciones del cultivo
- **Verificador** — Verifica origen y calidad del café
- **Administrador** — Gestión completa del sistema
