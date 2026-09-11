import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
    root: '.',
    publicDir: 'public',
    
    server: {
        port: 5173,
        host: 'localhost',
        open: true,           // Auto-open browser
        cors: true,           // Enable CORS
        strictPort: false,    // Allow fallback if port busy
        headers: {
            'Access-Control-Allow-Origin': '*',
            'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers': 'Content-Type, Authorization'
        }
    },
    
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        sourcemap: true,
        rollupOptions: {
            input: {
                main: resolve(__dirname, 'index.html'),
                // Add more HTML files if you split them:
                // login: resolve(__dirname, 'login.html'),
                // register: resolve(__dirname, 'register.html'),
                // dashboard: resolve(__dirname, 'dashboard.html')
            }
        }
    },
    
    preview: {
        port: 5173,
        open: true
    }
});