import apiClient from './client';

export async function login(username, password) {
  const response = await apiClient.post('/login', { username, password });
  return response.data;
}

export async function register(username, email, password, passwordReminder) {
  const response = await apiClient.post('/register', {
    username,
    email,
    password,
    password_reminder: passwordReminder,
  });
  return response.data;
}
