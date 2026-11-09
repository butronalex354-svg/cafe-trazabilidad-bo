
## Objetivo General

Desarrollar un sistema web de trazabilidad inteligente que permita el mejoramiento de la producción y la verificación de origen del café boliviano durante los procesos de exportación.

## Objetivos Específicos

1. Diagnosticar los requerimientos de información y trazabilidad de los productores de café boliviano.
2. Diseñar la arquitectura modular del sistema web (producción, monitoreo, procesamiento, calidad, inventario, verificación y exportación).
3. Incorporar un componente de inteligencia artificial para la detección temprana de plagas y enfermedades del café (incluyendo la roya).
4. Construir el módulo de verificación de origen mediante código QR, desde la producción hasta la exportación, permitiendo el contacto directo entre comprador y productor.
5. Separar claramente las responsabilidades del Verificador (validación operativa de cada lote) y del Administrador (supervisión general del sistema).
6. Validar el funcionamiento del sistema mediante pruebas con datos reales o simulados.

---

## Actores del Sistema

| Actor | Rol |
|---|---|
| Productor | Produce y registra: parcela, cultivo, monitoreo, cosecha, procesamiento, calidad e inventario. |
| Verificador | Comprueba y valida: revisa evidencias, verifica origen/producción/procesamiento/calidad, aprueba u observa cada lote. |
| Administrador | Administra y supervisa: usuarios, roles, verificadores, reclamos, autorización final de exportación, reportes y auditoría. |
| Comprador | Sin cuenta en el sistema. Escanea el QR público, consulta el historial del lote, contacta al productor por WhatsApp y puede registrar un reclamo. |

---

## Requerimientos Funcionales (RF)

### Módulo 1 — Producción
- **RF01:** El sistema debe permitir el registro de productores (datos personales, CI, teléfono, WhatsApp, dirección).
- **RF02:** El sistema debe permitir el registro de parcelas con ubicación geográfica (mapa/GPS) y tamaño de terreno.
- **RF03:** El sistema debe permitir registrar cada cosecha (fecha, cantidad, variedad de café, parcela de origen) y generar un código de trazabilidad único por lote.

### Módulo 2 — Monitoreo
- **RF04:** El sistema debe consumir una API externa de clima para predicción meteorológica de la parcela (temperatura, humedad, probabilidad de lluvia).
- **RF05:** El sistema debe permitir al productor registrar observaciones periódicas del cultivo (fotos, notas).

### Módulo 3 — Calidad
- **RF06:** El sistema debe permitir subir imágenes de las plantas de café para análisis.
- **RF07:** El sistema debe procesar las imágenes mediante un modelo de IA y devolver un diagnóstico (roya, otra plaga/enfermedad detectada, o planta sana).
- **RF08:** El sistema debe almacenar el historial de diagnósticos por parcela.
- **RF09:** El sistema debe notificar al productor cuando se detecte una alerta de plaga o enfermedad.
- **RF10:** El sistema debe permitir registrar el control de calidad del lote (humedad, defectos, clasificación).

### Módulo 4 — Procesamiento
- **RF11:** El sistema debe permitir registrar las etapas de post-cosecha (despulpado, fermentación, lavado, secado, tostado, envasado, embalaje) con duración, fecha y responsable.
- **RF12:** El sistema debe vincular cada etapa de procesamiento al lote correspondiente, manteniendo el historial en orden cronológico.

### Módulo 5 — Inventario
- **RF13:** El sistema debe permitir al productor consultar la cantidad de café disponible, por lote y por estado (en proceso, terminado, exportado).

### Módulo 6 — Verificación
- **RF14:** El sistema debe permitir al verificador recibir solicitudes de verificación de lotes pendientes.
- **RF15:** El sistema debe permitir al verificador comprobar el origen (ubicación de la parcela), la producción, el procesamiento y la calidad del lote, con acceso a las evidencias (fotos, documentos) registradas por el productor.
- **RF16:** El sistema debe permitir cambiar el estado del lote entre Pendiente, Observado, Verificado o Rechazado.
- **RF17:** El sistema debe permitir al verificador registrar incidencias (inconsistencias) y solicitar correcciones al productor.

