import { Box } from '@mui/material';
import { Outlet } from 'react-router-dom';
import Header from './Header';
import PageContainer from './PageContainer';

export default function Layout() {
  return (
    <Box sx={{ display: 'flex', flexDirection: 'column', minHeight: '100vh' }}>
      <Header />
      <Box component="main" sx={{ flexGrow: 1 }}>
        <PageContainer>
          <Outlet />
        </PageContainer>
      </Box>
    </Box>
  );
}