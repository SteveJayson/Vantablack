import { useState } from 'react';

const API = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api';

export default function ForgotPassword({ onShowLogin }) {
    const [step, setStep] = useState(1); // 1 = enter username, 2 = reset password
    const [username, setUsername] = useState('');
    const [resetToken, setResetToken] = useState('');
    const [newPassword, setNewPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [loading, setLoading] = useState(false);

    // STEP 1: Request reset token
    const handleRequestToken = async (e) => {
        e.preventDefault();
        setError('');
        setSuccess('');
        setLoading(true);

        try {
            const res = await fetch(`${API}/auth/forgot-password`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username })
            });

            const data = await res.json();

            if (data.success) {
                if (data.data.reset_token) {
                    setResetToken(data.data.reset_token);
                    setSuccess(`Reset token generated! Valid for ${data.data.expires_in}.`);
                    setTimeout(() => setStep(2), 1500);
                } else {
                    setError(data.message || 'User not found');
                }
            } else {
                setError(data.message || 'Request failed');
            }
        } catch (err) {
            setError('Cannot connect to server');
        } finally {
            setLoading(false);
        }
    };

    // STEP 2: Reset password with token
    const handleResetPassword = async (e) => {
        e.preventDefault();
        setError('');
        setSuccess('');

        if (newPassword.length < 6) {
            setError('Password must be at least 6 characters');
            return;
        }

        if (newPassword !== confirmPassword) {
            setError('Passwords do not match');
            return;
        }

        setLoading(true);

        try {
            const res = await fetch(`${API}/auth/reset-password`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    username,
                    reset_token: resetToken,
                    new_password: newPassword
                })
            });

            const data = await res.json();

            if (data.success) {
                setSuccess('Password reset successful! Redirecting to login...');
                setTimeout(() => onShowLogin(), 2000);
            } else {
                setError(data.message || 'Reset failed');
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
                <h1 style={styles.title}>VANTABLACK</h1>
                <h2 style={styles.subtitle}>RESET PASSWORD</h2>

                {/* Progress Indicator */}
                <div style={styles.progress}>
                    <div style={{
                        ...styles.progressStep,
                        ...(step >= 1 ? styles.progressActive : {})
                    }}>
                        1. Username
                    </div>
                    <div style={styles.progressLine} />
                    <div style={{
                        ...styles.progressStep,
                        ...(step >= 2 ? styles.progressActive : {})
                    }}>
                        2. New Password
                    </div>
                </div>

                {error && <div style={styles.error}>{error}</div>}
                {success && <div style={styles.success}>{success}</div>}

                {step === 1 && (
                    <form onSubmit={handleRequestToken}>
                        <p style={styles.info}>
                            Enter your username and we'll generate a reset token for you.
                        </p>

                        <div style={styles.formGroup}>
                            <label style={styles.label}>USERNAME</label>
                            <input
                                type="text"
                                value={username}
                                onChange={(e) => setUsername(e.target.value)}
                                style={styles.input}
                                placeholder="Enter your username"
                                required
                                autoFocus
                            />
                        </div>

                        <button type="submit" disabled={loading} style={styles.button}>
                            {loading ? 'GENERATING...' : 'GET RESET TOKEN'}
                        </button>
                    </form>
                )}

                {step === 2 && (
                    <form onSubmit={handleResetPassword}>
                        <div style={styles.tokenBox}>
                            <div style={styles.tokenLabel}>Reset Token (auto-filled):</div>
                            <input
                                type="text"
                                value={resetToken}
                                onChange={(e) => setResetToken(e.target.value)}
                                style={styles.tokenInput}
                                required
                            />
                        </div>

                        <div style={styles.formGroup}>
                            <label style={styles.label}>NEW PASSWORD</label>
                            <input
                                type="password"
                                value={newPassword}
                                onChange={(e) => setNewPassword(e.target.value)}
                                style={styles.input}
                                placeholder="Min 6 characters"
                                required
                                autoFocus
                            />
                        </div>

                        <div style={styles.formGroup}>
                            <label style={styles.label}>CONFIRM NEW PASSWORD</label>
                            <input
                                type="password"
                                value={confirmPassword}
                                onChange={(e) => setConfirmPassword(e.target.value)}
                                style={styles.input}
                                placeholder="Confirm password"
                                required
                            />
                        </div>

                        <button type="submit" disabled={loading} style={styles.button}>
                            {loading ? 'RESETTING...' : 'RESET PASSWORD'}
                        </button>
                    </form>
                )}

                <div style={styles.linkContainer}>
                    <span
                        onClick={onShowLogin}
                        style={styles.link}
                    >
                        Back to Login
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
    progress: {
        display: 'flex',
        alignItems: 'center',
        marginBottom: '25px',
        justifyContent: 'center'
    },
    progressStep: {
        padding: '6px 12px',
        borderRadius: '20px',
        fontSize: '0.65rem',
        color: '#8888cc',
        background: 'rgba(0,0,0,0.3)',
        border: '1px solid rgba(0,240,255,0.2)',
        whiteSpace: 'nowrap'
    },
    progressActive: {
        color: '#00f0ff',
        background: 'rgba(0,240,255,0.15)',
        borderColor: '#00f0ff',
        fontWeight: 'bold'
    },
    progressLine: {
        width: '30px',
        height: '2px',
        background: 'rgba(0,240,255,0.3)',
        margin: '0 8px'
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
        fontFamily: 'monospace',
        marginTop: '10px'
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
    success: {
        padding: '12px',
        background: 'rgba(0, 255, 136, 0.1)',
        border: '1px solid #00ff88',
        color: '#00ff88',
        borderRadius: '8px',
        marginBottom: '20px',
        fontSize: '0.8rem'
    },
    info: {
        fontSize: '0.75rem',
        color: '#8888cc',
        marginBottom: '20px',
        lineHeight: '1.5'
    },
    tokenBox: {
        padding: '12px',
        background: 'rgba(255, 170, 0, 0.1)',
        border: '1px solid #ffaa00',
        borderRadius: '8px',
        marginBottom: '20px'
    },
    tokenLabel: {
        fontSize: '0.65rem',
        color: '#ffaa00',
        marginBottom: '6px'
    },
    tokenInput: {
        width: '100%',
        padding: '8px 10px',
        background: 'rgba(0,0,0,0.6)',
        border: '1px solid rgba(255, 170, 0, 0.4)',
        borderRadius: '6px',
        color: '#ffaa00',
        fontSize: '0.7rem',
        fontFamily: 'monospace',
        boxSizing: 'border-box'
    },
    linkContainer: {
        textAlign: 'center',
        marginTop: '20px'
    },
    link: {
        color: '#00f0ff',
        fontSize: '0.8rem',
        fontWeight: 'bold',
        cursor: 'pointer',
        textDecoration: 'underline'
    }
};