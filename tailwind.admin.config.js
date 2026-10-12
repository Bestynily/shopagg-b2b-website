/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './app/views/admin/**/*.php',
    './app/Controllers/**/*.php',
    './app/Core/**/*.php',
    './app/Plugins/**/*.php',
    './assets/admin/base.js'
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: [
          '-apple-system',
          'BlinkMacSystemFont',
          '"Segoe UI"',
          'Roboto',
          'Oxygen',
          'Ubuntu',
          'Cantarell',
          '"PingFang SC"',
          '"Hiragino Sans GB"',
          '"Microsoft YaHei"',
          '"Helvetica Neue"',
          'sans-serif'
        ]
      },
      colors: {
        admin: {
          primary: '#667eea',
          primaryDark: '#4c51bf',
          slate: '#0f172a'
        }
      },
      boxShadow: {
        admin: '0 12px 40px rgba(15, 23, 42, 0.12)'
      }
    }
  }
};
