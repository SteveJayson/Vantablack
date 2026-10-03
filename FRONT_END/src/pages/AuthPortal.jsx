import { useState, useEffect } from 'react';
import { Eye, EyeOff } from 'lucide-react';


const API = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api';

// ----------------------------------------------------------------
// Design tokens — sampled from the reference video.
// ----------------------------------------------------------------
const COLORS = {
    pageBg: '#111524',
    cardBg: 'rgba(21, 23, 42, 0.8)',
    cardBorder: 'rgba(255, 255, 255, 0.07)',
    gradientStart: '#29b6f6',   // cyan-blue
    gradientMid: '#3f6fe0',
    gradientEnd: '#6a2fd6',     // violet
    buttonFrom: '#22b2e6',
    buttonTo: '#2f7fe0',
    text: '#eef0f7',
    textDim: '#8a90a8',
    inputBg: 'rgba(255, 255, 255, 0.05)',
    inputBorder: 'rgba(255, 255, 255, 0.09)',
    danger: '#ff5577',
    success: '#2fe0a8',
};

// ----------------------------------------------------------------
// Roles available at registration. Admin is intentionally excluded.
// To add a role later: add an entry here (unique key/label/icon/color).
// ----------------------------------------------------------------
const ROLES = [
    { key: 'civilian', label: 'Civilian', icon: '', color: '#ffb648' },
    { key: 'hero', label: 'Hero', icon: '', color: COLORS.gradientStart },
    { key: 'villain', label: 'Villain', icon: '', color: '#c13cff' },
];

const MOBILE_BREAKPOINT = 768;

export default function AuthPortal({ onLogin, onShowForgotPassword }) {
    const [isSignUp, setIsSignUp] = useState(false);
    const [isMobile, setIsMobile] = useState(
        typeof window !== 'undefined' ? window.innerWidth < MOBILE_BREAKPOINT : false
    );

    const [loginUsername, setLoginUsername] = useState('');
    const [loginPassword, setLoginPassword] = useState('');
    const [loginShowPassword, setLoginShowPassword] = useState(false);
    const [loginError, setLoginError] = useState('');
    const [loginLoading, setLoginLoading] = useState(false);

    const [regName, setRegName] = useState('');
    const [regUsername, setRegUsername] = useState('');
    const [regPassword, setRegPassword] = useState('');
    const [regConfirmPassword, setRegConfirmPassword] = useState('');
    const [regShowPassword, setRegShowPassword] = useState(false);
    const [regShowConfirm, setRegShowConfirm] = useState(false);
    const [regRole, setRegRole] = useState('');
    const [regError, setRegError] = useState('');
    const [regSuccess, setRegSuccess] = useState('');
    const [regLoading, setRegLoading] = useState(false);

    useEffect(() => {
        const handleResize = () => setIsMobile(window.innerWidth < MOBILE_BREAKPOINT);
        window.addEventListener('resize', handleResize);
        return () => window.removeEventListener('resize', handleResize);
    }, []);

    function completeAuth(data) {
        localStorage.setItem('aegis_token', data.data.token);
        localStorage.setItem('aegis_user', JSON.stringify(data.data.user));
        onLogin(data.data.user);
    }

    async function handleLoginSubmit(e) {
        e.preventDefault();
        setLoginError('');

        if (!loginUsername.trim() || !loginPassword) {
            setLoginError('Username and password are required.');
            return;
        }

        setLoginLoading(true);
        try {
            const res = await fetch(`${API}/auth/login`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username: loginUsername, password: loginPassword }),
            });
            const data = await res.json();

            if (data.success) {
                completeAuth(data);
            } else {
                setLoginError(data.message || 'Login failed. Check your credentials.');
            }
        } catch (err) {
            setLoginError('Cannot connect to server.');
        } finally {
            setLoginLoading(false);
        }
    }

    async function handleRegisterSubmit(e) {
        e.preventDefault();
        setRegError('');
        setRegSuccess('');

        if (!regName.trim() || !regUsername.trim() || !regPassword || !regConfirmPassword) {
            setRegError('All fields are required.');
            return;
        }
        if (regPassword.length < 6) {
            setRegError('Password must be at least 6 characters.');
            return;
        }
        if (regPassword !== regConfirmPassword) {
            setRegError('Passwords do not match.');
            return;
        }
        if (!regRole) {
            setRegError('Select a role to continue.');
            return;
        }

        setRegLoading(true);
        try {
            const res = await fetch(`${API}/auth/register`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    username: regUsername,
                    password: regPassword,
                    name: regName,
                    role: regRole,
                }),
            });
            const data = await res.json();

            if (data.success) {
                setRegSuccess('Account created. Signing you in...');
                setTimeout(() => completeAuth(data), 600);
            } else {
                setRegError(data.message || 'Registration failed.');
            }
        } catch (err) {
            setRegError('Cannot connect to server.');
        } finally {
            setRegLoading(false);
        }
    }

    function switchToSignUp() {
        setLoginError('');
        setIsSignUp(true);
    }
    function switchToSignIn() {
        setRegError('');
        setRegSuccess('');
        setIsSignUp(false);
    }

    return (
        <div style={styles.page}>
            <GlobalStyle />

            {isMobile ? (
                <MobileLayout
                    isSignUp={isSignUp}
                    setIsSignUp={setIsSignUp}
                    loginProps={{
                        loginUsername, setLoginUsername,
                        loginPassword, setLoginPassword,
                        loginShowPassword, setLoginShowPassword,
                        loginError, loginLoading,
                        handleLoginSubmit,
                        onShowForgotPassword,
                    }}
                    registerProps={{
                        regName, setRegName,
                        regUsername, setRegUsername,
                        regPassword, setRegPassword,
                        regConfirmPassword, setRegConfirmPassword,
                        regShowPassword, setRegShowPassword,
                        regShowConfirm, setRegShowConfirm,
                        regRole, setRegRole,
                        regError, regSuccess, regLoading,
                        handleRegisterSubmit,
                    }}
                />
            ) : (
                <DesktopLayout
                    isSignUp={isSignUp}
                    switchToSignUp={switchToSignUp}
                    switchToSignIn={switchToSignIn}
                    loginProps={{
                        loginUsername, setLoginUsername,
                        loginPassword, setLoginPassword,
                        loginShowPassword, setLoginShowPassword,
                        loginError, loginLoading,
                        handleLoginSubmit,
                        onShowForgotPassword,
                    }}
                    registerProps={{
                        regName, setRegName,
                        regUsername, setRegUsername,
                        regPassword, setRegPassword,
                        regConfirmPassword, setRegConfirmPassword,
                        regShowPassword, setRegShowPassword,
                        regShowConfirm, setRegShowConfirm,
                        regRole, setRegRole,
                        regError, regSuccess, regLoading,
                        handleRegisterSubmit,
                    }}
                />
            )}
        </div>
    );
}

