import { api } from './api.js';
import { clearSession, saveSession, state } from './state.js';

const root = document.querySelector('#app');

function escapeHtml(value = '') {
  return String(value).replace(/[&<>'"]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char]));
}

function formatGuatemala(value) {
  if (!value) return 'Sin fecha';
  return new Intl.DateTimeFormat('es-GT', { dateStyle: 'short', timeStyle: 'short', timeZone: 'America/Guatemala' }).format(new Date(value));
}

function showError(error) {
  root.querySelectorAll('.alert-danger').forEach((node) => node.remove());
  const alert = document.createElement('div');
  alert.className = 'alert alert-danger mt-3';
  alert.textContent = error.message;
  root.prepend(alert);
}

window.addEventListener('crm:unauthorized', (event) => {
  clearSession();
  loginView();
  showError(new Error(event.detail || 'La sesión ya no es válida. Ingrese nuevamente.'));
});

function loginView() {
  root.innerHTML = `<div class="row justify-content-center align-items-center min-vh-100"><div class="col-md-6 col-lg-4"><div class="card crm-card p-4"><h1 class="h3 crm-brand text-center mb-2">Mueblería ClaWil</h1><p class="text-center text-secondary">Portal para vendedores</p><form id="login-form" class="mt-3"><label class="form-label" for="email">Correo</label><input class="form-control mb-3" id="email" type="email" required><label class="form-label" for="password">Contraseña</label><input class="form-control mb-3" id="password" type="password" required><button class="btn btn-primary w-100" type="submit">Ingresar</button></form></div></div></div>`;
  root.querySelector('#login-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    try {
      const data = await api.login(root.querySelector('#email').value, root.querySelector('#password').value);
      saveSession(data);
      await dashboardView();
    } catch (error) { showError(error); }
  });
}

function layout(content) {
  root.innerHTML = `<nav class="navbar navbar-dark bg-primary rounded-3 mb-4 px-3"><span class="navbar-brand">CRM Mueblería ClaWil</span><div class="d-flex align-items-center gap-3 text-white"><span>${escapeHtml(state.user?.nombre || state.user?.name || '')}</span><button id="logout" class="btn btn-outline-light btn-sm">Salir</button></div></nav><div id="message"></div>${content}`;
  root.querySelector('#logout').addEventListener('click', () => { clearSession(); loginView(); });
}

function dashboardTemplate() {
  return `<div class="d-flex flex-wrap justify-content-between align-items-center mb-3"><div><h1 class="h3 crm-brand">Mi gestión comercial</h1><p class="text-secondary mb-0">Clientes, oportunidades y actividades de la mueblería.</p></div><button id="refresh" class="btn btn-outline-primary">Actualizar</button></div><div id="stats" class="row g-3 mb-4"></div><ul class="nav nav-tabs mb-3" id="sections"><li class="nav-item"><button class="nav-link active" data-section="clients">Clientes</button></li><li class="nav-item"><button class="nav-link" data-section="opportunities">Oportunidades</button></li><li class="nav-item"><button class="nav-link" data-section="activities">Actividades</button></li></ul><section id="section-content"></section>`;
}

async function loadData(clientQuery = '') {
  const [clients, availableClients, opportunities, activities, summary] = await Promise.all([api.clients(clientQuery), api.availableClients(), api.opportunities(), api.activities(), api.monthlySummary()]);
  state.clients = clients; state.availableClients = availableClients; state.opportunities = opportunities; state.activities = activities; state.summary = summary;
}

function renderStats() {
  const money = new Intl.NumberFormat('es-GT', { style: 'currency', currency: 'GTQ' });
  const summary = state.summary;
  document.querySelector('#stats').innerHTML = [
    ['Ventas', summary.ventas],
    ['Total vendido', money.format(summary.total)],
    ['Pronóstico', money.format(summary.pronostico)],
    ['Cartera', summary.cartera.clientes + ' de ' + summary.cartera.maximo],
  ].map(([label, value]) => '<div class="col-6 col-lg-3"><div class="card crm-card p-3">' +
    '<div class="text-secondary">' + label + '</div><strong>' + value + '</strong></div></div>').join('');
  const controls = document.createElement('div');
  controls.className = 'col-12';
  controls.innerHTML = '<label for="report-month">Mes del reporte</label> <input id="report-month" type="month">';
  const month = controls.querySelector('input');
  month.value = summary.mes;
  month.addEventListener('change', async () => {
    if (!month.value) return;
    try {
      state.summary = await api.monthlySummary(month.value);
      if (month.isConnected) renderStats();
    } catch (error) { showError(error); }
  });
  document.querySelector('#stats').append(controls);
}

