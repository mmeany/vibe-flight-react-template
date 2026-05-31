import {
  Dashboard as DashboardIcon,
  Help as HelpIcon,
  Info as InfoIcon,
  Logout as LogoutIcon,
  Menu as MenuIcon,
  People as PeopleIcon,
  Settings as SettingsIcon,
} from '@mui/icons-material';
import {
  AppBar,
  Avatar,
  Box,
  Button,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  Drawer,
  IconButton,
  List,
  ListItemButton,
  ListItemIcon,
  ListItemText,
  Menu,
  MenuItem,
  Toolbar,
  Typography,
  useMediaQuery,
  useTheme as useMuiTheme,
} from '@mui/material';
import { useState } from 'react';
import { Link as RouterLink, useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

export default function Header() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const muiTheme = useMuiTheme();
  const isMobile = useMediaQuery(muiTheme.breakpoints.down('sm'));

  const appName = 'Flight React App';
  const [anchorEl, setAnchorEl] = useState(null);
  const [drawerOpen, setDrawerOpen] = useState(false);
  const [logoutDialogOpen, setLogoutDialogOpen] = useState(false);

  const handleAvatarClick = (event) => setAnchorEl(event.currentTarget);
  const handleMenuClose = () => setAnchorEl(null);
  const handleDrawerToggle = () => setDrawerOpen(prev => !prev);

  const handleLogoutConfirm = () => {
    setLogoutDialogOpen(false);
    handleMenuClose();
    setDrawerOpen(false);
    logout();
    navigate('/');
  };

  const navigateTo = (path) => {
    handleMenuClose();
    setDrawerOpen(false);
    navigate(path);
  };

  const openLogoutDialog = () => {
    handleMenuClose();
    setDrawerOpen(false);
    setLogoutDialogOpen(true);
  };

  const displayName = user?.settings?.user_alias || user?.username || 'User';
  const initials = displayName.charAt(0).toUpperCase();
  const isAdmin = Boolean(user?.is_admin);

  const drawerContent = (
    <Box sx={{ width: 250 }}>
      <Box sx={{
        p: 2, bgcolor: 'primary.main', color: 'primary.contrastText',
        display: 'flex', alignItems: 'center', gap: 2,
      }}>
        <Avatar sx={{ bgcolor: 'primary.contrastText', color: 'primary.main' }}>
          {initials}
        </Avatar>
        <Typography variant="subtitle1">{displayName}</Typography>
      </Box>
      <List>
        <ListItemButton onClick={() => navigateTo('/dashboard')}>
          <ListItemIcon><DashboardIcon /></ListItemIcon>
          <ListItemText primary="Dashboard" />
        </ListItemButton>
        <ListItemButton onClick={() => navigateTo('/settings')}>
          <ListItemIcon><SettingsIcon /></ListItemIcon>
          <ListItemText primary="Settings" />
        </ListItemButton>
        <ListItemButton onClick={() => navigateTo('/help')}>
          <ListItemIcon><HelpIcon /></ListItemIcon>
          <ListItemText primary="Help" />
        </ListItemButton>
        <ListItemButton onClick={() => navigateTo('/about')}>
          <ListItemIcon><InfoIcon /></ListItemIcon>
          <ListItemText primary="About" />
        </ListItemButton>
        {isAdmin && (
          <ListItemButton onClick={() => navigateTo('/admin/users')}>
            <ListItemIcon><PeopleIcon /></ListItemIcon>
            <ListItemText primary="Users" />
          </ListItemButton>
        )}
      </List>
      <List>
        <ListItemButton onClick={openLogoutDialog}>
          <ListItemIcon><LogoutIcon /></ListItemIcon>
          <ListItemText primary="Logout" />
        </ListItemButton>
      </List>
    </Box>
  );

  return (
    <>
      <AppBar position="sticky">
        <Toolbar disableGutters>
          <Box sx={{
            maxWidth: muiTheme.custom.maxContentWidth,
            width: '100%', mx: 'auto', px: 2,
            display: 'flex', alignItems: 'center',
          }}>
            {isMobile && (
              <IconButton color="inherit" edge="start" onClick={handleDrawerToggle} sx={{ mr: 1 }}>
                <MenuIcon />
              </IconButton>
            )}
            <Typography
              variant="h6"
              component={RouterLink}
              to="/dashboard"
              sx={{ flexGrow: 1, textDecoration: 'none', color: 'inherit' }}
            >
              {appName}
            </Typography>
            <IconButton onClick={handleAvatarClick} sx={{ p: 0 }}>
              <Avatar sx={{ width: 32, height: 32, bgcolor: 'primary.dark' }}>
                {initials}
              </Avatar>
            </IconButton>
          </Box>
        </Toolbar>
      </AppBar>

      <Menu
        anchorEl={anchorEl}
        open={Boolean(anchorEl)}
        onClose={handleMenuClose}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
        transformOrigin={{ vertical: 'top', horizontal: 'right' }}
      >
        <MenuItem onClick={() => navigateTo('/dashboard')}>
          <ListItemIcon><DashboardIcon fontSize="small" /></ListItemIcon>
          Dashboard
        </MenuItem>
        <MenuItem onClick={() => navigateTo('/settings')}>
          <ListItemIcon><SettingsIcon fontSize="small" /></ListItemIcon>
          Settings
        </MenuItem>
        <MenuItem onClick={() => navigateTo('/help')}>
          <ListItemIcon><HelpIcon fontSize="small" /></ListItemIcon>
          Help
        </MenuItem>
        <MenuItem onClick={() => navigateTo('/about')}>
          <ListItemIcon><InfoIcon fontSize="small" /></ListItemIcon>
          About
        </MenuItem>
        {isAdmin && (
          <MenuItem onClick={() => navigateTo('/admin/users')}>
            <ListItemIcon><PeopleIcon fontSize="small" /></ListItemIcon>
            Users
          </MenuItem>
        )}
        <MenuItem onClick={openLogoutDialog}>
          <ListItemIcon><LogoutIcon fontSize="small" /></ListItemIcon>
          Logout
        </MenuItem>
      </Menu>

      <Drawer anchor="left" open={drawerOpen} onClose={handleDrawerToggle}>
        {drawerContent}
      </Drawer>

      <Dialog open={logoutDialogOpen} onClose={() => setLogoutDialogOpen(false)}>
        <DialogTitle>Confirm Logout</DialogTitle>
        <DialogContent>
          <Typography>Are you sure you want to logout?</Typography>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setLogoutDialogOpen(false)}>Cancel</Button>
          <Button onClick={handleLogoutConfirm} color="error" variant="contained">Logout</Button>
        </DialogActions>
      </Dialog>
    </>
  );
}
