import { useState, useEffect, useRef } from 'react';

const API = 'http://localhost:8080/api';

export default function Login({ onLogin, onShowRegister, onShowForgotPassword }) {
    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);
    const canvasRef = useRef(null);

    // Live animated anime-style background: neon city skyline + falling sakura petals.
    useEffect(() => {
        const canvas = canvasRef.current;
        if (!canvas) return;
        const ctx = canvas.getContext('2d');

        const prefersReducedMotion = window.matchMedia(
            '(prefers-reduced-motion: reduce)'
        ).matches;

        let width, height, buildings, petals, animationId, frame = 0;

        function resize() {
            width = canvas.width = window.innerWidth;
            height = canvas.height = window.innerHeight;
            buildings = createSkyline();
        }

        function createSkyline() {
            const list = [];
            let x = 0;
            while (x < width) {
                const w = 40 + Math.random() * 70;
                const h = height * (0.18 + Math.random() * 0.32);
                const windowRows = Math.floor(h / 18);
                const windowCols = Math.max(1, Math.floor(w / 14));
                const windows = [];
                for (let r = 0; r < windowRows; r++) {
                    for (let c = 0; c < windowCols; c++) {
                        if (Math.random() < 0.55) {
                            windows.push({
                                r, c,
                                lit: Math.random() < 0.7,
                                color: Math.random() < 0.5 ? '#ff8fd6' : '#7fe8ff',
                                phase: Math.random() * Math.PI * 2,
                            });
                        }
                    }
                }
                list.push({ x, w, h, windowRows, windowCols, windows });
                x += w + 2;
            }
            return list;
        }

        function createPetals() {
            const count = Math.min(60, Math.floor((width * height) / 22000));
            return Array.from({ length: count }, () => ({
                x: Math.random() * width,
                y: Math.random() * height,
                size: 5 + Math.random() * 6,
                speed: 0.4 + Math.random() * 0.6,
                sway: 0.6 + Math.random() * 1.2,
                swayPhase: Math.random() * Math.PI * 2,
                rotation: Math.random() * Math.PI * 2,
                rotationSpeed: (Math.random() - 0.5) * 0.03,
                hue: Math.random() < 0.7 ? '#ffc2e2' : '#fff0f6',
            }));
        }

        function drawSky() {
            const g = ctx.createLinearGradient(0, 0, 0, height);
            g.addColorStop(0, '#140b26');
            g.addColorStop(0.55, '#2a1240');
            g.addColorStop(1, '#4a1a4f');
            ctx.fillStyle = g;
            ctx.fillRect(0, 0, width, height);

            // Moon
            const moonX = width * 0.78;
            const moonY = height * 0.18;
            const moonGlow = ctx.createRadialGradient(moonX, moonY, 5, moonX, moonY, 90);
            moonGlow.addColorStop(0, 'rgba(255, 235, 200, 0.35)');
            moonGlow.addColorStop(1, 'rgba(255, 235, 200, 0)');
            ctx.fillStyle = moonGlow;
            ctx.beginPath();
            ctx.arc(moonX, moonY, 90, 0, Math.PI * 2);
            ctx.fill();

            ctx.fillStyle = '#fff3d6';
            ctx.beginPath();
            ctx.arc(moonX, moonY, 26, 0, Math.PI * 2);
            ctx.fill();
        }

        function drawSkyline(t) {
            for (const b of buildings) {
                ctx.fillStyle = '#150a22';
                ctx.fillRect(b.x, height - b.h, b.w, b.h);

                for (const win of b.windows) {
                    if (!win.lit) continue;
                    const flicker = 0.6 + 0.4 * Math.sin(t * 0.002 + win.phase);
                    ctx.fillStyle = win.color;
                    ctx.globalAlpha = flicker;
                    ctx.fillRect(
                        b.x + 6 + win.c * 14,
                        height - b.h + 8 + win.r * 18,
                        6, 8
                    );
                    ctx.globalAlpha = 1;
                }
            }
        }

        function drawPetal(p) {
            ctx.save();
            ctx.translate(p.x, p.y);
            ctx.rotate(p.rotation);
            ctx.fillStyle = p.hue;
            ctx.beginPath();
            ctx.ellipse(0, 0, p.size, p.size * 0.55, 0, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
        }

        function step(t) {
            frame = t;
            ctx.clearRect(0, 0, width, height);
            drawSky();
            drawSkyline(t);

            for (const p of petals) {
                p.y += p.speed;
                p.x += Math.sin(t * 0.001 * p.sway + p.swayPhase) * 0.6;
                p.rotation += p.rotationSpeed;

                if (p.y > height + 10) {
                    p.y = -10;
                    p.x = Math.random() * width;
                }
                if (p.x > width + 10) p.x = -10;
                if (p.x < -10) p.x = width + 10;

                drawPetal(p);
            }

            animationId = requestAnimationFrame(step);
        }

        resize();
        petals = createPetals();

        if (prefersReducedMotion) {
            drawSky();
            drawSkyline(0);
            petals.forEach(drawPetal);
        } else {
            animationId = requestAnimationFrame(step);
        }

        function handleResize() {
            resize();
            petals = createPetals();
        }
        window.addEventListener('resize', handleResize);

        return () => {
            window.removeEventListener('resize', handleResize);
            if (animationId) cancelAnimationFrame(animationId);
        };
    }, []);

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

    return (
        <div style={styles.container}>
            <canvas ref={canvasRef} style={styles.canvas} />

            {/* Heartbeat pulse animation, scoped to this component */}
            <style>{`
                @keyframes vb-heartbeat {
                    0%   { box-shadow: 0 0 0px 0px rgba(255, 143, 214, 0.3); }
                    14%  { box-shadow: 0 0 18px 3px rgba(255, 143, 214, 0.5); }
                    28%  { box-shadow: 0 0 4px 0px rgba(255, 143, 214, 0.25); }
                    42%  { box-shadow: 0 0 22px 4px rgba(255, 143, 214, 0.55); }
                    70%  { box-shadow: 0 0 0px 0px rgba(255, 143, 214, 0.3); }
                    100% { box-shadow: 0 0 0px 0px rgba(255, 143, 214, 0.3); }
                }
                .vb-pulse-card {
                    animation: vb-heartbeat 2.4s ease-in-out infinite;
                }
                .vb-pulse-btn:not(:disabled):hover {
                    filter: brightness(1.12);
                }
            `}</style>

            <div className="vb-pulse-card" style={styles.card}>
                <h1 style={styles.title}>VANTABLACK</h1>
                <p style={styles.subtitle}>System Login</p>

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
                            Forgot Password?
                        </span>
                    </div>

                    <button
                        type="submit"
                        disabled={loading}
                        className="vb-pulse-btn"
                        style={styles.button}
                    >
                        {loading ? 'LOGGING IN...' : 'LOGIN'}
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
        position: 'relative',
        minHeight: '100vh',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        color: '#f3f4f6',
        fontFamily: 'monospace',
        padding: '20px',
        overflow: 'hidden'
    },
    canvas: {
        position: 'absolute',
        top: 0,
        left: 0,
        width: '100%',
        height: '100%',
        zIndex: 0
    },
    card: {
        position: 'relative',
        zIndex: 1,
        background: '#252a31',
        border: '1px solid #3a424c',
        borderRadius: '12px',
        padding: '30px',
        maxWidth: '450px',
        width: '100%'
    },
    title: {
        textAlign: 'center',
        color: '#f1f3f5',
        fontSize: '1.5rem',
        marginBottom: '5px',
        marginTop: 0
    },
    subtitle: {
        textAlign: 'center',
        color: '#b8c1cc',
        fontSize: '0.75rem',
        letterSpacing: '2px',
        textTransform: 'uppercase',
        marginBottom: '25px',
        marginTop: 0
    },
    formGroup: { marginBottom: '20px' },
    label: {
        display: 'block',
        fontSize: '0.7rem',
        color: '#b8c1cc',
        marginBottom: '8px',
        letterSpacing: '2px'
    },
    input: {
        width: '100%',
        padding: '12px 15px',
        background: '#eef1f4',
        border: '1px solid #7c8794',
        borderRadius: '8px',
        color: '#1f2933',
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
        color: '#d1d5db',
        fontSize: '0.75rem',
        cursor: 'pointer',
        textDecoration: 'none'
    },
    button: {
        width: '100%',
        padding: '14px',
        background: '#526b80',
        color: '#f8fafc',
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
        background: '#343b44',
        border: '1px solid #59636e',
        color: '#f3f4f6',
        borderRadius: '8px',
        marginBottom: '20px',
        fontSize: '0.8rem'
    },
    registerLink: {
        textAlign: 'center',
        marginTop: '20px',
        fontSize: '0.8rem',
        color: '#b8c1cc'
    },
    registerLinkBtn: {
        color: '#dbe2e8',
        fontWeight: 'bold',
        cursor: 'pointer',
        textDecoration: 'underline'
    }
};