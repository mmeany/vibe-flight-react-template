import { Paper } from '@mui/material';
import AuthenticatedContactForm from '../components/AuthenticatedContactForm';

export default function ContactPage() {
  return (
    <Paper sx={{ p: { xs: 2, sm: 3 } }}>
      <AuthenticatedContactForm />
    </Paper>
  );
}
