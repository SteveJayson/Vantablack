import { useState, useEffect } from 'react';

const API = 'http://localhost:8080/api';

export default function Events({ user, onBack, onEventAction }) {
    const [events, setEvents] = useState([]);
    const [activeTab, setActiveTab] = useState('active');
    const [selectedEvent, setSelectedEvent] = useState(null);
    const [loading, setLoading] = useState(true);
    const [actionLoading, setActionLoading] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');

    useEffect(() => {
        loadEvents();
    }, [activeTab]);

    const loadEvents = async () => {
        setLoading(true);
        setError('');
        try {
            const res = await fetch(`${API}/events?combatantId=${user.id}&status=${activeTab}`);
            const data = await res.json();

            if (data.success) {
                setEvents(data.data.events);
            } else {
                setError(data.message);
            }
        } catch (err) {
            setError('Cannot load events');
        } finally {
            setLoading(false);
        }
    };

    const loadEventDetails = async (eventId) => {
        try {
            const res = await fetch(`${API}/events/${eventId}?combatantId=${user.id}`);
            const data = await res.json();
            if (data.success) {
                setSelectedEvent(data.data.event);
            }
        } catch (err) {
            console.error('Failed to load event details:', err);
        }
    };

    const joinEvent = async (eventId) => {
        setActionLoading(true);
        setError('');
        setSuccess('');

        try {
            const res = await fetch(`${API}/events/join`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ combatantId: user.id, eventId })
            });
            const data = await res.json();

            if (data.success) {
                setSuccess(`${data.message}`);
                if (onEventAction) onEventAction(data.data.newBalance);
                loadEvents();
                if (selectedEvent?.id === eventId) loadEventDetails(eventId);
            } else {
                setError(data.message);
            }
        } catch (err) {
            setError('Cannot join event');
        } finally {
            setActionLoading(false);
        }
    };

    const claimRewards = async (eventId) => {
        setActionLoading(true);
        setError('');
        setSuccess('');

        try {
            const res = await fetch(`${API}/events/claim-rewards`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ combatantId: user.id, eventId })
            });
            const data = await res.json();

            if (data.success) {
                const msgs = data.data.messages.join(' • ');
                setSuccess(`${msgs}`);
                if (onEventAction) onEventAction(data.data.newBalance);
                loadEvents();
                loadEventDetails(eventId);
            } else {
                setError(data.message);
            }
        } catch (err) {
            setError('Cannot claim rewards');
        } finally {
            setActionLoading(false);
        }
    };

    const getEventTypeColor = (type) => {
        const colors = {
            holiday: '#ff00ff',
            tournament: '#ffaa00',
            special: '#00f0ff',
            weekly: '#00ff88'
        };
        return colors[type] || '#00f0ff';
    };

    const getTimeRemaining = (endDate) => {
        const now = new Date();
        const end = new Date(endDate);
        const diffMs = end - now;

        if (diffMs < 0) return 'Ended';

        const days = Math.floor(diffMs / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diffMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const mins = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));

        if (days > 0) return `${days}d ${hours}h left`;
        if (hours > 0) return `${hours}h ${mins}m left`;
        return `${mins}m left`;
    };

    const getStartsIn = (startDate) => {
        const now = new Date();
        const start = new Date(startDate);
        const diffMs = start - now;

        const days = Math.floor(diffMs / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diffMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));

        if (days > 0) return `Starts in ${days}d ${hours}h`;
        return `Starts in ${hours}h`;
    };

    return (
        <div style={styles.container}>
            {/* HEADER */}
            <div style={styles.header}>
                <button onClick={onBack} style={styles.backBtn}>Back</button>
                <h1 style={styles.title}>EVENTS & TOURNAMENTS</h1>
                <div style={styles.userBadge}>
                    <span>₵{user.credits?.toLocaleString()}</span>
                </div>
            </div>

            {/* TABS */}
            <div style={styles.tabs}>
                <button
                    onClick={() => setActiveTab('active')}
                    style={{ ...styles.tab, ...(activeTab === 'active' ? styles.tabActive : {}) }}
                >
                    ACTIVE
                </button>
                <button
                    onClick={() => setActiveTab('upcoming')}
                    style={{ ...styles.tab, ...(activeTab === 'upcoming' ? styles.tabActive : {}) }}
                >
                    UPCOMING
                </button>
                <button
                    onClick={() => setActiveTab('ended')}
                    style={{ ...styles.tab, ...(activeTab === 'ended' ? styles.tabActive : {}) }}
                >
                    ENDED
                </button>
            </div>

            {error && <div style={styles.errorBox}>{error}</div>}
            {success && <div style={styles.successBox}>{success}</div>}

            <div style={styles.grid}>
                {/* EVENTS LIST */}
                <div style={styles.panel}>
                    <h2 style={styles.panelTitle}>{activeTab.toUpperCase()} EVENTS</h2>

                    {loading ? (
                        <div style={styles.loading}>Loading events...</div>
                    ) : events.length === 0 ? (
                        <div style={styles.loading}>No {activeTab} events</div>
                    ) : (
                        <div style={styles.eventList}>
                            {events.map(event => (
                                <div
                                    key={event.id}
                                    onClick={() => loadEventDetails(event.id)}
                                    style={{
                                        ...styles.eventCard,
                                        borderColor: selectedEvent?.id === event.id
                                            ? getEventTypeColor(event.eventType)
                                            : 'rgba(0,240,255,0.15)',
                                        background: selectedEvent?.id === event.id
                                            ? 'rgba(0,240,255,0.1)'
                                            : 'rgba(0,0,0,0.3)'
                                    }}
                                >
                                    <div style={styles.eventHeader}>
                                        <span style={styles.eventIcon}>{event.icon}</span>
                                        <div style={styles.eventInfo}>
                                            <div style={styles.eventName}>{event.name}</div>
                                            <div style={{
                                                ...styles.eventType,
                                                background: getEventTypeColor(event.eventType)
                                            }}>
                                                {event.eventType.toUpperCase()}
                                            </div>
                                        </div>
                                        <div style={styles.eventStatus}>
                                            {event.currentStatus === 'active' && (
                                                <div style={styles.timeRemaining}>
                                                    ⏰ {getTimeRemaining(event.endDate)}
                                                </div>
                                            )}
                                            {event.currentStatus === 'upcoming' && (
                                                <div style={styles.startsIn}>
                                                    {getStartsIn(event.startDate)}
                                                </div>
                                            )}
                                            {event.currentStatus === 'ended' && (
                                                <div style={styles.endedBadge}>ENDED</div>
                                            )}
                                        </div>
                                    </div>

                                    <div style={styles.eventMeta}>
                                        <span>{event.participantCount} joined</span>
                                        {event.entryFee > 0 && <span>Entry: ₵{event.entryFee}</span>}
                                        {event.entryFee === 0 && <span style={{ color: '#d1d5db' }}>FREE</span>}
                                    </div>

                                    {event.myParticipation && (
                                        <div style={styles.myParticipation}>
                                            You joined! Score: {event.myParticipation.score}
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* EVENT DETAILS */}
                <div style={styles.panel}>
                    <h2 style={styles.panelTitle}>EVENT DETAILS</h2>

                    {!selectedEvent ? (
                        <div style={styles.loading}>Select an event</div>
                    ) : (
                        <div>
                            <div style={styles.detailHeader}>
                                <span style={styles.detailIcon}>{selectedEvent.icon}</span>
                                <div>
                                    <h3 style={styles.detailName}>{selectedEvent.name}</h3>
                                    <div style={{
                                        ...styles.eventType,
                                        background: getEventTypeColor(selectedEvent.eventType),
                                        display: 'inline-block'
                                    }}>
                                        {selectedEvent.eventType.toUpperCase()}
                                    </div>
                                </div>
                            </div>

                            <div style={styles.detailDesc}>{selectedEvent.description}</div>

                            <div style={styles.detailStats}>
                                <div style={styles.detailStat}>
                                    <div style={styles.statLabel}>STATUS</div>
                                    <div style={styles.statValue}>
                                        {selectedEvent.currentStatus.toUpperCase()}
                                    </div>
                                </div>
                                <div style={styles.detailStat}>
                                    <div style={styles.statLabel}>PARTICIPANTS</div>
                                    <div style={styles.statValue}>
                                        {selectedEvent.participantCount}
                                        {selectedEvent.maxParticipants > 0 && ` / ${selectedEvent.maxParticipants}`}
                                    </div>
                                </div>
                                <div style={styles.detailStat}>
                                    <div style={styles.statLabel}>ENTRY FEE</div>
                                    <div style={{ ...styles.statValue, color: '#00ff88' }}>
                                        {selectedEvent.entryFee > 0 ? `₵${selectedEvent.entryFee}` : 'FREE'}
                                    </div>
                                </div>
                            </div>

                            {/* Time info */}
                            <div style={styles.timeInfo}>
                                {selectedEvent.currentStatus === 'active' && (
                                    <div style={{ color: '#ffaa00' }}>
                                        {getTimeRemaining(selectedEvent.endDate)}
                                    </div>
                                )}
                                {selectedEvent.currentStatus === 'upcoming' && (
                                    <div style={{ color: '#00f0ff' }}>
                                        {getStartsIn(selectedEvent.startDate)}
                                    </div>
                                )}
                            </div>

                            {/* Reward pool */}
                            <h4 style={styles.subTitle}>REWARD POOL</h4>
                            <div style={styles.rewardPool}>
                                {Object.entries(selectedEvent.rewardPool).map(([place, reward], i) => (
                                    <div key={i} style={styles.rewardRow}>
                                        <span style={styles.rewardPlace}>{place.replace('_', ' ').toUpperCase()}</span>
                                        <span style={styles.rewardDetails}>
                                            {reward.credits && `₵${reward.credits.toLocaleString()}`}
                                            {reward.gear && ` GEAR`}
                                        </span>
                                    </div>
                                ))}
                            </div>

                            {/* My participation */}
                            {selectedEvent.myParticipation && (
                                <div style={styles.myStats}>
                                    <h4 style={styles.subTitle}>YOUR STATS</h4>
                                    <div style={styles.myStatsRow}>
                                        <span>Score: <strong>{selectedEvent.myParticipation.score}</strong></span>
                                        {selectedEvent.myParticipation.rank && (
                                            <span>Rank: <strong>#{selectedEvent.myParticipation.rank}</strong></span>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Actions */}
                            <div style={styles.actions}>
                                {selectedEvent.currentStatus === 'active' && !selectedEvent.myParticipation && (
                                    <button
                                        onClick={() => joinEvent(selectedEvent.id)}
                                        disabled={actionLoading}
                                        style={styles.joinBtn}
                                    >
                                        {actionLoading ? 'JOINING...' : `JOIN EVENT${selectedEvent.entryFee > 0 ? ` (₵${selectedEvent.entryFee})` : ''}` }
                                    </button>
                                )}
                                {selectedEvent.currentStatus === 'active' && selectedEvent.myParticipation && (
                                    <div style={styles.joinedMsg}>
                                        You're participating! Battle to earn points.
                                    </div>
                                )}
                                {selectedEvent.currentStatus === 'ended' &&
                                    selectedEvent.myParticipation &&
                                    !selectedEvent.myParticipation.rewardsClaimed && (
                                        <button
                                            onClick={() => claimRewards(selectedEvent.id)}
                                            disabled={actionLoading}
                                            style={styles.claimBtn}
                                        >
                                            {actionLoading ? 'CLAIMING...' : 'CLAIM REWARDS'}
                                        </button>
                                    )}
                                {selectedEvent.currentStatus === 'ended' &&
                                    selectedEvent.myParticipation?.rewardsClaimed && (
                                        <div style={styles.claimedMsg}>Rewards claimed!</div>
                                    )}
                            </div>

                            {/* Leaderboard */}
                            {selectedEvent.leaderboard && selectedEvent.leaderboard.length > 0 && (
                                <>
                                    <h4 style={styles.subTitle}>LEADERBOARD</h4>
                                    <div style={styles.leaderboard}>
                                        {selectedEvent.leaderboard.slice(0, 10).map((entry, i) => (
                                            <div key={i} style={styles.leaderboardRow}>
                                                <span style={styles.rankNum}>
                                                    {i === 0 ? '1' : i === 1 ? '2' : i === 2 ? '3' : `#${i + 1}`}
                                                </span>
                                                <span style={styles.playerName}>{entry.name}</span>
                                                <span style={styles.playerScore}>{entry.score} pts</span>
                                            </div>
                                        ))}
                                    </div>
                                </>
                            )}
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
    userBadge: { padding: '8px 16px', background: 'rgba(0,255,136,0.1)', border: '1px solid #00ff88', color: '#00ff88', borderRadius: '6px', fontWeight: 'bold' },
    tabs: { display: 'flex', gap: '8px', marginBottom: '20px' },
    tab: { flex: 1, padding: '12px', background: 'rgba(0,0,0,0.3)', border: '1px solid rgba(0,240,255,0.2)', color: '#8888cc', cursor: 'pointer', borderRadius: '8px', fontFamily: 'monospace', fontWeight: 'bold', letterSpacing: '2px' },
    tabActive: { background: 'rgba(0,240,255,0.15)', borderColor: '#00f0ff', color: '#00f0ff' },
    errorBox: { padding: '12px', background: 'rgba(255,0,68,0.1)', border: '1px solid #ff0044', color: '#ff0044', borderRadius: '8px', marginBottom: '15px', fontSize: '0.85rem' },
    successBox: { padding: '12px', background: 'rgba(0,255,136,0.1)', border: '1px solid #00ff88', color: '#00ff88', borderRadius: '8px', marginBottom: '15px', fontSize: '0.85rem' },
    grid: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px' },
    panel: { background: 'rgba(10, 10, 30, 0.85)', border: '1px solid rgba(0,240,255,0.15)', borderRadius: '12px', padding: '20px', maxHeight: '80vh', overflowY: 'auto' },
    panelTitle: { color: '#00f0ff', fontSize: '0.85rem', marginBottom: '15px', letterSpacing: '2px', marginTop: 0, borderBottom: '1px solid rgba(0,240,255,0.2)', paddingBottom: '10px' },
    loading: { textAlign: 'center', color: '#8888cc', fontSize: '0.85rem', padding: '40px' },
    eventList: { display: 'flex', flexDirection: 'column', gap: '12px' },
    eventCard: { padding: '15px', border: '2px solid', borderRadius: '10px', cursor: 'pointer', transition: 'all 0.3s' },
    eventHeader: { display: 'flex', alignItems: 'center', gap: '12px', marginBottom: '10px' },
    eventIcon: { fontSize: '2rem' },
    eventInfo: { flex: 1 },
    eventName: { fontWeight: 'bold', fontSize: '0.95rem', color: '#e0e0ff', marginBottom: '4px' },
    eventType: { display: 'inline-block', padding: '2px 8px', borderRadius: '8px', fontSize: '0.6rem', fontWeight: 'bold', color: '#0a0a1a' },
    eventStatus: { textAlign: 'right' },
    timeRemaining: { fontSize: '0.7rem', color: '#ffaa00', fontWeight: 'bold' },
    startsIn: { fontSize: '0.7rem', color: '#00f0ff', fontWeight: 'bold' },
    endedBadge: { fontSize: '0.7rem', color: '#8888cc', fontWeight: 'bold' },
    eventMeta: { display: 'flex', gap: '15px', fontSize: '0.7rem', color: '#8888cc' },
    myParticipation: { marginTop: '8px', padding: '6px 10px', background: 'rgba(0,240,255,0.15)', borderRadius: '6px', fontSize: '0.7rem', color: '#00f0ff', fontWeight: 'bold' },
    detailHeader: { display: 'flex', alignItems: 'center', gap: '15px', marginBottom: '15px' },
    detailIcon: { fontSize: '3rem' },
    detailName: { color: '#00f0ff', fontSize: '1.1rem', marginBottom: '6px' },
    detailDesc: { fontSize: '0.75rem', color: '#8888cc', marginBottom: '15px', lineHeight: '1.5' },
    detailStats: { display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '10px', marginBottom: '15px' },
    detailStat: { padding: '10px', background: 'rgba(0,0,0,0.3)', borderRadius: '6px', textAlign: 'center' },
    statLabel: { fontSize: '0.6rem', color: '#8888cc', marginBottom: '4px' },
    statValue: { fontSize: '0.9rem', fontWeight: 'bold', color: '#00f0ff' },
    timeInfo: { textAlign: 'center', fontSize: '0.85rem', fontWeight: 'bold', marginBottom: '15px' },
    subTitle: { color: '#00f0ff', fontSize: '0.75rem', marginTop: '15px', marginBottom: '10px', letterSpacing: '2px' },
    rewardPool: { background: 'rgba(255,170,0,0.05)', border: '1px solid rgba(255,170,0,0.2)', borderRadius: '8px', padding: '12px', marginBottom: '15px' },
    rewardRow: { display: 'flex', justifyContent: 'space-between', padding: '6px 0', fontSize: '0.75rem', borderBottom: '1px solid rgba(255,170,0,0.1)' },
    rewardPlace: { color: '#ffaa00', fontWeight: 'bold' },
    rewardDetails: { color: '#e0e0ff' },
    myStats: { background: 'rgba(0,240,255,0.05)', border: '1px solid rgba(0,240,255,0.2)', borderRadius: '8px', padding: '12px', marginBottom: '15px' },
    myStatsRow: { display: 'flex', gap: '20px', fontSize: '0.85rem', color: '#e0e0ff' },
    actions: { marginTop: '15px' },
    joinBtn: { width: '100%', padding: '14px', background: 'linear-gradient(90deg, #00f0ff, #ff00ff)', color: '#0a0a1a', border: 'none', borderRadius: '8px', fontSize: '0.85rem', fontWeight: 'bold', cursor: 'pointer', fontFamily: 'monospace', letterSpacing: '2px' },
    claimBtn: { width: '100%', padding: '14px', background: 'linear-gradient(90deg, #00ff88, #00f0ff)', color: '#0a0a1a', border: 'none', borderRadius: '8px', fontSize: '0.85rem', fontWeight: 'bold', cursor: 'pointer', fontFamily: 'monospace', letterSpacing: '2px' },
    joinedMsg: { padding: '12px', background: 'rgba(0,240,255,0.1)', border: '1px solid #00f0ff', color: '#00f0ff', borderRadius: '8px', textAlign: 'center', fontSize: '0.8rem' },
    claimedMsg: { padding: '12px', background: 'rgba(0,255,136,0.1)', border: '1px solid #00ff88', color: '#00ff88', borderRadius: '8px', textAlign: 'center', fontSize: '0.8rem' },
    leaderboard: { background: 'rgba(0,0,0,0.3)', borderRadius: '8px', padding: '10px' },
    leaderboardRow: { display: 'flex', alignItems: 'center', gap: '10px', padding: '8px 0', fontSize: '0.8rem', borderBottom: '1px solid rgba(0,240,255,0.05)' },
    rankNum: { width: '30px', fontSize: '0.9rem' },
    playerName: { flex: 1, color: '#e0e0ff' },
    playerScore: { color: '#00ff88', fontWeight: 'bold' }
};