import { useState, useEffect } from 'react';

const API = 'http://localhost:8080/api';

export default function Combat({ user, onBack, onBattleComplete }) {
    const [opponents, setOpponents] = useState([]);
    const [selectedOpponent, setSelectedOpponent] = useState(null);
    const [battleResult, setBattleResult] = useState(null);
    const [loading, setLoading] = useState(false);
    const [history, setHistory] = useState([]);
    const [error, setError] = useState('');

    useEffect(() => {
        loadOpponents();
        loadHistory();
    }, []);

    const loadOpponents = async () => {
        try {
            const res = await fetch(`${API}/combat/opponents?combatantId=${user.id}`);
            const data = await res.json();
            if (data.success) {
                setOpponents(data.data.opponents || []);
            }
        } catch (err) {
            console.error('Failed to load opponents:', err);
            setError('Cannot load opponents');
        }
    };

    const loadHistory = async () => {
        try {
            const res = await fetch(`${API}/combat/history/${user.id}?limit=5`);
            const data = await res.json();
            if (data.success) {
                setHistory(data.data.history || []);
            }
        } catch (err) {
            console.error('Failed to load history:', err);
        }
    };

    const simulateBattle = async () => {
        if (!selectedOpponent) return;

        setLoading(true);
        setError('');

        try {
            const res = await fetch(`${API}/combat/simulate`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    attackerId: user.id,
                    defenderId: selectedOpponent.id
                })
            });
            const data = await res.json();

            if (data.success) {
                setBattleResult(data.data);
                loadHistory();

                // ✅ Pass the updated credits
                if (onBattleComplete) {
                    onBattleComplete(data.data);
                }
            } else {
                setError(data.message || 'Battle failed');
            }
        } catch (err) {
            setError('Cannot connect to server');
        } finally {
            setLoading(false);
        }
    };

    const resetBattle = () => {
        setBattleResult(null);
        setSelectedOpponent(null);
    };

    const getRoleColor = (role) => {
        const colors = { civilian: '#ffaa00', hero: '#00f0ff', villain: '#ff0044', admin: '#ff00ff' };
        return colors[role] || '#00f0ff';
    };

    return (
        <div style={styles.container}>
            {/* HEADER */}
            <div style={styles.header}>
                <button onClick={onBack} style={styles.backBtn}>← Back to Dashboard</button>
                <h1 style={styles.title}>⚔️ COMBAT SIMULATOR</h1>
                <div style={styles.userBadge}>
                    <span>{user.name}</span>
                    <span style={{
                        ...styles.roleBadge,
                        background: getRoleColor(user.role)
                    }}>
                        {user.role.toUpperCase()}
                    </span>
                </div>
            </div>

            {error && (
                <div style={styles.errorBox}>❌ {error}</div>
            )}

            <div style={styles.grid}>
                {/* LEFT: OPPONENT SELECTOR */}
                <div style={styles.panel}>
                    <h2 style={styles.panelTitle}>🎯 SELECT OPPONENT</h2>

                    {opponents.length === 0 ? (
                        <div style={styles.loading}>Loading opponents...</div>
                    ) : (
                        <div style={styles.opponentList}>
                            {opponents.map(opp => (
                                <div
                                    key={opp.id}
                                    onClick={() => !battleResult && setSelectedOpponent(opp)}
                                    style={{
                                        ...styles.opponentCard,
                                        borderColor: selectedOpponent?.id === opp.id ? '#00f0ff' : 'rgba(0,240,255,0.2)',
                                        background: selectedOpponent?.id === opp.id ? 'rgba(0,240,255,0.1)' : 'rgba(0,0,0,0.3)',
                                        opacity: battleResult ? 0.5 : 1,
                                        cursor: battleResult ? 'not-allowed' : 'pointer'
                                    }}
                                >
                                    <div style={styles.oppHeader}>
                                        <div style={styles.oppName}>{opp.name}</div>
                                        <div style={{
                                            ...styles.oppRole,
                                            background: getRoleColor(opp.role)
                                        }}>
                                            {opp.role.toUpperCase()}
                                        </div>
                                    </div>
                                    <div style={styles.oppStats}>
                                        <div style={styles.oppStat}>
                                            <span style={styles.statLabel}>⚡ BIO</span>
                                            <span style={styles.statValue}>{opp.bioCapacityMax}</span>
                                        </div>
                                        <div style={styles.oppStat}>
                                            <span style={styles.statLabel}>💰 CREDITS</span>
                                            <span style={styles.statValue}>₵{opp.credits.toLocaleString()}</span>
                                        </div>
                                        <div style={styles.oppStat}>
                                            <span style={styles.statLabel}>⚔️ POWER</span>
                                            <span style={styles.statValue}>{Math.round(opp.powerScore)}</span>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* CENTER: BATTLE ARENA */}
                <div style={styles.panel}>
                    <h2 style={styles.panelTitle}>⚔️ BATTLE ARENA</h2>

                    {!battleResult ? (
                        <div style={styles.arena}>
                            {/* FIGHTER 1 */}
                            <div style={styles.fighterCard}>
                                <div style={{
                                    ...styles.fighterAvatar,
                                    background: getRoleColor(user.role)
                                }}>
                                    {user.name.charAt(0).toUpperCase()}
                                </div>
                                <div style={styles.fighterName}>{user.name}</div>
                                <div style={styles.fighterRole}>{user.role.toUpperCase()}</div>
                                <div style={styles.fighterStats}>
                                    ⚡ {user.bioCapacityMax} • ₵{user.credits?.toLocaleString()}
                                </div>
                                <div style={{
                                    marginTop: '8px',
                                    padding: '6px 12px',
                                    background: 'rgba(0,240,255,0.15)',
                                    borderRadius: '6px',
                                    fontSize: '0.75rem',
                                    color: '#00f0ff',
                                    fontWeight: 'bold'
                                }}>
                                    ⚔️ Power: {Math.round(user.bioCapacityMax * 0.7)}
                                </div>
                            </div>

                            {/* VS */}
                            <div style={styles.vsContainer}>
                                <div style={styles.vs}>VS</div>
                            </div>

                            {/* FIGHTER 2 */}
                            <div style={styles.fighterCard}>
                                {selectedOpponent ? (
                                    <>
                                        <div style={{
                                            ...styles.fighterAvatar,
                                            background: getRoleColor(selectedOpponent.role)
                                        }}>
                                            {selectedOpponent.name.charAt(0).toUpperCase()}
                                        </div>
                                        <div style={styles.fighterName}>{selectedOpponent.name}</div>
                                        <div style={styles.fighterRole}>{selectedOpponent.role.toUpperCase()}</div>
                                        <div style={styles.fighterStats}>
                                            ⚡ {selectedOpponent.bioCapacityMax} • ₵{selectedOpponent.credits.toLocaleString()}
                                        </div>
                                        <div style={{
                                            marginTop: '8px',
                                            padding: '6px 12px',
                                            background: 'rgba(255,0,68,0.15)',
                                            borderRadius: '6px',
                                            fontSize: '0.75rem',
                                            color: '#ff0044',
                                            fontWeight: 'bold'
                                        }}>
                                            ⚔️ Power: {Math.round(selectedOpponent.bioCapacityMax * 0.7)}
                                        </div>
                                    </>
                                ) : (
                                    <>
                                        <div style={{
                                            ...styles.fighterAvatar,
                                            background: 'rgba(0,0,0,0.5)',
                                            border: '2px dashed rgba(0,240,255,0.3)'
                                        }}>
                                            ?
                                        </div>
                                        <div style={styles.fighterName}>— Select —</div>
                                        <div style={styles.fighterRole}>OPPONENT</div>
                                    </>
                                )}
                            </div>

                            <button
                                onClick={simulateBattle}
                                disabled={!selectedOpponent || loading}
                                style={{
                                    ...styles.fightBtn,
                                    opacity: (!selectedOpponent || loading) ? 0.5 : 1,
                                    cursor: (!selectedOpponent || loading) ? 'not-allowed' : 'pointer'
                                }}
                            >
                                {loading ? '⚔️ FIGHTING...' : '⚔️ FIGHT!'}
                            </button>
                        </div>
                    ) : (
                        <div style={styles.result}>
                            {/* WIN/LOSE BANNER */}
                            <div style={{
                                ...styles.resultBanner,
                                background: battleResult.winner === user.id ? 'rgba(0,255,136,0.15)' : 'rgba(255,0,68,0.15)',
                                borderColor: battleResult.winner === user.id ? '#00ff88' : '#ff0044',
                                color: battleResult.winner === user.id ? '#00ff88' : '#ff0044'
                            }}>
                                {battleResult.winner === user.id ? '🏆 VICTORY!' : '💀 DEFEAT'}
                            </div>

                            {/* BATTLE LOG */}
                            <div style={styles.battleLog}>
                                <h4 style={styles.logTitle}>📜 BATTLE LOG</h4>
                                {battleResult.log.map((line, i) => (
                                    <div key={i} style={styles.logLine}>
                                        <span style={styles.logNum}>[{i + 1}]</span> {line}
                                    </div>
                                ))}
                            </div>

                            {/* REWARDS */}
                            <div style={styles.rewards}>
                                <div style={styles.rewardItem}>
                                    <span style={styles.rewardLabel}>💰 Credits</span>
                                    <span style={{
                                        ...styles.rewardValue,
                                        color: battleResult.creditsEarned > 0 ? '#00ff88' : '#ff0044'
                                    }}>
                                        {battleResult.creditsEarned > 0 ? '+' : ''}₵{battleResult.creditsEarned}
                                    </span>
                                </div>
                                <div style={styles.rewardItem}>
                                    <span style={styles.rewardLabel}>⚔️ Damage Dealt</span>
                                    <span style={styles.rewardValue}>{battleResult.damageDealt}</span>
                                </div>
                                <div style={styles.rewardItem}>
                                    <span style={styles.rewardLabel}>🛡️ Damage Taken</span>
                                    <span style={styles.rewardValue}>{battleResult.damageTaken}</span>
                                </div>
                                <div style={styles.rewardItem}>
                                    <span style={styles.rewardLabel}>🏆 Winner</span>
                                    <span style={styles.rewardValue}>{battleResult.winnerName}</span>
                                </div>
                            </div>

                            <button onClick={resetBattle} style={styles.fightBtn}>
                                🔄 FIGHT AGAIN
                            </button>
                        </div>
                    )}
                </div>

                {/* RIGHT: HISTORY */}
                <div style={styles.panel}>
                    <h2 style={styles.panelTitle}>📜 RECENT BATTLES</h2>

                    {history.length === 0 ? (
                        <div style={styles.loading}>No battles yet</div>
                    ) : (
                        <div style={styles.historyList}>
                            {history.map(h => (
                                <div key={h.id} style={styles.historyItem}>
                                    <div style={styles.historyTop}>
                                        <span style={styles.historyOpp}>vs {h.opponent_name}</span>
                                        <span style={{
                                            ...styles.historyResult,
                                            color: h.result === 'win' ? '#00ff88' : '#ff0044'
                                        }}>
                                            {h.result === 'win' ? '🏆 WIN' : '💀 LOSS'}
                                        </span>
                                    </div>
                                    <div style={styles.historyMeta}>
                                        <span>💰 ₵{h.credits_earned}</span>
                                        <span>⚔️ {h.damage_dealt} dmg</span>
                                    </div>
                                    <div style={styles.historyDate}>
                                        {new Date(h.fought_at).toLocaleString()}
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

const styles = {
    container: { minHeight: '100vh', background: '#0a0a1a', color: '#e0e0ff', fontFamily: 'monospace', padding: '20px' },
    header: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '20px', background: 'rgba(10, 10, 30, 0.85)', border: '1px solid rgba(0,240,255,0.2)', borderRadius: '12px', marginBottom: '20px', flexWrap: 'wrap', gap: '15px' },
    backBtn: { padding: '8px 16px', background: 'rgba(0,240,255,0.1)', border: '1px solid #00f0ff', color: '#00f0ff', borderRadius: '6px', cursor: 'pointer', fontFamily: 'monospace', fontWeight: 'bold' },
    title: { color: '#00f0ff', fontSize: '1.5rem', margin: 0, letterSpacing: '4px' },
    userBadge: { display: 'flex', alignItems: 'center', gap: '10px' },
    roleBadge: { padding: '4px 12px', borderRadius: '12px', fontSize: '0.7rem', fontWeight: 'bold', color: '#0a0a1a' },
    errorBox: { padding: '12px', background: 'rgba(255,0,68,0.1)', border: '1px solid #ff0044', color: '#ff0044', borderRadius: '8px', marginBottom: '20px', fontSize: '0.8rem' },
    grid: { display: 'grid', gridTemplateColumns: '1fr 1.5fr 1fr', gap: '20px' },
    panel: { background: 'rgba(10, 10, 30, 0.85)', border: '1px solid rgba(0,240,255,0.15)', borderRadius: '12px', padding: '20px' },
    panelTitle: { color: '#00f0ff', fontSize: '0.85rem', marginBottom: '15px', letterSpacing: '2px', marginTop: 0, borderBottom: '1px solid rgba(0,240,255,0.2)', paddingBottom: '10px' },
    loading: { textAlign: 'center', color: '#8888cc', fontSize: '0.8rem', padding: '20px' },
    opponentList: { display: 'flex', flexDirection: 'column', gap: '10px', maxHeight: '600px', overflowY: 'auto' },
    opponentCard: { padding: '12px', border: '2px solid', borderRadius: '8px', cursor: 'pointer', transition: 'all 0.3s' },
    oppHeader: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' },
    oppName: { fontWeight: 'bold', fontSize: '0.9rem' },
    oppRole: { padding: '2px 8px', borderRadius: '8px', fontSize: '0.6rem', fontWeight: 'bold', color: '#0a0a1a' },
    oppStats: { display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '8px', fontSize: '0.7rem' },
    oppStat: { display: 'flex', flexDirection: 'column', gap: '2px' },
    statLabel: { fontSize: '0.55rem', color: '#8888cc' },
    statValue: { fontSize: '0.75rem', fontWeight: 'bold', color: '#e0e0ff' },
    arena: { display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '20px', padding: '30px 0' },
    fighterCard: { textAlign: 'center', padding: '15px', background: 'rgba(0,0,0,0.3)', borderRadius: '10px', minWidth: '200px' },
    fighterAvatar: { width: '70px', height: '70px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '2rem', fontWeight: 'bold', color: '#0a0a1a', margin: '0 auto 10px' },
    fighterName: { fontSize: '1.1rem', fontWeight: 'bold', color: '#e0e0ff', marginBottom: '4px' },
    fighterRole: { fontSize: '0.65rem', color: '#8888cc', letterSpacing: '1px', marginBottom: '8px' },
    fighterStats: { fontSize: '0.7rem', color: '#00f0ff' },
    vsContainer: { display: 'flex', alignItems: 'center', justifyContent: 'center' },
    vs: { fontSize: '1.8rem', color: '#ffaa00', fontWeight: 'bold', textShadow: '0 0 20px rgba(255,170,0,0.6)', letterSpacing: '2px' },
    fightBtn: { padding: '15px 40px', background: 'linear-gradient(90deg, #ff0044, #ff00ff)', color: 'white', border: 'none', borderRadius: '8px', fontSize: '0.9rem', fontWeight: 'bold', cursor: 'pointer', fontFamily: 'monospace', letterSpacing: '2px', marginTop: '10px' },
    result: { textAlign: 'center' },
    resultBanner: { padding: '20px', borderRadius: '10px', fontSize: '1.5rem', fontWeight: 'bold', marginBottom: '20px', border: '2px solid', letterSpacing: '3px' },
    battleLog: { background: 'rgba(0,0,0,0.4)', padding: '15px', borderRadius: '8px', textAlign: 'left', marginBottom: '15px', maxHeight: '200px', overflowY: 'auto' },
    logTitle: { color: '#00f0ff', fontSize: '0.7rem', marginBottom: '10px', marginTop: 0, letterSpacing: '2px' },
    logLine: { fontSize: '0.75rem', color: '#e0e0ff', padding: '6px 0', borderBottom: '1px solid rgba(0,240,255,0.05)' },
    logNum: { color: '#8888cc', marginRight: '6px' },
    rewards: { background: 'rgba(0,0,0,0.3)', padding: '15px', borderRadius: '8px', marginBottom: '20px', display: 'flex', flexDirection: 'column', gap: '10px' },
    rewardItem: { display: 'flex', justifyContent: 'space-between', fontSize: '0.8rem' },
    rewardLabel: { color: '#8888cc' },
    rewardValue: { fontWeight: 'bold', color: '#e0e0ff' },
    historyList: { display: 'flex', flexDirection: 'column', gap: '10px', maxHeight: '600px', overflowY: 'auto' },
    historyItem: { padding: '12px', background: 'rgba(0,0,0,0.3)', borderRadius: '8px', border: '1px solid rgba(0,240,255,0.1)' },
    historyTop: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '6px' },
    historyOpp: { fontSize: '0.8rem', fontWeight: 'bold', color: '#e0e0ff' },
    historyResult: { fontSize: '0.7rem', fontWeight: 'bold' },
    historyMeta: { display: 'flex', gap: '12px', fontSize: '0.7rem', color: '#8888cc', marginBottom: '4px' },
    historyDate: { fontSize: '0.65rem', color: '#666', textAlign: 'right' }
};