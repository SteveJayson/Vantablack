import { useState, useEffect } from 'react';

const API = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api';

export default function Leaderboards({ user, onBack }) {
    const [activeTab, setActiveTab] = useState('global');
    const [data, setData] = useState(null);
    const [myRank, setMyRank] = useState(null);
    const [factionData, setFactionData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        loadData();
    }, [activeTab]);

    useEffect(() => {
        loadMyRank();
        loadFactionData();
    }, []);

    const loadData = async () => {
        setLoading(true);
        setError('');
        try {
            const endpoints = {
                global: '/leaderboards/global?limit=20',
                fighters: '/leaderboards/fighters?limit=20',
                earners: '/leaderboards/earners?limit=20',
                collectors: '/leaderboards/collectors?limit=20',
                achievements: '/leaderboards/achievements?limit=20'
            };

            const res = await fetch(`${API}${endpoints[activeTab]}`);
            const result = await res.json();

            if (result.success) {
                setData(result.data.data || result.data);
            } else {
                setError(result.message);
            }
        } catch (err) {
            setError('Cannot load leaderboard');
        } finally {
            setLoading(false);
        }
    };

    const loadMyRank = async () => {
        try {
            const res = await fetch(`${API}/leaderboards/my-rank/${user.id}`);
            const data = await res.json();
            if (data.success) {
                setMyRank(data.data);
            }
        } catch (err) {
            console.error('Failed to load my rank:', err);
        }
    };

    const loadFactionData = async () => {
        try {
            const res = await fetch(`${API}/leaderboards/factions`);
            const data = await res.json();
            if (data.success) {
                setFactionData(data.data);
            }
        } catch (err) {
            console.error('Failed to load faction data:', err);
        }
    };

    const getRankIcon = (rank) => {
        if (rank === 1) return '1';
        if (rank === 2) return '2';
        if (rank === 3) return '3';
        return `#${rank}`;
    };

    const getRankColor = (rank) => {
        if (rank === 1) return '#ffd700';
        if (rank === 2) return '#c0c0c0';
        if (rank === 3) return '#cd7f32';
        return '#8888cc';
    };

    const getRoleIcon = (role) => {
        const icons = { civilian: 'C', hero: 'H', villain: 'V', admin: 'A' };
        return icons[role] || 'N';
    };

    const getRoleColor = (role) => {
        const colors = { civilian: '#ffaa00', hero: '#00f0ff', villain: '#ff0044', admin: '#ff00ff' };
        return colors[role] || '#00f0ff';
    };

    const tabs = [
        { id: 'global', label: 'Global', icon: 'G' },
        { id: 'fighters', label: 'Fighters', icon: 'F' },
        { id: 'earners', label: 'Earners', icon: 'E' },
        { id: 'collectors', label: 'Collectors', icon: 'C' },
        { id: 'achievements', label: 'Achievements', icon: 'A' }
    ];

    const renderRow = (entry, index, category) => {
        const rank = index + 1;
        const isMe = entry.id === user.id;

        return (
            <div
                key={entry.id}
                style={{
                    ...styles.row,
                    background: isMe ? 'rgba(0, 240, 255, 0.1)' : 'rgba(0,0,0,0.3)',
                    borderLeft: isMe ? '3px solid #00f0ff' : '3px solid transparent'
                }}
            >
                <div style={{ ...styles.rank, color: getRankColor(rank) }}>
                    {getRankIcon(rank)}
                </div>
                <div style={styles.roleIcon}>{getRoleIcon(entry.role)}</div>
                <div style={styles.nameCell}>
                    <div style={styles.name}>
                        {entry.name}
                        {isMe && <span style={styles.youBadge}>(You)</span>}
                    </div>
                    <div style={{ ...styles.roleTag, color: getRoleColor(entry.role) }}>
                        {entry.role.toUpperCase()}
                    </div>
                </div>

                {category === 'global' && (
                    <>
                        <div style={styles.statCell}>
                            <div style={styles.statValue}>{entry.globalScore}</div>
                            <div style={styles.statLabel}>SCORE</div>
                        </div>
                        <div style={styles.statCell}>
                            <div style={styles.statValue}>{entry.wins}</div>
                            <div style={styles.statLabel}>WINS</div>
                        </div>
                        <div style={styles.statCell}>
                            <div style={styles.statValue}>₵{entry.credits.toLocaleString()}</div>
                            <div style={styles.statLabel}>CREDITS</div>
                        </div>
                    </>
                )}

                {category === 'fighters' && (
                    <>
                        <div style={styles.statCell}>
                            <div style={styles.statValue}>{entry.wins}</div>
                            <div style={styles.statLabel}>WINS</div>
                        </div>
                        <div style={styles.statCell}>
                            <div style={styles.statValue}>{entry.winRate}%</div>
                            <div style={styles.statLabel}>WIN RATE</div>
                        </div>
                        <div style={styles.statCell}>
                            <div style={{ ...styles.statValue, color: '#d1d5db' }}>{entry.currentStreak}</div>
                            <div style={styles.statLabel}>STREAK</div>
                        </div>
                    </>
                )}

                {category === 'earners' && (
                    <>
                        <div style={styles.statCell}>
                            <div style={styles.statValue}>₵{entry.netWorth.toLocaleString()}</div>
                            <div style={styles.statLabel}>NET WORTH</div>
                        </div>
                        <div style={styles.statCell}>
                            <div style={styles.statValue}>₵{entry.totalEarned.toLocaleString()}</div>
                            <div style={styles.statLabel}>EARNED</div>
                        </div>
                        <div style={styles.statCell}>
                            <div style={{ ...styles.statValue, color: '#00ff88' }}>₵{entry.gearValue.toLocaleString()}</div>
                            <div style={styles.statLabel}>GEAR VALUE</div>
                        </div>
                    </>
                )}

                {category === 'collectors' && (
                    <>
                        <div style={styles.statCell}>
                            <div style={styles.statValue}>{entry.gearCount}</div>
                            <div style={styles.statLabel}>ITEMS</div>
                        </div>
                        <div style={styles.statCell}>
                            <div style={{ ...styles.statValue, color: '#ff00ff' }}>{entry.legendaryCount}</div>
                            <div style={styles.statLabel}>LEGENDARY</div>
                        </div>
                        <div style={styles.statCell}>
                            <div style={{ ...styles.statValue, color: '#00ff88' }}>₵{entry.totalGearValue.toLocaleString()}</div>
                            <div style={styles.statLabel}>VALUE</div>
                        </div>
                    </>
                )}

                {category === 'achievements' && (
                    <>
                        <div style={styles.statCell}>
                            <div style={styles.statValue}>{entry.achievementCount}</div>
                            <div style={styles.statLabel}>UNLOCKED</div>
                        </div>
                        <div style={styles.statCell}>
                            <div style={{ ...styles.statValue, color: '#ffaa00' }}>{entry.totalPoints}</div>
                            <div style={styles.statLabel}>POINTS</div>
                        </div>
                    </>
                )}
            </div>
        );
    };

    return (
        <div style={styles.container}>
            {/* HEADER */}
            <div style={styles.header}>
                <button onClick={onBack} style={styles.backBtn}>Back</button>
                <h1 style={styles.title}>LEADERBOARDS</h1>
                <div style={styles.userBadge}>
                    <span>{user.name}</span>
                </div>
            </div>

            {/* MY RANK SUMMARY */}
            {myRank && (
                <div style={styles.myRankCard}>
                    <h3 style={styles.myRankTitle}>YOUR RANKINGS</h3>
                    <div style={styles.myRankGrid}>
                        <div style={styles.myRankItem}>
                            <div style={styles.myRankLabel}>Global</div>
                            <div style={styles.myRankValue}>#{myRank.globalRank}</div>
                        </div>
                        <div style={styles.myRankItem}>
                            <div style={styles.myRankLabel}>Fighter</div>
                            <div style={styles.myRankValue}>#{myRank.fighterRank}</div>
                        </div>
                        <div style={styles.myRankItem}>
                            <div style={styles.myRankLabel}>Earner</div>
                            <div style={styles.myRankValue}>#{myRank.earnerRank}</div>
                        </div>
                        <div style={styles.myRankItem}>
                            <div style={styles.myRankLabel}>Collector</div>
                            <div style={styles.myRankValue}>#{myRank.collectorRank}</div>
                        </div>
                        <div style={styles.myRankItem}>
                            <div style={styles.myRankLabel}>Achiever</div>
                            <div style={styles.myRankValue}>#{myRank.achievementRank}</div>
                        </div>
                    </div>
                </div>
            )}

            {/* FACTION RANKINGS */}
            {factionData && (
                <div style={styles.factionCard}>
                    <h3 style={styles.factionTitle}>FACTION RANKINGS</h3>
                    <div style={styles.factionGrid}>
                        <div style={{
                            ...styles.factionItem,
                            borderColor: factionData.leader === 'hero' ? '#00f0ff' : 'rgba(0,240,255,0.2)',
                            background: factionData.leader === 'hero' ? 'rgba(0,240,255,0.1)' : 'rgba(0,0,0,0.3)'
                        }}>
                            <div style={styles.factionHeader}>
                                <span style={styles.factionIcon}>H</span>
                                <span style={styles.factionName}>HEROES</span>
                                {factionData.leader === 'hero' && <span style={styles.leaderBadge}>LEADING</span>}
                            </div>
                            <div style={styles.factionStats}>
                                <div style={styles.factionStat}>
                                    <div style={styles.factionStatValue}>{factionData.hero.memberCount}</div>
                                    <div style={styles.factionStatLabel}>MEMBERS</div>
                                </div>
                                <div style={styles.factionStat}>
                                    <div style={styles.factionStatValue}>{factionData.hero.totalWins}</div>
                                    <div style={styles.factionStatLabel}>WINS</div>
                                </div>
                                <div style={styles.factionStat}>
                                    <div style={styles.factionStatValue}>{factionData.hero.territories}</div>
                                    <div style={styles.factionStatLabel}>TERRITORIES</div>
                                </div>
                                <div style={styles.factionStat}>
                                    <div style={{ ...styles.factionStatValue, color: '#00f0ff' }}>
                                        {factionData.hero.powerScore}
                                    </div>
                                    <div style={styles.factionStatLabel}>POWER</div>
                                </div>
                            </div>
                        </div>

                        <div style={{
                            ...styles.factionItem,
                            borderColor: factionData.leader === 'villain' ? '#ff0044' : 'rgba(255,0,68,0.2)',
                            background: factionData.leader === 'villain' ? 'rgba(255,0,68,0.1)' : 'rgba(0,0,0,0.3)'
                        }}>
                            <div style={styles.factionHeader}>
                                <span style={styles.factionIcon}>V</span>
                                <span style={{ ...styles.factionName, color: '#ff0044' }}>VILLAINS</span>
                                {factionData.leader === 'villain' && <span style={styles.leaderBadge}>LEADING</span>}
                            </div>
                            <div style={styles.factionStats}>
                                <div style={styles.factionStat}>
                                    <div style={styles.factionStatValue}>{factionData.villain.memberCount}</div>
                                    <div style={styles.factionStatLabel}>MEMBERS</div>
                                </div>
                                <div style={styles.factionStat}>
                                    <div style={styles.factionStatValue}>{factionData.villain.totalWins}</div>
                                    <div style={styles.factionStatLabel}>WINS</div>
                                </div>
                                <div style={styles.factionStat}>
                                    <div style={styles.factionStatValue}>{factionData.villain.territories}</div>
                                    <div style={styles.factionStatLabel}>TERRITORIES</div>
                                </div>
                                <div style={styles.factionStat}>
                                    <div style={{ ...styles.factionStatValue, color: '#ff0044' }}>
                                        {factionData.villain.powerScore}
                                    </div>
                                    <div style={styles.factionStatLabel}>POWER</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style={styles.factionSummary}>
                        <strong>{factionData.leader.toUpperCase()}S</strong> are leading by <strong>{factionData.difference}</strong> power points
                    </div>
                </div>
            )}

            {/* TABS */}
            <div style={styles.tabs}>
                {tabs.map(tab => (
                    <button
                        key={tab.id}
                        onClick={() => setActiveTab(tab.id)}
                        style={{
                            ...styles.tab,
                            ...(activeTab === tab.id ? styles.tabActive : {})
                        }}
                    >
                        {tab.label}
                    </button>
                ))}
            </div>

            {error && <div style={styles.errorBox}>{error}</div>}

            {/* LEADERBOARD LIST */}
            <div style={styles.panel}>
                <h2 style={styles.panelTitle}>
                    {tabs.find(t => t.id === activeTab)?.label} RANKINGS
                </h2>

                {loading ? (
                    <div style={styles.loading}>Loading rankings...</div>
                ) : !data || data.length === 0 ? (
                    <div style={styles.loading}>No rankings available yet</div>
                ) : (
                    <div style={styles.list}>
                        {/* HEADER ROW */}
                        <div style={styles.headerRow}>
                            <div style={{ ...styles.rank, color: '#00f0ff' }}>RANK</div>
                            <div style={styles.roleIcon}></div>
                            <div style={styles.nameCell}>
                                <div style={{ ...styles.name, color: '#00f0ff', fontSize: '0.7rem' }}>PLAYER</div>
                            </div>

                            {activeTab === 'global' && (
                                <>
                                    <div style={styles.statCell}><div style={{ ...styles.statLabel, color: '#00f0ff' }}>SCORE</div></div>
                                    <div style={styles.statCell}><div style={{ ...styles.statLabel, color: '#00f0ff' }}>WINS</div></div>
                                    <div style={styles.statCell}><div style={{ ...styles.statLabel, color: '#00f0ff' }}>CREDITS</div></div>
                                </>
                            )}
                            {activeTab === 'fighters' && (
                                <>
                                    <div style={styles.statCell}><div style={{ ...styles.statLabel, color: '#00f0ff' }}>WINS</div></div>
                                    <div style={styles.statCell}><div style={{ ...styles.statLabel, color: '#00f0ff' }}>WIN RATE</div></div>
                                    <div style={styles.statCell}><div style={{ ...styles.statLabel, color: '#00f0ff' }}>STREAK</div></div>
                                </>
                            )}
                            {activeTab === 'earners' && (
                                <>
                                    <div style={styles.statCell}><div style={{ ...styles.statLabel, color: '#00f0ff' }}>NET WORTH</div></div>
                                    <div style={styles.statCell}><div style={{ ...styles.statLabel, color: '#00f0ff' }}>EARNED</div></div>
                                    <div style={styles.statCell}><div style={{ ...styles.statLabel, color: '#00f0ff' }}>GEAR VALUE</div></div>
                                </>
                            )}
                            {activeTab === 'collectors' && (
                                <>
                                    <div style={styles.statCell}><div style={{ ...styles.statLabel, color: '#00f0ff' }}>ITEMS</div></div>
                                    <div style={styles.statCell}><div style={{ ...styles.statLabel, color: '#00f0ff' }}>LEGENDARY</div></div>
                                    <div style={styles.statCell}><div style={{ ...styles.statLabel, color: '#00f0ff' }}>VALUE</div></div>
                                </>
                            )}
                            {activeTab === 'achievements' && (
                                <>
                                    <div style={styles.statCell}><div style={{ ...styles.statLabel, color: '#00f0ff' }}>UNLOCKED</div></div>
                                    <div style={styles.statCell}><div style={{ ...styles.statLabel, color: '#00f0ff' }}>POINTS</div></div>
                                </>
                            )}
                        </div>

                        {/* DATA ROWS */}
                        {data.map((entry, index) => renderRow(entry, index, activeTab))}
                    </div>
                )}
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

    myRankCard: { background: 'rgba(255, 170, 0, 0.05)', border: '1px solid rgba(255, 170, 0, 0.3)', borderRadius: '12px', padding: '20px', marginBottom: '20px' },
    myRankTitle: { color: '#ffaa00', fontSize: '0.85rem', marginBottom: '15px', letterSpacing: '2px', marginTop: 0 },
    myRankGrid: { display: 'grid', gridTemplateColumns: 'repeat(5, 1fr)', gap: '10px' },
    myRankItem: { padding: '12px', background: 'rgba(0,0,0,0.3)', borderRadius: '8px', textAlign: 'center' },
    myRankLabel: { fontSize: '0.65rem', color: '#8888cc', marginBottom: '6px' },
    myRankValue: { fontSize: '1.3rem', fontWeight: 'bold', color: '#ffaa00' },

    factionCard: { background: 'rgba(255, 0, 255, 0.05)', border: '1px solid rgba(255, 0, 255, 0.3)', borderRadius: '12px', padding: '20px', marginBottom: '20px' },
    factionTitle: { color: '#ff00ff', fontSize: '0.85rem', marginBottom: '15px', letterSpacing: '2px', marginTop: 0 },
    factionGrid: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '15px', marginBottom: '15px' },
    factionItem: { padding: '15px', border: '2px solid', borderRadius: '10px' },
    factionHeader: { display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '12px' },
    factionIcon: { fontSize: '1.5rem' },
    factionName: { fontSize: '0.85rem', fontWeight: 'bold', color: '#00f0ff', letterSpacing: '2px', flex: 1 },
    leaderBadge: { padding: '3px 8px', background: 'rgba(255, 215, 0, 0.2)', color: '#ffd700', borderRadius: '8px', fontSize: '0.6rem', fontWeight: 'bold' },
    factionStats: { display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: '8px' },
    factionStat: { padding: '8px', background: 'rgba(0,0,0,0.3)', borderRadius: '6px', textAlign: 'center' },
    factionStatValue: { fontSize: '1rem', fontWeight: 'bold', color: '#e0e0ff', marginBottom: '4px' },
    factionStatLabel: { fontSize: '0.55rem', color: '#8888cc', letterSpacing: '1px' },
    factionSummary: { textAlign: 'center', fontSize: '0.8rem', color: '#e0e0ff', padding: '10px', background: 'rgba(0,0,0,0.3)', borderRadius: '6px' },

    tabs: { display: 'flex', gap: '8px', marginBottom: '20px', flexWrap: 'wrap' },
    tab: { flex: 1, minWidth: '120px', padding: '10px 14px', background: 'rgba(0,0,0,0.3)', border: '1px solid rgba(0,240,255,0.2)', color: '#8888cc', cursor: 'pointer', borderRadius: '8px', fontFamily: 'monospace', fontWeight: 'bold', fontSize: '0.75rem', letterSpacing: '1px' },
    tabActive: { background: 'rgba(0,240,255,0.15)', borderColor: '#00f0ff', color: '#00f0ff' },

    errorBox: { padding: '12px', background: 'rgba(255,0,68,0.1)', border: '1px solid #ff0044', color: '#ff0044', borderRadius: '8px', marginBottom: '15px', fontSize: '0.85rem' },

    panel: { background: 'rgba(10, 10, 30, 0.85)', border: '1px solid rgba(0,240,255,0.15)', borderRadius: '12px', padding: '20px' },
    panelTitle: { color: '#00f0ff', fontSize: '0.85rem', marginBottom: '15px', letterSpacing: '2px', marginTop: 0, borderBottom: '1px solid rgba(0,240,255,0.2)', paddingBottom: '10px' },
    loading: { textAlign: 'center', color: '#8888cc', fontSize: '0.85rem', padding: '40px' },

    list: { display: 'flex', flexDirection: 'column', gap: '6px' },
    headerRow: { display: 'flex', alignItems: 'center', gap: '10px', padding: '10px 15px', background: 'rgba(0,240,255,0.05)', borderRadius: '6px', marginBottom: '6px' },
    row: { display: 'flex', alignItems: 'center', gap: '10px', padding: '12px 15px', borderRadius: '8px', transition: 'all 0.2s' },

    rank: { width: '50px', fontSize: '1rem', fontWeight: 'bold', textAlign: 'center', flexShrink: 0 },
    roleIcon: { fontSize: '1.3rem', flexShrink: 0, width: '30px' },
    nameCell: { flex: 1, minWidth: 0 },
    name: { fontSize: '0.85rem', fontWeight: 'bold', color: '#e0e0ff', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' },
    youBadge: { fontSize: '0.65rem', color: '#00f0ff', marginLeft: '8px', fontStyle: 'italic' },
    roleTag: { fontSize: '0.6rem', fontWeight: 'bold', marginTop: '2px', letterSpacing: '1px' },

    statCell: { width: '100px', textAlign: 'center', flexShrink: 0 },
    statValue: { fontSize: '0.85rem', fontWeight: 'bold', color: '#e0e0ff' },
    statLabel: { fontSize: '0.55rem', color: '#666', letterSpacing: '1px', marginTop: '2px' }
};