function renderClients() {
  document.querySelector('#section-content').innerHTML = `<div class="card crm-card"><div class="card-body"><div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><h2 class="h5 mb-0">Mi cartera</h2><button class="btn btn-primary btn-sm" id="new-client">Nuevo cliente</button></div><form id="client-search" class="row g-2 mb-3"><div class="col-sm-9"><label class="visually-hidden" for="client-query">Buscar cliente</label><input class="form-control" id="client-query" name="q" placeholder="Buscar por nombre o NIT"></div><div class="col-sm-3"><button class="btn btn-outline-primary w-100">Buscar</button></div></form><div class="table-responsive"><table class="table"><thead><tr><th>Nombre</th><th>Teléfono</th><th>Correo</th><th></th></tr></thead><tbody>${state.clients.map((client) => `<tr><td>${escapeHtml(client.nombre)}</td><td>${escapeHtml(client.telefono)}</td><td>${escapeHtml(client.correo || 'Sin correo')}</td><td><button class="btn btn-outline-secondary btn-sm client-activity" data-id="${client.id}">Actividad</button></td></tr>`).join('') || '<tr><td colspan="4" class="text-center">No hay clientes disponibles.</td></tr>'}</tbody></table></div></div></div>`;
  const available = document.createElement('section');
  available.className = 'card crm-card mt-3 p-3';
  const title = document.createElement('h2');
  title.className = 'h5';
  title.textContent = 'Clientes disponibles · ' + state.clients.length + ' de 10 en cartera';
  available.append(title);

  if (state.availableClients.length === 0) {
    available.append('No hay clientes disponibles.');
  }

  for (const client of state.availableClients) {
    const row = document.createElement('div');
    row.className = 'd-flex justify-content-between align-items-center py-2';
    const name = document.createElement('span');
    name.textContent = client.nombre;
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-outline-primary btn-sm';
    button.textContent = 'Tomar cliente';
    button.disabled = state.clients.length >= 10;
    button.addEventListener('click', async () => {
      button.disabled = true;
      try {
        await api.claimClient(client.id);
        await dashboardView();
      } catch (error) {
        showError(error);
        button.disabled = false;
      }
    });
    row.append(name, button);
    available.append(row);
  }

  document.querySelector('#section-content').append(available);
  document.querySelector('#new-client').addEventListener('click', () => clientForm());
  document.querySelector('#client-search').addEventListener('submit', async (event) => {
    event.preventDefault();
    const query = new FormData(event.target).get('q')?.toString().trim() || '';
    try { state.clients = await api.clients(query); renderClients(); } catch (error) { showError(error); }
  });
  document.querySelectorAll('.client-activity').forEach((button) => button.addEventListener('click', () => { document.querySelector('[data-section="activities"]').click(); activityForm(Number(button.dataset.id)); }));
}


function opportunityProducts(id) {
  const dialog = document.createElement('dialog');
  dialog.className = 'rounded border p-4 w-75';
  const money = new Intl.NumberFormat('es-GT', { style: 'currency', currency: 'GTQ' });
  const path = '/oportunidades/' + encodeURIComponent(id);
  dialog.addEventListener('close', () => dialog.remove());
  document.body.append(dialog);
  dialog.showModal();

  async function render() {
    const [item, catalog] = await Promise.all([api.opportunityRequest(path), api.opportunityRequest('/productos')]);
    if (!dialog.open) return;
    const closed = ['ganada', 'perdida'].includes(item.etapa);
    const lines = item.productos.map(line =>
      '<li class="mb-2">' + escapeHtml(line.nombre) + ' · ' + line.cantidad + ' × ' +
      money.format(line.precio_unitario) + ' = ' + money.format(line.subtotal) +
      (closed ? '' : '<form class="d-flex gap-2 mt-1" data-line="' + escapeHtml(line.id) + '">' +
        '<input type="number" name="cantidad" aria-label="Cantidad" min="1" max="999" required value="' + line.cantidad + '">' +
        '<button class="btn btn-sm btn-outline-primary">Guardar</button>' +
        '<button type="button" class="btn btn-sm btn-outline-danger" data-remove>Quitar</button></form>') + '</li>',
    ).join('');
    const history = item.historial.map(entry =>
      '<li>' + escapeHtml(entry.etapa_anterior || 'creación') + ' → ' + escapeHtml(entry.etapa_nueva) + ' · ' + escapeHtml(entry.usuario) + ' · ' + escapeHtml(formatGuatemala(entry.fecha)) + '</li>',
    ).join('');
    dialog.innerHTML = '<button type="button" class="btn-close float-end" aria-label="Cerrar"></button>' +
      '<h2 class="h5">' + escapeHtml(item.titulo) + '</h2><p class="text-danger" role="alert"></p>' +
      '<ul>' + lines + '</ul><p><strong>Total: ' + money.format(item.total) + '</strong></p>' +
      '<h3 class="h6 mt-3">Historial</h3><ul class="small">' + history + '</ul>' +
      (closed || !catalog.length ? '' : '<form class="d-flex flex-wrap gap-2">' +
        '<select name="producto_id" aria-label="Producto" required>' + catalog.map(product =>
          '<option value="' + escapeHtml(product.id) + '">' + escapeHtml(product.nombre) + ' · ' +
          money.format(product.precio) + '</option>').join('') + '</select>' +
        '<input type="number" name="cantidad" aria-label="Cantidad" min="1" max="999" required value="1">' +
        '<button class="btn btn-sm btn-primary">Agregar</button></form>');
    dialog.querySelector('.btn-close').onclick = () => dialog.close();
  }

  async function mutate(method, form) {
    try {
      const data = new FormData(form);
      const line = form.dataset.line;
      await api.opportunityRequest(path + '/productos' + (line ? '/' + encodeURIComponent(line) : ''), {
        method,
        ...(method === 'DELETE' ? {} : { body: JSON.stringify({
          producto_id: data.get('producto_id'), cantidad: Number(data.get('cantidad')),
        }) }),
      });
      await render();
    } catch (error) {
      if (dialog.open) dialog.querySelector('[role="alert"]').textContent = error.message;
    }
  }

  dialog.addEventListener('submit', event => {
    event.preventDefault();
    mutate(event.target.dataset.line ? 'PATCH' : 'POST', event.target);
  });
  dialog.addEventListener('click', event => {
    if (event.target.matches('[data-remove]')) mutate('DELETE', event.target.closest('form'));
  });
  render().catch(error => { dialog.close(); showError(error); });
}

