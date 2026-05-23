import { Box, Typography } from '@mui/material';
import { APP_VERSION } from '../version';

export default function DashboardPage() {
  return (
    <Box sx={{ display: 'flex', flexDirection: 'column', minHeight: '100%', pb: 5 }}>
      <Box sx={{ flexGrow: 1 }} />

      <Box
        component="footer"
        sx={{
          position: 'fixed',
          left: 0,
          right: 0,
          bottom: 0,
          py: 1,
          textAlign: 'center',
          bgcolor: 'background.default',
          borderTop: 1,
          borderColor: 'divider',
        }}
      >
        <Typography variant="caption" color="text.secondary">
          Version {APP_VERSION}
        </Typography>
      </Box>
    </Box>
  );
}
