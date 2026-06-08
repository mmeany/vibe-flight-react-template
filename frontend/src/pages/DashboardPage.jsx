import { Box } from '@mui/material';
import SecurityFooter from '../components/SecurityFooter';

export default function DashboardPage() {
  return (
    <Box sx={{ display: 'flex', flexDirection: 'column', minHeight: '100%', pb: 8 }}>
      <Box sx={{ flexGrow: 1 }} />
      <SecurityFooter />
    </Box>
  );
}
