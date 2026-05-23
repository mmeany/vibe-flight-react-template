import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, act } from '@testing-library/react';
import { ThemeProvider, useThemeContext } from './ThemeContext';

// Mock useAuth to return user with settings
const mockUpdateSettings = vi.fn();
let mockUser = null;

vi.mock('./AuthContext', () => ({
  useAuth: () => ({
    user: mockUser,
    updateSettings: mockUpdateSettings,
  }),
}));

function TestConsumer() {
  const ctx = useThemeContext();
  return (
    <div>
      <span data-testid="mode">{ctx.themeMode}</span>
      <span data-testid="format">{ctx.dateFormat}</span>
      <button data-testid="toggle" onClick={ctx.toggleTheme}>Toggle</button>
      <button data-testid="set-format" onClick={() => ctx.setDateFormat('DD/MM/YYYY')}>
        Set Format
      </button>
    </div>
  );
}

function renderTheme() {
  return render(
    <ThemeProvider>
      <TestConsumer />
    </ThemeProvider>
  );
}

describe('ThemeContext', () => {
  beforeEach(() => {
    mockUser = null;
    mockUpdateSettings.mockReset();
    mockUpdateSettings.mockResolvedValue({ settings: {} });
  });

  it('falls back to browser default when no server settings', () => {
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    renderTheme();
    expect(screen.getByTestId('mode').textContent).toBe(prefersDark ? 'dark' : 'light');
  });

  it('uses server theme_mode when available', () => {
    mockUser = { settings: { theme_mode: 'dark', date_format: 'YYYY-MM-DD' } };
    renderTheme();
    expect(screen.getByTestId('mode').textContent).toBe('dark');
    expect(screen.getByTestId('format').textContent).toBe('YYYY-MM-DD');
  });

  it('toggles theme and calls updateSettings', async () => {
    mockUser = { settings: { theme_mode: 'light' } };
    renderTheme();
    expect(screen.getByTestId('mode').textContent).toBe('light');

    await act(async () => {
      screen.getByTestId('toggle').click();
    });

    expect(screen.getByTestId('mode').textContent).toBe('dark');
    expect(mockUpdateSettings).toHaveBeenCalledWith('theme_mode', 'dark');
  });

  it('setDateFormat updates format and calls updateSettings', async () => {
    mockUser = { settings: { date_format: 'MM/DD/YYYY' } };
    renderTheme();

    await act(async () => {
      screen.getByTestId('set-format').click();
    });

    expect(screen.getByTestId('format').textContent).toBe('DD/MM/YYYY');
    expect(mockUpdateSettings).toHaveBeenCalledWith('date_format', 'DD/MM/YYYY');
  });
});