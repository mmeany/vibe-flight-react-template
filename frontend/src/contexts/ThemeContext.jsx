import { createContext, useContext, useState, useCallback, useEffect, useMemo } from 'react';
import { createTheme } from '@mui/material/styles';
import { useAuth } from './AuthContext';

const ThemeContext = createContext(null);

export function ThemeProvider({ children }) {
  const { user, updateSettings } = useAuth();
  const serverSettings = user?.settings;

  const [themeMode, setThemeMode] = useState(() => {
    if (serverSettings?.theme_mode) return serverSettings.theme_mode;
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  });

  const [dateFormat, setDateFormat] = useState(
    () => serverSettings?.date_format || 'MM/DD/YYYY'
  );

  useEffect(() => {
    if (serverSettings?.theme_mode) {
      // eslint-disable-next-line react-hooks/set-state-in-effect
      setThemeMode(serverSettings.theme_mode);
    }
  }, [serverSettings?.theme_mode]);

  useEffect(() => {
    if (serverSettings?.date_format) {
      // eslint-disable-next-line react-hooks/set-state-in-effect
      setDateFormat(serverSettings.date_format);
    }
  }, [serverSettings?.date_format]);

  const handleSetThemeMode = useCallback(async (mode) => {
    setThemeMode(mode);
    try {
      await updateSettings('theme_mode', mode);
    } catch {
      // revert on failure
    }
  }, [updateSettings]);

  const handleSetDateFormat = useCallback(async (format) => {
    setDateFormat(format);
    try {
      await updateSettings('date_format', format);
    } catch {
      // revert on failure
    }
  }, [updateSettings]);

  const toggleTheme = useCallback(() => {
    handleSetThemeMode(themeMode === 'light' ? 'dark' : 'light');
  }, [themeMode, handleSetThemeMode]);

  const theme = useMemo(
    () =>
      createTheme({
        palette: {
          mode: themeMode,
          primary: { main: themeMode === 'dark' ? '#90caf9' : '#1976d2' },
        },
        custom: {
          maxContentWidth: {
            xs: '100%',
            sm: 600,
            md: 900,
            lg: 1200,
            xl: 1536,
          },
        },
      }),
    [themeMode]
  );

  return (
    <ThemeContext.Provider
      value={{
        theme,
        themeMode,
        dateFormat,
        toggleTheme,
        setDateFormat: handleSetDateFormat,
      }}
    >
      {children}
    </ThemeContext.Provider>
  );
}

export function useThemeContext() {
  const context = useContext(ThemeContext);
  if (!context) throw new Error('useThemeContext must be used within a ThemeProvider');
  return context;
}
