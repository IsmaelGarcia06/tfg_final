<?php require __DIR__ . '/../layouts/header.php'; ?>

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h1 class="h2">Calendario de Citas</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#appointmentModal" onclick="resetApptForm()">
                <i class="bi bi-plus-lg"></i> Nueva Cita
            </button>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div id='calendar'></div>
        </div>
    </div>

    <!-- Modal Nueva Cita -->
    <div class="modal fade" id="appointmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Agendar Cita</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="apptForm" onsubmit="saveAppointment(event)">
                        <div class="mb-3">
                            <label class="form-label">Paciente</label>
                            <select class="form-select" id="apptPatient" required onchange="checkPatientPacks()">
                                <option value="">Seleccione un paciente...</option>
                            </select>
                        </div>

                        <!-- Selector de Servicio/Tarifa -->
                        <div class="mb-3">
                            <label class="form-label">Servicio / Tarifa</label>
                            <select class="form-select" id="apptService" required>
                                <option value="">Seleccione un servicio...</option>
                            </select>
                            <div class="form-text text-muted" id="servicePrice"></div>
                        </div>

                        <!-- Selector de Método de Pago (Bono o Sesión Suelta) -->
                        <div class="mb-3" id="paymentMethodDiv" style="display:none;">
                            <label class="form-label">Método de Pago</label>
                            <select class="form-select" id="apptPaymentMethod">
                                <option value="single">Sesión Individual (Pago directo)</option>
                            </select>
                            <div class="form-text text-success" id="packInfo"></div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Fecha</label>
                                <input type="date" class="form-control" id="apptDate" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Hora</label>
                                <input type="time" class="form-control" id="apptTime" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Duración (minutos)</label>
                            <select class="form-select" id="apptDuration">
                                <option value="30">30 min</option>
                                <option value="45">45 min</option>
                                <option value="60" selected>60 min</option>
                                <option value="90">90 min</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notas (Opcional)</label>
                            <textarea class="form-control" id="apptNotes" rows="2"></textarea>
                        </div>

                        <div id="conflictAlert" class="alert alert-danger d-none">
                            <i class="bi bi-exclamation-triangle-fill"></i> Ya existe una cita en ese horario.
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">Guardar Cita</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detalles Cita -->
    <div class="modal fade" id="eventModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventTitle">Detalles de la Cita</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Paciente:</strong> <span id="eventPatient"></span></p>
                    <p><strong>Horario:</strong> <span id="eventTime"></span></p>
                    <p><strong>Estado:</strong> <span id="eventStatus"></span></p>
                    <p><strong>Servicio:</strong> <span id="eventService"></span></p>
                    <p><strong>Notas:</strong> <span id="eventNotes"></span></p>

                    <hr>
                    <div class="d-flex justify-content-between">
                        <button class="btn btn-outline-danger btn-sm" onclick="updateStatus('cancelled')">Cancelar Cita</button>
                        <button class="btn btn-success btn-sm" onclick="updateStatus('completed')">Marcar como Realizada</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let calendar;
        let selectedEventId = null;
        let patientPacks = [];
        let allServices = [];

        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');

            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
                },
                locale: 'es',
                buttonText: {
                    today: 'Hoy',
                    month: 'Mes',
                    week:  'Semana',
                    day:   'Día',
                    list:  'Agenda'
                },
                buttonIcons: {
                    prev: 'chevron-left',
                    next: 'chevron-right',
                },
                firstDay: 1,
                height: 'auto',
                events: BASE_URL + '/api/sessions?format=calendar',
                eventClick: function(info) {
                    showEventDetails(info.event);
                },
                dateClick: function(info) {
                    resetApptForm();
                    document.getElementById('apptDate').value = info.dateStr;
                    new bootstrap.Modal(document.getElementById('appointmentModal')).show();
                    loadPatientsSelect();
                }
            });

            calendar.render();
            loadPatientsSelect();
            loadServicesSelect();
        });

        // Cargar Servicios y poblar selector y precio
        async function loadServicesSelect() {
            const select = document.getElementById('apptService');
            try {
                const res = await fetch(BASE_URL + '/api/services');
                if (!res.ok) return;
                const services = await res.json();
                allServices = services;

                select.innerHTML = '<option value="">Seleccione un servicio...</option>';
                services.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.dataset.price = s.price;
                    opt.dataset.duration = s.duration_minutes;
                    opt.textContent = `${s.name} — ${parseFloat(s.price).toFixed(2)}€`;
                    select.appendChild(opt);
                });

                select.addEventListener('change', function() {
                    const opt = this.options[this.selectedIndex];

                    if (opt.value) {
                        const price = parseFloat(opt.dataset.price).toFixed(2);
                        document.getElementById('servicePrice').textContent = `Precio base: ${price}€`;

                        if (opt.dataset.duration) {
                            const durSelect = document.getElementById('apptDuration');
                            const durVal = opt.dataset.duration;
                            for (let o of durSelect.options) {
                                if (o.value == durVal) { durSelect.value = durVal; break; }
                            }
                        }
                    } else {
                        document.getElementById('servicePrice').textContent = '';
                    }
                });
            } catch (e) {
                console.error('Error cargando servicios:', e);
            }
        }


        async function loadPatientsSelect() {
            const select = document.getElementById('apptPatient');
            if (select.options.length > 1) return;

            try {
                const res = await fetch(BASE_URL + '/api/patients');
                let data = await res.json();

                let patients = [];
                if (data.active && Array.isArray(data.active)) {
                    patients = data.active;
                } else if (Array.isArray(data)) {
                    patients = data;
                }

                patients.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.id;
                    opt.textContent = p.name + ' ' + (p.surname || '');
                    select.appendChild(opt);
                });
            } catch (e) { console.error('Error cargando pacientes:', e); }
        }

        async function checkPatientPacks() {
            const patientId = document.getElementById('apptPatient').value;
            const paymentDiv = document.getElementById('paymentMethodDiv');
            const paymentSelect = document.getElementById('apptPaymentMethod');

            paymentSelect.innerHTML = '<option value="single">Sesión Individual (Pago directo)</option>';
            document.getElementById('packInfo').innerText = '';

            if (!patientId) {
                paymentDiv.style.display = 'none';
                return;
            }

            try {
                const res = await fetch(BASE_URL + `/api/patients/${patientId}/packs`);
                if (res.ok) {
                    const packs = await res.json();
                    if (packs.length > 0) {
                        packs.forEach(pack => {
                            const remaining = pack.sessions_total - pack.sessions_used;
                            const opt = document.createElement('option');
                            opt.value = pack.id;
                            opt.textContent = `Usar Bono: ${pack.service_name} (${remaining} restantes)`;
                            paymentSelect.appendChild(opt);
                        });
                        paymentDiv.style.display = 'block';
                    } else {
                        paymentDiv.style.display = 'none';
                    }
                }
            } catch (e) { console.error(e); }
        }

        function resetApptForm() {
            document.getElementById('apptForm').reset();
            document.getElementById('conflictAlert').classList.add('d-none');
            document.getElementById('paymentMethodDiv').style.display = 'none';
            document.getElementById('servicePrice').textContent = '';

            const now = new Date();
            document.getElementById('apptDate').value = now.toISOString().split('T')[0];
            document.getElementById('apptTime').value = now.toTimeString().slice(0,5);
        }

        async function saveAppointment(e) {
            e.preventDefault();
            document.getElementById('conflictAlert').classList.add('d-none');

            const date = document.getElementById('apptDate').value;
            const time = document.getElementById('apptTime').value;
            const paymentMethod = document.getElementById('apptPaymentMethod').value;
            const serviceId = document.getElementById('apptService').value;

            const data = {
                patient_id:  document.getElementById('apptPatient').value,
                service_id:  serviceId || null,
                start_time:  `${date} ${time}`,
                duration:    document.getElementById('apptDuration').value,
                notes:       document.getElementById('apptNotes').value
            };

            if (paymentMethod !== 'single') {
                data.patient_pack_id = paymentMethod;
            }

            try {
                const res = await fetch(BASE_URL + '/api/sessions', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                if (res.status === 409) {
                    document.getElementById('conflictAlert').classList.remove('d-none');
                    return;
                }

                if (res.ok) {
                    bootstrap.Modal.getInstance(document.getElementById('appointmentModal')).hide();
                    calendar.refetchEvents();
                    alert('Cita agendada correctamente');
                } else {
                    const err = await res.json();
                    alert('Error: ' + (err.message || 'Error al guardar'));
                }
            } catch (err) {
                console.error(err);
                alert('Error de conexión');
            }
        }

        function showEventDetails(event) {
            selectedEventId = event.id;
            document.getElementById('eventTitle').innerText = event.title;
            document.getElementById('eventPatient').innerText = event.title;

            const start = event.start.toLocaleString([], {dateStyle: 'short', timeStyle: 'short'});
            const end = event.end ? event.end.toLocaleTimeString([], {timeStyle: 'short'}) : '';
            document.getElementById('eventTime').innerText = `${start} - ${end}`;

            const status = event.extendedProps.status;
            const statusMap = { 'scheduled': 'Programada', 'completed': 'Realizada', 'cancelled': 'Cancelada' };
            document.getElementById('eventStatus').innerText = statusMap[status] || status;

            document.getElementById('eventService').innerText = event.extendedProps.service_name || '-';
            document.getElementById('eventNotes').innerText = event.extendedProps.notes || '-';

            new bootstrap.Modal(document.getElementById('eventModal')).show();
        }

        async function updateStatus(newStatus) {
            if (!selectedEventId) return;
            if (!confirm(`¿Seguro que deseas marcar esta cita como ${newStatus}?`)) return;

            try {
                const res = await fetch(BASE_URL + `/api/sessions/${selectedEventId}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ status: newStatus })
                });

                if (res.ok) {
                    bootstrap.Modal.getInstance(document.getElementById('eventModal')).hide();
                    calendar.refetchEvents();
                } else {
                    alert('Error al actualizar');
                }
            } catch (e) { console.error(e); }
        }
    </script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>