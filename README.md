# Sistema Web de Trazabilidad Inteligente del Café Boliviano

**Universidad Técnica Privada Cosmos - UNITEPC**
**Carrera:** Ingeniería de Sistemas
**Postulante:** Alex Butrón López
**Año:** 2026

---

## Descripción

Sistema web que permite el mejoramiento de la producción y la verificación de origen del café boliviano durante los procesos de exportación, incorporando inteligencia artificial para la detección de plagas y generación de códigos QR de trazabilidad.

Documento completo del proyecto: [docs/documento.md](docs/documento.md)

---

## Módulos del Sistema

| Módulo | Descripción |
|---|---|
| Producción | Registro de productores, parcelas y cosechas |
| Monitoreo | Condiciones del cultivo (clima, observaciones) |
| Elaboración | Etapas de post-cosecha (lavado, secado, tostado) |
| Calidad | Diagnóstico de plagas/enfermedades con IA |
| Exportación | Verificación de origen y generación de QR/PDF |

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
    └── documento.md        # Documento completo de tesis (objetivos, RF, RNF, herramientas, cronograma)
```

---

## Roles del Sistema

- **Productor** — Registra cosechas, parcelas y condiciones del cultivo
- **Verificador** — Verifica origen y calidad del café
- **Administrador** — Gestión completa del sistema

---

## Requerimientos Funcionales (resumen)

Ver detalle completo en [docs/documento.md](docs/documento.md#requerimientos-funcionales-rf).

- RF01-RF03: Registro de productores, parcelas y cosechas
- RF04-RF05: Monitoreo de condiciones del cultivo
- RF06-RF07: Etapas de post-cosecha
- RF08-RF11: Diagnóstico de plagas/enfermedades con IA
- RF12-RF14: Código QR de trazabilidad y certificado PDF
- RF15-RF16: Autenticación por roles y búsqueda de lotes

---

## Herramientas y Tecnologías

| Categoría | Herramienta |
|---|---|
| Frontend | Quasar (Vue 3) |
| Backend | Laravel (PHP) |
| Base de datos | MySQL |
| IA / Modelo | Python + TensorFlow/Keras |
| Servicio IA expuesto como API | FastAPI |
| Generación/lectura QR | Librería JS |
| Control de versiones | Git + GitHub |
| Documentación técnica | PlantUML (diagramas), Markdown |
| Gestión del proyecto | Trello / GitHub Projects (Scrum) |
| Hosting (pruebas) | Servidor local / hosting académico / Railway-Render |

Ver el detalle de qué hace cada herramienta en [docs/documento.md](docs/documento.md#herramientas-y-tecnologías).
