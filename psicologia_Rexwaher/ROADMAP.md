# Roadmap Técnico: CRM Clínica Psicología

**Rol:** Tech Lead
**Estado:** Borrador Inicial
**Fecha:** Octubre 2023

---

## 1. Visión y Arquitectura

### Decisiones Arquitectónicas
*   **Backend:** PHP 8.x Vanilla con arquitectura MVC ligera.
    *   *Por qué:* Reduce overhead, despliegue universal (cualquier hosting compartido o VPS), curva de aprendizaje baja para mantenimiento futuro.
*   **Frontend:** HTML5 + Bootstrap 5 + Vanilla JS (ES6).
    *   *Por qué:* Iteración rápida. Evitamos complejidad de build tools (Webpack/Vite) para este tamaño de proyecto.
*   **Base de Datos:** MySQL 8.0.
    *   *Integridad:* Uso estricto de Foreign Keys y Transacciones.
*   **Integraciones:**
    *   *Asincronía:* Patrón "Database Queue" para emails y tareas pesadas.
    *   *Google:* Librería oficial PHP Client, estrategia "Fail-safe" (si falla Google, el CRM sigue funcionando).

---

## 2. Fases de Implementación

### FASE 1: MVP (Producto Mínimo Viable)
**Objetivo:** Operatividad básica. Que la clínica pueda dejar de usar Excel/Papel.
**Tiempo estimado:** 2-4 Semanas.

| Módulo | Prioridad | Funcionalidad Clave | Criterios de Aceptación (DoD) |
| :--- | :--- | :--- | :--- |
| **Auth & Roles** | Alta | Login, Logout, Protección de rutas. | - Usuario no logueado es redirigido a login.<br>- Profesional no puede ver menú "Economía".<br>- Passwords hasheados (Bcrypt). |
| **Pacientes** | Alta | CRUD, Estado (Abierto/Cerrado). | - Crear paciente con datos básicos.<br>- Listado carga en < 1s.<br>- Filtro "Activos" funciona. |
| **Sesiones** | Alta | Agendar, Completar, Cancelar. | - No permite agendar sin paciente.<br>- Al completar sesión, se actualiza `last_session_at` del paciente. |
| **Calendario** | Media | Vista Semanal (Local). | - Se ven los huecos ocupados.<br>- Click en hueco abre modal "Nueva Sesión".<br>- Colores diferencian estados. |
| **Economía** | Baja | Reporte simple (Tabla). | - Suma total de ingresos del mes actual.<br>- Desglose básico por profesional. |

**Riesgos MVP:**
*   Resistencia al cambio del staff (UX debe ser muy simple).
*   Datos sucios al migrar de Excel.

---

### FASE 2: v1 (Automatización y Conectividad)
**Objetivo:** Eficiencia y Reducción de errores humanos (No-shows).
**Tiempo estimado:** +4 Semanas post-MVP.

| Módulo | Prioridad | Funcionalidad Clave | Criterios de Aceptación (DoD) |
| :--- | :--- | :--- | :--- |
| **Google Sync** | Alta | 1-Way Sync (CRM -> GCal). | - Al crear sesión en CRM, aparece en GCal del profesional en < 5s.<br>- Si borro en CRM, se borra en GCal.<br>- Manejo de token expirado (Refresh Token). |
| **Emails** | Alta | Recordatorios automáticos. | - Cron ejecuta cada 5min.<br>- Email sale 24h antes.<br>- No se envían duplicados.<br>- Log de "Enviado" visible en BD. |
| **Auditoría** | Media | Logs de seguridad. | - Registro de quién borró una cita.<br>- Registro de intentos de login fallidos. |
| **Alertas UX** | Media | Pacientes "Abandonados". | - Badge rojo en pacientes sin sesión > 15 días.<br>- Filtro rápido en dashboard para ver estos casos. |

**Riesgos v1:**
*   Bloqueos de API de Google (Quota limits).
*   Emails llegando a SPAM (Configurar SPF/DKIM es requisito de infra).

---

### FASE 3: v2 (Escalabilidad y Negocio)
**Objetivo:** Inteligencia de negocio y optimización.
**Tiempo estimado:** Futuro (Q1 2024).

| Módulo | Prioridad | Funcionalidad Clave | Criterios de Aceptación (DoD) |
| :--- | :--- | :--- | :--- |
| **Economía Pro** | Media | Cálculo de Fees complejos. | - Configuración de % variable por profesional.<br>- Exportación a Excel/CSV para contabilidad. |
| **Google 2-Way** | Alta | Sync GCal -> CRM. | - Webhook de Google notifica cambios.<br>- Bloqueo inverso (si pongo "Dentista" en GCal, se bloquea en CRM). |
| **Dashboard** | Baja | Métricas visuales. | - Gráficos de retención de pacientes.<br>- Tasa de ocupación de sala. |
| **Seguridad** | Alta | 2FA y Rate Limiting. | - Login con 2FA opcional.<br>- Bloqueo de IP tras 5 intentos fallidos. |

---

## 3. Plan de Pruebas (QA)

### Para el MVP
1.  **Smoke Test:** Login -> Crear Paciente -> Agendar Cita -> Completar Cita -> Verificar Reporte.
2.  **Seguridad:** Intentar acceder a `/api/patients` sin login (debe dar 401). Intentar acceder a datos de otro profesional (debe dar 403).

### Para la v1
1.  **Integración:** Desconectar internet y tratar de crear cita (debe guardarse en local y loguear error de Google, no explotar).
2.  **Cron:** Forzar fecha de sesión a "mañana" y ejecutar script manual para verificar generación de email.

---

## 4. Stack Tecnológico Final

*   **Lenguaje:** PHP 8.1+
*   **DB:** MySQL 8.0 / MariaDB 10.5
*   **Server:** Apache/Nginx
*   **Libs:**
    *   `google/apiclient`: ^2.15
    *   `phpmailer/phpmailer`: ^6.8
    *   `fullcalendar`: v6 (CDN)
    *   `bootstrap`: v5.3 (CDN)
