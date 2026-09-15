import { useState, useEffect } from 'react';
import WeatherWidget from '../components/WeatherWidget';
import CurrencyWidget from '../components/CurrencyWidget';
import DailyRewardModal from '../components/DailyRewardModal';
import ChatWidget from '../components/ChatWidget';
import NotificationBell from '../components/NotificationBell';
import AdminCharts from '../components/AdminCharts';

const API = 'http://localhost:8080/api';

export default function Dashboard({ user: initialUser, onLogout, onShowCombat, onShowCrafting, onShowFactions, onShowEvents, onShowLeaderboards, onShowReplays }) {
    const [user, setUser] = useState(initialUser);
    const [catalog, setCatalog] = useState([]);
    const [inventory, setInventory] = useState([]);
    const [tab, setTab] = useState('armory');
    const [adminData, setAdminData] = useState(null);
    const [alert, setAlert] = useState(null);
    const [showWeather, setShowWeather] = useState(true);
    const [showCurrency, setShowCurrency] = useState(true);
    const [showChat, setShowChat] = useState(false);
    const [showDailyReward, setShowDailyReward] = useState(false);

    const role = user?.role || 'hero';
    const canAct = role === 'hero' || role === 'villain';

    useEffect(() => {
        syncUser();
        loadData();
        checkDailyReward();
    }, [tab]);

    const syncUser = async () => {
        try {
            const token = localStorage.getItem('aegis_token');
            const res = await fetch(`${API}/auth/me`, {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const data = await res.json();
            if (data.success) {
                setUser(data.data.user);
                localStorage.setItem('aegis_user', JSON.stringify(data.data.user));
            }
        } catch (err) {
            console.error('Sync failed:', err);
        }
    };

    const checkDailyReward = async () => {
        try {
            const res = await fetch(`${API}/rewards/daily-status/${initialUser.id}`);
            const data = await res.json();
            if (data.success && data.data.canClaim) {
                setTimeout(() => setShowDailyReward(true), 1500);
            }
        } catch (err) {
            console.error('Daily check failed:', err);
        }
    };

    const loadData = async () => {
        if (role === 'admin') {
            loadAdminDashboard();
            return;
        }

        if (tab === 'inventory') {
            const res = await fetch(`${API}/marketplace/inventory/${user.id}`);
            const data = await res.json();
            setInventory(data.data?.inventory || []);
        } else {
            const res = await fetch(`${API}/gear`);
            const data = await res.json();
            let items = data.data?.catalog || [];
            items = items.filter(i => i.source === tab);
            setCatalog(items);
        }
    };

    const loadAdminDashboard = async () => {
        try {
            const res = await fetch(`${API}/admin/dashboard?adminId=${user.id}`);
            const data = await res.json();
            if (data.success) setAdminData(data.data);
        } catch (error) {
            console.error('Failed to load admin dashboard:', error);
        }
    };

    const purchase = async (gearId) => {
        const res = await fetch(`${API}/marketplace/purchase`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ combatantId: user.id, gearId })
        });
        const data = await res.json();

        if (data.success) {
            const updatedUser = { ...user, credits: data.data.newBalance };
            setUser(updatedUser);
            localStorage.setItem('aegis_user', JSON.stringify(updatedUser));
            showAlert('success', `✅ Purchased! Balance: ₵${data.data.newBalance.toLocaleString()}`);
            loadData();
        } else {
            showAlert('error', `❌ ${data.message}`);
        }
    };

    const sell = async (inventoryId) => {
        const res = await fetch(`${API}/marketplace/sell`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ combatantId: user.id, inventoryId })
        });
        const data = await res.json();

        if (data.success) {
            const updatedUser = { ...user, credits: data.data.newBalance };
            setUser(updatedUser);
            localStorage.setItem('aegis_user', JSON.stringify(updatedUser));
            showAlert('success', `✅ Sold for ₵${data.data.sellPrice.toLocaleString()}`);
            loadData();
        } else {
            showAlert('error', `❌ ${data.message}`);
        }
    };

    const generateBots = async (roleType) => {
        try {
            const res = await fetch(`${API}/external/generate-bots`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ count: 3, role: roleType })
            });
            const data = await res.json();

            if (data.success) {
                showAlert('success', `✅ Generated ${data.data.count} ${roleType}s!`);
                loadAdminDashboard();
            } else {
                showAlert('error', `❌ ${data.message}`);
            }
        } catch (error) {
            showAlert('error', `❌ Error: ${error.message}`);
        }
    };

    const showAlert = (type, msg) => {
        setAlert({ type, msg });
        setTimeout(() => setAlert(null), 4000);
    };

    const canBuy = role !== 'admin';
    const canSell = role === 'hero' || role === 'villain';

    return (
        <div style={styles.container}>
            {/* ============================================
                HEADER - REORGANIZED
            ============================================ */}
            <div style={styles.header}>
                {/* LEFT: Logo */}
                <div style={styles.logoSection}>
                    <h1 style={styles.logoTitle}>⚡ VANTABLACK</h1>
                    <p style={styles.logoSub}>TACTICAL COMMAND CENTER</p>
                </div>

                {/* RIGHT: User + Actions */}
                <div style={styles.headerRight}>
                    {/* User Info Row */}
                    <div style={styles.userRow}>
                        <span style={styles.userName}>{user.name}</span>
                        <span style={{
                            ...styles.roleBadge,
                            background: getRoleColor(role),
                            color: '#0a0a1a'
                        }}>
                            {role.toUpperCase()}
                        </span>
                        <span style={styles.creditBadge}>₵{user.credits?.toLocaleString()}</span>

                        {/* Notification Bell */}
                        <NotificationBell user={user} />

                        {/* Logout */}
                        <button onClick={onLogout} style={styles.logoutBtn}>🚪</button>
                    </div>

                    {/* Action Buttons Row */}
                    <div style={styles.actionRow}>
                        {canAct && (
                            <>
                                <button onClick={onShowCombat} style={styles.combatBtn}>
                                    ⚔️ Combat
                                </button>
                                <button onClick={onShowCrafting} style={styles.craftBtn}>
                                    🔨 Craft
                                </button>
                                <button onClick={onShowFactions} style={styles.factionBtn}>
                                    🏴 Factions
                                </button>
                            </>
                        )}
                        <button onClick={onShowEvents} style={styles.eventBtn}>
                            🎪 Events
                        </button>
                        <button onClick={onShowLeaderboards} style={styles.leaderboardBtn}>
                            🎖️ Ranks
                        </button>
                        <button onClick={onShowReplays} style={styles.replayBtn}>
                            🎬 Replays
                        </button>
                        <button onClick={() => setShowDailyReward(true)} style={styles.rewardBtn}>
                            🎁 Rewards
                        </button>
                        <button
                            onClick={() => setShowChat(!showChat)}
                            style={{
                                ...styles.chatBtn,
                                ...(showChat ? styles.chatBtnActive : {})
                            }}
                        >
                            💬 Chat
                        </button>
                        <button
                            onClick={() => setShowWeather(!showWeather)}
                            style={{
                                ...styles.toggleBtn,
                                ...(showWeather ? styles.toggleBtnActive : {})
                            }}
                            title="Toggle Weather"
                        >
                            🌍
                        </button>
                        <button
                            onClick={() => setShowCurrency(!showCurrency)}
                            style={{
                                ...styles.toggleBtn,
                                ...(showCurrency ? styles.toggleBtnActive : {})
                            }}
                            title="Toggle Currency"
                        >
                            💱
                        </button>
                    </div>
                </div>
            </div>

            {alert && (
                <div style={{
                    ...styles.alert,
                    ...(alert.type === 'error' ? styles.alertError : styles.alertSuccess)
                }}>
                    {alert.msg}
                </div>
            )}

            <div style={styles.grid}>
                {/* LEFT PANEL */}
                <div style={styles.leftColumn}>
                    {/* Profile Card */}
                    <div style={styles.panel}>
                        <h2 style={styles.panelTitle}>🎭 ACTIVE USER</h2>
                        <div style={styles.profileCard}>
                            <h3 style={styles.profileName}>{user.name}</h3>
                            <div style={{
                                ...styles.roleBadge,
                                background: getRoleColor(role),
                                display: 'inline-block',
                                color: '#0a0a1a'
                            }}>
                                {role.toUpperCase()}
                            </div>
                            <div style={styles.statsGrid}>
                                <div style={styles.stat}>
                                    <div style={styles.statValue}>₵{user.credits?.toLocaleString()}</div>
                                    <div style={styles.statLabel}>CREDITS</div>
                                </div>
                                <div style={styles.stat}>
                                    <div style={styles.statValue}>{user.bioCapacityMax}</div>
                                    <div style={styles.statLabel}>BIO CAP</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Weather Widget */}
                    {showWeather && (
                        <div style={styles.panel}>
                            <WeatherWidget />
                        </div>
                    )}

                    {/* Currency Widget */}
                    {showCurrency && (
                        <div style={styles.panel}>
                            <CurrencyWidget credits={user.credits} />
                        </div>
                    )}

                    {/* Chat Widget */}
                    {showChat && (
                        <div style={styles.panel}>
                            <ChatWidget user={user} />
                        </div>
                    )}
                </div>

                {/* MAIN CONTENT */}
                <div style={styles.panel}>
                    {role === 'admin' ? (
                        <div>
                            <h2 style={styles.panelTitle}>👑 ADMIN DASHBOARD</h2>

                            {adminData && (
                                <>
                                    {/* REGISTERED */}
                                    <h3 style={styles.sectionTitle}>📋 REGISTERED USERS</h3>
                                    <div style={styles.adminStats}>
                                        <AdminStat value={adminData.registered.civilians} label="CIVILIANS" color="#ffaa00" />
                                        <AdminStat value={adminData.registered.heroes} label="HEROES" color="#00f0ff" />
                                        <AdminStat value={adminData.registered.villains} label="VILLAINS" color="#ff0044" />
                                        <AdminStat value={adminData.registered.admins} label="ADMINS" color="#ff00ff" />
                                    </div>

                                    {/* LOGGED IN */}
                                    <h3 style={styles.sectionTitle}>✅ LOGGED IN</h3>
                                    <div style={styles.adminStats}>
                                        <AdminStat value={adminData.logged_in.civilians} label="CIVILIANS" color="#ffaa00" />
                                        <AdminStat value={adminData.logged_in.heroes} label="HEROES" color="#00f0ff" />
                                        <AdminStat value={adminData.logged_in.villains} label="VILLAINS" color="#ff0044" />
                                        <AdminStat value={adminData.logged_in.admins} label="ADMINS" color="#ff00ff" />
                                    </div>

                                    {/* ONLINE */}
                                    <h3 style={styles.sectionTitle}>🟢 ONLINE NOW</h3>
                                    <div style={styles.adminStats}>
                                        <AdminStat value={adminData.online.civilians} label="CIVILIANS" color="#ffaa00" />
                                        <AdminStat value={adminData.online.heroes} label="HEROES" color="#00f0ff" />
                                        <AdminStat value={adminData.online.villains} label="VILLAINS" color="#ff0044" />
                                        <AdminStat value={adminData.online.admins} label="ADMINS" color="#ff00ff" />
                                    </div>

                                    {/* SPENDING */}
                                    <h3 style={styles.sectionTitle}>🛒 TOTAL PURCHASES BY ROLE</h3>
                                    <div style={styles.adminStats}>
                                        <div style={styles.adminStat}>
                                            <div style={{ ...styles.adminStatValue, color: '#ffaa00' }}>
                                                ₵{(adminData.spending_by_role?.civilians?.total_spent || 0).toLocaleString()}
                                            </div>
                                            <div style={styles.adminStatLabel}>👤 CIVILIANS</div>
                                            <div style={{ ...styles.adminStatLabel, color: '#00ff88', marginTop: '4px' }}>
                                                {adminData.spending_by_role?.civilians?.purchase_count || 0} purchases
                                            </div>
                                        </div>
                                        <div style={styles.adminStat}>
                                            <div style={{ ...styles.adminStatValue, color: '#00f0ff' }}>
                                                ₵{(adminData.spending_by_role?.heroes?.total_spent || 0).toLocaleString()}
                                            </div>
                                            <div style={styles.adminStatLabel}>🦸 HEROES</div>
                                            <div style={{ ...styles.adminStatLabel, color: '#00ff88', marginTop: '4px' }}>
                                                {adminData.spending_by_role?.heroes?.purchase_count || 0} purchases
                                            </div>
                                        </div>
                                        <div style={styles.adminStat}>
                                            <div style={{ ...styles.adminStatValue, color: '#ff0044' }}>
                                                ₵{(adminData.spending_by_role?.villains?.total_spent || 0).toLocaleString()}
                                            </div>
                                            <div style={styles.adminStatLabel}>🦹 VILLAINS</div>
                                            <div style={{ ...styles.adminStatLabel, color: '#00ff88', marginTop: '4px' }}>
                                                {adminData.spending_by_role?.villains?.purchase_count || 0} purchases
                                            </div>
                                        </div>
                                        <div style={styles.adminStat}>
                                            <div style={{ ...styles.adminStatValue, color: '#00ff88' }}>
                                                ₵{(adminData.transactions?.total_revenue || 0).toLocaleString()}
                                            </div>
                                            <div style={styles.adminStatLabel}>💰 TOTAL REVENUE</div>
                                        </div>
                                    </div>

                                    {/* SPENDING TABLE */}
                                    <h3 style={styles.sectionTitle}>💰 DETAILED SPENDING BREAKDOWN</h3>
                                    <table style={styles.dataTable}>
                                        <thead>
                                            <tr>
                                                <th style={styles.th}>ROLE</th>
                                                <th style={styles.th}>PURCHASES</th>
                                                <th style={styles.th}>TOTAL SPENT</th>
                                                <th style={styles.th}>SELLS</th>
                                                <th style={styles.th}>TOTAL EARNED</th>
                                                <th style={styles.th}>NET FLOW</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {['civilians', 'heroes', 'villains'].map(roleKey => {
                                                const data = adminData.spending_by_role?.[roleKey] || {};
                                                const totalSpent = data.total_spent || 0;
                                                const totalEarned = data.total_earned || 0;
                                                const netFlow = totalSpent - totalEarned;
                                                const roleName = roleKey.slice(0, -1);

                                                return (
                                                    <tr key={roleKey}>
                                                        <td style={styles.td}>
                                                            <span style={{
                                                                padding: '3px 8px',
                                                                borderRadius: '10px',
                                                                fontSize: '0.7rem',
                                                                fontWeight: 'bold',
                                                                background: roleKey === 'civilians' ? 'rgba(255,170,0,0.2)' :
                                                                    roleKey === 'heroes' ? 'rgba(0,240,255,0.2)' :
                                                                        'rgba(255,0,68,0.2)',
                                                                color: roleKey === 'civilians' ? '#ffaa00' :
                                                                    roleKey === 'heroes' ? '#00f0ff' : '#ff0044'
                                                            }}>
                                                                {roleName.toUpperCase()}
                                                            </span>
                                                        </td>
                                                        <td style={styles.td}>{data.purchase_count || 0}</td>
                                                        <td style={{ ...styles.td, color: '#00f0ff', fontWeight: 'bold' }}>
                                                            ₵{totalSpent.toLocaleString()}
                                                        </td>
                                                        <td style={styles.td}>{data.sell_count || 0}</td>
                                                        <td style={{ ...styles.td, color: '#00ff88', fontWeight: 'bold' }}>
                                                            ₵{totalEarned.toLocaleString()}
                                                        </td>
                                                        <td style={{ ...styles.td, color: netFlow >= 0 ? '#00ff88' : '#ff0044', fontWeight: 'bold' }}>
                                                            ₵{netFlow.toLocaleString()}
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>

                                    {/* TOP SPENDERS */}
                                    {adminData.top_spenders && adminData.top_spenders.length > 0 && (
                                        <>
                                            <h3 style={styles.sectionTitle}>🏆 TOP SPENDERS</h3>
                                            <table style={styles.dataTable}>
                                                <thead>
                                                    <tr>
                                                        <th style={styles.th}>#</th>
                                                        <th style={styles.th}>NAME</th>
                                                        <th style={styles.th}>ROLE</th>
                                                        <th style={styles.th}>PURCHASES</th>
                                                        <th style={styles.th}>TOTAL SPENT</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {adminData.top_spenders.map((spender, idx) => (
                                                        <tr key={idx}>
                                                            <td style={styles.td}>{idx + 1}</td>
                                                            <td style={styles.td}>{spender.combatant_name}</td>
                                                            <td style={styles.td}>
                                                                <span style={{
                                                                    padding: '3px 8px',
                                                                    borderRadius: '10px',
                                                                    fontSize: '0.65rem',
                                                                    fontWeight: 'bold',
                                                                    background: spender.combatant_role === 'hero' ? 'rgba(0,240,255,0.2)' :
                                                                        spender.combatant_role === 'villain' ? 'rgba(255,0,68,0.2)' :
                                                                            'rgba(255,170,0,0.2)',
                                                                    color: spender.combatant_role === 'hero' ? '#00f0ff' :
                                                                        spender.combatant_role === 'villain' ? '#ff0044' : '#ffaa00'
                                                                }}>
                                                                    {spender.combatant_role.toUpperCase()}
                                                                </span>
                                                            </td>
                                                            <td style={styles.td}>{spender.purchase_count}</td>
                                                            <td style={{ ...styles.td, color: '#00ff88', fontWeight: 'bold' }}>
                                                                ₵{parseInt(spender.total_spent).toLocaleString()}
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </>
                                    )}

                                    {/* RECENT TRANSACTIONS */}
                                    {adminData.recent_transactions && adminData.recent_transactions.length > 0 && (
                                        <>
                                            <h3 style={styles.sectionTitle}>📜 RECENT TRANSACTIONS</h3>
                                            <table style={styles.dataTable}>
                                                <thead>
                                                    <tr>
                                                        <th style={styles.th}>USER</th>
                                                        <th style={styles.th}>ROLE</th>
                                                        <th style={styles.th}>TYPE</th>
                                                        <th style={styles.th}>GEAR</th>
                                                        <th style={styles.th}>AMOUNT</th>
                                                        <th style={styles.th}>STATUS</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {adminData.recent_transactions.slice(0, 10).map((t, idx) => (
                                                        <tr key={idx}>
                                                            <td style={styles.td}>{t.combatant_name}</td>
                                                            <td style={styles.td}>
                                                                <span style={{
                                                                    padding: '3px 8px',
                                                                    borderRadius: '10px',
                                                                    fontSize: '0.65rem',
                                                                    fontWeight: 'bold',
                                                                    background: t.combatant_role === 'hero' ? 'rgba(0,240,255,0.2)' :
                                                                        t.combatant_role === 'villain' ? 'rgba(255,0,68,0.2)' :
                                                                            'rgba(255,170,0,0.2)',
                                                                    color: t.combatant_role === 'hero' ? '#00f0ff' :
                                                                        t.combatant_role === 'villain' ? '#ff0044' : '#ffaa00'
                                                                }}>
                                                                    {t.combatant_role.toUpperCase()}
                                                                </span>
                                                            </td>
                                                            <td style={styles.td}>
                                                                {t.transaction_type === 'purchase' ? '🛒 BUY' : '💰 SELL'}
                                                            </td>
                                                            <td style={styles.td}>{t.gear_name}</td>
                                                            <td style={{ ...styles.td, color: t.transaction_type === 'purchase' ? '#00f0ff' : '#00ff88', fontWeight: 'bold' }}>
                                                                ₵{parseInt(t.amount).toLocaleString()}
                                                            </td>
                                                            <td style={styles.td}>
                                                                <span style={{
                                                                    padding: '3px 8px',
                                                                    borderRadius: '10px',
                                                                    fontSize: '0.65rem',
                                                                    fontWeight: 'bold',
                                                                    background: t.status === 'completed' ? 'rgba(0,255,136,0.2)' : 'rgba(255,0,68,0.2)',
                                                                    color: t.status === 'completed' ? '#00ff88' : '#ff0044'
                                                                }}>
                                                                    {t.status}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </>
                                    )}

                                    {/* BOT GENERATOR */}
                                    <h3 style={styles.sectionTitle}>🤖 QUICK TEST DATA GENERATOR</h3>
                                    <div style={styles.botControls}>
                                        <button
                                            onClick={() => generateBots('civilian')}
                                            style={{ ...styles.botBtn, background: 'linear-gradient(90deg, #ffaa00, #ff6600)' }}
                                        >
                                            👤 Generate 3 Civilians
                                        </button>
                                        <button
                                            onClick={() => generateBots('hero')}
                                            style={{ ...styles.botBtn, background: 'linear-gradient(90deg, #00f0ff, #00aaff)' }}
                                        >
                                            🦸 Generate 3 Heroes
                                        </button>
                                        <button
                                            onClick={() => generateBots('villain')}
                                            style={{ ...styles.botBtn, background: 'linear-gradient(90deg, #ff0044, #ff00ff)' }}
                                        >
                                            🦹 Generate 3 Villains
                                        </button>
                                    </div>

                                    {/* CHARTS */}
                                    <div style={{ marginTop: '30px' }}>
                                        <AdminCharts adminId={user.id} />
                                    </div>
                                </>
                            )}
                        </div>
                    ) : (
                        <div>
                            <h2 style={styles.panelTitle}>🏪 MARKETPLACE</h2>
                            <div style={styles.tabs}>
                                {['armory', 'black-market', 'inventory'].map(t => (
                                    <button
                                        key={t}
                                        onClick={() => setTab(t)}
                                        style={{ ...styles.tab, ...(tab === t ? styles.tabActive : {}) }}
                                    >
                                        {t.toUpperCase().replace('-', ' ')}
                                    </button>
                                ))}
                            </div>

                            <div style={styles.gearGrid}>
                                {tab === 'inventory' ? (
                                    inventory.length === 0 ? (
                                        <p style={styles.emptyMsg}>No items in inventory</p>
                                    ) : (
                                        inventory.map(item => (
                                            <GearCard
                                                key={item.inventoryId}
                                                item={item}
                                                actionLabel={canSell ? `SELL ₵${Math.floor(item.price * 0.5).toLocaleString()}` : 'CANNOT SELL'}
                                                onAction={() => sell(item.inventoryId)}
                                                disabled={!canSell}
                                                actionColor="#00ff88"
                                            />
                                        ))
                                    )
                                ) : (
                                    catalog.length === 0 ? (
                                        <p style={styles.emptyMsg}>Loading gear...</p>
                                    ) : (
                                        catalog.map(item => (
                                            <GearCard
                                                key={item.id}
                                                item={item}
                                                actionLabel={canBuy ? 'BUY' : 'ADMIN CANNOT BUY'}
                                                onAction={() => purchase(item.id)}
                                                disabled={!canBuy}
                                                actionColor="#00f0ff"
                                            />
                                        ))
                                    )
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* DAILY REWARD MODAL */}
            {showDailyReward && (
                <DailyRewardModal
                    user={user}
                    onClose={() => setShowDailyReward(false)}
                    onClaimed={async (newBalance) => {
                        const updatedUser = { ...user, credits: newBalance };
                        setUser(updatedUser);
                        localStorage.setItem('aegis_user', JSON.stringify(updatedUser));
                    }}
                />
            )}
        </div>
    );
}

// ============================================
// SUB-COMPONENTS
// ============================================

function GearCard({ item, actionLabel, onAction, disabled, actionColor }) {
    return (
        <div style={styles.gearCard}>
            <div style={styles.gearName}>{item.name}</div>
            <div style={styles.gearMeta}>
                {item.slot?.toUpperCase()} • {item.source?.toUpperCase()}
            </div>
            <div style={styles.gearStats}>
                <span>⚡ {item.bioCapacity}</span>
                <span>🔄 {item.recoveryRate}</span>
                <span>⚠️ {item.riskModifier}</span>
            </div>
            <div style={styles.gearPrice}>
                <span style={{ color: '#00ff88', fontWeight: 'bold' }}>₵{item.price?.toLocaleString()}</span>
                <button
                    onClick={onAction}
                    disabled={disabled}
                    style={{ ...styles.actionBtn, background: disabled ? '#666' : actionColor }}
                >
                    {actionLabel}
                </button>
            </div>
        </div>
    );
}

function AdminStat({ value, label, color }) {
    return (
        <div style={styles.adminStat}>
            <div style={{ ...styles.adminStatValue, color }}>{value}</div>
            <div style={styles.adminStatLabel}>{label}</div>
        </div>
    );
}

const getRoleColor = (role) => {
    const colors = { civilian: '#ffaa00', hero: '#00f0ff', villain: '#ff0044', admin: '#ff00ff' };
    return colors[role] || '#00f0ff';
};

// ============================================
// STYLES
// ============================================

const styles = {
    container: {
        minHeight: '100vh',
        background: '#0a0a1a',
        color: '#e0e0ff',
        fontFamily: 'monospace',
        padding: '20px'
    },

    // ============================================
    // HEADER - REORGANIZED
    // ============================================
    header: {
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'flex-start',
        padding: '20px 25px',
        background: 'rgba(10, 10, 30, 0.85)',
        border: '1px solid rgba(0,240,255,0.2)',
        borderRadius: '12px',
        marginBottom: '20px',
        flexWrap: 'wrap',
        gap: '20px'
    },
    logoSection: {
        display: 'flex',
        flexDirection: 'column'
    },
    logoTitle: {
        color: '#00f0ff',
        fontSize: '1.5rem',
        margin: 0,
        letterSpacing: '2px'
    },
    logoSub: {
        color: '#8888cc',
        fontSize: '0.65rem',
        letterSpacing: '4px',
        margin: 0
    },
    headerRight: {
        display: 'flex',
        flexDirection: 'column',
        gap: '10px',
        alignItems: 'flex-end'
    },
    userRow: {
        display: 'flex',
        alignItems: 'center',
        gap: '10px'
    },
    userName: {
        color: '#e0e0ff',
        fontSize: '0.9rem',
        fontWeight: 'bold'
    },
    roleBadge: {
        padding: '4px 12px',
        borderRadius: '12px',
        fontSize: '0.65rem',
        fontWeight: 'bold',
        letterSpacing: '1px'
    },
    creditBadge: {
        padding: '4px 12px',
        background: 'rgba(0,255,136,0.1)',
        border: '1px solid rgba(0,255,136,0.3)',
        color: '#00ff88',
        borderRadius: '12px',
        fontSize: '0.7rem',
        fontWeight: 'bold'
    },
    actionRow: {
        display: 'flex',
        alignItems: 'center',
        gap: '8px',
        flexWrap: 'wrap',
        justifyContent: 'flex-end'
    },

    // ============================================
    // BUTTONS
    // ============================================
    combatBtn: {
        padding: '8px 14px',
        background: 'linear-gradient(90deg, #ff0044, #ff00ff)',
        border: 'none',
        color: 'white',
        borderRadius: '6px',
        cursor: 'pointer',
        fontFamily: 'monospace',
        fontWeight: 'bold',
        fontSize: '0.7rem',
        letterSpacing: '1px',
        whiteSpace: 'nowrap'
    },
    craftBtn: {
        padding: '8px 14px',
        background: 'linear-gradient(90deg, #ffaa00, #ff6600)',
        border: 'none',
        color: '#0a0a1a',
        borderRadius: '6px',
        cursor: 'pointer',
        fontFamily: 'monospace',
        fontWeight: 'bold',
        fontSize: '0.7rem',
        letterSpacing: '1px',
        whiteSpace: 'nowrap'
    },
    factionBtn: {
        padding: '8px 14px',
        background: 'linear-gradient(90deg, #00f0ff, #00ff88)',
        border: 'none',
        color: '#0a0a1a',
        borderRadius: '6px',
        cursor: 'pointer',
        fontFamily: 'monospace',
        fontWeight: 'bold',
        fontSize: '0.7rem',
        letterSpacing: '1px',
        whiteSpace: 'nowrap'
    },
    eventBtn: {
        padding: '8px 14px',
        background: 'linear-gradient(90deg, #ff00ff, #ffaa00)',
        border: 'none',
        color: '#0a0a1a',
        borderRadius: '6px',
        cursor: 'pointer',
        fontFamily: 'monospace',
        fontWeight: 'bold',
        fontSize: '0.7rem',
        letterSpacing: '1px',
        whiteSpace: 'nowrap'
    },
    rewardBtn: {
        padding: '8px 14px',
        background: 'linear-gradient(90deg, #00ff88, #00f0ff)',
        border: 'none',
        color: '#0a0a1a',
        borderRadius: '6px',
        cursor: 'pointer',
        fontFamily: 'monospace',
        fontWeight: 'bold',
        fontSize: '0.7rem',
        letterSpacing: '1px',
        whiteSpace: 'nowrap'
    },
    chatBtn: {
        padding: '8px 14px',
        background: 'rgba(0,240,255,0.1)',
        border: '1px solid rgba(0,240,255,0.3)',
        color: '#8888cc',
        borderRadius: '6px',
        cursor: 'pointer',
        fontFamily: 'monospace',
        fontWeight: 'bold',
        fontSize: '0.7rem',
        letterSpacing: '1px',
        whiteSpace: 'nowrap',
        transition: 'all 0.2s'
    },
    chatBtnActive: {
        background: 'linear-gradient(90deg, #00f0ff, #ff00ff)',
        color: '#0a0a1a',
        borderColor: 'transparent'
    },
    toggleBtn: {
        padding: '8px 12px',
        background: 'rgba(0,240,255,0.05)',
        border: '1px solid rgba(0,240,255,0.2)',
        color: '#8888cc',
        borderRadius: '6px',
        cursor: 'pointer',
        fontFamily: 'monospace',
        fontSize: '0.9rem',
        fontWeight: 'bold',
        transition: 'all 0.2s'
    },
    toggleBtnActive: {
        background: 'rgba(0,240,255,0.15)',
        borderColor: '#00f0ff',
        color: '#00f0ff'
    },
    logoutBtn: {
        padding: '8px 12px',
        background: 'rgba(255,0,68,0.2)',
        border: '1px solid #ff0044',
        color: '#ff0044',
        borderRadius: '6px',
        cursor: 'pointer',
        fontFamily: 'monospace',
        fontWeight: 'bold',
        fontSize: '0.9rem'
    },

    // ============================================
    // LAYOUT
    // ============================================
    grid: {
        display: 'grid',
        gridTemplateColumns: '380px 1fr',
        gap: '20px'
    },
    leftColumn: {
        display: 'flex',
        flexDirection: 'column',
        gap: '20px'
    },
    panel: {
        background: 'rgba(10, 10, 30, 0.85)',
        border: '1px solid rgba(0,240,255,0.15)',
        borderRadius: '12px',
        padding: '20px',
        overflow: 'hidden'
    },
    panelTitle: {
        color: '#00f0ff',
        fontSize: '0.9rem',
        marginBottom: '15px',
        letterSpacing: '2px',
        marginTop: 0,
        paddingBottom: '10px',
        borderBottom: '1px solid rgba(0,240,255,0.15)'
    },
    sectionTitle: {
        color: '#00f0ff',
        fontSize: '0.75rem',
        marginBottom: '10px',
        letterSpacing: '2px',
        marginTop: '20px'
    },

    // ============================================
    // PROFILE
    // ============================================
    profileCard: {
        padding: '20px',
        background: 'rgba(0,240,255,0.05)',
        borderRadius: '10px'
    },
    profileName: {
        marginBottom: '8px',
        marginTop: 0,
        color: '#e0e0ff'
    },
    statsGrid: {
        display: 'grid',
        gridTemplateColumns: '1fr 1fr',
        gap: '10px',
        marginTop: '15px'
    },
    stat: {
        padding: '10px',
        background: 'rgba(0,0,0,0.3)',
        borderRadius: '6px',
        textAlign: 'center'
    },
    statValue: {
        color: '#00f0ff',
        fontSize: '1.3rem',
        fontWeight: 'bold'
    },
    statLabel: {
        color: '#8888cc',
        fontSize: '0.6rem'
    },

    // ============================================
    // MARKETPLACE
    // ============================================
    tabs: {
        display: 'flex',
        gap: '5px',
        marginBottom: '15px'
    },
    tab: {
        flex: 1,
        padding: '8px',
        background: 'transparent',
        border: '1px solid rgba(0,240,255,0.1)',
        color: '#8888cc',
        cursor: 'pointer',
        borderRadius: '6px',
        fontFamily: 'monospace',
        fontWeight: 'bold',
        fontSize: '0.7rem'
    },
    tabActive: {
        background: 'rgba(0,240,255,0.15)',
        borderColor: '#00f0ff',
        color: '#00f0ff'
    },
    gearGrid: {
        display: 'grid',
        gap: '10px'
    },
    emptyMsg: {
        textAlign: 'center',
        color: '#8888cc',
        fontSize: '0.8rem',
        padding: '20px'
    },
    gearCard: {
        padding: '12px',
        background: 'rgba(0,0,0,0.3)',
        border: '1px solid rgba(0,240,255,0.1)',
        borderRadius: '8px'
    },
    gearName: {
        fontWeight: 'bold',
        marginBottom: '4px'
    },
    gearMeta: {
        fontSize: '0.65rem',
        color: '#8888cc',
        marginBottom: '6px'
    },
    gearStats: {
        display: 'flex',
        gap: '10px',
        fontSize: '0.65rem',
        marginBottom: '8px'
    },
    gearPrice: {
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        paddingTop: '8px',
        borderTop: '1px solid rgba(0,240,255,0.1)'
    },
    actionBtn: {
        padding: '6px 14px',
        border: 'none',
        borderRadius: '6px',
        fontSize: '0.7rem',
        fontWeight: 'bold',
        cursor: 'pointer',
        fontFamily: 'monospace'
    },

    // ============================================
    // ADMIN
    // ============================================
    adminStats: {
        display: 'grid',
        gridTemplateColumns: 'repeat(4, 1fr)',
        gap: '10px',
        marginBottom: '20px'
    },
    adminStat: {
        padding: '15px',
        background: 'rgba(255,0,255,0.05)',
        border: '1px solid rgba(255,0,255,0.2)',
        borderRadius: '8px',
        textAlign: 'center'
    },
    adminStatValue: {
        fontSize: '1.5rem',
        fontWeight: 'bold',
        marginBottom: '5px'
    },
    adminStatLabel: {
        fontSize: '0.65rem',
        color: '#8888cc'
    },
    dataTable: {
        width: '100%',
        borderCollapse: 'collapse',
        fontSize: '0.75rem',
        marginBottom: '20px'
    },
    th: {
        textAlign: 'left',
        padding: '10px 8px',
        color: '#00f0ff',
        fontSize: '0.65rem',
        letterSpacing: '1px',
        borderBottom: '1px solid rgba(0,240,255,0.2)'
    },
    td: {
        padding: '10px 8px',
        borderBottom: '1px solid rgba(0,240,255,0.05)',
        color: '#e0e0ff'
    },
    botControls: {
        display: 'grid',
        gridTemplateColumns: 'repeat(3, 1fr)',
        gap: '10px'
    },
    botBtn: {
        padding: '12px',
        color: '#0a0a1a',
        border: 'none',
        borderRadius: '8px',
        fontSize: '0.7rem',
        fontWeight: 'bold',
        cursor: 'pointer',
        fontFamily: 'monospace'
    },

    // ============================================
    // ALERTS
    // ============================================
    alert: {
        padding: '12px 16px',
        borderRadius: '8px',
        marginBottom: '15px',
        fontSize: '0.8rem',
        border: '1px solid'
    },
    alertSuccess: {
        background: 'rgba(0,255,136,0.1)',
        borderColor: '#00ff88',
        color: '#00ff88'
    },
    alertError: {
        background: 'rgba(255,0,68,0.1)',
        borderColor: '#ff0044',
        color: '#ff0044'
    },
    leaderboardBtn: {
        padding: '8px 14px',
        background: 'linear-gradient(90deg, #ffd700, #ffaa00)',
        border: 'none',
        color: '#0a0a1a',
        borderRadius: '6px',
        cursor: 'pointer',
        fontFamily: 'monospace',
        fontWeight: 'bold',
        fontSize: '0.7rem',
        letterSpacing: '1px',
        whiteSpace: 'nowrap'
    },
    replayBtn: {
        padding: '8px 14px',
        background: 'linear-gradient(90deg, #00f0ff, #ff00ff)',
        border: 'none',
        color: '#0a0a1a',
        borderRadius: '6px',
        cursor: 'pointer',
        fontFamily: 'monospace',
        fontWeight: 'bold',
        fontSize: '0.7rem',
        letterSpacing: '1px',
        whiteSpace: 'nowrap'
    },
};