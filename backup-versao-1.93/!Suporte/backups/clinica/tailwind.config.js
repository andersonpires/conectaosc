/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./**/*.{html,js,ts,tsx}",
    "./src/**/*.{html,js,ts,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        'monday-blue': '#18385c',
        'monday-blue-light': '#2c5a8a',
        'purple-accent': '#6b4ea2',
        'primary': '#18385c',
        'secondary': '#2c5a8a',
        'neutral': { 50: '#fafafa', 100: '#f5f5f5', 200: '#e5e5e5', 300: '#d4d4d4', 400: '#a3a3a3', 500: '#737373', 600: '#525252', 700: '#404040', 800: '#262626', 900: '#171717' },
        'semantic': { success: '#22c55e', warning: '#f59e0b', error: '#ef4444', info: '#3b82f6' },
      },
      fontFamily: {
        sans: ['Figtree', 'system-ui', 'sans-serif'],
        mono: ['Roboto Mono', 'monospace'],
      },
      spacing: {
        '18': '4.5rem',
        '22': '5.5rem',
        '4.5': '1.125rem',
        '5.5': '1.375rem',
      },
      borderRadius: {
        'xl': '1rem',
        '2xl': '1.5rem',
      },
      boxShadow: {
        'monday': '0 2px 8px rgba(24, 56, 92, 0.15)',
        'monday-lg': '0 4px 20px rgba(24, 56, 92, 0.2)',
        'shadow-monday': '0 2px 8px rgba(24, 56, 92, 0.15)',
      },
      minHeight: {
        'touch': '44px',
      },
      minWidth: {
        'touch': '44px',
      },
      screens: {
        'xs': '375px',
      },
      transitionDuration: {
        '250': '250ms',
      },
    },
  },
  plugins: [],
};
