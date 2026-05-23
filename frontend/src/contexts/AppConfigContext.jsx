import { createContext, useContext, useState, useEffect } from 'react';
import { fetchPublicConfig } from '../api/config';

const AppConfigContext = createContext(null);

export function AppConfigProvider({ children }) {
  const [isRegistrationEnabled, setIsRegistrationEnabled] = useState(true);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    fetchPublicConfig()
      .then(data => {
        setIsRegistrationEnabled(data.registration.enabled);
      })
      .catch(() => {
        setIsRegistrationEnabled(true);
      })
      .finally(() => {
        setIsLoading(false);
      });
  }, []);

  return (
    <AppConfigContext.Provider value={{ isRegistrationEnabled, isLoading }}>
      {children}
    </AppConfigContext.Provider>
  );
}

export function useAppConfig() {
  const context = useContext(AppConfigContext);
  if (!context) throw new Error('useAppConfig must be used within AppConfigProvider');
  return context;
}
