
## Objetivo General

Desarrollar un sistema web de trazabilidad inteligente que permita el mejoramiento de la producción y la verificación de origen del café boliviano durante los procesos de exportación.

## Objetivos Específicos

1. Diagnosticar los requerimientos de información y trazabilidad de los productores de café boliviano.
2. Diseñar la arquitectura modular del sistema web (producción, monitoreo, elaboración, calidad y exportación).
3. Incorporar un componente de inteligencia artificial para la detección temprana de plagas y enfermedades del café.
4. Construir el módulo de verificación de origen mediante código QR, desde la producción hasta la exportación.
5. Validar el funcionamiento del sistema mediante pruebas con datos reales o simulados.

---

## Requerimientos Funcionales (RF)

### Módulo Producción
- **RF01:** El sistema debe permitir el registro de productores (datos personales, ubicación de parcela, tamaño de terreno).
- **RF02:** El sistema debe permitir registrar cada cosecha (fecha, cantidad, variedad de café, parcela de origen).
- **RF03:** El sistema debe generar un código de trazabilidad único por lote de cosecha.

### Módulo Monitoreo
- **RF04:** El sistema debe registrar condiciones climáticas asociadas a la parcela (manual o vía API externa).
- **RF05:** El sistema debe permitir al productor registrar observaciones periódicas del cultivo (fotos, notas).

### Módulo Elaboración
- **RF06:** El sistema debe permitir registrar las etapas de post-cosecha (despulpado, lavado, secado, tostado) con fecha y responsable.
- **RF07:** El sistema debe vincular cada etapa de elaboración al lote correspondiente, manteniendo el historial.

### Módulo Calidad (IA)
- **RF08:** El sistema debe permitir subir imágenes de las plantas de café para análisis.
- **RF09:** El sistema debe procesar las imágenes mediante un modelo de IA y devolver un diagnóstico (plaga/enfermedad detectada o planta sana).
- **RF10:** El sistema debe almacenar el historial de diagnósticos por parcela.
- **RF11:** El sistema debe notificar al productor cuando se detecte una alerta de plaga o enfermedad.

### Módulo Exportación
- **RF12:** El sistema debe generar un código QR único por lote, enlazado a todo su historial de trazabilidad.
- **RF13:** El sistema debe permitir que cualquier persona (comprador, verificador) escanee el QR y visualice el historial completo del lote (origen, elaboración, calidad).
- **RF14:** El sistema debe generar un reporte/certificado de trazabilidad exportable (PDF) por lote.

### Transversal
- **RF15:** El sistema debe contar con autenticación y roles diferenciados (productor, verificador/exportador, administrador).
- **RF16:** El sistema debe permitir búsqueda y filtrado de lotes por productor, fecha o estado.

---

## Requerimientos No Funcionales (RNF)

| # | Categoría | Descripción |
|---|---|---|
| RNF01 | Usabilidad | La interfaz debe ser intuitiva para usuarios con conocimientos técnicos limitados (productores rurales). |
| RNF02 | Disponibilidad | El sistema debe estar disponible al menos 95% del tiempo (considerando hosting básico/académico). |
| RNF03 | Rendimiento | El diagnóstico de IA debe procesar una imagen en un tiempo razonable (ej. menos de 10-15 segundos). |
| RNF04 | Seguridad | El acceso a datos sensibles (productor, ubicación) debe estar protegido mediante autenticación y control de roles. |
| RNF05 | Escalabilidad | La arquitectura modular debe permitir agregar nuevos módulos sin afectar los existentes. |
| RNF06 | Compatibilidad | El sistema debe ser accesible desde navegadores web estándar (Chrome, Firefox) y responsive para verificación de QR desde celular. |
| RNF07 | Portabilidad de datos | El sistema debe permitir exportar los datos de trazabilidad en formatos estándar (PDF, y opcionalmente CSV/JSON). |
| RNF08 | Mantenibilidad | El código debe seguir una estructura modular documentada, facilitando su mantenimiento posterior. |

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
| Gestión del proyecto | Trello / GitHub Projects (metodología ágil, ej. Scrum) |
| Hosting (pruebas) | Servidor local / hosting académico / Railway-Render (para demo) |

**Detalle de cada herramienta:**

- **Quasar (Vue 3):** framework para el frontend, las pantallas donde el productor, el verificador y el administrador interactúan con el sistema.
- **Laravel (PHP):** framework de backend, maneja la lógica del sistema, la autenticación por roles y la comunicación entre el frontend y la base de datos.
- **MySQL:** base de datos, guarda toda la información: productores, parcelas, cosechas, etapas de elaboración, diagnósticos de IA y trazabilidad de lotes.
- **Python + TensorFlow/Keras:** construye y entrena el modelo de IA que analiza las imágenes de las plantas y detecta plagas o enfermedades.
- **FastAPI:** expone ese modelo de IA como un servicio web, para que Laravel le envíe una imagen y reciba el diagnóstico de vuelta.
- **Librería QR (JS):** genera el código QR único de cada lote y permite escanearlo desde el celular para consultar su historial.
- **Git + GitHub:** control de versiones, guarda el historial de cambios del código y permite trabajar ordenado por ramas.
- **PlantUML:** genera los diagramas técnicos (casos de uso, actividad, secuencia) para la documentación de la tesis.
- **GitHub Projects:** organiza las tareas del proyecto en tablero kanban, evidencia de metodología ágil.
- **Railway/Render:** aloja el sistema en internet para mostrar la demo funcionando.
- **Postman:** prueba que las APIs (Laravel y FastAPI) respondan correctamente antes de conectarlas con el frontend.

---

## Cronograma Aproximado

| Fase | Duración | Semanas |
|---|---|---|
| Diagnóstico y requerimientos | 2 sem | 1-2 |
| Diseño de arquitectura y BD | 2 sem | 3-4 |
| Producción + Monitoreo | 2 sem | 5-6 |
| Elaboración | 2 sem | 7-8 |
| Modelo IA (Calidad) | 3 sem | 9-11 |
| Exportación + QR | 2 sem | 12-13 |
| Integración y pruebas | 2 sem | 14-15 |
| Documentación final | 1 sem | 16 |
