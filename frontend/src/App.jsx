import { Routes, Route, Navigate } from 'react-router-dom';
import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';
import LandingPage from './pages/LandingPage';
import DashboardPage from './pages/DashboardPage';
import SettingsPage from './pages/SettingsPage';
import HelpPage from './pages/HelpPage';
import AboutPage from './pages/AboutPage';
import ContactPage from './pages/ContactPage';
import { DEFAULT_HELP_TOPIC_ID } from './help/helpTopics';
import AdminUsersPage from './pages/AdminUsersPage';
import AdminSubmissionsPage from './pages/AdminSubmissionsPage';
import TermsPage from './pages/TermsPage';
import PrivacyPolicyPage from './pages/PrivacyPolicyPage';
import AnalyticsRouteTracker from './components/AnalyticsRouteTracker';
import CookieConsentManager from './components/CookieConsentManager';
import ProtectedRoute from './components/ProtectedRoute';
import AdminRoute from './components/AdminRoute';
import PublicLayout from './components/PublicLayout';
import Layout from './components/Layout';
import PageContainer from './components/PageContainer';

export default function App() {
  return (
    <>
      <CookieConsentManager />
      <AnalyticsRouteTracker />
      <Routes>
      <Route element={<PublicLayout />}>
        <Route index element={<LandingPage />} />
        <Route path="terms" element={<TermsPage />} />
        <Route path="privacy" element={<PrivacyPolicyPage />} />
        <Route path="login" element={
          <PageContainer fullPage><LoginPage /></PageContainer>
        } />
        <Route path="register" element={
          <PageContainer fullPage><RegisterPage /></PageContainer>
        } />
      </Route>
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
        <Route path="/contact" element={<ProtectedRoute><ContactPage /></ProtectedRoute>} />
        <Route path="/admin/users" element={
          <ProtectedRoute>
            <AdminRoute><AdminUsersPage /></AdminRoute>
          </ProtectedRoute>
        } />
        <Route path="/admin/submissions" element={
          <ProtectedRoute>
            <AdminRoute><AdminSubmissionsPage /></AdminRoute>
          </ProtectedRoute>
        } />
      </Route>
    </Routes>
    </>
  );
}
