export const state = {
  user: JSON.parse(localStorage.getItem('crm_user') || 'null'),
  clients: [],
  availableClients: [],
  opportunities: [],
  activities: [],
  summary: null,
};

export function saveSession({ token, usuario }) {
  localStorage.setItem('crm_token', token);
  localStorage.setItem('crm_user', JSON.stringify(usuario));
  state.user = usuario;
}

export function clearSession() {
  localStorage.removeItem('crm_token');
  localStorage.removeItem('crm_user');
  state.user = null;
}
