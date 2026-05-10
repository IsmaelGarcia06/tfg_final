# Diseño Técnico CRM Clínica Psicología

## 1. Modelo de Datos (MySQL)

### Tablas Principales

**`users`**
*   `id` (PK, INT, AI)
*   `name` (VARCHAR)
*   `email` (VARCHAR, UNIQUE)
*   `password_hash` (VARCHAR)
*   `role` (ENUM: 'admin', 'manager', 'professional')
*   `google_refresh_token` (VARCHAR, NULL) - Para integración GCal
*   `active` (BOOLEAN, DEFAULT 1)
*   `created_at` (DATETIME)

**`patients`**
*   `id` (PK, INT, AI)
*   `professional_id` (FK -> users.id) - El profesional asignado
*   `name` (VARCHAR)
*   `email` (VARCHAR)
*   `phone` (VARCHAR)
*   `status` (ENUM: 'open', 'closed')
*   `last_session_at` (DATETIME, NULL) - Para optimizar la alerta de 15 días
*   `created_at` (DATETIME)
*   `updated_at` (DATETIME)

**`sessions`**
*   `id` (PK, INT, AI)
*   `patient_id` (FK -> patients.id)
*   `professional_id` (FK -> users.id)
*   `start_time` (DATETIME)
*   `end_time` (DATETIME)
*   `status` (ENUM: 'scheduled', 'completed', 'cancelled', 'no_show')
*   `notes` (TEXT) - Notas internas privadas
*   `google_event_id` (VARCHAR, NULL) - ID del evento en Google Calendar
*   `fee_amount` (DECIMAL 10,2) - Costo de la sesión
*   `manager_fee_percentage` (DECIMAL 5,2) - % que se queda la clínica (snapshot al crear la sesión)
*   `created_at` (DATETIME)

**`email_queue`**
*   `id` (PK, INT, AI)
*   `recipient_email` (VARCHAR)
*   `subject` (VARCHAR)
*   `body` (TEXT)
*   `status` (ENUM: 'pending', 'sent', 'failed')
*   `attempts` (INT, DEFAULT 0)
*   `send_after` (DATETIME) - Cuándo se debe enviar
*   `created_at` (DATETIME)

**`audit_logs`**
*   `id` (PK, INT, AI)
*   `user_id` (FK -> users.id)
*   `action` (VARCHAR) - Ej: 'create_patient', 'update_session'
*   `entity_type` (VARCHAR)
*   `entity_id` (INT)
*   `details` (JSON) - Valores anteriores/nuevos
*   `created_at` (DATETIME)

## 2. Endpoints y Permisos

| Método | Ruta | Permisos | Descripción |
| :--- | :--- | :--- | :--- |
| POST | `/login` | Público | Autenticación |
| POST | `/logout` | Auth | Cerrar sesión |
| GET | `/dashboard` | Auth | Datos resumen según rol |
| **Usuarios** | | | |
| GET | `/users` | Admin | Listar usuarios |
| POST | `/users` | Admin | Crear usuario |
| PUT | `/users/{id}` | Admin | Editar usuario |
| **Pacientes** | | | |
| GET | `/patients` | Admin/Manager (Todos), Prof (Sus pacientes) | Listar pacientes (filtros: estado, alerta 15 días) |
| POST | `/patients` | Admin/Manager/Prof | Crear paciente |
| GET | `/patients/{id}` | Admin/Manager, Prof (Owner) | Ver detalle |
| PUT | `/patients/{id}` | Admin/Manager, Prof (Owner) | Editar paciente |
| **Sesiones** | | | |
| GET | `/sessions` | Admin/Manager (Todas), Prof (Suyas) | Listar sesiones (rango fechas) |
| POST | `/sessions` | Admin/Manager/Prof | Agendar sesión |
| PUT | `/sessions/{id}` | Admin/Manager, Prof (Owner) | Editar/Completar sesión |
| DELETE | `/sessions/{id}` | Admin/Manager, Prof (Owner) | Cancelar/Borrar |
| **Reportes** | | | |
| GET | `/reports/financial` | Admin/Manager | Reporte económico mensual |
| **Integración** | | | |
| GET | `/auth/google` | Prof | Iniciar OAuth Google |
| GET | `/auth/google/callback` | Prof | Callback OAuth |

