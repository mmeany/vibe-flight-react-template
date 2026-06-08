import apiClient from './client';
import { TOKEN_KEY } from './storage';

export async function listUsers({
  page = 1,
  perPage = 25,
  includeInactive = false,
  search = '',
  sort = 'username',
  order = 'asc',
} = {}) {
  const response = await apiClient.get('/admin/users', {
    params: {
      page,
      per_page: perPage,
      include_inactive: includeInactive ? 1 : 0,
      search: search || undefined,
      sort,
      order,
    },
  });
  return {
    items: response.data.data,
    meta: response.data.meta,
  };
}

export async function createUser({ username, email, password, password_reminder, user_alias }) {
  const response = await apiClient.post('/admin/users', {
    username,
    email,
    password,
    password_reminder,
    user_alias,
  });
  return response.data.data;
}

export async function updateUser(id, { username, email, password, password_reminder, user_alias }) {
  const body = { username, email, user_alias };
  if (password) {
    body.password = password;
  }
  if (password_reminder !== undefined) {
    body.password_reminder = password_reminder;
  }
  const response = await apiClient.patch(`/admin/users/${id}`, body);
  return response.data.data;
}

export async function deactivateUser(id) {
  const response = await apiClient.delete(`/admin/users/${id}`);
  return response.data.data;
}

export async function restoreUser(id) {
  const response = await apiClient.post(`/admin/users/${id}/restore`);
  return response.data.data;
}

export async function importUsers(file) {
  const formData = new FormData();
  formData.append('file', file);
  const token = localStorage.getItem(TOKEN_KEY);
  const response = await fetch(`${import.meta.env.BASE_URL}api/v1/admin/users/import`, {
    method: 'POST',
    headers: token ? { Authorization: `Bearer ${token}` } : {},
    body: formData,
  });
  const json = await response.json();
  if (!response.ok) {
    throw new Error(json?.error?.message || 'Import failed');
  }
  return json.data;
}
