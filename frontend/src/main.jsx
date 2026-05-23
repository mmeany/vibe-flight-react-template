import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { ThemeProvider as MuiThemeProvider, CssBaseline } from '@mui/material';
import App from './App';
import { AuthProvider } from './contexts/AuthContext';
import { AppConfigProvider } from './contexts/AppConfigContext';
import { ThemeProvider, useThemeContext } from './contexts/ThemeContext';

function ThemeWrapper({ children }) {
  const { theme } = useThemeContext();
  return <MuiThemeProvider theme={theme}>{children}</MuiThemeProvider>;
}

export { ThemeWrapper };

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <BrowserRouter basename={import.meta.env.BASE_URL}>
      <AuthProvider>
        <ThemeProvider>
          <ThemeWrapper>
            <CssBaseline />
            <AppConfigProvider>
              <App />
            </AppConfigProvider>
          </ThemeWrapper>
        </ThemeProvider>
      </AuthProvider>
    </BrowserRouter>
  </StrictMode>
);
