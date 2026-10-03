import { useState, useEffect } from 'react';

const API = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api';

export default function Factions({ user, onBack, onAttackComplete }) {
    const [territories, setTerritories] = useState([]);
    const [summary, setSummary] = useState(null);
    const [stats, setStats] = useState(null);
    const [selectedTerritory, setSelectedTerritory] = useState(null);
    const [attackResult, setAttackResult] = useState(null);
    const [history, setHistory] = useState([]);
    const [loading, setLoading] = useState(true);
    const [attacking, setAttacking] = useState(false);
    const [error, setError] = useState('');

    useEffect(() => {
        loadData();
    }, []);

    const loadData = async () => {
        setLoading(true);
        try {
            const [territoriesRes, statsRes, historyRes] = await Promise.all([
                fetch(`${API}/factions/territories`),
                fetch(`${API}/factions/stats`),
                fetch(`${API}/factions/history?limit=10`)
            ]);

            const territoriesData = await territoriesRes.json();
            const statsData = await statsRes.json();
            const historyData = await historyRes.json();

            if (territoriesData.success) {
                setTerritories(territoriesData.data.territories);
                setSummary(territoriesData.data.summary);
            }
            if (statsData.success) setStats(statsData.data);
            if (historyData.success) setHistory(historyData.data.history);
        } catch (err) {
            setError('Failed to load faction data');
        } finally {
            setLoading(false);
        }
    };

    const attackTerritory = async () => {
        if (!selectedTerritory) return;

        setAttacking(true);
        setError('');

        try {
            const res = await fetch(`${API}/factions/attack`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    combatantId: user.id,
                    territoryId: selectedTerritory.id
                })
            });
            const data = await res.json();

            if (data.success) {
                setAttackResult(data.data);
                loadData();

                // Pass the new credits to parent
                if (onAttackComplete) {
                    onAttackComplete(data.data.attackerNewCredits);
                }
            } else {
                setError(data.message || 'Attack failed');
            }
        } catch (err) {
            setError('Cannot connect to server');
        } finally {
            setAttacking(false);
        }
    };

    const resetAttack = () => {
        setAttackResult(null);
        setSelectedTerritory(null);
    };

    const getBonusIcon = (bonusType) => {
        const icons = {
            'discount': 'D',
            'credit_multiplier': 'C',
            'crafting_bonus': 'F',
            'weather_resist': 'W',
            'combat_power': 'P',
            'loot_bonus': 'L'
        };
        return icons[bonusType] || 'N';
    };

    const getBonusLabel = (bonusType) => {
        const labels = {
            'discount': 'Shop Discount',
            'credit_multiplier': 'Credit Bonus',
            'crafting_bonus': 'Crafting Bonus',
            'weather_resist': 'Weather Resist',
            'combat_power': 'Combat Power',
            'loot_bonus': 'Loot Bonus'
        };
        return labels[bonusType] || bonusType;
    };

    const getFactionColor = (faction) => {
        return faction === 'hero' ? '#00f0ff' : '#ff0044';
    };

    if (loading) {
        return (
            <div style={styles.container}>
                <div style={styles.loading}>Loading territories...</div>
            </div>
        );
    }

    return (
        <div style={styles.container}>
            {/* HEADER */}
            <div style={styles.header}>
                <button onClick={onBack} style={styles.backBtn}>Back</button>
                <h1 style={styles.title}>FACTION WARS</h1>
                <div style={styles.factionBadge}>
                    <span style={{
                        padding: '6px 14px',
                        background: getFactionColor(user.faction),
                        color: '#0a0a1a',
                        borderRadius: '6px',
                        fontWeight: 'bold',
                        fontSize: '0.75rem'
                    }}>
                        {user.faction.toUpperCase()}
                    </span>
                </div>
            </div>

            {/* SUMMARY */}
            {summary && (
                <div style={styles.summaryRow}>
                    <div style={{ ...styles.summaryCard, borderColor: '#00f0ff' }}>
                        <div style={styles.summaryLabel}>HERO TERRITORIES</div>
                        <div style={{ ...styles.summaryValue, color: '#00f0ff' }}>
                            {summary.heroControlled} / {summary.total}
                        </div>
                    </div>
                    <div style={{ ...styles.summaryCard, borderColor: '#ff0044' }}>
                        <div style={styles.summaryLabel}>VILLAIN TERRITORIES</div>
                        <div style={{ ...styles.summaryValue, color: '#ff0044' }}>
                            {summary.villainControlled} / {summary.total}
                        </div>
                    </div>
                    {stats && (
                        <>
                            <div style={{ ...styles.summaryCard, borderColor: '#00f0ff' }}>
                                <div style={styles.summaryLabel}>HERO POWER</div>
                                <div style={{ ...styles.summaryValue, color: '#00f0ff' }}>
                                    {stats.heroes.power.toLocaleString()}
                                </div>
                            </div>
                            <div style={{ ...styles.summaryCard, borderColor: '#ff0044' }}>
                                <div style={styles.summaryLabel}>VILLAIN POWER</div>
                                <div style={{ ...styles.summaryValue, color: '#ff0044' }}>
                                    {stats.villains.power.toLocaleString()}
                                </div>
                            </div>
                        </>
                    )}
                </div>
            )}

            {error && <div style={styles.errorBox}>{error}</div>}

            <div style={styles.grid}>
                {/* TERRITORIES */}
                <div style={styles.panel}>
                    <h2 style={styles.panelTitle}>TERRITORIES</h2>

                    <div style={styles.territoryList}>
                        {territories.map(t => {
                            const isEnemy = t.controllingFaction !== user.faction;
                            const isSelected = selectedTerritory?.id === t.id;

                            return (
                                <div
                                    key={t.id}
                                    onClick={() => !attackResult && isEnemy && setSelectedTerritory(t)}
                                    style={{
                                        ...styles.territoryCard,
                                        borderColor: isSelected ? '#ffaa00' : getFactionColor(t.controllingFaction),
                                        background: isSelected ? 'rgba(255,170,0,0.1)' : 'rgba(0,0,0,0.3)',
                                        cursor: isEnemy && !attackResult ? 'pointer' : 'default',
                                        opacity: !isEnemy ? 0.7 : 1
                                    }}
                                >
                                    <div style={styles.territoryHeader}>
                                        <div style={styles.territoryName}>{t.name}</div>
                                        <div style={{
                                            ...styles.factionTag,
                                            background: getFactionColor(t.controllingFaction),
                                            color: '#0a0a1a'
                                        }}>
                                            {t.controllingFaction.toUpperCase()}
                                        </div>
                                    </div>

                                    <div style={styles.territoryDesc}>{t.description}</div>

                                    <div style={styles.controlBar}>
                                        <div style={{
                                            ...styles.controlFill,
                                            width: `${t.controlPercentage}%`,
                                            background: getFactionColor(t.controllingFaction)
                                        }} />
                                        <span style={styles.controlText}>
                                            {t.controlPercentage}% CONTROL
                                        </span>
                                    </div>

                                    <div style={styles.territoryFooter}>
                                        <div style={styles.bonusTag}>
                                            {getBonusIcon(t.bonusType)} {getBonusLabel(t.bonusType)} +{t.bonusValue}%
                                        </div>
                                        <div style={styles.defenseTag}>
                                            {t.defensePower.toLocaleString()}
                                        </div>
                                    </div>

                                    {isEnemy && !attackResult && (
                                        <div style={styles.attackHint}>Click to attack</div>
                                    )}
                                    {!isEnemy && (
                                        <div style={styles.friendlyHint}>Your territory</div>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                </div>

                {/* ATTACK PANEL */}
                <div style={styles.panel}>
                    <h2 style={styles.panelTitle}>ATTACK PANEL</h2>

                    {!attackResult ? (
                        <div>
                            {!selectedTerritory ? (
                                <div style={styles.loading}>Select an enemy territory to attack</div>
                            ) : (
                                <>
                                    <div style={styles.attackTarget}>
                                        <h3 style={styles.targetName}>{selectedTerritory.name}</h3>
                                        <div style={styles.targetMeta}>{selectedTerritory.region}</div>
                                        <div style={styles.targetStats}>
                                            <div style={styles.targetStat}>
                                                <div style={styles.statLabel}>Defense</div>
                                                <div style={styles.statValue}>{selectedTerritory.defensePower.toLocaleString()}</div>
                                            </div>
                                            <div style={styles.targetStat}>
                                                <div style={styles.statLabel}>Your Power</div>
                                                <div style={styles.statValue}>{Math.round(user.bioCapacityMax * 0.8)}</div>
                                            </div>
                                            <div style={styles.targetStat}>
                                                <div style={styles.statLabel}>Control</div>
                                                <div style={styles.statValue}>{selectedTerritory.controlPercentage}%</div>
                                            </div>
                                        </div>
                                        <div style={styles.targetBonus}>
                                            {getBonusIcon(selectedTerritory.bonusType)}{' '}
                                            {getBonusLabel(selectedTerritory.bonusType)} +{selectedTerritory.bonusValue}%
                                            <div style={{ fontSize: '0.65rem', color: '#8888cc', marginTop: '4px' }}>
                                                Capture this to give your faction the bonus!
                                            </div>
                                        </div>
                                    </div>

                                    <button
                                        onClick={attackTerritory}
                                        disabled={attacking}
                                        style={{
                                            ...styles.attackBtn,
                                            opacity: attacking ? 0.5 : 1,
                                            cursor: attacking ? 'not-allowed' : 'pointer'
                                        }}
                                    >
                                        {attacking ? 'ATTACKING...' : 'ATTACK NOW!'}
                                    </button>
                                </>
                            )}
                        </div>
                    ) : (
                        <div style={styles.resultPanel}>
                            <div style={{
                                ...styles.resultBanner,
                                background: attackResult.result === 'victory' ? 'rgba(0,255,136,0.15)' : 'rgba(255,0,68,0.15)',
                                borderColor: attackResult.result === 'victory' ? '#00ff88' : '#ff0044',
                                color: attackResult.result === 'victory' ? '#00ff88' : '#ff0044'
                            }}>
                                {attackResult.result === 'victory' ? 'VICTORY!' : 'DEFEAT'}
                            </div>

                            <div style={styles.logBox}>
                                {attackResult.log.map((line, i) => (
                                    <div key={i} style={styles.logLine}>{line}</div>
                                ))}
                            </div>

                            <div style={styles.resultStats}>
                                <div style={styles.resultStat}>
                                    <span style={styles.rewardLabel}>Credits</span>
                                    <span style={{
                                        ...styles.rewardValue,
                                        color: attackResult.creditsEarned > 0 ? '#d1d5db' : '#b8beca'
                                    }}>
                                        {attackResult.creditsEarned > 0 ? '+' : ''}₵{attackResult.creditsEarned}
                                    </span>
                                </div>
                                <div style={styles.resultStat}>
                                    <span style={styles.rewardLabel}>Control</span>
                                    <span style={styles.rewardValue}>
                                        {attackResult.controlChange > 0 ? '-' : ''}{attackResult.controlChange}%
                                    </span>
                                </div>
                            </div>

                            <button onClick={resetAttack} style={styles.attackBtn}>
                                ATTACK AGAIN
                            </button>
                        </div>
                    )}
                </div>

                {/* HISTORY */}
                <div style={styles.panel}>
                    <h2 style={styles.panelTitle}>RECENT ATTACKS</h2>

                    {history.length === 0 ? (
                        <div style={styles.loading}>No attacks yet</div>
                    ) : (
                        <div style={styles.historyList}>
                            {history.map(h => (
                                <div key={h.id} style={styles.historyItem}>
                                    <div style={styles.historyTop}>
                                        <span style={styles.historyAttacker}>
                                            {h.attackerName}
                                        </span>
                                        <span style={{
                                            padding: '2px 8px',
                                            borderRadius: '8px',
                                            fontSize: '0.6rem',
                                            fontWeight: 'bold',
                                            background: getFactionColor(h.attackerFaction),
                                            color: '#0a0a1a'
                                        }}>
                                            {h.attackerFaction.toUpperCase()}
                                        </span>
                                    </div>
                                    <div style={styles.historyTarget}>→ {h.territoryName}</div>
                                    <div style={styles.historyMeta}>
                                        <span style={{
                                            color: h.result === 'victory' ? '#00ff88' : '#ff0044',
                                            fontWeight: 'bold'
                                        }}>
                                            {h.result === 'victory' ? 'WIN' : 'LOSS'}
                                        </span>
                                        <span style={{ color: '#8888cc', fontSize: '0.65rem' }}>
                                            {new Date(h.foughtAt).toLocaleTimeString()}
                                        </span>
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
    factionBadge: { display: 'flex', alignItems: 'center', gap: '10px' },
    loading: { textAlign: 'center', color: '#8888cc', fontSize: '0.85rem', padding: '30px' },
    summaryRow: { display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: '15px', marginBottom: '20px' },
    summaryCard: { padding: '15px', background: 'rgba(0,0,0,0.3)', border: '2px solid', borderRadius: '8px', textAlign: 'center' },
    summaryLabel: { fontSize: '0.65rem', color: '#8888cc', letterSpacing: '1px', marginBottom: '6px' },
    summaryValue: { fontSize: '1.5rem', fontWeight: 'bold' },
    errorBox: { padding: '12px', background: 'rgba(255,0,68,0.1)', border: '1px solid #ff0044', color: '#ff0044', borderRadius: '8px', marginBottom: '20px', fontSize: '0.85rem' },
    grid: { display: 'grid', gridTemplateColumns: '1.5fr 1fr 1fr', gap: '20px' },
    panel: { background: 'rgba(10, 10, 30, 0.85)', border: '1px solid rgba(0,240,255,0.15)', borderRadius: '12px', padding: '20px' },
    panelTitle: { color: '#00f0ff', fontSize: '0.85rem', marginBottom: '15px', letterSpacing: '2px', marginTop: 0, borderBottom: '1px solid rgba(0,240,255,0.2)', paddingBottom: '10px' },
    territoryList: { display: 'flex', flexDirection: 'column', gap: '12px', maxHeight: '700px', overflowY: 'auto' },
    territoryCard: { padding: '15px', border: '2px solid', borderRadius: '10px', transition: 'all 0.3s' },
    territoryHeader: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' },
    territoryName: { fontWeight: 'bold', fontSize: '0.95rem', color: '#e0e0ff' },
    factionTag: { padding: '3px 10px', borderRadius: '10px', fontSize: '0.6rem', fontWeight: 'bold' },
    territoryDesc: { fontSize: '0.7rem', color: '#8888cc', marginBottom: '10px' },
    controlBar: { position: 'relative', width: '100%', height: '20px', background: 'rgba(0,0,0,0.5)', borderRadius: '10px', overflow: 'hidden', marginBottom: '10px' },
    controlFill: { height: '100%', transition: 'width 0.3s' },
    controlText: { position: 'absolute', top: '50%', left: '50%', transform: 'translate(-50%, -50%)', fontSize: '0.65rem', fontWeight: 'bold', color: 'white', textShadow: '0 0 4px black' },
    territoryFooter: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', fontSize: '0.7rem' },
    bonusTag: { color: '#ffaa00' },
    defenseTag: { color: '#00f0ff', fontWeight: 'bold' },
    attackHint: { marginTop: '8px', fontSize: '0.65rem', color: '#ffaa00', textAlign: 'center', padding: '4px', background: 'rgba(255,170,0,0.1)', borderRadius: '4px' },
    friendlyHint: { marginTop: '8px', fontSize: '0.65rem', color: '#00ff88', textAlign: 'center', padding: '4px', background: 'rgba(0,255,136,0.1)', borderRadius: '4px' },
    attackTarget: { padding: '20px', background: 'rgba(255,170,0,0.05)', border: '1px solid rgba(255,170,0,0.3)', borderRadius: '10px', marginBottom: '20px', textAlign: 'center' },
    targetName: { color: '#ffaa00', fontSize: '1.2rem', marginBottom: '5px', marginTop: 0 },
    targetMeta: { fontSize: '0.7rem', color: '#8888cc', marginBottom: '15px' },
    targetStats: { display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '10px', marginBottom: '15px' },
    targetStat: { padding: '10px', background: 'rgba(0,0,0,0.3)', borderRadius: '6px', textAlign: 'center' },
    statLabel: { fontSize: '0.6rem', color: '#8888cc', marginBottom: '4px' },
    statValue: { fontSize: '0.95rem', fontWeight: 'bold', color: '#00f0ff' },
    targetBonus: { padding: '10px', background: 'rgba(255,170,0,0.1)', border: '1px solid rgba(255,170,0,0.3)', borderRadius: '6px', fontSize: '0.75rem', color: '#ffaa00', textAlign: 'center' },
    attackBtn: { width: '100%', padding: '15px', background: 'linear-gradient(90deg, #ff0044, #ff00ff)', color: 'white', border: 'none', borderRadius: '8px', fontSize: '0.9rem', fontWeight: 'bold', cursor: 'pointer', fontFamily: 'monospace', letterSpacing: '2px' },
    resultPanel: { textAlign: 'center' },
    resultBanner: { padding: '20px', borderRadius: '10px', fontSize: '1.5rem', fontWeight: 'bold', marginBottom: '20px', border: '2px solid', letterSpacing: '3px' },
    logBox: { background: 'rgba(0,0,0,0.4)', padding: '15px', borderRadius: '8px', textAlign: 'left', marginBottom: '15px' },
    logLine: { fontSize: '0.75rem', color: '#e0e0ff', padding: '6px 0', borderBottom: '1px solid rgba(0,240,255,0.05)' },
    resultStats: { background: 'rgba(0,0,0,0.3)', padding: '15px', borderRadius: '8px', marginBottom: '20px', display: 'flex', flexDirection: 'column', gap: '10px' },
    resultStat: { display: 'flex', justifyContent: 'space-between', fontSize: '0.8rem' },
    rewardLabel: { color: '#8888cc' },
    rewardValue: { fontWeight: 'bold', color: '#e0e0ff' },
    historyList: { display: 'flex', flexDirection: 'column', gap: '10px', maxHeight: '600px', overflowY: 'auto' },
    historyItem: { padding: '10px', background: 'rgba(0,0,0,0.3)', borderRadius: '6px', border: '1px solid rgba(0,240,255,0.1)' },
    historyTop: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '4px' },
    historyAttacker: { fontSize: '0.8rem', fontWeight: 'bold', color: '#e0e0ff' },
    historyTarget: { fontSize: '0.7rem', color: '#8888cc', marginBottom: '4px' },
    historyMeta: { display: 'flex', justifyContent: 'space-between', fontSize: '0.75rem' }
};