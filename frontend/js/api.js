const API_URL = 'http://localhost:3000/api';

async function request(path, options = {}) {
  const token = localStorage.getItem('crm_token');
  const response = await fetch(`${API_URL}${path}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...(options.headers || {}),
    },
  });
  const data = await response.json().catch(() => ({}));
  if (!response.ok) throw new Error(data.error || Object.values(data.errores || {}).join(" ") || data.mensaje || 'No se pudo completar la operación.');
  return data;
}

export const api = {
  opportunityRequest: request,
  login: (email, password) => request('/auth/login', { method: 'POST', body: JSON.stringify({ correo: email, clave: password }) }),
  clients: () => request('/clientes'),
  availableClients: () => request('/clientes/disponibles'),
  claimClient: (id) => request('/clientes/' + encodeURIComponent(id) + '/tomar', { method: 'POST' }),
  createClient: (payload) => request('/clientes', { method: 'POST', body: JSON.stringify(payload) }),
  opportunities: () => request('/oportunidades'),
  createOpportunity: (payload) => request('/oportunidades', { method: 'POST', body: JSON.stringify(payload) }),
  changeStage: (id, payload) => request(`/oportunidades/${id}/etapa`, { method: 'PATCH', body: JSON.stringify(payload) }),
  activities: () => request('/actividades'),
  createActivity: (payload) => request('/clientes/' + encodeURIComponent(payload.cliente_id) + '/actividades', { method: 'POST', body: JSON.stringify(payload) }),
  completeActivity: (id, completada) => request(`/actividades/${id}`, { method: 'PATCH', body: JSON.stringify({ completada: Boolean(completada) }) }),
  monthlySummary: (mes) => request('/reportes/resumen' + (mes ? '?mes=' + encodeURIComponent(mes) : '')),
};
