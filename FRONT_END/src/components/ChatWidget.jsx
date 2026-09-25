import { useState, useEffect, useRef } from 'react';

const API = 'http://localhost:8080/api';

export default function ChatWidget({ user }) {
    const [channel, setChannel] = useState('global');
    const [messages, setMessages] = useState([]);
    const [input, setInput] = useState('');
    const [loading, setLoading] = useState(true);
    const [sending, setSending] = useState(false);
    const [error, setError] = useState('');
    const messagesEndRef = useRef(null);
    const pollIntervalRef = useRef(null);
    const shouldScrollRef = useRef(true);  // ← NEW: Controls auto-scroll

    useEffect(() => {
        shouldScrollRef.current = true;  // Scroll on channel change
        loadMessages();

        // Poll every 5 seconds
        pollIntervalRef.current = setInterval(() => {
            loadMessages(true);
        }, 5000);

        return () => {
            if (pollIntervalRef.current) {
                clearInterval(pollIntervalRef.current);
            }
        };
    }, [channel]);

    useEffect(() => {
        // Only scroll if flag is set
        if (shouldScrollRef.current) {
            scrollToBottom();
            shouldScrollRef.current = false;  // Reset after scrolling
        }
    }, [messages]);

    const scrollToBottom = () => {
        if (messagesEndRef.current) {
            messagesEndRef.current.scrollIntoView({ behavior: 'smooth' });
        }
    };

    const loadMessages = async (silent = false) => {
        if (!silent) setLoading(true);

        try {
            const res = await fetch(
                `${API}/chat/messages?channel=${channel}&limit=50&combatantId=${user.id}`
            );
            const data = await res.json();

            if (data.success) {
                setMessages(data.data.messages);
                setError('');
            } else {
                if (!silent) setError(data.message);
            }
        } catch (err) {
            if (!silent) setError('Cannot load messages');
        } finally {
            if (!silent) setLoading(false);
        }
    };

    const sendMessage = async (e) => {
        e.preventDefault();

        const trimmed = input.trim();
        if (!trimmed || sending) return;

        setSending(true);
        setError('');

        try {
            const res = await fetch(`${API}/chat/send`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    combatantId: user.id,
                    channel,
                    message: trimmed
                })
            });
            const data = await res.json();

            if (data.success) {
                setInput('');
                shouldScrollRef.current = true;  // ← Scroll after sending
                loadMessages(true);
            } else {
                setError(data.message);
                setTimeout(() => setError(''), 3000);
            }
        } catch (err) {
            setError('Cannot send message');
            setTimeout(() => setError(''), 3000);
        } finally {
            setSending(false);
        }
    };

    const getRoleColor = (role) => {
        const colors = {
            civilian: '#ffaa00',
            hero: '#00f0ff',
            villain: '#ff0044',
            admin: '#ff00ff'
        };
        return colors[role] || '#8888cc';
    };

    const getRoleIcon = (role) => {
        const icons = {
            civilian: '',
            hero: '',
            villain: '',
            admin: ''
        };
        return icons[role] || '';
    };

    const getChannelInfo = () => {
        const info = {
            global: { icon: '', name: 'Global', color: '#7aa2ff' },
            faction: { icon: '', name: user.faction === 'hero' ? 'Heroes' : 'Villains', color: user.faction === 'hero' ? '#7aa2ff' : '#b0b4c6' },
            trade: { icon: '', name: 'Trade', color: '#a6afc4' }
        };
        return info[channel];
    };

    const formatTime = (timestamp) => {
        const date = new Date(timestamp);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);

        if (diffMins < 1) return 'now';
        if (diffMins < 60) return `${diffMins}m ago`;
        if (diffMins < 1440) return `${Math.floor(diffMins / 60)}h ago`;
        return `${Math.floor(diffMins / 1440)}d ago`;
    };

    const channelInfo = getChannelInfo();

    return (
        <div style={styles.widget}>
            {/* HEADER */}
            <div style={styles.header}>
                <h3 style={styles.title}>CHAT</h3>
                <div style={styles.onlineIndicator}>
                    <span style={styles.onlineDot}>●</span>
                    <span style={styles.onlineText}>LIVE</span>
                </div>
            </div>

            {/* CHANNEL TABS */}
            <div style={styles.channelTabs}>
                <button
                    onClick={() => setChannel('global')}
                    style={{ ...styles.channelTab, ...(channel === 'global' ? styles.channelTabActive : {}) }}
                >
                     GLOBAL
                </button>
                <button
                    onClick={() => setChannel('faction')}
                    style={{ ...styles.channelTab, ...(channel === 'faction' ? styles.channelTabActive : {}) }}
                >
                     FACTION
                </button>
                <button
                    onClick={() => setChannel('trade')}
                    style={{ ...styles.channelTab, ...(channel === 'trade' ? styles.channelTabActive : {}) }}
                >
                     TRADE
                </button>
            </div>

            {/* CHANNEL INFO */}
            <div style={{ ...styles.channelInfo, borderColor: channelInfo.color }}>
                <span style={{ color: channelInfo.color }}>
                    #{channelInfo.name}
                </span>
            </div>

            {/* ERROR */}
            {error && <div style={styles.errorBox}>{error}</div>}

            {/* MESSAGES */}
            <div style={styles.messagesContainer}>
                {loading ? (
                    <div style={styles.loading}>Loading messages...</div>
                ) : messages.length === 0 ? (
                    <div style={styles.loading}>
                        No messages yet. Be the first to say something!
                    </div>
                ) : (
                    messages.map(msg => {
                        const isOwn = msg.combatantId === user.id;
                        const roleColor = getRoleColor(msg.senderRole);

                        return (
                            <div
                                key={msg.id}
                                style={{
                                    ...styles.message,
                                    ...(isOwn ? styles.ownMessage : {})
                                }}
                            >
                                <div style={styles.messageHeader}>
                                    <span style={styles.senderIcon}>
                                        {getRoleIcon(msg.senderRole)}
                                    </span>
                                    <span style={{ ...styles.senderName, color: roleColor }}>
                                        {msg.senderName}
                                    </span>
                                    {isOwn && <span style={styles.youBadge}>(you)</span>}
                                    <span style={styles.timestamp}>
                                        {formatTime(msg.createdAt)}
                                    </span>
                                </div>
                                <div style={styles.messageText}>
                                    {msg.message}
                                </div>
                            </div>
                        );
                    })
                )}
                <div ref={messagesEndRef} />
            </div>

            {/* INPUT */}
            <form onSubmit={sendMessage} style={styles.inputForm}>
                <input
                    type="text"
                    value={input}
                    onChange={(e) => setInput(e.target.value)}
                    placeholder={`Message #${channelInfo.name.toLowerCase()}...`}
                    maxLength={500}
                    style={styles.input}
                    disabled={sending}
                />
                <button
                    type="submit"
                    disabled={!input.trim() || sending}
                    style={{
                        ...styles.sendBtn,
                        opacity: (!input.trim() || sending) ? 0.5 : 1,
                        cursor: (!input.trim() || sending) ? 'not-allowed' : 'pointer'
                    }}
                >
                    {sending ? 'Sending...' : 'Send'}
                </button>
            </form>

            {/* CHAR COUNT */}
            <div style={styles.charCount}>
                {input.length}/500
            </div>
        </div>
    );
}

