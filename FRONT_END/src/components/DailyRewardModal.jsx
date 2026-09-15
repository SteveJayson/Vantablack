import { useState, useEffect } from 'react';

const API = 'http://localhost:8080/api';

export default function DailyRewardModal({ user, onClose, onClaimed }) {
    const [status, setStatus] = useState(null);
    const [loading, setLoading] = useState(true);
    const [claiming, setClaiming] = useState(false);
    const [claimResult, setClaimResult] = useState(null);
    const [error, setError] = useState('');

    useEffect(() => {
        loadStatus();
    }, []);

    const loadStatus = async () => {
        setLoading(true);
        try {
            const res = await fetch(`${API}/rewards/daily-status/${user.id}`);
            const data = await res.json();
            if (data.success) {
                setStatus(data.data);
            } else {
                setError(data.message);
            }
        } catch (err) {
            setError('Cannot load status');
        } finally {
            setLoading(false);
        }
    };

    const claimReward = async () => {
        setClaiming(true);
        setError('');

        try {
            const res = await fetch(`${API}/rewards/claim-daily`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ combatantId: user.id })
            });
            const data = await res.json();

            if (data.success) {
                setClaimResult(data.data);
                if (onClaimed) onClaimed(data.data.newBalance);
                loadStatus();
            } else {
                setError(data.message || 'Claim failed');
            }
        } catch (err) {
            setError('Cannot connect to server');
        } finally {
            setClaiming(false);
        }
    };

    const getDayColor = (day, currentStreak, nextStreak) => {
        if (day <= currentStreak) return '#00ff88';       // Already claimed
        if (day === nextStreak) return '#ffaa00';         // Next to claim
        return '#8888cc';                                  // Future
    };

    if (loading) {
        return (
            <div style={styles.overlay}>
                <div style={styles.modal}>
                    <div style={styles.loading}>Loading daily rewards...</div>
                </div>
            </div>
        );
    }

    return (
        <div style={styles.overlay} onClick={onClose}>
            <div style={styles.modal} onClick={(e) => e.stopPropagation()}>
                {/* HEADER */}
                <div style={styles.header}>
                    <h2 style={styles.title}>🎁 DAILY REWARDS</h2>
                    <button onClick={onClose} style={styles.closeBtn}>✕</button>
                </div>

                {error && <div style={styles.errorBox}>❌ {error}</div>}

                {/* SUCCESS STATE */}
                {claimResult ? (
                    <div style={styles.successPanel}>
                        <div style={styles.successIcon}>🎉</div>
                        <h3 style={styles.successTitle}>REWARD CLAIMED!</h3>
                        <div style={styles.successDay}>Day {claimResult.day}</div>
                        <div style={styles.successCredits}>
                            +₵{claimResult.creditsEarned.toLocaleString()}
                        </div>
                        {claimResult.bonusMessage && (
                            <div style={styles.bonusMessage}>{claimResult.bonusMessage}</div>
                        )}
                        {claimResult.streakBroken && (
                            <div style={styles.streakBrokenMsg}>
                                ⚠️ Your streak was broken. Starting over at Day {claimResult.newStreak}.
                            </div>
                        )}
                        <div style={styles.newBalance}>
                            New Balance: ₵{claimResult.newBalance.toLocaleString()}
                        </div>
                        <button onClick={onClose} style={styles.claimBtn}>
                            ✅ CLOSE
                        </button>
                    </div>
                ) : (
                    <>
                        {/* STREAK DISPLAY */}
                        <div style={styles.streakHeader}>
                            <div style={styles.streakInfo}>
                                <div style={styles.streakValue}>
                                    🔥 {status.currentStreak} Day Streak
                                </div>
                                <div style={styles.streakBest}>
                                    Best: {status.longestStreak} days
                                </div>
                            </div>
                            {status.canClaim ? (
                                <div style={styles.statusBadge}>
                                    ✅ Ready to claim!
                                </div>
                            ) : (
                                <div style={{ ...styles.statusBadge, background: 'rgba(255,170,0,0.2)', color: '#ffaa00' }}>
                                    ⏰ Next claim in {status.hoursUntilNext}h
                                </div>
                            )}
                        </div>

                        {/* 7 DAY GRID */}
                        <div style={styles.daysGrid}>
                            {[1, 2, 3, 4, 5, 6, 7].map(day => {
                                const reward = status.rewardTable[day];
                                const color = getDayColor(day, status.currentStreak, status.nextStreak);
                                const isClaimed = day <= status.currentStreak;
                                const isNext = day === status.nextStreak;

                                return (
                                    <div
                                        key={day}
                                        style={{
                                            ...styles.dayCard,
                                            borderColor: color,
                                            background: isClaimed
                                                ? 'rgba(0,255,136,0.1)'
                                                : isNext
                                                    ? 'rgba(255,170,0,0.15)'
                                                    : 'rgba(0,0,0,0.3)'
                                        }}
                                    >
                                        <div style={styles.dayLabel}>DAY {day}</div>
                                        <div style={styles.dayIcon}>
                                            {isClaimed ? '✅' : isNext ? '🎁' : '🔒'}
                                        </div>
                                        <div style={{ ...styles.dayCredits, color }}>
                                            ₵{reward.credits.toLocaleString()}
                                        </div>
                                        {reward.bonus_type && (
                                            <div style={styles.dayBonus}>
                                                +🎁 GEAR
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>

                        {/* CLAIM BUTTON */}
                        <button
                            onClick={claimReward}
                            disabled={!status.canClaim || claiming}
                            style={{
                                ...styles.claimBtn,
                                opacity: (!status.canClaim || claiming) ? 0.5 : 1,
                                cursor: (!status.canClaim || claiming) ? 'not-allowed' : 'pointer'
                            }}
                        >
                            {claiming
                                ? '⏳ CLAIMING...'
                                : status.canClaim
                                    ? `🎁 CLAIM DAY ${status.nextStreak} REWARD`
                                    : `⏰ COME BACK IN ${status.hoursUntilNext}H`
                            }
                        </button>

                        {/* RECENT CLAIMS */}
                        {status.history && status.history.length > 0 && (
                            <div style={styles.historySection}>
                                <h4 style={styles.historyTitle}>📜 RECENT CLAIMS</h4>
                                <div style={styles.historyList}>
                                    {status.history.slice(0, 5).map((h, i) => (
                                        <div key={i} style={styles.historyItem}>
                                            <span>Day {h.day_streak}</span>
                                            <span style={{ color: '#00ff88' }}>+₵{parseInt(h.credits_earned).toLocaleString()}</span>
                                            <span style={{ fontSize: '0.65rem', color: '#8888cc' }}>
                                                {new Date(h.claimed_at).toLocaleDateString()}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </>
                )}
            </div>
        </div>
    );
}

const styles = {
    overlay: {
        position: 'fixed',
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
        background: 'rgba(0,0,0,0.85)',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        zIndex: 1000,
        padding: '20px'
    },
    modal: {
        background: '#0a0a1a',
        border: '2px solid #00f0ff',
        borderRadius: '16px',
        padding: '25px',
        maxWidth: '700px',
        width: '100%',
        maxHeight: '90vh',
        overflowY: 'auto',
        fontFamily: 'monospace',
        color: '#e0e0ff',
        boxShadow: '0 0 60px rgba(0, 240, 255, 0.4)'
    },
    header: {
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: '20px',
        paddingBottom: '15px',
        borderBottom: '1px solid rgba(0, 240, 255, 0.2)'
    },
    title: {
        color: '#00f0ff',
        fontSize: '1.3rem',
        margin: 0,
        letterSpacing: '3px'
    },
    closeBtn: {
        background: 'rgba(255,0,68,0.2)',
        border: '1px solid #ff0044',
        color: '#ff0044',
        width: '32px',
        height: '32px',
        borderRadius: '50%',
        cursor: 'pointer',
        fontFamily: 'monospace',
        fontWeight: 'bold',
        fontSize: '1rem'
    },
    loading: {
        textAlign: 'center',
        color: '#8888cc',
        padding: '40px',
        fontSize: '0.9rem'
    },
    errorBox: {
        padding: '12px',
        background: 'rgba(255,0,68,0.1)',
        border: '1px solid #ff0044',
        color: '#ff0044',
        borderRadius: '8px',
        marginBottom: '15px',
        fontSize: '0.8rem',
        textAlign: 'center'
    },
    successPanel: {
        textAlign: 'center',
        padding: '20px 0'
    },
    successIcon: {
        fontSize: '4rem',
        marginBottom: '10px'
    },
    successTitle: {
        color: '#00ff88',
        fontSize: '1.5rem',
        marginBottom: '15px',
        letterSpacing: '3px'
    },
    successDay: {
        color: '#ffaa00',
        fontSize: '1rem',
        marginBottom: '10px',
        letterSpacing: '2px'
    },
    successCredits: {
        color: '#00ff88',
        fontSize: '2.5rem',
        fontWeight: 'bold',
        marginBottom: '20px'
    },
    bonusMessage: {
        padding: '12px',
        background: 'rgba(255,170,0,0.15)',
        border: '1px solid #ffaa00',
        borderRadius: '8px',
        color: '#ffaa00',
        fontSize: '0.85rem',
        marginBottom: '15px'
    },
    streakBrokenMsg: {
        padding: '10px',
        background: 'rgba(255,0,68,0.1)',
        border: '1px solid #ff0044',
        borderRadius: '8px',
        color: '#ff0044',
        fontSize: '0.75rem',
        marginBottom: '15px'
    },
    newBalance: {
        padding: '10px',
        background: 'rgba(0,240,255,0.1)',
        borderRadius: '8px',
        color: '#00f0ff',
        fontSize: '0.85rem',
        marginBottom: '20px',
        fontWeight: 'bold'
    },
    streakHeader: {
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        padding: '15px',
        background: 'rgba(255,170,0,0.1)',
        border: '1px solid rgba(255,170,0,0.3)',
        borderRadius: '10px',
        marginBottom: '20px',
        flexWrap: 'wrap',
        gap: '10px'
    },
    streakInfo: {
        display: 'flex',
        flexDirection: 'column',
        gap: '4px'
    },
    streakValue: {
        color: '#ffaa00',
        fontSize: '1.1rem',
        fontWeight: 'bold'
    },
    streakBest: {
        color: '#8888cc',
        fontSize: '0.7rem'
    },
    statusBadge: {
        padding: '8px 16px',
        background: 'rgba(0,255,136,0.15)',
        color: '#00ff88',
        borderRadius: '20px',
        fontSize: '0.75rem',
        fontWeight: 'bold'
    },
    daysGrid: {
        display: 'grid',
        gridTemplateColumns: 'repeat(7, 1fr)',
        gap: '8px',
        marginBottom: '20px'
    },
    dayCard: {
        padding: '10px 5px',
        border: '2px solid',
        borderRadius: '10px',
        textAlign: 'center',
        transition: 'all 0.3s'
    },
    dayLabel: {
        fontSize: '0.55rem',
        color: '#8888cc',
        letterSpacing: '1px',
        marginBottom: '6px'
    },
    dayIcon: {
        fontSize: '1.3rem',
        marginBottom: '6px'
    },
    dayCredits: {
        fontSize: '0.7rem',
        fontWeight: 'bold'
    },
    dayBonus: {
        fontSize: '0.55rem',
        color: '#ffaa00',
        marginTop: '4px'
    },
    claimBtn: {
        width: '100%',
        padding: '16px',
        background: 'linear-gradient(90deg, #00ff88, #00f0ff)',
        color: '#0a0a1a',
        border: 'none',
        borderRadius: '10px',
        fontSize: '0.9rem',
        fontWeight: 'bold',
        cursor: 'pointer',
        fontFamily: 'monospace',
        letterSpacing: '2px',
        marginTop: '10px'
    },
    historySection: {
        marginTop: '20px',
        paddingTop: '20px',
        borderTop: '1px solid rgba(0, 240, 255, 0.1)'
    },
    historyTitle: {
        color: '#00f0ff',
        fontSize: '0.75rem',
        marginBottom: '10px',
        letterSpacing: '2px'
    },
    historyList: {
        display: 'flex',
        flexDirection: 'column',
        gap: '6px'
    },
    historyItem: {
        display: 'flex',
        justifyContent: 'space-between',
        padding: '8px 12px',
        background: 'rgba(0,0,0,0.3)',
        borderRadius: '6px',
        fontSize: '0.75rem'
    }
};