## 3. Diseño de UI (Pantallas Clave)

1.  **Login**: Simple, email/password.
2.  **Dashboard**:
    *   **Admin/Manager**: KPIs globales (Ingresos mes, Sesiones hoy, Alertas pacientes abandonados).
    *   **Profesional**: Próxima sesión, Alertas de mis pacientes (sin sesión > 15 días).
3.  **Calendario (FullCalendar)**:
    *   Vista semanal/mensual.
    *   Click en slot -> Modal "Nueva Sesión".
    *   Click en evento -> Modal "Editar/Ver Sesión".
    *   Colores según estado (Verde: realizada, Gris: programada, Rojo: cancelada).
4.  **Listado de Pacientes**:
    *   Tabla con columnas: Nombre, Última Sesión, Estado, Acciones.
    *   **Resaltado**: Filas en amarillo/rojo si `last_session_at` > 15 días.
5.  **Reporte Económico**:
    *   Filtro por Mes/Año.
    *   Tabla: Profesional | Total Sesiones | Ingresos Brutos | Fee Clínica | Neto Profesional.

## 4. Estrategia Google Calendar

*   **Autenticación**: OAuth 2.0. Cada profesional debe ir a "Mi Perfil" y conectar su cuenta de Google. Guardamos el `refresh_token`.
*   **Sincronización (1-way MVP)**:
    *   CRM -> Google: Al crear/editar/borrar una sesión en el CRM, se usa la API de Google Calendar para replicar el cambio.
    *   Se guarda el `google_event_id` en la tabla `sessions` para futuras actualizaciones.
*   **Conflictos**:
    *   Al crear sesión en CRM, verificar disponibilidad en BD local.
    *   (Mejora v2) Consultar "Free/Busy" API de Google antes de permitir agendar.

## 5. Estrategia de Emails

*   **Cola de Envíos**: No enviar emails síncronamente (ralentiza la UI). Insertar en `email_queue`.
*   **Cron Job**: Un script PHP (`cron.php`) que se ejecuta cada 5-10 minutos.
    1.  Procesa emails pendientes en `email_queue`.
    2.  Busca sesiones programadas para mañana (24h) o en 2h que no tengan recordatorio generado aún, y genera la entrada en `email_queue`.
*   **Plantillas**: Archivos PHP/HTML simples con placeholders `{{patient_name}}`, `{{date}}`.

## 6. Plan de Implementación

1.  **Fase 0: Setup**: Estructura de carpetas, conexión BD, sistema de Routing básico, Autenticación.
2.  **Fase 1: Gestión de Usuarios y Pacientes**: CRUD usuarios (Admin), CRUD pacientes, lógica de "Mis Pacientes".
3.  **Fase 2: Agenda y Sesiones**: Integración FullCalendar, CRUD sesiones, validaciones de fecha.
4.  **Fase 3: Lógica de Negocio**: Alerta 15 días, cálculo de fees, reportes económicos.
5.  **Fase 4: Integraciones**: Google Calendar OAuth + Sync, Sistema de Emails (Cron).
6.  **Fase 5: Refinamiento**: UI/UX, Seguridad (CSRF, XSS), Tests básicos.

## 7. Riesgos y Recomendaciones

*   **Seguridad (IDOR)**: Es crítico asegurar que un profesional no pueda cambiar el ID en la URL para ver pacientes de otro. Implementar Middleware de "Ownership" estricto.
*   **Datos Sensibles**: Las notas de las sesiones son información médica/psicológica. Deben tratarse con máxima seguridad. Considerar encriptación en reposo para el campo `notes`.
*   **Google Calendar**: Los tokens caducan o el usuario revoca acceso. Manejar errores de API elegantemente (no bloquear la app si Google falla).
*   **Timezones**: Guardar todo en UTC en base de datos, convertir a zona horaria local en el Frontend/PHP.