### Módulo 7 — Exportación
- **RF18:** El sistema debe generar un código QR único por lote, enlazado a todo su historial de trazabilidad.
- **RF19:** El sistema debe permitir que cualquier persona (comprador) escanee el QR sin necesidad de cuenta y visualice el historial completo del lote (origen, procesamiento, calidad), incluyendo el mapa de ubicación de la parcela.
- **RF20:** El sistema debe mostrar en la página pública un enlace directo de contacto por WhatsApp con el productor dueño del lote.
- **RF21:** El sistema debe generar un certificado de trazabilidad exportable (PDF) por lote.
- **RF22:** El sistema debe permitir al comprador registrar un reclamo o devolución indicando el código de trazabilidad del lote.
- **RF23:** El sistema debe permitir al administrador autorizar la exportación final de un lote ya verificado.

### Transversal
- **RF24:** El sistema debe contar con autenticación y roles diferenciados (productor, verificador, administrador).
- **RF25:** El sistema debe permitir la gestión de roles y permisos por parte del administrador.
- **RF26:** El sistema debe permitir búsqueda y filtrado de lotes por productor, fecha o estado.
- **RF27:** El sistema debe registrar una auditoría de cambios (quién modificó qué información y cuándo).

---

## Requerimientos No Funcionales (RNF)

| # | Categoría | Descripción |
|---|---|---|
| RNF01 | Usabilidad | La interfaz debe ser intuitiva para usuarios con conocimientos técnicos limitados (productores rurales). |
| RNF02 | Disponibilidad | El sistema debe estar disponible al menos 95% del tiempo (considerando hosting básico/académico). |
| RNF03 | Rendimiento | El diagnóstico de IA debe procesar una imagen en un tiempo razonable (ej. menos de 10-15 segundos). |
| RNF04 | Seguridad | El acceso a datos sensibles (productor, ubicación) debe estar protegido mediante autenticación, control de roles y registro de auditoría. |
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
| IA / Modelo | Python + TensorFlow/Keras (entrenado con Teachable Machine) |
| Servicio IA expuesto como API | FastAPI |
| Clima | API externa de clima (predicción meteorológica) |
| Mapas / Geolocalización | Leaflet + OpenStreetMap |
| Generación/lectura QR | Librería JS / simple-qrcode |
| Control de versiones | Git + GitHub |
| Documentación técnica | Graphviz, Markdown |
| Gestión del proyecto | Trello / GitHub Projects (metodología ágil, ej. Scrum) |
| Hosting (pruebas) | Servidor local / hosting académico / Railway-Render (para demo) |

**Detalle de cada herramienta:**

- **Quasar (Vue 3):** framework para el frontend, las pantallas donde el productor, el verificador y el administrador interactúan con el sistema.
- **Laravel (PHP):** framework de backend, maneja la lógica del sistema, la autenticación por roles y la comunicación entre el frontend y la base de datos.
- **MySQL:** base de datos, guarda toda la información: productores, parcelas, cosechas, procesamiento, calidad, inventario, verificaciones y trazabilidad de lotes.
- **Python + TensorFlow/Keras:** construye y entrena el modelo de IA que analiza las imágenes de las plantas y detecta plagas o enfermedades (incluyendo roya).
- **FastAPI:** expone ese modelo de IA como un servicio web, para que Laravel le envíe una imagen y reciba el diagnóstico de vuelta.
- **API de clima:** provee la predicción meteorológica de cada parcela para el módulo de Monitoreo.
- **Leaflet + OpenStreetMap:** muestra y permite marcar la ubicación geográfica de cada parcela en un mapa interactivo.
- **Librería QR (JS):** genera el código QR único de cada lote y permite escanearlo desde el celular para consultar su historial.
- **Git + GitHub:** control de versiones, guarda el historial de cambios del código y permite trabajar ordenado por ramas.
- **Graphviz:** genera los diagramas técnicos (Entidad-Relación, flujo) para la documentación de la tesis.
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
| Procesamiento | 2 sem | 7-8 |
| Calidad + Modelo IA | 3 sem | 9-11 |
| Inventario + Verificación | 2 sem | 12-13 |
| Exportación + QR | 2 sem | 14-15 |
| Integración, pruebas y documentación final | 1 sem | 16 |
