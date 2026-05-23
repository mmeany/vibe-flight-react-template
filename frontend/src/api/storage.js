const SCOPE = (import.meta.env.BASE_URL || '/')
  .replace(/^\/+|\/+$/g, '')
  .replace(/\//g, '_');

export const TOKEN_KEY = SCOPE ? `auth_token_${SCOPE}` : 'auth_token';
export const USER_KEY = SCOPE ? `auth_user_${SCOPE}` : 'auth_user';
