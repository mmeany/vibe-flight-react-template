import { Alert, Box } from '@mui/material';
import { useAuth } from '../contexts/AuthContext';

export default function AdminRoute({ children }) {
  const { user, isLoading } = useAuth();

  if (isLoading) {
    return null;
  }

  if (!user?.is_admin) {
    return (
      <Box sx={{ py: 2 }}>
        <Alert severity="error">You do not have permission to access this page.</Alert>
      </Box>
    );
  }

  return children;
}