const stageOptions = {
  prospecto: ['prospecto', 'calificado'],
  calificado: ['calificado', 'propuesta'],
  propuesta: ['propuesta', 'negociacion', 'perdida'],
  negociacion: ['negociacion', 'ganada', 'perdida'],
};

function renderOpportunities() {
  const openStages = ['prospecto', 'calificado', 'propuesta', 'negociacion'];
  const openColumns = openStages.map((stage) => {
    const items = state.opportunities.filter((item) => item.etapa === stage);
    return `<div class="col-12 col-md-6 col-xl-3"><div class="card crm-card h-100"><div class="card-body"><h3 class="h6">${stage}</h3>${items.map((item) => `<article class="border-bottom py-2"><strong>${escapeHtml(item.titulo)}</strong><div class="small text-secondary">${escapeHtml(item.cliente.nombre)}</div><select class="form-select form-select-sm mt-2 stage-select" data-id="${escapeHtml(item.id)}">${stageOptions[item.etapa].map((next) => `<option ${next === item.etapa ? 'selected' : ''}>${next}</option>`).join('')}</select><button type="button" class="btn btn-link btn-sm px-0 opportunity-products" data-id="${escapeHtml(item.id)}">Ver detalle</button></article>`).join('') || '<p class="small text-secondary">Sin oportunidades.</p>'}</div></div></div>`;
  }).join('');
  const closed = state.opportunities.filter((item) => ['ganada', 'perdida'].includes(item.etapa));
  const closedList = closed.map((item) => `<li class="list-group-item d-flex justify-content-between"><span><strong>${escapeHtml(item.titulo)}</strong> · ${escapeHtml(item.cliente.nombre)}</span><span>${escapeHtml(item.etapa)} <button type="button" class="btn btn-link btn-sm opportunity-products" data-id="${escapeHtml(item.id)}">Detalle</button></span></li>`).join('') || '<li class="list-group-item text-secondary">Sin oportunidades cerradas.</li>';
  document.querySelector('#section-content').innerHTML = `<div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">Embudo de ventas</h2><button class="btn btn-primary btn-sm" id="new-opportunity">Nueva oportunidad</button></div><div class="row g-3">${openColumns}</div><div class="card crm-card mt-3"><div class="card-body"><h3 class="h6">Oportunidades cerradas</h3><ul class="list-group list-group-flush">${closedList}</ul></div></div>`;
  document.querySelectorAll('.opportunity-products').forEach(button => {
    button.addEventListener('click', () => opportunityProducts(button.dataset.id));
  });
  document.querySelector('#new-opportunity').addEventListener('click', opportunityForm);

  document.querySelectorAll('.stage-select').forEach(select => select.addEventListener('change', async () => {
    const item = state.opportunities.find(opportunity => String(opportunity.id) === select.dataset.id);
    const etapa = select.value;
    const motivo = etapa === 'perdida' ? window.prompt('Motivo de pérdida') : null;
    if (etapa === 'perdida' && !motivo?.trim()) {
      select.value = item.etapa;
      return;
    }
    select.disabled = true;
    try {
      await api.changeStage(item.id, { etapa, ...(motivo ? { motivo_perdida: motivo } : {}) });
      await dashboardView();
      document.querySelector('[data-section="opportunities"]')?.click();
    } catch (error) {
      select.value = item.etapa;
      showError(error);
    } finally {
      select.disabled = false;
    }
  }));
}