const styles = {
    widget: {
        display: 'flex',
        flexDirection: 'column',
        height: '100%',
        fontFamily: 'monospace',
        color: '#e0e0ff'
    },
    header: {
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: '12px'
    },
    title: {
        color: '#dfe6f3',
        fontSize: '0.9rem',
        margin: 0,
        letterSpacing: '2px'
    },
    onlineIndicator: {
        display: 'flex',
        alignItems: 'center',
        gap: '4px',
        fontSize: '0.65rem'
    },
    onlineDot: {
        color: '#7aa2ff',
        fontSize: '0.8rem'
    },
    onlineText: {
        color: '#a3b3d9',
        letterSpacing: '1px',
        fontWeight: 'bold'
    },
    channelTabs: {
        display: 'flex',
        gap: '4px',
        marginBottom: '10px'
    },
    channelTab: {
        flex: 1,
        padding: '8px 4px',
        background: 'rgba(0,0,0,0.3)',
        border: '1px solid rgba(164, 174, 201, 0.2)',
        color: '#a4acc8',
        cursor: 'pointer',
        borderRadius: '6px',
        fontFamily: 'monospace',
        fontSize: '0.6rem',
        fontWeight: 'bold',
        letterSpacing: '1px'
    },
    channelTabActive: {
        background: 'rgba(122, 162, 255, 0.12)',
        borderColor: '#7aa2ff',
        color: '#dfe6f3'
    },
    channelInfo: {
        padding: '6px 10px',
        background: 'rgba(0,0,0,0.3)',
        border: '1px solid',
        borderRadius: '6px',
        fontSize: '0.7rem',
        marginBottom: '10px',
        fontWeight: 'bold'
    },
    errorBox: {
        padding: '8px',
        background: 'rgba(180, 190, 210, 0.08)',
        border: '1px solid #9aa4bd',
        color: '#dfe6f3',
        borderRadius: '6px',
        fontSize: '0.7rem',
        marginBottom: '8px',
        textAlign: 'center'
    },
    messagesContainer: {
        flex: 1,
        overflowY: 'auto',
        padding: '10px',
        background: 'rgba(0,0,0,0.3)',
        borderRadius: '8px',
        marginBottom: '10px',
        maxHeight: '400px',
        minHeight: '300px'
    },
    loading: {
        textAlign: 'center',
        color: '#8888cc',
        fontSize: '0.75rem',
        padding: '30px 10px'
    },
    message: {
        padding: '8px 10px',
        marginBottom: '8px',
        background: 'rgba(0,0,0,0.3)',
        borderRadius: '6px',
        borderLeft: '2px solid rgba(0,240,255,0.3)'
    },
    ownMessage: {
        background: 'rgba(122,162,255,0.08)',
        borderLeft: '2px solid #7aa2ff'
    },
    messageHeader: {
        display: 'flex',
        alignItems: 'center',
        gap: '6px',
        marginBottom: '4px',
        fontSize: '0.7rem'
    },
    senderIcon: {
        fontSize: '0.85rem'
    },
    senderName: {
        fontWeight: 'bold',
        fontSize: '0.75rem'
    },
    youBadge: {
        fontSize: '0.6rem',
        color: '#ffaa00',
        fontStyle: 'italic'
    },
    timestamp: {
        marginLeft: 'auto',
        fontSize: '0.6rem',
        color: '#666'
    },
    messageText: {
        fontSize: '0.75rem',
        color: '#e0e0ff',
        wordBreak: 'break-word',
        lineHeight: '1.4'
    },
    inputForm: {
        display: 'flex',
        gap: '8px'
    },
    input: {
        flex: 1,
        padding: '10px 12px',
        background: 'rgba(0,0,0,0.4)',
        border: '1px solid rgba(0, 240, 255, 0.2)',
        borderRadius: '6px',
        color: '#e0e0ff',
        fontFamily: 'monospace',
        fontSize: '0.75rem'
    },
    sendBtn: {
        padding: '10px 16px',
        background: '#7aa2ff',
        border: 'none',
        borderRadius: '6px',
        cursor: 'pointer',
        fontSize: '0.75rem',
        fontWeight: 'bold',
        letterSpacing: '1px',
        color: '#0e172a'
    },
    charCount: {
        textAlign: 'right',
        fontSize: '0.6rem',
        color: '#666',
        marginTop: '4px'
    }
};