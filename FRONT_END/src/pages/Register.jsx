import { useState } from 'react';

const API = 'http://localhost:8080/api';

export default function Register({ onRegister, onShowLogin }) {
    const [name, setName] = useState('');
    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');
    const [role, setRole] = useState('civilian');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);

    // ONLY 3 ROLES (no admin!)
    const roles = [
        {
            id: 'civilian',
            icon: '👤',
            name: 'CIVILIAN',
            color: '#ffaa00',
            desc: 'Regular citizen. Safe and simple.',
            benefits: ['✅ Buy gear', '❌ Cannot sell', '💰 500 credits']
        },
        {
            id: 'hero',
            icon: '🦸',
            name: 'HERO',
            color: '#00f0ff',
            desc: 'Defender of justice.',
            benefits: ['✅ Buy gear', '✅ Sell gear', '💰 5,000 credits']
        },
        {
            id: 'villain',
            icon: '🦹',
            name: 'VILLAIN',
            color: '#ff0044',
            desc: 'Master of chaos.',
            benefits: ['✅ Buy gear', '✅ Sell gear', '💰 5,000 credits']
        }
    ];

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        setLoading(true);

        try {
            const res = await fetch(`${API}/auth/register`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, password, name, role })
            });

            const data = await res.json();

            if (data.success) {
                localStorage.setItem('vantablack_token', data.data.token);
                localStorage.setItem('vantablack_user', JSON.stringify(data.data.user));
                onRegister(data.data.user);
            } else {
                setError(data.message || 'Registration failed');
            }
        } catch (err) {
            setError('Cannot connect to server');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div style={styles.container}>
            <div style={styles.card}>
                <h1 style={styles.title}>⚡ VANTABLACK</h1>
                <h2 style={styles.subtitle}>📝 CREATE ACCOUNT</h2>

                {error && <div style={styles.error}>{error}</div>}

                <form onSubmit={handleSubmit}>
                    <div style={styles.formGroup}>
                        <label style={styles.label}>DISPLAY NAME</label>
                        <input
                            type="text"
                            value={name}
                            onChange={(e) => setName(e.target.value)}
                            style={styles.input}
                            placeholder="e.g. John Doe"
                            required
                        />
                    </div>

                    <div style={styles.formGroup}>
                        <label style={styles.label}>USERNAME</label>
                        <input
                            type="text"
                            value={username}
                            onChange={(e) => setUsername(e.target.value)}
                            style={styles.input}
                            placeholder="e.g. johndoe"
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
                            placeholder="min 6 characters"
                            required
                        />
                    </div>

                    <h3 style={styles.roleTitle}>🎭 CHOOSE YOUR ROLE</h3>

                    <div style={styles.roleGrid}>
                        {roles.map(r => (
                            <div
                                key={r.id}
                                style={{
                                    ...styles.roleCard,
                                    borderColor: role === r.id ? r.color : 'rgba(0, 240, 255, 0.2)',
                                    background: role === r.id ? `${r.color}15` : 'rgba(0,0,0,0.4)',
                                    boxShadow: role === r.id ? `0 0 20px ${r.color}40` : 'none'
                                }}
                                onClick={() => setRole(r.id)}
                            >
                                <div style={{ fontSize: '2.5rem' }}>{r.icon}</div>
                                <div style={{
                                    color: r.color,
                                    fontWeight: 'bold',
                                    margin: '8px 0',
                                    fontSize: '0.9rem',
                                    letterSpacing: '1px'
                                }}>
                                    {r.name}
                                </div>
                                <div style={styles.roleDesc}>{r.desc}</div>
                                <div style={styles.roleBenefits}>
                                    {r.benefits.map((b, i) => (
                                        <div key={i} style={styles.benefitItem}>{b}</div>
                                    ))}
                                </div>
                            </div>
                        ))}
                    </div>

                    <button type="submit" disabled={loading} style={styles.button}>
                        {loading ? '⏳ CREATING...' : '⚡ CREATE ACCOUNT'}
                    </button>
                </form>

                <div style={styles.loginLink}>
                    Already have an account?{' '}
                    <span
                        onClick={onShowLogin}
                        style={styles.loginLinkBtn}
                    >
                        Login here
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
        maxWidth: '700px',
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
        marginTop: '20px',
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
    roleTitle: {
        color: '#00f0ff',
        fontSize: '0.85rem',
        textAlign: 'center',
        margin: '20px 0 15px',
        letterSpacing: '2px'
    },
    roleGrid: {
        display: 'grid',
        gridTemplateColumns: 'repeat(3, 1fr)',
        gap: '12px'
    },
    roleCard: {
        padding: '20px 15px',
        borderRadius: '12px',
        border: '2px solid',
        textAlign: 'center',
        cursor: 'pointer',
        transition: 'all 0.3s'
    },
    roleDesc: {
        fontSize: '0.65rem',
        color: '#8888cc',
        marginBottom: '10px'
    },
    roleBenefits: {
        fontSize: '0.6rem',
        color: '#8888cc',
        textAlign: 'left',
        paddingTop: '10px',
        borderTop: '1px solid rgba(255, 255, 255, 0.1)'
    },
    benefitItem: { padding: '2px 0' },
    loginLink: {
        textAlign: 'center',
        marginTop: '20px',
        fontSize: '0.8rem',
        color: '#8888cc'
    },
    loginLinkBtn: {
        color: '#00f0ff',
        fontWeight: 'bold',
        cursor: 'pointer',
        textDecoration: 'underline'
    }
};