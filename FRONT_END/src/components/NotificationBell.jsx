import { useState, useEffect, useRef } from 'react';

const API = 'http://localhost:8080/api';

export default function NotificationBell({ user, onNavigate }) {
    const [notifications, setNotifications] = useState([]);
    const [unreadCount, setUnreadCount] = useState(0);
    const [isOpen, setIsOpen] = useState(false);
    const [loading, setLoading] = useState(false);
    const dropdownRef = useRef(null);
    const pollIntervalRef = useRef(null);

    useEffect(() => {
        loadNotifications();
        loadUnreadCount();

        // Poll every 10 seconds for new notifications
        pollIntervalRef.current = setInterval(() => {
            loadUnreadCount();
            if (isOpen) loadNotifications();
        }, 10000);

        // Close dropdown when clicking outside
        const handleClickOutside = (e) => {
            if (dropdownRef.current && !dropdownRef.current.contains(e.target)) {
                setIsOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);

        return () => {
            if (pollIntervalRef.current) clearInterval(pollIntervalRef.current);
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, []);

    const loadNotifications = async () => {
        setLoading(true);
        try {
            const res = await fetch(`${API}/notifications/${user.id}?limit=20`);
            const data = await res.json();
            if (data.success) {
                setNotifications(data.data.notifications);
                setUnreadCount(data.data.unreadCount);
            }
        } catch (err) {
            console.error('Failed to load notifications:', err);
        } finally {
            setLoading(false);
        }
    };

    const loadUnreadCount = async () => {
        try {
            const res = await fetch(`${API}/notifications/${user.id}/unread-count`);
            const data = await res.json();
            if (data.success) {
                setUnreadCount(data.data.unreadCount);
            }
        } catch (err) {
            console.error('Failed to load unread count:', err);
        }
    };

    const markAsRead = async (notificationId) => {
        try {
            await fetch(`${API}/notifications/${notificationId}/read`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ combatantId: user.id })
            });

            setNotifications(prev => prev.map(n =>
                n.id === notificationId ? { ...n, isRead: true } : n
            ));
            setUnreadCount(prev => Math.max(0, prev - 1));
        } catch (err) {
            console.error('Failed to mark as read:', err);
        }
    };

    const markAllAsRead = async () => {
        try {
            await fetch(`${API}/notifications/read-all`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ combatantId: user.id })
            });

            setNotifications(prev => prev.map(n => ({ ...n, isRead: true })));
            setUnreadCount(0);
        } catch (err) {
            console.error('Failed to mark all as read:', err);
        }
    };

    const deleteNotification = async (notificationId, e) => {
        e.stopPropagation();
        try {
            await fetch(`${API}/notifications/${notificationId}?combatantId=${user.id}`, {
                method: 'DELETE'
            });

            setNotifications(prev => prev.filter(n => n.id !== notificationId));
            loadUnreadCount();
        } catch (err) {
            console.error('Failed to delete:', err);
        }
    };

    const handleNotificationClick = (notification) => {
        if (!notification.isRead) {
            markAsRead(notification.id);
        }

        // Navigate based on type
        if (onNavigate && notification.actionUrl) {
            onNavigate(notification.actionUrl);
        } else if (onNavigate) {
            // Default navigation based on type
            const navMap = {
                battle: 'combat',
                achievement: 'dashboard',
                event: 'events',
                reward: 'dashboard',
                craft: 'crafting',
                purchase: 'dashboard'
            };
            const screen = navMap[notification.type];
            if (screen) onNavigate(screen);
        }

        setIsOpen(false);
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

    const getPriorityColor = (priority) => {
        const colors = {
            urgent: '#ff0044',
            high: '#ffaa00',
            normal: '#00f0ff',
            low: '#8888cc'
        };
        return colors[priority] || '#00f0ff';
    };

    return (
        <div ref={dropdownRef} style={styles.container}>
            {/* BELL BUTTON */}
            <button
                onClick={() => setIsOpen(!isOpen)}
                style={styles.bellBtn}
                title="Notifications"
            >
                ALERTS
                {unreadCount > 0 && (
                    <span style={styles.badge}>
                        {unreadCount > 99 ? '99+' : unreadCount}
                    </span>
                )}
            </button>

            {/* DROPDOWN */}
            {isOpen && (
                <div style={styles.dropdown}>
                    {/* HEADER */}
                    <div style={styles.dropdownHeader}>
                        <h3 style={styles.dropdownTitle}>
                            NOTIFICATIONS
                            {unreadCount > 0 && (
                                <span style={styles.headerBadge}>{unreadCount} new</span>
                            )}
                        </h3>
                        {unreadCount > 0 && (
                            <button onClick={markAllAsRead} style={styles.markAllBtn}>
                                Mark all read
                            </button>
                        )}
                    </div>

                    {/* LIST */}
                    <div style={styles.list}>
                        {loading ? (
                            <div style={styles.loading}>Loading...</div>
                        ) : notifications.length === 0 ? (
                            <div style={styles.empty}>
                                <div style={styles.emptyText}>No notifications</div>
                            </div>
                        ) : (
                            notifications.map(notification => (
                                <div
                                    key={notification.id}
                                    onClick={() => handleNotificationClick(notification)}
                                    style={{
                                        ...styles.notification,
                                        background: !notification.isRead
                                            ? 'rgba(0, 240, 255, 0.08)'
                                            : 'rgba(0,0,0,0.2)',
                                        borderLeftColor: getPriorityColor(notification.priority)
                                    }}
                                >
                                    <div style={styles.notifContent}>
                                        <div style={styles.notifHeader}>
                                            <span style={{
                                                ...styles.notifTitle,
                                                color: notification.isRead ? '#8888cc' : '#e0e0ff'
                                            }}>
                                                {notification.title}
                                            </span>
                                            {!notification.isRead && <span style={styles.unreadDot} />}
                                        </div>
                                        <div style={styles.notifMessage}>{notification.message}</div>
                                        <div style={styles.notifTime}>{formatTime(notification.createdAt)}</div>
                                    </div>
                                    <button
                                        onClick={(e) => deleteNotification(notification.id, e)}
                                        style={styles.deleteBtn}
                                        title="Delete"
                                    >
                                        X
                                    </button>
                                </div>
                            ))
                        )}
                    </div>

                    {/* FOOTER */}
                    {notifications.length > 0 && (
                        <div style={styles.dropdownFooter}>
                            Showing last {notifications.length} notifications
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

const styles = {
    container: {
        position: 'relative',
        display: 'inline-block'
    },
    bellBtn: {
        position: 'relative',
        padding: '8px 12px',
        background: 'rgba(0, 240, 255, 0.1)',
        border: '1px solid #00f0ff',
        color: '#00f0ff',
        borderRadius: '6px',
        cursor: 'pointer',
        fontFamily: 'monospace',
        fontSize: '0.7rem',
        fontWeight: 'bold',
        letterSpacing: '1px',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        minWidth: '45px'
    },
    badge: {
        position: 'absolute',
        top: '-6px',
        right: '-6px',
        background: '#ff0044',
        color: 'white',
        fontSize: '0.6rem',
        fontWeight: 'bold',
        padding: '2px 6px',
        borderRadius: '10px',
        minWidth: '18px',
        textAlign: 'center',
        boxShadow: '0 0 10px rgba(255, 0, 68, 0.6)'
    },
    dropdown: {
        position: 'absolute',
        top: 'calc(100% + 8px)',
        right: 0,
        width: '380px',
        maxWidth: '90vw',
        background: '#0a0a1a',
        border: '1px solid #00f0ff',
        borderRadius: '12px',
        boxShadow: '0 0 40px rgba(0, 240, 255, 0.3)',
        zIndex: 1000,
        fontFamily: 'monospace',
        color: '#e0e0ff',
        overflow: 'hidden'
    },
    dropdownHeader: {
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        padding: '12px 15px',
        background: 'rgba(0, 240, 255, 0.05)',
        borderBottom: '1px solid rgba(0, 240, 255, 0.15)'
    },
    dropdownTitle: {
        fontSize: '0.75rem',
        color: '#00f0ff',
        margin: 0,
        letterSpacing: '2px',
        display: 'flex',
        alignItems: 'center',
        gap: '8px'
    },
    headerBadge: {
        background: 'rgba(255, 0, 68, 0.2)',
        color: '#ff0044',
        padding: '2px 8px',
        borderRadius: '10px',
        fontSize: '0.6rem',
        fontWeight: 'bold'
    },
    markAllBtn: {
        padding: '4px 10px',
        background: 'rgba(0, 255, 136, 0.1)',
        border: '1px solid #00ff88',
        color: '#00ff88',
        borderRadius: '4px',
        cursor: 'pointer',
        fontSize: '0.6rem',
        fontFamily: 'monospace',
        fontWeight: 'bold'
    },
    list: {
        maxHeight: '400px',
        overflowY: 'auto'
    },
    loading: {
        textAlign: 'center',
        color: '#8888cc',
        padding: '30px',
        fontSize: '0.75rem'
    },
    empty: {
        textAlign: 'center',
        padding: '40px 20px'
    },
    emptyText: {
        color: '#8888cc',
        fontSize: '0.75rem'
    },
    notification: {
        display: 'flex',
        gap: '10px',
        padding: '12px 15px',
        borderBottom: '1px solid rgba(0, 240, 255, 0.08)',
        borderLeft: '3px solid',
        cursor: 'pointer',
        transition: 'all 0.2s',
        position: 'relative'
    },
    notifContent: {
        flex: 1,
        minWidth: 0
    },
    notifHeader: {
        display: 'flex',
        alignItems: 'center',
        gap: '6px',
        marginBottom: '4px'
    },
    notifTitle: {
        fontSize: '0.75rem',
        fontWeight: 'bold'
    },
    unreadDot: {
        width: '6px',
        height: '6px',
        borderRadius: '50%',
        background: '#ff0044',
        boxShadow: '0 0 6px #ff0044'
    },
    notifMessage: {
        fontSize: '0.7rem',
        color: '#8888cc',
        lineHeight: '1.4',
        marginBottom: '4px'
    },
    notifTime: {
        fontSize: '0.6rem',
        color: '#666'
    },
    deleteBtn: {
        background: 'transparent',
        border: 'none',
        color: '#ff0044',
        cursor: 'pointer',
        fontSize: '0.75rem',
        fontWeight: 'bold',
        padding: '4px',
        opacity: 0.6,
        flexShrink: 0
    },
    dropdownFooter: {
        padding: '8px 15px',
        textAlign: 'center',
        fontSize: '0.6rem',
        color: '#666',
        background: 'rgba(0,0,0,0.3)',
        borderTop: '1px solid rgba(0, 240, 255, 0.1)'
    }
};