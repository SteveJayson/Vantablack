import { useState } from 'react';

const API = 'http://localhost:8080/api';

export default function Login({ onLogin, onShowRegister, onShowForgotPassword }) {
    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        setLoading(true);

        try {
            const res = await fetch(`${API}/auth/login`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, password })
            });

            const data = await res.json();

            if (data.success) {
                localStorage.setItem('vantablack_token', data.data.token);
                localStorage.setItem('vantablack_user', JSON.stringify(data.data.user));
                onLogin(data.data.user);
            } else {
                setError(data.message || 'Login failed');
            }
        } catch (err) {
            setError('Cannot connect to server');
        } finally {
            setLoading(false);
        }
    };

    const fillDemo = (u, p) => {
        setUsername(u);
        setPassword(p);
    };

    return (
        <div style={styles.container}>
            <div style={styles.card}>
                <h1 style={styles.title}>⚡ VANTABLACK</h1>
                <h2 style={styles.subtitle}>🔐 SYSTEM LOGIN</h2>

                {error && <div style={styles.error}>{error}</div>}

                <form onSubmit={handleSubmit}>
                    <div style={styles.formGroup}>
                        <label style={styles.label}>USERNAME</label>
                        <input
                            type="text"
                            value={username}
                            onChange={(e) => setUsername(e.target.value)}
                            style={styles.input}
                            required
                        />
                    </div>

                    <div style={styles.formGroup}>
                        <label style={styles.label}>PASSWORD</label>
                        <input
                            type="password"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            style={styles.input}
                            required
                        />
                    </div>

                    {/* FORGOT PASSWORD LINK */}
                    <div style={styles.forgotPasswordRow}>
                        <span
                            onClick={onShowForgotPassword}
                            style={styles.forgotPasswordLink}
                        >
                            🔑 Forgot Password?
                        </span>
                    </div>

                    <button type="submit" disabled={loading} style={styles.button}>
                        {loading ? '⏳ LOGGING IN...' : '⚡ LOGIN'}
                    </button>
                </form>

                {/* REGISTER LINK */}
                <div style={styles.registerLink}>
                    Don't have an account?{' '}
                    <span
                        onClick={onShowRegister}
                        style={styles.registerLinkBtn}
                    >
                        Register here
                    </span>
                </div>
            </div>
        </div>
    );
}

const styles = {
    container: {
        minHeight: '100vh',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        background: '#0a0a1a',
        color: '#e0e0ff',
        fontFamily: 'monospace',
        padding: '20px'
    },
    card: {
        background: 'rgba(10, 10, 30, 0.85)',
        border: '1px solid rgba(0, 240, 255, 0.2)',
        borderRadius: '12px',
        padding: '30px',
        maxWidth: '450px',
        width: '100%'
    },
    title: {
        textAlign: 'center',
        color: '#00f0ff',
        fontSize: '1.5rem',
        marginBottom: '5px',
        marginTop: 0
    },
    subtitle: {
        textAlign: 'center',
        color: '#00f0ff',
        fontSize: '1rem',
        marginBottom: '25px',
        marginTop: 0
    },
    formGroup: { marginBottom: '20px' },
    label: {
        display: 'block',
        fontSize: '0.7rem',
        color: '#8888cc',
        marginBottom: '8px',
        letterSpacing: '2px'
    },
    input: {
        width: '100%',
        padding: '12px 15px',
        background: 'rgba(0,0,0,0.4)',
        border: '1px solid rgba(0, 240, 255, 0.2)',
        borderRadius: '8px',
        color: '#e0e0ff',
        fontSize: '0.9rem',
        fontFamily: 'monospace',
        boxSizing: 'border-box'
    },
    forgotPasswordRow: {
        textAlign: 'right',
        marginTop: '-10px',
        marginBottom: '15px'
    },
    forgotPasswordLink: {
        color: '#ffaa00',
        fontSize: '0.75rem',
        cursor: 'pointer',
        textDecoration: 'none'
    },
    button: {
        width: '100%',
        padding: '14px',
        background: 'linear-gradient(90deg, #00f0ff, #ff00ff)',
        color: '#0a0a1a',
        border: 'none',
        borderRadius: '8px',
        fontSize: '0.85rem',
        fontWeight: 'bold',
        letterSpacing: '2px',
        cursor: 'pointer',
        textTransform: 'uppercase',
        fontFamily: 'monospace'
    },
    error: {
        padding: '12px',
        background: 'rgba(255, 0, 68, 0.1)',
        border: '1px solid #ff0044',
        color: '#ff0044',
        borderRadius: '8px',
        marginBottom: '20px',
        fontSize: '0.8rem'
    },
    demo: {
        marginTop: '20px',
        padding: '15px',
        background: 'rgba(0,0,0,0.3)',
        borderRadius: '8px',
        fontSize: '0.7rem'
    },
    demoTitle: {
        color: '#ffaa00',
        fontSize: '0.7rem',
        marginBottom: '10px',
        marginTop: 0
    },
    demoItem: {
        display: 'flex',
        justifyContent: 'space-between',
        padding: '6px 0',
        color: '#8888cc',
        cursor: 'pointer'
    },
    registerLink: {
        textAlign: 'center',
        marginTop: '20px',
        fontSize: '0.8rem',
        color: '#8888cc'
    },
    registerLinkBtn: {
        color: '#00f0ff',
        fontWeight: 'bold',
        cursor: 'pointer',
        textDecoration: 'underline'
    }
};