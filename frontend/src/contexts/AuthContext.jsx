import { createContext, useContext, useState, useCallback, useEffect } from 'react';
import { TOKEN_KEY, USER_KEY } from '../api/storage';

const AuthContext = createContext(null);

async function applyTimezoneIfMissing(user, updateSettingsApi, getBrowserTimezone) {
  if (user.settings?.timezone) {
    return user;
  }
  try {
    const result = await updateSettingsApi('timezone', getBrowserTimezone());
    return { ...user, settings: result.settings };
  } catch {
    return user;
  }
}

export function AuthProvider({ children }) {
  const [state, setState] = useState(() => {
    const token = localStorage.getItem(TOKEN_KEY);
    const userJson = localStorage.getItem(USER_KEY);
    let user = null;
    if (userJson) {
      try {
        user = JSON.parse(userJson);
      } catch {
        localStorage.removeItem(USER_KEY);
      }
    }
    return {
      user,
      token,
      isLoading: !!token,
      isAuthenticated: !!token && !!user,
    };
  });

  useEffect(() => {
    const storedToken = localStorage.getItem(TOKEN_KEY);
    if (storedToken) {
      (async () => {
        try {
          const { getMe, updateSettings: updateSettingsApi } = await import('../api/me');
          const { getBrowserTimezone } = await import('../utils/date');
          let data = await getMe();
          data = await applyTimezoneIfMissing(data, updateSettingsApi, getBrowserTimezone);
          localStorage.setItem(USER_KEY, JSON.stringify(data));
          setState({
            user: data,
            token: storedToken,
            isLoading: false,
            isAuthenticated: true,
          });
        } catch {
          localStorage.removeItem(TOKEN_KEY);
          localStorage.removeItem(USER_KEY);
          setState({ user: null, token: null, isLoading: false, isAuthenticated: false });
        }
      })();
    }
  }, []);

  const login = useCallback(async (username, password) => {
    setState(prev => ({ ...prev, isLoading: true }));
    try {
      const { login: loginApi } = await import('../api/auth');
      const { updateSettings: updateSettingsApi } = await import('../api/me');
      const { getBrowserTimezone } = await import('../utils/date');
      const response = await loginApi(username, password);
      if (response.error) throw new Error(response.error.message);
      let user = await applyTimezoneIfMissing(response.data.user, updateSettingsApi, getBrowserTimezone);
      localStorage.setItem(TOKEN_KEY, response.data.token);
      localStorage.setItem(USER_KEY, JSON.stringify(user));
      setState({
        user,
        token: response.data.token,
        isLoading: false,
        isAuthenticated: true,
      });
    } catch (err) {
      setState(prev => ({ ...prev, isLoading: false }));
      throw err;
    }
  }, []);

  const startRegistration = useCallback(async (payload) => {
    const { startRegistration: startRegistrationApi } = await import('../api/auth');
    const response = await startRegistrationApi(payload);
    if (response.error) throw new Error(response.error.message);
    return response.data;
  }, []);

  const completeRegistration = useCallback(async (pendingToken, code) => {
    const { verifyRegistration } = await import('../api/auth');
    const { updateSettings: updateSettingsApi } = await import('../api/me');
    const { getBrowserTimezone } = await import('../utils/date');
    const response = await verifyRegistration(pendingToken, code);
    if (response.error) throw new Error(response.error.message);
    let user = await applyTimezoneIfMissing(response.data.user, updateSettingsApi, getBrowserTimezone);
    localStorage.setItem(TOKEN_KEY, response.data.token);
    localStorage.setItem(USER_KEY, JSON.stringify(user));
    setState({
      user,
      token: response.data.token,
      isLoading: false,
      isAuthenticated: true,
    });
  }, []);

  const updateSettings = useCallback(async (key, value) => {
    const { updateSettings: updateSettingsApi } = await import('../api/me');
    const result = await updateSettingsApi(key, value);
    setState(prev => {
      if (!prev.user) return prev;
      const updated = { ...prev.user, settings: result.settings };
      localStorage.setItem(USER_KEY, JSON.stringify(updated));
      return {
        ...prev,
        user: updated,
      };
    });
  }, []);

  const logout = useCallback(() => {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
    setState({
      user: null,
      token: null,
      isLoading: false,
      isAuthenticated: false,
    });
  }, []);

  return (
    <AuthContext.Provider
      value={{
        ...state,
        login,
        startRegistration,
        completeRegistration,
        updateSettings,
        logout,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) throw new Error('useAuth must be used within an AuthProvider');
  return context;
}
