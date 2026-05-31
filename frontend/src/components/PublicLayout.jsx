import { Box } from '@mui/material';
import { Outlet } from 'react-router-dom';
import GuestRoute from './GuestRoute';
import PublicHeader from './PublicHeader';

export default function PublicLayout() {
  return (
    <GuestRoute>
      <Box sx={{ minHeight: '100vh', display: 'flex', flexDirection: 'column' }}>
        <PublicHeader />
        <Box component="main" sx={{ flexGrow: 1 }}>
          <Outlet />
        </Box>
      </Box>
    </GuestRoute>
  );
}
