import { Link, Stack, Typography } from '@mui/material';
import { Link as RouterLink } from 'react-router-dom';
import { showPreferences } from '../cookieConsent/config';

export default function LegalFooter() {
  return (
    <Stack
      direction={{ xs: 'column', sm: 'row' }}
      spacing={2}
      justifyContent="center"
      alignItems="center"
      sx={{ pt: 4, borderTop: 1, borderColor: 'divider' }}
    >
      <Typography
        component={RouterLink}
        to="/privacy"
        variant="body2"
        color="text.secondary"
        sx={{ textDecoration: 'none', '&:hover': { textDecoration: 'underline' } }}
      >
        Privacy Policy
      </Typography>
      <Typography
        component={RouterLink}
        to="/terms"
        variant="body2"
        color="text.secondary"
        sx={{ textDecoration: 'none', '&:hover': { textDecoration: 'underline' } }}
      >
        Terms & Conditions
      </Typography>
      <Link
        component="button"
        type="button"
        variant="body2"
        color="text.secondary"
        onClick={showPreferences}
        sx={{ cursor: 'pointer' }}
      >
        Cookie preferences
      </Link>
    </Stack>
  );
}
