import { AppBar, Box, Button, Toolbar, Typography, useTheme } from '@mui/material';
import { Link as RouterLink } from 'react-router-dom';
import { useAppConfig } from '../contexts/AppConfigContext';
import { LANDING_APP_NAME } from '../landing/landingContent';

export default function PublicHeader() {
  const theme = useTheme();
  const { isRegistrationEnabled, isLoading: configLoading } = useAppConfig();

  return (
    <AppBar position="sticky" color="default" elevation={0} sx={{ borderBottom: 1, borderColor: 'divider' }}>
      <Toolbar disableGutters>
        <Box
          sx={{
            maxWidth: theme.custom.maxContentWidth,
            width: '100%',
            mx: 'auto',
            px: 2,
            display: 'flex',
            alignItems: 'center',
            gap: 1,
          }}
        >
          <Typography
            variant="h6"
            component={RouterLink}
            to="/"
            sx={{ flexGrow: 1, textDecoration: 'none', color: 'text.primary', fontWeight: 600 }}
          >
            {LANDING_APP_NAME}
          </Typography>
          <Button component={RouterLink} to="/login" color="primary">
            Sign in
          </Button>
          {!configLoading && isRegistrationEnabled && (
            <Button component={RouterLink} to="/register" variant="outlined" color="primary">
              Sign up
            </Button>
          )}
        </Box>
      </Toolbar>
    </AppBar>
  );
}
