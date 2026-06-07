import apiClient from './client';

export async function login(username, password) {
  const response = await apiClient.post('/login', { username, password });
  return response.data;
}

export async function startRegistration({
  username,
  email,
  password,
  passwordReminder,
  challengeToken,
  challengeAnswer,
  formLoadedAt,
  website,
}) {
  const response = await apiClient.post('/register', {
    username,
    email,
    password,
    password_reminder: passwordReminder,
    challenge_token: challengeToken,
    challenge_answer: challengeAnswer,
    form_loaded_at: formLoadedAt,
    _website: website,
  });
  return response.data;
}

export async function verifyRegistration(pendingToken, code) {
  const response = await apiClient.post('/register/verify', {
    pending_token: pendingToken,
    code,
  });
  return response.data;
}

export async function resendVerification(pendingToken) {
  const response = await apiClient.post('/register/resend', {
    pending_token: pendingToken,
  });
  return response.data;
}
