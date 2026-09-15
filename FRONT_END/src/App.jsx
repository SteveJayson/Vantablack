import { useState } from 'react';
import Login from './pages/Login';
import Register from './pages/Register';
import Dashboard from './pages/Dashboard';
import ForgotPassword from './pages/ForgotPassword';

export default function App() {
    const [user, setUser] = useState(() => {
        const saved = localStorage.getItem('vantablack_user') || localStorage.getItem('aegis_user');
        return saved ? JSON.parse(saved) : null;
    });
    const [screen, setScreen] = useState(user ? 'dashboard' : 'login');

    const handleLogin = (userData) => {
        setUser(userData);
        setScreen('dashboard');
    };

    const handleLogout = () => {
        localStorage.removeItem('vantablack_token');
        localStorage.removeItem('vantablack_user');
        localStorage.removeItem('aegis_token');
        localStorage.removeItem('aegis_user');
        setUser(null);
        setScreen('login');
    };

    if (screen === 'dashboard' && user) {
        return <Dashboard user={user} onLogout={handleLogout} />;
    }

    if (screen === 'register') {
        return (
            <Register
                onRegister={handleLogin}
                onShowLogin={() => setScreen('login')}
            />
        );
    }

    if (screen === 'forgot') {
        return (
            <ForgotPassword
                onShowLogin={() => setScreen('login')}
            />
        );
    }

    return (
        <Login
            onLogin={handleLogin}
            onShowRegister={() => setScreen('register')}
            onShowForgotPassword={() => setScreen('forgot')}
            onShowLogin={() => setScreen('login')}
        />
    );
}