/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./public/**/*.{html,js}",
    "./app/**/*.php"
  ],
  theme: {
    extend: {
      colors: {
        'gaming-primary': '#121212',    // black
        'gaming-secondary': '#282828',  // dark gray
        'gaming-accent': '#404040',     // dark gray (replacing blue)
        'gaming-dark': '#1c1c1c',       // very dark gray
        'gaming-light': '#f8f8f8',      // white
        'gaming-gray': {
          100: '#e5e5e5',
          200: '#c7c7c7',
          300: '#a3a3a3',
          400: '#737373',
          500: '#525252',
          600: '#404040',
          700: '#282828',
          800: '#1c1c1c',
        },
        'gaming-success': '#1f8c3b',
        'gaming-warning': '#bb7d00',
        'gaming-error': '#c11c1c',
      },
      fontFamily: {
        'sans': ['Inter', 'sans-serif'],
      },
    },
  },
  plugins: [],
  darkMode: 'class',
} 