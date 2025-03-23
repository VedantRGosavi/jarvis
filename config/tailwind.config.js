/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./public/**/*.{html,js}",
    "./app/**/*.php"
  ],
  theme: {
    extend: {
      colors: {
        'gaming-primary': '#2563eb',
        'gaming-secondary': '#1e293b',
        'gaming-accent': '#f97316',
        'gaming-dark': '#111827',
        'gaming-light': '#f8fafc',
      },
    },
  },
  plugins: [],
  darkMode: 'class',
} 