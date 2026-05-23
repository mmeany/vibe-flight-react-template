import { Routes, Route } from 'react-router-dom';
import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';
import DashboardPage from './pages/DashboardPage';
import SettingsPage from './pages/SettingsPage';
import AdminUsersPage from './pages/AdminUsersPage';
import ProtectedRoute from './components/ProtectedRoute';
import AdminRoute from './components/AdminRoute';
import Layout from './components/Layout';
import PageContainer from './components/PageContainer';

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={
        <PageContainer fullPage><LoginPage /></PageContainer>
      } />
      <Route path="/register" element={
        <PageContainer fullPage><RegisterPage /></PageContainer>
      } />
      <Route element={<Layout />}>
        <Route path="/" element={<ProtectedRoute><DashboardPage /></ProtectedRoute>} />
        <Route path="/settings" element={<ProtectedRoute><SettingsPage /></ProtectedRoute>} />
        <Route path="/admin/users" element={
          <ProtectedRoute>
            <AdminRoute><AdminUsersPage /></AdminRoute>
          </ProtectedRoute>
        } />
      </Route>
    </Routes>
  );
}