function renderActivities() {
  document.querySelector('#section-content').innerHTML = `<div class="card crm-card"><div class="card-body"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">Seguimiento</h2><button class="btn btn-primary btn-sm" id="new-activity">Nueva actividad</button></div><div class="list-group">${state.activities.map((item) => `<div class="list-group-item d-flex justify-content-between align-items-center"><div><strong>${escapeHtml(item.tipo)}</strong> · ${escapeHtml(item.cliente.nombre)}<div class="text-secondary">${escapeHtml(item.descripcion)}</div></div><button class="btn btn-sm ${item.completada ? 'btn-outline-secondary' : 'btn-success'} activity-complete" data-id="${item.id}" data-value="${item.completada ? 0 : 1}">${item.completada ? 'Reabrir' : 'Completar'}</button></div>`).join('') || '<div class="text-center text-secondary">No hay actividades.</div>'}</div></div></div>`;
  document.querySelector('#new-activity').addEventListener('click', () => activityForm());
  document.querySelectorAll('.activity-complete').forEach((button) => button.addEventListener('click', async () => { try { await api.completeActivity(button.dataset.id, Number(button.dataset.value)); await dashboardView(); } catch (error) { showError(error); } }));
}

function formModal(title, body, onSubmit) {
  const wrapper = document.createElement('div'); wrapper.className = 'modal fade show d-block'; wrapper.style.background = 'rgba(0,0,0,.45)'; wrapper.innerHTML = `<div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">${title}</h5><button class="btn-close" data-close></button></div><form><div class="modal-body">${body}</div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-close>Cancelar</button><button class="btn btn-primary">Guardar</button></div></form></div></div>`; document.body.append(wrapper); wrapper.querySelectorAll('[data-close]').forEach((node) => node.addEventListener('click', () => wrapper.remove())); wrapper.querySelector('form').addEventListener('submit', async (event) => { event.preventDefault(); try { await onSubmit(new FormData(event.target)); wrapper.remove(); await dashboardView(); } catch (error) { showError(error); } });
}

function clientForm() { formModal('Nuevo cliente', '<input class="form-control mb-3" name="nombre" placeholder="Nombre completo o empresa" required><input class="form-control mb-3" name="telefono" placeholder="Teléfono de Guatemala" inputmode="tel" required><input class="form-control mb-3" name="nit" placeholder="NIT"><input class="form-control" name="correo" type="email" placeholder="Correo">', (form) => api.createClient(Object.fromEntries(form))); }
function opportunityForm() { formModal('Nueva oportunidad', `<select class="form-select mb-3" name="cliente_id" required>${state.clients.map((client) => `<option value="${client.id}">${escapeHtml(client.nombre)}</option>`).join('')}</select><input class="form-control mb-3" name="titulo" placeholder="Título" required>`, (form) => api.createOpportunity(Object.fromEntries(form))); }
function activityForm(clientId = '') { formModal('Nueva actividad', `<select class="form-select mb-3" name="cliente_id" required>${state.clients.map((client) => `<option value="${client.id}" ${String(client.id) === String(clientId) ? 'selected' : ''}>${escapeHtml(client.nombre)}</option>`).join('')}</select><select class="form-select mb-3" name="tipo"><option>llamada</option><option>reunion</option><option>tarea</option></select><textarea class="form-control mb-3" name="descripcion" placeholder="Descripción" required></textarea><label class="form-label" for="activity-due">Vence en hora local</label><input class="form-control" id="activity-due" type="datetime-local" name="vence_en">`, (form) => { const data = Object.fromEntries(form); if (data.vence_en) data.vence_en = new Date(data.vence_en).toISOString(); return api.createActivity(data); }); }

async function dashboardView() {
  try { layout(dashboardTemplate()); document.querySelector('#section-content').innerHTML = '<div class="alert alert-info">Cargando información...</div>'; await loadData(); renderStats(); renderClients(); document.querySelectorAll('[data-section]').forEach((button) => button.addEventListener('click', () => { document.querySelectorAll('[data-section]').forEach((item) => item.classList.remove('active')); button.classList.add('active'); ({ clients: renderClients, opportunities: renderOpportunities, activities: renderActivities }[button.dataset.section])(); })); document.querySelector('#refresh').addEventListener('click', dashboardView); } catch (error) { showError(error); }
}

if (localStorage.getItem('crm_token')) dashboardView(); else loginView();
