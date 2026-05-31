import { Box } from '@mui/material';
import { Routes, Route, Navigate } from 'react-router-dom';
import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';
import LandingPage from './pages/LandingPage';
import DashboardPage from './pages/DashboardPage';
import SettingsPage from './pages/SettingsPage';
import HelpPage from './pages/HelpPage';
import AboutPage from './pages/AboutPage';
import { DEFAULT_HELP_TOPIC_ID } from './help/helpTopics';
import AdminUsersPage from './pages/AdminUsersPage';
import ProtectedRoute from './components/ProtectedRoute';
import AdminRoute from './components/AdminRoute';
import GuestRoute from './components/GuestRoute';
import PublicHeader from './components/PublicHeader';
import Layout from './components/Layout';
import PageContainer from './components/PageContainer';

export default function App() {
  return (
    <Routes>
      <Route
        path="/"
        element={
          <GuestRoute>
            <Box sx={{ minHeight: '100vh', display: 'flex', flexDirection: 'column' }}>
              <PublicHeader />
              <LandingPage />
            </Box>
          </GuestRoute>
        }
      />
      <Route path="/login" element={
        <PageContainer fullPage><LoginPage /></PageContainer>
      } />
      <Route path="/register" element={
        <PageContainer fullPage><RegisterPage /></PageContainer>
      } />
      <Route element={<Layout />}>
        <Route path="/dashboard" element={
          <ProtectedRoute><DashboardPage /></ProtectedRoute>
        } />
        <Route path="/settings" element={<ProtectedRoute><SettingsPage /></ProtectedRoute>} />
        <Route path="/help" element={
          <ProtectedRoute>
            <Navigate to={`/help/${DEFAULT_HELP_TOPIC_ID}`} replace />
          </ProtectedRoute>
        } />
        <Route path="/help/:topicId" element={<ProtectedRoute><HelpPage /></ProtectedRoute>} />
        <Route path="/about" element={<ProtectedRoute><AboutPage /></ProtectedRoute>} />
        <Route path="/admin/users" element={
          <ProtectedRoute>
            <AdminRoute><AdminUsersPage /></AdminRoute>
          </ProtectedRoute>
        } />
      </Route>
    </Routes>
  );
}
