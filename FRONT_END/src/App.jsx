import { useState } from 'react';
import Login from './pages/Login';
import Register from './pages/Register';
import Dashboard from './pages/Dashboard';
import ForgotPassword from './pages/ForgotPassword';
import Combat from './pages/Combat';
import Crafting from './pages/Crafting';

export default function App() {
    const [user, setUser] = useState(() => {
        const saved = localStorage.getItem('aegis_user');
        return saved ? JSON.parse(saved) : null;
    });
    const [screen, setScreen] = useState(user ? 'dashboard' : 'login');

    const handleLogin = (userData) => {
        setUser(userData);
        setScreen('dashboard');
    };

    const handleLogout = () => {
        localStorage.removeItem('aegis_token');
        localStorage.removeItem('aegis_user');
        setUser(null);
        setScreen('login');
    };

    const handleBattleComplete = (result) => {
        if (result.attackerNewCredits !== undefined) {
            const updatedUser = { ...user, credits: result.attackerNewCredits };
            setUser(updatedUser);
            localStorage.setItem('aegis_user', JSON.stringify(updatedUser));
        }
    };

    const handleCraftComplete = async () => {
        // Refresh user credits
        try {
            const token = localStorage.getItem('aegis_token');
            const res = await fetch('http://localhost:8080/api/auth/me', {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const data = await res.json();
            if (data.success) {
                setUser(data.data.user);
                localStorage.setItem('aegis_user', JSON.stringify(data.data.user));
            }
        } catch (err) { }
    };

    if (screen === 'crafting' && user) {
        return (
            <Crafting
                user={user}
                onBack={() => setScreen('dashboard')}
                onCraftComplete={handleCraftComplete}
            />
        );
    }

    if (screen === 'combat' && user) {
        return (
            <Combat
                user={user}
                onBack={() => setScreen('dashboard')}
                onBattleComplete={handleBattleComplete}
            />
        );
    }

    if (screen === 'dashboard' && user) {
        return (
            <Dashboard
                user={user}
                onLogout={handleLogout}
                onShowCombat={() => setScreen('combat')}
                onShowCrafting={() => setScreen('crafting')}
            />
        );
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
            <ForgotPassword onShowLogin={() => setScreen('login')} />
        );
    }

    return (
        <Login
            onLogin={handleLogin}
            onShowRegister={() => setScreen('register')}
            onShowForgotPassword={() => setScreen('forgot')}
        />
    );
}