/* ================================================================
   DESKTOP LAYOUT — dual panel sliding container.
   The ONLY thing that changes on toggle is the container's
   className (vb-right-active added/removed). Every transform,
   opacity, and transition lives in the stylesheet below, exactly
   like the video's own `container.classList.add("right-panel-active")`
   approach. This is the fix for the "no animation" issue.
   ================================================================ */
function DesktopLayout({ isSignUp, switchToSignUp, switchToSignIn, loginProps, registerProps }) {
    return (
        <div className={`vb-auth-container${isSignUp ? ' vb-right-active' : ''}`} style={styles.container}>
            <div className="vb-sign-in-panel">
                <LoginForm {...loginProps} />
            </div>

            <div className="vb-sign-up-panel">
                <RegisterForm {...registerProps} />
            </div>

            <div className="vb-overlay-container">
                <div className="vb-gradient-anim vb-overlay-gradient">
                    {!isSignUp ? (
                        <div style={styles.overlayContent}>
                            <h2 style={styles.overlayTitle}>New Here?</h2>
                            <p style={styles.overlaySubtitle}>
                                Register as a Hero, Villain, or Civilian and start your mission.
                            </p>
                            <button type="button" onClick={switchToSignUp} className="vb-ghost-btn" style={styles.ghostButton}>
                                Sign Up
                            </button>
                        </div>
                    ) : (
                        <div style={styles.overlayContent}>
                            <h2 style={styles.overlayTitle}>Hello, Combatant!</h2>
                            <p style={styles.overlaySubtitle}>
                                Already have a profile? Sign in to access your loadout.
                            </p>
                            <button type="button" onClick={switchToSignIn} className="vb-ghost-btn" style={styles.ghostButton}>
                                Sign In
                            </button>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

/* ================================================================
   MOBILE LAYOUT — stacked card with a tab switcher, no sliding
   ================================================================ */
function MobileLayout({ isSignUp, setIsSignUp, loginProps, registerProps }) {
    return (
        <div style={styles.mobileCard}>
            <div style={styles.mobileTabs}>
                <button
                    type="button"
                    onClick={() => setIsSignUp(false)}
                    style={{ ...styles.mobileTab, ...(!isSignUp ? styles.mobileTabActive : {}) }}
                >
                    Sign In
                </button>
                <button
                    type="button"
                    onClick={() => setIsSignUp(true)}
                    style={{ ...styles.mobileTab, ...(isSignUp ? styles.mobileTabActive : {}) }}
                >
                    Sign Up
                </button>
            </div>

            <div style={styles.mobileFormWrap}>
                {!isSignUp ? <LoginForm {...loginProps} /> : <RegisterForm {...registerProps} />}
            </div>
        </div>
    );
}

/* ================================================================
   LOGIN FORM
   ================================================================ */
function LoginForm({
    loginUsername, setLoginUsername,
    loginPassword, setLoginPassword,
    loginShowPassword, setLoginShowPassword,
    loginError, loginLoading,
    handleLoginSubmit,
    onShowForgotPassword,
}) {
    return (
        <form onSubmit={handleLoginSubmit} style={{ width: '100%' }}>
            <h1 style={styles.formTitle}>Welcome Back</h1>
            <p style={styles.brandLabel}>⚡ VANTABLACK</p> 
            
            
            {loginError && <div style={styles.errorBox}>{loginError}</div>}

            <div style={styles.inputGroup}>
                <span style={styles.inputIcon}></span>
                <input
                    type="text"
                    placeholder="Username"
                    value={loginUsername}
                    onChange={(e) => setLoginUsername(e.target.value)}
                    className="vb-input"
                    style={styles.input}
                    required
                />
            </div>

            <div style={styles.inputGroup}>
                <span style={styles.inputIcon}></span>
                <input
                    type={loginShowPassword ? 'text' : 'password'}
                    placeholder="Password"
                    value={loginPassword}
                    onChange={(e) => setLoginPassword(e.target.value)}
                    className="vb-input"
                    style={styles.input}
                    required
                />
                <button
                    type="button"
                    onClick={() => setLoginShowPassword((v) => !v)}
                    className="vb-eye-btn"
                    style={styles.eyeButton}
                    aria-label="Toggle password visibility"
                >
                    {loginShowPassword ? (
                        <EyeOff size={18} strokeWidth={2} color="rgba(238,240,247,0.9)" />
                    ) : (
                        <Eye size={18} strokeWidth={2} color="rgba(238,240,247,0.9)" />
                    )}
                </button>
            </div>

            <div style={styles.forgotRow}>
                <span onClick={onShowForgotPassword} style={styles.forgotLink}>
                    Forgot your password?
                </span>
            </div>

            <button type="submit" disabled={loginLoading} className="vb-submit-btn" style={styles.submitButton}>
                {loginLoading ? 'Signing in...' : 'Sign In'}
            </button>
        </form>
    );
}

/* ================================================================
   REGISTER FORM
   ================================================================ */
function RegisterForm({
    regName, setRegName,
    regUsername, setRegUsername,
    regPassword, setRegPassword,
    regConfirmPassword, setRegConfirmPassword,
    regShowPassword, setRegShowPassword,
    regShowConfirm, setRegShowConfirm,
    regRole, setRegRole,
    regError, regSuccess, regLoading,
    handleRegisterSubmit,
}) {
    return (
        <form onSubmit={handleRegisterSubmit} style={{ width: '100%' }}>
            <h1 style={styles.formTitle}>Create Account</h1>

            {regError && <div style={styles.errorBox}>{regError}</div>}
            {regSuccess && <div style={styles.successBox}>{regSuccess}</div>}

            <div style={styles.inputGroup}>
                <span style={styles.inputIcon}></span>
                <input
                    type="text"
                    placeholder="Name"
                    value={regName}
                    onChange={(e) => setRegName(e.target.value)}
                    className="vb-input"
                    style={styles.input}
                    required
                />
            </div>

            <div style={styles.inputGroup}>
                <span style={styles.inputIcon}></span>
                <input
                    type="text"
                    placeholder="Username"
                    value={regUsername}
                    onChange={(e) => setRegUsername(e.target.value)}
                    className="vb-input"
                    style={styles.input}
                    required
                />
            </div>

            <div style={styles.inputGroup}>
                <span style={styles.inputIcon}></span>
                <input
                    type={regShowPassword ? 'text' : 'password'}
                    placeholder="Password"
                    value={regPassword}
                    onChange={(e) => setRegPassword(e.target.value)}
                    className="vb-input"
                    style={styles.input}
                    required
                />
                <button
                    type="button"
                    onClick={() => setRegShowPassword((v) => !v)}
                    className="vb-eye-btn"
                    style={styles.eyeButton}
                    aria-label="Toggle password visibility"
                >
                    {regShowPassword ? (
                        <EyeOff size={18} strokeWidth={2} color="rgba(238,240,247,0.9)" />
                    ) : (
                        <Eye size={18} strokeWidth={2} color="rgba(238,240,247,0.9)" />
                    )}
                </button>
            </div>

            <div style={styles.inputGroup}>
                <span style={styles.inputIcon}></span>
                <input
                    type={regShowConfirm ? 'text' : 'password'}
                    placeholder="Confirm Password"
                    value={regConfirmPassword}
                    onChange={(e) => setRegConfirmPassword(e.target.value)}
                    className="vb-input"
                    style={styles.input}
                    required
                />
                <button
                    type="button"
                    onClick={() => setRegShowConfirm((v) => !v)}
                    className="vb-eye-btn"
                    style={styles.eyeButton}
                    aria-label="Toggle confirm password visibility"
                >
                    {regShowConfirm ? (
                        <EyeOff size={18} strokeWidth={2} color="rgba(238,240,247,0.9)" />
                    ) : (
                        <Eye size={18} strokeWidth={2} color="rgba(238,240,247,0.9)" />
                    )}
                </button>
            </div>

            <div style={styles.roleRow}>
                {ROLES.map((role) => {
                    const selected = regRole === role.key;
                    return (
                        <button
                            key={role.key}
                            type="button"
                            onClick={() => setRegRole(role.key)}
                            className="vb-role-pill"
                            style={{
                                ...styles.rolePill,
                                borderColor: selected ? role.color : COLORS.inputBorder,
                                color: selected ? role.color : COLORS.textDim,
                                boxShadow: selected ? `0 0 14px ${role.color}55` : 'none',
                                background: selected ? `${role.color}1a` : 'rgba(255,255,255,0.03)',
                            }}
                        >
                            <span style={{ fontSize: '1.05rem' }}>{role.icon}</span>
                            <span>{role.label}</span>
                        </button>
                    );
                })}
            </div>

            <button type="submit" disabled={regLoading} className="vb-submit-btn" style={styles.submitButton}>
                {regLoading ? 'Creating account...' : 'Sign Up'}
            </button>
        </form>
    );
}

/* ================================================================
   Global <style> block.
   This is where the actual sliding mechanics live now — it's a
   direct port of the class-toggle pattern from the reference video
   (.right-panel-active in the video -> .vb-right-active here).
   ================================================================ */
function GlobalStyle() {
    return (
        <style>{`
            @keyframes vb-gradientShift {
                0%   { background-position: 0% 50%; }
                50%  { background-position: 100% 50%; }
                100% { background-position: 0% 50%; }
            }
            .vb-gradient-anim {
                background-size: 200% 200%;
                animation: vb-gradientShift 8s ease infinite;
            }

            /* ---- Sliding panel mechanics ---- */
            .vb-auth-container {
                position: relative;
            }
            .vb-sign-in-panel,
            .vb-sign-up-panel {
                position: absolute;
                top: 0;
                left: 0;
                width: 50%;
                height: 100%;
                background: #15172a;
                display: flex;
                flex-direction: column;
                justify-content: center;
                padding: 40px 50px;
                box-sizing: border-box;
                overflow-y: auto;
                transition: transform 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55),
                            opacity 0.6s ease;
            }
            .vb-sign-in-panel {
                z-index: 2;
            }
            .vb-sign-up-panel {
                opacity: 0;
                z-index: 1;
            }
            .vb-overlay-container {
                position: absolute;
                top: 0;
                left: 50%;
                width: 50%;
                height: 100%;
                overflow: hidden;
                z-index: 100;
                transition: transform 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            }
            .vb-overlay-gradient {
                width: 100%;
                height: 100%;
                background: linear-gradient(135deg, ${COLORS.gradientStart}, ${COLORS.gradientMid}, ${COLORS.gradientEnd});
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .vb-right-active .vb-sign-in-panel {
                transform: translateX(100%);
            }
            .vb-right-active .vb-sign-up-panel {
                transform: translateX(100%);
                opacity: 1;
                z-index: 5;
                animation: vb-show 0.6s;
            }
            .vb-right-active .vb-overlay-container {
                transform: translateX(-100%);
            }
            @keyframes vb-show {
                0%, 49.99% { opacity: 0; z-index: 1; }
                50%, 100%  { opacity: 1; z-index: 5; }
            }

            /* ---- Interactive states ---- */
            input[type="password"]::-ms-reveal,
            input[type="password"]::-ms-clear,
            input[type="password"]::-webkit-credentials-auto-fill-button,
            input[type="password"]::-webkit-strong-password-auto-fill-button {
                display: none !important;
                -webkit-appearance: none !important;
                appearance: none !important;
                pointer-events: none;
            }

            .vb-input:focus {
                outline: none;
                border-color: ${COLORS.gradientStart} !important;
                box-shadow: 0 0 0 3px rgba(41, 182, 246, 0.15), 0 0 14px rgba(41, 182, 246, 0.3);
            }
            .vb-ghost-btn {
                transition: background 0.2s ease, transform 0.2s ease;
            }
            .vb-ghost-btn:hover {
                background: rgba(255, 255, 255, 0.15) !important;
                transform: translateY(-1px);
            }
            .vb-submit-btn {
                transition: filter 0.2s ease, transform 0.2s ease;
            }
            .vb-submit-btn:hover:not(:disabled) {
                filter: brightness(1.12);
                transform: translateY(-1px);
            }
            .vb-role-pill {
                transition: all 0.2s ease;
            }
            .vb-role-pill:hover {
                transform: translateY(-2px);
            }
            .vb-eye-btn {
                transition: opacity 1s ease;
                opacity: 1;
            }
            .vb-eye-btn:hover {
                opacity: 1;
            }
        `}</style>
    );
}

/* ================================================================
   STYLES — layout-only inline styles. Anything that needs to
   transition on toggle (transform/opacity/z-index for the sliding
   panels) now lives in GlobalStyle above, not here.
   ================================================================ */
const styles = {
    page: {
        position: 'relative',
        minHeight: '100vh',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        background: COLORS.pageBg,
        fontFamily: "'Space Mono', monospace",
        color: COLORS.text,
        padding: '20px',
        boxSizing: 'border-box',
    },

    container: {
        position: 'relative',
        width: '900px',
        maxWidth: '95vw',
        height: '550px',
        maxHeight: '90vh',
        background: COLORS.cardBg,
        backdropFilter: 'blur(16px)',
        WebkitBackdropFilter: 'blur(16px)',
        border: `1px solid ${COLORS.cardBorder}`,
        borderRadius: '18px',
        boxShadow: '0 8px 32px rgba(0, 0, 0, 0.45)',
        overflow: 'hidden',
        zIndex: 1,
    },

    overlayContent: {
        textAlign: 'center',
        padding: '0 40px',
        color: '#ffffff',
    },
    overlayTitle: {
        fontFamily: "'Orbitron', sans-serif",
        fontSize: '1.6rem',
        letterSpacing: '1px',
        marginBottom: '14px',
    },
    overlaySubtitle: {
        fontSize: '0.8rem',
        lineHeight: 1.6,
        opacity: 0.92,
        marginBottom: '26px',
    },
    ghostButton: {
        padding: '12px 36px',
        background: 'transparent',
        border: '2px solid #ffffff',
        color: '#ffffff',
        borderRadius: '30px',
        fontFamily: "'Space Mono', monospace",
        fontSize: '0.8rem',
        letterSpacing: '2px',
        textTransform: 'uppercase',
        cursor: 'pointer',
    },

    mobileCard: {
        position: 'relative',
        width: '100%',
        maxWidth: '420px',
        background: COLORS.cardBg,
        backdropFilter: 'blur(16px)',
        WebkitBackdropFilter: 'blur(16px)',
        border: `1px solid ${COLORS.cardBorder}`,
        borderRadius: '18px',
        boxShadow: '0 8px 32px rgba(0, 0, 0, 0.45)',
        padding: '30px 26px',
        boxSizing: 'border-box',
        zIndex: 1,
    },
    mobileTabs: {
        display: 'flex',
        gap: '8px',
        marginBottom: '24px',
        background: 'rgba(255,255,255,0.04)',
        borderRadius: '12px',
        padding: '4px',
    },
    mobileTab: {
        flex: 1,
        padding: '10px 0',
        background: 'transparent',
        border: 'none',
        borderRadius: '10px',
        color: COLORS.textDim,
        fontFamily: "'Space Mono', monospace",
        fontSize: '0.75rem',
        letterSpacing: '1px',
        textTransform: 'uppercase',
        cursor: 'pointer',
    },
    mobileTabActive: {
        background: `linear-gradient(90deg, ${COLORS.gradientStart}, ${COLORS.gradientEnd})`,
        color: '#0d0f1a',
        fontWeight: 'bold',
    },
    mobileFormWrap: {
        width: '100%',
    },

    formTitle: {
        fontFamily: "'Orbitron', sans-serif",
        fontSize: '1.4rem',
        letterSpacing: '1px',
        color: COLORS.text,
        textAlign: 'center',
        margin: '0 0 22px',
    },
    errorBox: {
        padding: '10px 14px',
        background: 'rgba(255, 85, 119, 0.1)',
        border: `1px solid ${COLORS.danger}`,
        color: COLORS.danger,
        borderRadius: '8px',
        fontSize: '0.75rem',
        marginBottom: '16px',
    },
    successBox: {
        padding: '10px 14px',
        background: 'rgba(47, 224, 168, 0.1)',
        border: `1px solid ${COLORS.success}`,
        color: COLORS.success,
        borderRadius: '8px',
        fontSize: '0.75rem',
        marginBottom: '16px',
    },
    inputGroup: {
        position: 'relative',
        display: 'flex',
        alignItems: 'center',
        marginBottom: '14px',
        background: COLORS.inputBg,
        border: `1px solid ${COLORS.inputBorder}`,
        borderRadius: '10px',
        padding: '0 12px',
    },
    inputIcon: {
        fontSize: '0.9rem',
        marginRight: '8px',
        opacity: 0.8,
    },
    input: {
        flex: 1,
        background: 'transparent',
        border: 'none',
        outline: 'none',
        padding: '12px 0',
        color: COLORS.text,
        fontSize: '0.85rem',
        fontFamily: "'Space Mono', monospace",
    },
    eyeButton: {
        background: 'transparent',
        border: 'none',
        cursor: 'pointer',
        fontSize: '0.9rem',
        padding: '4px',
    },
    forgotRow: {
        textAlign: 'center',
        marginBottom: '20px',
    },
    forgotLink: {
        color: COLORS.textDim,
        fontSize: '0.72rem',
        cursor: 'pointer',
    },
    submitButton: {
        width: '100%',
        padding: '13px',
        background: `linear-gradient(90deg, ${COLORS.buttonFrom}, ${COLORS.buttonTo})`,
        color: '#ffffff',
        border: 'none',
        borderRadius: '30px',
        fontSize: '0.8rem',
        fontWeight: 'bold',
        letterSpacing: '2px',
        textTransform: 'uppercase',
        cursor: 'pointer',
        fontFamily: "'Space Mono', monospace",
        boxShadow: `0 6px 18px ${COLORS.buttonTo}4d`,
    },
    roleRow: {
        display: 'flex',
        gap: '8px',
        marginBottom: '20px',
    },
    rolePill: {
        flex: 1,
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        gap: '4px',
        padding: '10px 4px',
        border: '1px solid',
        borderRadius: '12px',
        fontSize: '0.65rem',
        fontFamily: "'Space Mono', monospace",
        cursor: 'pointer',
    },
};
