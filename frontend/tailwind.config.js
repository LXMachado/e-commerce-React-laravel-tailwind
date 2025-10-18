/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#f0f9f7',
          100: '#dcf2ed',
          200: '#bce4db',
          300: '#8fd1c2',
          400: '#5eb8a5',
          500: '#419e8f',
          600: '#35847a',
          700: '#2e6e65',
          800: '#295c55',
          900: '#254e48',
          950: '#1a352f',
        },
      },
    },
  },
  plugins: [],
}