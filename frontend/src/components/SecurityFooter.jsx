import { Box, Link, Stack, Typography } from '@mui/material';
import { Link as RouterLink } from 'react-router-dom';
import { showPreferences } from '../cookieConsent/config';
import { APP_VERSION } from '../version';

const linkSx = {
  textDecoration: 'none',
  '&:hover': { textDecoration: 'underline' },
};

export default function SecurityFooter() {
  return (
    <Box
      component="footer"
      sx={{
        position: 'fixed',
        left: 0,
        right: 0,
        bottom: 0,
        py: 1.5,
        px: 2,
        textAlign: 'center',
        bgcolor: 'background.default',
        borderTop: 1,
        borderColor: 'divider',
      }}
    >
      <Stack
        direction={{ xs: 'column', sm: 'row' }}
        spacing={{ xs: 0.5, sm: 2 }}
        justifyContent="center"
        alignItems="center"
        sx={{ mb: 0.5 }}
      >
        <Typography
          component={RouterLink}
          to="/contact"
          variant="body2"
          color="text.secondary"
          sx={linkSx}
        >
          Contact Us
        </Typography>
        <Link
          component="button"
          type="button"
          variant="body2"
          color="text.secondary"
          onClick={showPreferences}
          sx={{ cursor: 'pointer', ...linkSx }}
        >
          Cookie preferences
        </Link>
      </Stack>
      <Typography variant="caption" color="text.secondary">
        Version {APP_VERSION}
      </Typography>
    </Box>
  );
}
