import { useState, useEffect } from 'react';

const API = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api';

export default function Replays({ user, onBack }) {
    const [activeTab, setActiveTab] = useState('mine');
    const [replays, setReplays] = useState([]);
    const [sharedReplays, setSharedReplays] = useState([]);
    const [stats, setStats] = useState(null);
    const [selectedReplay, setSelectedReplay] = useState(null);
    const [replayDetail, setReplayDetail] = useState(null);
    const [playing, setPlaying] = useState(false);
    const [currentStep, setCurrentStep] = useState(0);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        if (activeTab === 'mine') loadMyReplays();
        else if (activeTab === 'shared') loadSharedReplays();
    }, [activeTab]);

    useEffect(() => {
        if (selectedReplay) loadReplayDetail(selectedReplay.id);
    }, [selectedReplay]);

    const loadMyReplays = async () => {
        setLoading(true);
        try {
            const res = await fetch(`${API}/replays/${user.id}?limit=30`);
            const data = await res.json();
            if (data.success) {
                setReplays(data.data.replays);
                setStats(data.data.stats);
            }
        } catch (err) {
            setError('Cannot load replays');
        } finally {
            setLoading(false);
        }
    };

    const loadSharedReplays = async () => {
        setLoading(true);
        try {
            const res = await fetch(`${API}/replays/shared?limit=30&sort=popular`);
            const data = await res.json();
            if (data.success) {
                setSharedReplays(data.data.replays);
            }
        } catch (err) {
            setError('Cannot load shared replays');
        } finally {
            setLoading(false);
        }
    };

    const loadReplayDetail = async (replayId) => {
        try {
            const res = await fetch(`${API}/replays/detail/${replayId}`);
            const data = await res.json();
            if (data.success) {
                setReplayDetail(data.data.replay);
                setCurrentStep(0);
                setPlaying(false);
            }
        } catch (err) {
            setError('Cannot load replay');
        }
    };

    const deleteReplay = async (replayId, e) => {
        e.stopPropagation();
        if (!confirm('Delete this replay?')) return;

        try {
            const res = await fetch(`${API}/replays/${replayId}?combatantId=${user.id}`, {
                method: 'DELETE'
            });
            const data = await res.json();
            if (data.success) {
                setReplays(prev => prev.filter(r => r.id !== replayId));
                if (selectedReplay?.id === replayId) {
                    setSelectedReplay(null);
                    setReplayDetail(null);
                }
            }
        } catch (err) {
            console.error('Delete failed:', err);
        }
    };

    const playReplay = () => {
        if (!replayDetail?.replay_data?.steps) return;

        setPlaying(true);
        setCurrentStep(0);

        const steps = replayDetail.replay_data.steps;
        let step = 0;

        const interval = setInterval(() => {
            step++;
            if (step >= steps.length) {
                clearInterval(interval);
                setPlaying(false);
                setCurrentStep(steps.length - 1);
                return;
            }
            setCurrentStep(step);
        }, 1500);
    };

    const stopReplay = () => {
        setPlaying(false);
        setCurrentStep(0);
    };

    const formatTime = (timestamp) => {
        const date = new Date(timestamp);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);

        if (diffMins < 1) return 'just now';
        if (diffMins < 60) return `${diffMins}m ago`;
        if (diffMins < 1440) return `${Math.floor(diffMins / 60)}h ago`;
        return `${Math.floor(diffMins / 1440)}d ago`;
    };

    const getResultColor = (result) => {
        return result === 'win' ? '#00ff88' : '#ff0044';
    };

    return (
        <div style={styles.container}>
            {/* HEADER */}
            <div style={styles.header}>
                <button onClick={onBack} style={styles.backBtn}>Back</button>
                <h1 style={styles.title}>COMBAT REPLAYS</h1>
                <div style={styles.userBadge}>
                    <span>{user.name}</span>
                </div>
            </div>

            {/* STATS */}
            {stats && activeTab === 'mine' && (
                <div style={styles.statsGrid}>
                    <div style={styles.statCard}>
                        <div style={styles.statValue}>{stats.totalReplays}</div>
                        <div style={styles.statLabel}>TOTAL REPLAYS</div>
                    </div>
                    <div style={styles.statCard}>
                        <div style={{ ...styles.statValue, color: '#00ff88' }}>{stats.wins}</div>
                        <div style={styles.statLabel}>WINS</div>
                    </div>
                    <div style={styles.statCard}>
                        <div style={{ ...styles.statValue, color: '#ff0044' }}>{stats.losses}</div>
                        <div style={styles.statLabel}>LOSSES</div>
                    </div>
                    <div style={styles.statCard}>
                        <div style={{ ...styles.statValue, color: '#ffaa00' }}>{stats.totalViews}</div>
                        <div style={styles.statLabel}>TOTAL VIEWS</div>
                    </div>
                </div>
            )}

            {/* TABS */}
            <div style={styles.tabs}>
                <button
                    onClick={() => setActiveTab('mine')}
                    style={{ ...styles.tab, ...(activeTab === 'mine' ? styles.tabActive : {}) }}
                >
                    MY REPLAYS
                </button>
                <button
                    onClick={() => setActiveTab('shared')}
                    style={{ ...styles.tab, ...(activeTab === 'shared' ? styles.tabActive : {}) }}
                >
                    SHARED REPLAYS
                </button>
            </div>

            {error && <div style={styles.errorBox}>{error}</div>}

            <div style={styles.grid}>
                {/* LIST */}
                <div style={styles.panel}>
                    <h2 style={styles.panelTitle}>
                        {activeTab === 'mine' ? 'YOUR BATTLES' : 'POPULAR REPLAYS'}
                    </h2>

                    {loading ? (
                        <div style={styles.loading}>Loading...</div>
                    ) : (activeTab === 'mine' ? replays : sharedReplays).length === 0 ? (
                        <div style={styles.empty}>
                            <div style={styles.emptyIcon}> </div>
                            <div style={styles.emptyText}>
                                {activeTab === 'mine'
                                    ? 'No battle replays yet. Fight some battles to record them!'
                                    : 'No shared replays available'}
                            </div>
                        </div>
                    ) : (
                        <div style={styles.replayList}>
                            {(activeTab === 'mine' ? replays : sharedReplays).map(replay => (
                                <div
                                    key={replay.id}
                                    onClick={() => setSelectedReplay(replay)}
                                    style={{
                                        ...styles.replayCard,
                                        borderColor: selectedReplay?.id === replay.id ? '#00f0ff' : 'rgba(0,240,255,0.15)',
                                        background: selectedReplay?.id === replay.id
                                            ? 'rgba(0,240,255,0.1)'
                                            : 'rgba(0,0,0,0.3)'
                                    }}
                                >
                                    <div style={styles.replayHeader}>
                                        <span style={styles.resultIcon}>
                                            {replay.result === 'win' ? 'W' : 'L'}
                                        </span>
                                        <div style={styles.replayInfo}>
                                            <div style={styles.replayVs}>
                                                {activeTab === 'shared' && (
                                                    <span style={styles.ownerName}>{replay.ownerName} </span>
                                                )}
                                                vs {replay.opponentName}
                                            </div>
                                            <div style={{
                                                ...styles.replayResult,
                                                color: getResultColor(replay.result)
                                            }}>
                                                {replay.result.toUpperCase()}
                                            </div>
                                        </div>
                                    </div>
                                    <div style={styles.replayMeta}>
                                        <span>{replay.damageDealt} dmg</span>
                                        <span>{replay.views} views</span>
                                        <span style={styles.replayTime}>{formatTime(replay.createdAt)}</span>
                                    </div>
                                    {activeTab === 'mine' && (
                                        <button
                                            onClick={(e) => deleteReplay(replay.id, e)}
                                            style={styles.deleteBtn}
                                        >
                                            X
                                        </button>
                                    )}
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* PLAYBACK */}
                <div style={styles.panel}>
                    <h2 style={styles.panelTitle}>REPLAY PLAYER</h2>

                    {!selectedReplay ? (
                        <div style={styles.empty}>
                            <div style={styles.emptyIcon}></div>
                            <div style={styles.emptyText}>Select a replay to view</div>
                        </div>
                    ) : !replayDetail ? (
                        <div style={styles.loading}>Loading replay...</div>
                    ) : (
                        <div>
                            {/* BATTLE INFO */}
                            <div style={styles.battleHeader}>
                                <div style={styles.fighter}>
                                    <div style={{
                                        ...styles.fighterAvatar,
                                        background: '#00f0ff'
                                    }}>
                                        {replayDetail.replay_data.attackerName.charAt(0)}
                                    </div>
                                    <div style={styles.fighterName}>
                                        {replayDetail.replay_data.attackerName}
                                    </div>
                                    <div style={styles.fighterPower}>
                                        {replayDetail.replay_data.attackerPower}
                                    </div>
                                </div>
                                <div style={styles.vs}>VS</div>
                                <div style={styles.fighter}>
                                    <div style={{
                                        ...styles.fighterAvatar,
                                        background: '#ff0044'
                                    }}>
                                        {replayDetail.replay_data.defenderName.charAt(0)}
                                    </div>
                                    <div style={styles.fighterName}>
                                        {replayDetail.replay_data.defenderName}
                                    </div>
                                    <div style={styles.fighterPower}>
                                        {replayDetail.replay_data.defenderPower}
                                    </div>
                                </div>
                            </div>

                            {/* HEALTH BARS */}
                            <div style={styles.healthBars}>
                                <div style={styles.healthBar}>
                                    <div style={{
                                        ...styles.healthFill,
                                        width: `${replayDetail.replay_data.steps[currentStep]?.attackerHP || 0}%`,
                                        background: 'linear-gradient(90deg, #00f0ff, #00ff88)'
                                    }} />
                                    <span style={styles.healthText}>
                                        {replayDetail.replay_data.steps[currentStep]?.attackerHP || 0}%
                                    </span>
                                </div>
                                <div style={styles.healthBar}>
                                    <div style={{
                                        ...styles.healthFill,
                                        width: `${replayDetail.replay_data.steps[currentStep]?.defenderHP || 0}%`,
                                        background: 'linear-gradient(90deg, #ff0044, #ff00ff)'
                                    }} />
                                    <span style={styles.healthText}>
                                        {replayDetail.replay_data.steps[currentStep]?.defenderHP || 0}%
                                    </span>
                                </div>
                            </div>

                            {/* STEP DISPLAY */}
                            <div style={styles.stepDisplay}>
                                <div style={styles.stepMessage}>
                                    {replayDetail.replay_data.steps[currentStep]?.message || 'Ready'}
                                </div>
                                <div style={styles.stepCounter}>
                                    Step {currentStep + 1} / {replayDetail.replay_data.steps.length}
                                </div>
                            </div>

                            {/* CONTROLS */}
                            <div style={styles.controls}>
                                {!playing ? (
                                    <button onClick={playReplay} style={styles.playBtn}>
                                        PLAY REPLAY
                                    </button>
                                ) : (
                                    <button onClick={stopReplay} style={styles.stopBtn}>
                                        STOP
                                    </button>
                                )}
                            </div>

                            {/* STEP TIMELINE */}
                            <div style={styles.timeline}>
                                {replayDetail.replay_data.steps.map((step, i) => (
                                    <div
                                        key={i}
                                        onClick={() => { setCurrentStep(i); setPlaying(false); }}
                                        style={{
                                            ...styles.timelineStep,
                                            background: i === currentStep
                                                ? '#00f0ff'
                                                : i < currentStep
                                                    ? 'rgba(0,240,255,0.3)'
                                                    : 'rgba(0,240,255,0.1)'
                                        }}
                                        title={step.message}
                                    />
                                ))}
                            </div>

                            {/* BATTLE STATS */}
                            <div style={styles.battleStats}>
                                <div style={styles.battleStat}>
                                    <div style={styles.battleStatLabel}>Credits</div>
                                    <div style={{
                                        ...styles.battleStatValue,
                                        color: replayDetail.replay_data.creditsEarned >= 0 ? '#d1d5db' : '#b8beca'
                                    }}>
                                        {replayDetail.replay_data.creditsEarned >= 0 ? '+' : ''}₵{replayDetail.replay_data.creditsEarned}
                                    </div>
                                </div>
                                <div style={styles.battleStat}>
                                    <div style={styles.battleStatLabel}>Damage Dealt</div>
                                    <div style={styles.battleStatValue}>{replayDetail.replay_data.damageDealt}</div>
                                </div>
                                <div style={styles.battleStat}>
                                    <div style={styles.battleStatLabel}>Damage Taken</div>
                                    <div style={styles.battleStatValue}>{replayDetail.replay_data.damageTaken}</div>
                                </div>
                                <div style={styles.battleStat}>
                                    <div style={styles.battleStatLabel}>Winner</div>
                                    <div style={styles.battleStatValue}>{replayDetail.replay_data.winnerName}</div>
                                </div>
                            </div>
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
    userBadge: { padding: '8px 16px', background: 'rgba(0,240,255,0.1)', border: '1px solid #00f0ff', color: '#00f0ff', borderRadius: '6px', fontWeight: 'bold', fontSize: '0.85rem' },

    statsGrid: { display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: '15px', marginBottom: '20px' },
    statCard: { padding: '15px', background: 'rgba(0, 240, 255, 0.05)', border: '1px solid rgba(0,240,255,0.2)', borderRadius: '12px', textAlign: 'center' },
    statValue: { fontSize: '1.8rem', fontWeight: 'bold', color: '#00f0ff', marginBottom: '6px' },
    statLabel: { fontSize: '0.7rem', color: '#8888cc', letterSpacing: '1px' },

    tabs: { display: 'flex', gap: '10px', marginBottom: '20px' },
    tab: { flex: 1, padding: '12px', background: 'rgba(0,0,0,0.3)', border: '1px solid rgba(0,240,255,0.2)', color: '#8888cc', cursor: 'pointer', borderRadius: '8px', fontFamily: 'monospace', fontWeight: 'bold', letterSpacing: '2px', fontSize: '0.8rem' },
    tabActive: { background: 'rgba(0,240,255,0.15)', borderColor: '#00f0ff', color: '#00f0ff' },

    errorBox: { padding: '12px', background: 'rgba(255,0,68,0.1)', border: '1px solid #ff0044', color: '#ff0044', borderRadius: '8px', marginBottom: '15px', fontSize: '0.85rem' },

    grid: { display: 'grid', gridTemplateColumns: '1fr 1.3fr', gap: '20px' },
    panel: { background: 'rgba(10, 10, 30, 0.85)', border: '1px solid rgba(0,240,255,0.15)', borderRadius: '12px', padding: '20px', maxHeight: '85vh', overflowY: 'auto' },
    panelTitle: { color: '#00f0ff', fontSize: '0.85rem', marginBottom: '15px', letterSpacing: '2px', marginTop: 0, borderBottom: '1px solid rgba(0,240,255,0.2)', paddingBottom: '10px' },
    loading: { textAlign: 'center', color: '#8888cc', fontSize: '0.85rem', padding: '40px' },
    empty: { textAlign: 'center', padding: '40px 20px' },
    emptyIcon: { fontSize: '3rem', marginBottom: '15px', opacity: 0.5 },
    emptyText: { color: '#8888cc', fontSize: '0.8rem', lineHeight: '1.6' },

    replayList: { display: 'flex', flexDirection: 'column', gap: '10px' },
    replayCard: { padding: '15px', border: '2px solid', borderRadius: '10px', cursor: 'pointer', transition: 'all 0.3s', position: 'relative' },
    replayHeader: { display: 'flex', alignItems: 'center', gap: '12px', marginBottom: '8px' },
    resultIcon: { fontSize: '1.5rem' },
    replayInfo: { flex: 1 },
    replayVs: { fontSize: '0.85rem', color: '#e0e0ff', fontWeight: 'bold', marginBottom: '4px' },
    ownerName: { color: '#00f0ff' },
    replayResult: { fontSize: '0.7rem', fontWeight: 'bold', letterSpacing: '1px' },
    replayMeta: { display: 'flex', gap: '12px', fontSize: '0.65rem', color: '#8888cc', flexWrap: 'wrap' },
    replayTime: { marginLeft: 'auto' },
    deleteBtn: { position: 'absolute', top: '10px', right: '10px', background: 'transparent', border: 'none', color: '#ff0044', cursor: 'pointer', fontSize: '1rem', opacity: 0.6 },

    battleHeader: { display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '20px', padding: '15px', background: 'rgba(0,0,0,0.3)', borderRadius: '10px' },
    fighter: { textAlign: 'center', flex: 1 },
    fighterAvatar: { width: '50px', height: '50px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '1.5rem', fontWeight: 'bold', color: '#0a0a1a', margin: '0 auto 8px' },
    fighterName: { fontSize: '0.8rem', fontWeight: 'bold', color: '#e0e0ff', marginBottom: '4px' },
    fighterPower: { fontSize: '0.7rem', color: '#00f0ff' },
    vs: { fontSize: '1.3rem', color: '#ffaa00', fontWeight: 'bold', textShadow: '0 0 20px rgba(255,170,0,0.6)' },

    healthBars: { display: 'flex', flexDirection: 'column', gap: '10px', marginBottom: '20px' },
    healthBar: { position: 'relative', height: '25px', background: 'rgba(0,0,0,0.5)', borderRadius: '12px', overflow: 'hidden', border: '1px solid rgba(0,240,255,0.2)' },
    healthFill: { height: '100%', transition: 'width 0.5s ease' },
    healthText: { position: 'absolute', top: '50%', left: '50%', transform: 'translate(-50%, -50%)', fontSize: '0.75rem', fontWeight: 'bold', color: 'white', textShadow: '0 0 4px black' },

    stepDisplay: { padding: '20px', background: 'rgba(0,0,0,0.5)', border: '1px solid rgba(0,240,255,0.3)', borderRadius: '10px', marginBottom: '15px', textAlign: 'center', minHeight: '80px', display: 'flex', flexDirection: 'column', justifyContent: 'center' },
    stepMessage: { fontSize: '0.9rem', color: '#e0e0ff', marginBottom: '8px' },
    stepCounter: { fontSize: '0.7rem', color: '#8888cc' },

    controls: { marginBottom: '15px' },
    playBtn: { width: '100%', padding: '15px', background: 'linear-gradient(90deg, #00f0ff, #00ff88)', color: '#0a0a1a', border: 'none', borderRadius: '8px', fontSize: '0.9rem', fontWeight: 'bold', cursor: 'pointer', fontFamily: 'monospace', letterSpacing: '2px' },
    stopBtn: { width: '100%', padding: '15px', background: 'linear-gradient(90deg, #ff0044, #ff00ff)', color: 'white', border: 'none', borderRadius: '8px', fontSize: '0.9rem', fontWeight: 'bold', cursor: 'pointer', fontFamily: 'monospace', letterSpacing: '2px' },

    timeline: { display: 'flex', gap: '4px', marginBottom: '20px' },
    timelineStep: { flex: 1, height: '8px', borderRadius: '4px', cursor: 'pointer', transition: 'all 0.3s' },

    battleStats: { display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '10px' },
    battleStat: { padding: '12px', background: 'rgba(0,0,0,0.3)', borderRadius: '8px', textAlign: 'center' },
    battleStatLabel: { fontSize: '0.65rem', color: '#8888cc', marginBottom: '4px' },
    battleStatValue: { fontSize: '0.9rem', fontWeight: 'bold', color: '#e0e0ff' }
};