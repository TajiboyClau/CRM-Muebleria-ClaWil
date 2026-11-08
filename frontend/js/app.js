import { api } from './api.js';
import { clearSession, saveSession, state } from './state.js';

const root = document.querySelector('#app');

function escapeHtml(value = '') {
  return String(value).replace(/[&<>'"]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char]));
}

function showError(error) {
  root.querySelectorAll('.alert-danger').forEach((node) => node.remove());
  const alert = document.createElement('div');
  alert.className = 'alert alert-danger mt-3';
  alert.textContent = error.message;
  root.prepend(alert);
}

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
  root.innerHTML = `<nav class="navbar navbar-dark bg-primary rounded-3 mb-4 px-3"><span class="navbar-brand">CRM Mueblería ClaWil</span><div class="d-flex align-items-center gap-3 text-white"><span>${escapeHtml(state.user?.name || '')}</span><button id="logout" class="btn btn-outline-light btn-sm">Salir</button></div></nav><div id="message"></div>${content}`;
  root.querySelector('#logout').addEventListener('click', () => { clearSession(); loginView(); });
}

function dashboardTemplate() {
  return `<div class="d-flex flex-wrap justify-content-between align-items-center mb-3"><div><h1 class="h3 crm-brand">Mi gestión comercial</h1><p class="text-secondary mb-0">Clientes, oportunidades y actividades de la mueblería.</p></div><button id="refresh" class="btn btn-outline-primary">Actualizar</button></div><div id="stats" class="row g-3 mb-4"></div><ul class="nav nav-tabs mb-3" id="sections"><li class="nav-item"><button class="nav-link active" data-section="clients">Clientes</button></li><li class="nav-item"><button class="nav-link" data-section="opportunities">Oportunidades</button></li><li class="nav-item"><button class="nav-link" data-section="activities">Actividades</button></li></ul><section id="section-content"></section>`;
}

async function loadData() {
  const [clients, availableClients, opportunities, activities, summary] = await Promise.all([api.clients(), api.availableClients(), api.opportunities(), api.activities(), api.monthlySummary()]);
  state.clients = clients.datos; state.availableClients = availableClients; state.opportunities = opportunities.datos; state.activities = activities.datos; state.summary = summary;
}

function renderStats() {
  const opportunities = state.summary?.oportunidades || {};
  const activities = state.summary?.actividades || {};
  document.querySelector('#stats').innerHTML = [
    ['Oportunidades abiertas', opportunities.abiertas || 0],
    ['Oportunidades ganadas', opportunities.ganadas || 0],
    ['Actividades pendientes', Math.max((activities.total_actividades || 0) - (activities.completadas || 0), 0)],
  ].map(([label, value]) => `<div class="col-md-4"><div class="card crm-card crm-stat p-3"><div class="text-secondary small">${label}</div><div class="display-6 fw-semibold">${value}</div></div></div>`).join('');
}

function renderClients() {
  document.querySelector('#section-content').innerHTML = `<div class="card crm-card"><div class="card-body"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">Mi cartera</h2><button class="btn btn-primary btn-sm" id="new-client">Nuevo cliente</button></div><div class="table-responsive"><table class="table"><thead><tr><th>Nombre</th><th>Teléfono</th><th>Correo</th><th></th></tr></thead><tbody>${state.clients.map((client) => `<tr><td>${escapeHtml(client.nombre)}</td><td>${escapeHtml(client.telefono)}</td><td>${escapeHtml(client.correo || 'Sin correo')}</td><td><button class="btn btn-outline-secondary btn-sm client-activity" data-id="${client.id}">Actividad</button></td></tr>`).join('') || '<tr><td colspan="4" class="text-center">No hay clientes disponibles.</td></tr>'}</tbody></table></div></div></div>`;
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
    dialog.innerHTML = '<button type="button" class="btn-close float-end" aria-label="Cerrar"></button>' +
      '<h2 class="h5">' + escapeHtml(item.titulo) + '</h2><p class="text-danger" role="alert"></p>' +
      '<ul>' + lines + '</ul><p><strong>Total: ' + money.format(item.total) + '</strong></p>' +
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

function renderOpportunities() {
  document.querySelector('#section-content').innerHTML = `<div class="card crm-card"><div class="card-body"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">Embudo de ventas</h2><button class="btn btn-primary btn-sm" id="new-opportunity">Nueva oportunidad</button></div><div class="table-responsive"><table class="table"><thead><tr><th>Título</th><th>Cliente</th><th>Etapa</th><th>Actualizar</th></tr></thead><tbody>${state.opportunities.map((item) => `<tr><td>${escapeHtml(item.titulo)}</td><td>${escapeHtml(item.cliente)}</td><td>${escapeHtml(item.etapa)}</td><td><select class="form-select form-select-sm stage-select" data-id="${item.id}">${['prospecto','calificado','propuesta','negociacion','ganada','perdida'].map((stage) => `<option ${stage === item.etapa ? 'selected' : ''}>${stage}</option>`).join('')}</select><button type="button" class="btn btn-link btn-sm opportunity-products" data-id="${escapeHtml(item.id)}">Productos</button></td></tr>`).join('') || '<tr><td colspan="4" class="text-center">No hay oportunidades.</td></tr>'}</tbody></table></div></div></div>`;
  document.querySelectorAll('.opportunity-products').forEach(button => {
    button.addEventListener('click', () => opportunityProducts(button.dataset.id));
  });
  document.querySelector('#new-opportunity').addEventListener('click', opportunityForm);
  document.querySelectorAll('.stage-select').forEach((select) => select.addEventListener('change', async () => { try { await api.changeStage(select.dataset.id, { etapa: select.value, fecha_cierre: ['ganada', 'perdida'].includes(select.value) ? new Date().toISOString().slice(0, 19).replace('T', ' ') : null, motivo_perdida: select.value === 'perdida' ? window.prompt('Motivo de pérdida') : null }); await dashboardView(); } catch (error) { showError(error); } }));
}

function renderActivities() {
  document.querySelector('#section-content').innerHTML = `<div class="card crm-card"><div class="card-body"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">Seguimiento</h2><button class="btn btn-primary btn-sm" id="new-activity">Nueva actividad</button></div><div class="list-group">${state.activities.map((item) => `<div class="list-group-item d-flex justify-content-between align-items-center"><div><strong>${escapeHtml(item.tipo)}</strong> · ${escapeHtml(item.cliente)}<div class="text-secondary">${escapeHtml(item.descripcion)}</div></div><button class="btn btn-sm ${item.completada ? 'btn-outline-secondary' : 'btn-success'} activity-complete" data-id="${item.id}" data-value="${item.completada ? 0 : 1}">${item.completada ? 'Reabrir' : 'Completar'}</button></div>`).join('') || '<div class="text-center text-secondary">No hay actividades.</div>'}</div></div></div>`;
  document.querySelector('#new-activity').addEventListener('click', () => activityForm());
  document.querySelectorAll('.activity-complete').forEach((button) => button.addEventListener('click', async () => { try { await api.completeActivity(button.dataset.id, Number(button.dataset.value)); await dashboardView(); } catch (error) { showError(error); } }));
}

function formModal(title, body, onSubmit) {
  const wrapper = document.createElement('div'); wrapper.className = 'modal fade show d-block'; wrapper.style.background = 'rgba(0,0,0,.45)'; wrapper.innerHTML = `<div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">${title}</h5><button class="btn-close" data-close></button></div><form><div class="modal-body">${body}</div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-close>Cancelar</button><button class="btn btn-primary">Guardar</button></div></form></div></div>`; document.body.append(wrapper); wrapper.querySelectorAll('[data-close]').forEach((node) => node.addEventListener('click', () => wrapper.remove())); wrapper.querySelector('form').addEventListener('submit', async (event) => { event.preventDefault(); try { await onSubmit(new FormData(event.target)); wrapper.remove(); await dashboardView(); } catch (error) { showError(error); } });
}

function clientForm() { formModal('Nuevo cliente', '<input class="form-control mb-3" name="nombre" placeholder="Nombre completo o empresa" required><input class="form-control mb-3" name="telefono" placeholder="Teléfono de 8 dígitos" pattern="[0-9]{8}" required><input class="form-control mb-3" name="nit" placeholder="NIT"><input class="form-control" name="correo" type="email" placeholder="Correo">', (form) => api.createClient(Object.fromEntries(form))); }
function opportunityForm() { formModal('Nueva oportunidad', `<select class="form-select mb-3" name="cliente_id" required>${state.clients.map((client) => `<option value="${client.id}">${escapeHtml(client.nombre)}</option>`).join('')}</select><input class="form-control mb-3" name="titulo" placeholder="Título" required><select class="form-select" name="etapa"><option>prospecto</option><option>calificado</option><option>propuesta</option></select>`, (form) => api.createOpportunity(Object.fromEntries(form))); }
function activityForm(clientId = '') { formModal('Nueva actividad', `<select class="form-select mb-3" name="cliente_id" required>${state.clients.map((client) => `<option value="${client.id}" ${String(client.id) === String(clientId) ? 'selected' : ''}>${escapeHtml(client.nombre)}</option>`).join('')}</select><select class="form-select mb-3" name="tipo"><option>llamada</option><option>reunion</option><option>tarea</option></select><textarea class="form-control" name="descripcion" placeholder="Descripción" required></textarea>`, (form) => api.createActivity(Object.fromEntries(form))); }

async function dashboardView() {
  try { layout(dashboardTemplate()); await loadData(); renderStats(); renderClients(); document.querySelectorAll('[data-section]').forEach((button) => button.addEventListener('click', () => { document.querySelectorAll('[data-section]').forEach((item) => item.classList.remove('active')); button.classList.add('active'); ({ clients: renderClients, opportunities: renderOpportunities, activities: renderActivities }[button.dataset.section])(); })); document.querySelector('#refresh').addEventListener('click', dashboardView); } catch (error) { showError(error); }
}

if (localStorage.getItem('crm_token')) dashboardView(); else loginView();
