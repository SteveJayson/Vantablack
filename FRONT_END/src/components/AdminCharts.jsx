import { useState, useEffect } from 'react';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
    Filler
} from 'chart.js';
import { Line, Pie, Bar, Doughnut } from 'react-chartjs-2';

// Register Chart.js components
ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
    Filler
);

const API = 'http://localhost:8080/api';

export default function AdminCharts({ adminId }) {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [activeTab, setActiveTab] = useState('overview');

    useEffect(() => {
        loadData();
    }, []);

    const loadData = async () => {
        setLoading(true);
        try {
            const res = await fetch(`${API}/admin/charts?adminId=${adminId}`);
            const result = await res.json();

            if (result.success) {
                setData(result.data);
            } else {
                setError(result.message);
            }
        } catch (err) {
            setError('Failed to load chart data');
        } finally {
            setLoading(false);
        }
    };

    // ============================================
    // CHART CONFIGURATIONS
    // ============================================

    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    color: '#e0e0ff',
                    font: { family: 'monospace', size: 11 }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(10, 10, 30, 0.95)',
                borderColor: '#00f0ff',
                borderWidth: 1,
                titleColor: '#00f0ff',
                bodyColor: '#e0e0ff',
                titleFont: { family: 'monospace' },
                bodyFont: { family: 'monospace' }
            }
        },
        scales: {
            x: {
                ticks: {
                    color: '#8888cc',
                    font: { family: 'monospace', size: 10 }
                },
                grid: { color: 'rgba(0, 240, 255, 0.05)' }
            },
            y: {
                ticks: {
                    color: '#8888cc',
                    font: { family: 'monospace', size: 10 }
                },
                grid: { color: 'rgba(0, 240, 255, 0.05)' }
            }
        }
    };

    const pieOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    color: '#e0e0ff',
                    font: { family: 'monospace', size: 11 },
                    padding: 15
                }
            },
            tooltip: {
                backgroundColor: 'rgba(10, 10, 30, 0.95)',
                borderColor: '#00f0ff',
                borderWidth: 1,
                titleColor: '#00f0ff',
                bodyColor: '#e0e0ff',
                titleFont: { family: 'monospace' },
                bodyFont: { family: 'monospace' }
            }
        }
    };

    // ============================================
    // LOADING & ERROR STATES
    // ============================================

    if (loading) {
        return (
            <div style={styles.panel}>
                <div style={styles.loading}>📊 Loading charts...</div>
            </div>
        );
    }

    if (error) {
        return (
            <div style={styles.panel}>
                <div style={styles.error}>❌ {error}</div>
            </div>
        );
    }

    if (!data) return null;

    // ============================================
    // CHART DATA
    // ============================================

    // 1. Line Chart - Daily Transactions
    const lineData = {
        labels: data.daily.labels.map(date => {
            const d = new Date(date);
            return d.toLocaleDateString('en', { month: 'short', day: 'numeric' });
        }),
        datasets: [
            {
                label: '🛒 Purchases',
                data: data.daily.purchases,
                borderColor: '#00f0ff',
                backgroundColor: 'rgba(0, 240, 255, 0.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#00f0ff',
                pointBorderColor: '#0a0a1a',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            },
            {
                label: '💰 Sells',
                data: data.daily.sells,
                borderColor: '#00ff88',
                backgroundColor: 'rgba(0, 255, 136, 0.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#00ff88',
                pointBorderColor: '#0a0a1a',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }
        ]
    };

    // 2. Pie Chart - Role Distribution
    const pieData = {
        labels: ['👤 Civilians', '🦸 Heroes', '🦹 Villains', '👑 Admins'],
        datasets: [
            {
                data: [
                    data.roleDistribution.civilian || 0,
                    data.roleDistribution.hero || 0,
                    data.roleDistribution.villain || 0,
                    data.roleDistribution.admin || 0
                ],
                backgroundColor: [
                    'rgba(255, 170, 0, 0.7)',
                    'rgba(0, 240, 255, 0.7)',
                    'rgba(255, 0, 68, 0.7)',
                    'rgba(255, 0, 255, 0.7)'
                ],
                borderColor: [
                    '#ffaa00',
                    '#00f0ff',
                    '#ff0044',
                    '#ff00ff'
                ],
                borderWidth: 2
            }
        ]
    };

    // 3. Bar Chart - Top Spenders
    const barData = {
        labels: data.topSpenders.map(s => s.combatant_name.substring(0, 12)),
        datasets: [
            {
                label: '💰 Total Spent',
                data: data.topSpenders.map(s => s.total_spent),
                backgroundColor: data.topSpenders.map(s => {
                    if (s.combatant_role === 'hero') return 'rgba(0, 240, 255, 0.7)';
                    if (s.combatant_role === 'villain') return 'rgba(255, 0, 68, 0.7)';
                    return 'rgba(255, 170, 0, 0.7)';
                }),
                borderColor: data.topSpenders.map(s => {
                    if (s.combatant_role === 'hero') return '#00f0ff';
                    if (s.combatant_role === 'villain') return '#ff0044';
                    return '#ffaa00';
                }),
                borderWidth: 2,
                borderRadius: 6
            }
        ]
    };

    // 4. Doughnut Chart - Revenue by Role
    const doughnutData = {
        labels: ['👤 Civilians', '🦸 Heroes', '🦹 Villains'],
        datasets: [
            {
                data: [
                    data.revenueByRole.civilian?.revenue || 0,
                    data.revenueByRole.hero?.revenue || 0,
                    data.revenueByRole.villain?.revenue || 0
                ],
                backgroundColor: [
                    'rgba(255, 170, 0, 0.7)',
                    'rgba(0, 240, 255, 0.7)',
                    'rgba(255, 0, 68, 0.7)'
                ],
                borderColor: [
                    '#ffaa00',
                    '#00f0ff',
                    '#ff0044'
                ],
                borderWidth: 2
            }
        ]
    };

    const doughnutOptions = {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '60%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    color: '#e0e0ff',
                    font: { family: 'monospace', size: 11 },
                    padding: 15
                }
            },
            tooltip: {
                backgroundColor: 'rgba(10, 10, 30, 0.95)',
                borderColor: '#00f0ff',
                borderWidth: 1,
                titleColor: '#00f0ff',
                bodyColor: '#e0e0ff',
                callbacks: {
                    label: function (context) {
                        return ' ₵' + context.parsed.toLocaleString();
                    }
                }
            }
        }
    };

    // ============================================
    // RENDER
    // ============================================

    return (
        <div style={styles.container}>
            <div style={styles.header}>
                <h2 style={styles.title}>📈 ANALYTICS DASHBOARD</h2>
                <div style={styles.tabs}>
                    <button
                        onClick={() => setActiveTab('overview')}
                        style={{ ...styles.tab, ...(activeTab === 'overview' ? styles.tabActive : {}) }}
                    >
                        📊 Overview
                    </button>
                    <button
                        onClick={() => setActiveTab('detailed')}
                        style={{ ...styles.tab, ...(activeTab === 'detailed' ? styles.tabActive : {}) }}
                    >
                        📈 Detailed
                    </button>
                </div>
            </div>

            {activeTab === 'overview' && (
                <div style={styles.grid}>
                    {/* Line Chart - Daily Transactions */}
                    <div style={styles.chartCard}>
                        <h3 style={styles.chartTitle}>📈 DAILY TRANSACTIONS (Last 7 Days)</h3>
                        <div style={styles.chartWrapper}>
                            <Line data={lineData} options={chartOptions} />
                        </div>
                    </div>

                    {/* Pie Chart - Role Distribution */}
                    <div style={styles.chartCard}>
                        <h3 style={styles.chartTitle}>🥧 USERS BY ROLE</h3>
                        <div style={styles.chartWrapper}>
                            <Pie data={pieData} options={pieOptions} />
                        </div>
                    </div>

                    {/* Bar Chart - Top Spenders */}
                    <div style={styles.chartCard}>
                        <h3 style={styles.chartTitle}>🏆 TOP SPENDERS</h3>
                        <div style={styles.chartWrapper}>
                            <Bar data={barData} options={chartOptions} />
                        </div>
                    </div>

                    {/* Doughnut Chart - Revenue by Role */}
                    <div style={styles.chartCard}>
                        <h3 style={styles.chartTitle}>💰 REVENUE BY ROLE</h3>
                        <div style={styles.chartWrapper}>
                            <Doughnut data={doughnutData} options={doughnutOptions} />
                        </div>
                    </div>
                </div>
            )}

            {activeTab === 'detailed' && (
                <div style={styles.grid}>
                    {/* Revenue Trend */}
                    <div style={{ ...styles.chartCard, gridColumn: 'span 2' }}>
                        <h3 style={styles.chartTitle}>📊 REVENUE TREND (Last 7 Days)</h3>
                        <div style={styles.chartWrapper}>
                            <Bar
                                data={{
                                    labels: data.daily.labels.map(date => {
                                        const d = new Date(date);
                                        return d.toLocaleDateString('en', { month: 'short', day: 'numeric' });
                                    }),
                                    datasets: [{
                                        label: '💰 Revenue (₵)',
                                        data: data.daily.revenue,
                                        backgroundColor: 'rgba(0, 255, 136, 0.6)',
                                        borderColor: '#00ff88',
                                        borderWidth: 2,
                                        borderRadius: 6
                                    }]
                                }}
                                options={chartOptions}
                            />
                        </div>
                    </div>

                    {/* Slot Distribution */}
                    <div style={styles.chartCard}>
                        <h3 style={styles.chartTitle}>🎒 GEAR BY SLOT</h3>
                        <div style={styles.chartWrapper}>
                            <Bar
                                data={{
                                    labels: ['Helmet', 'Core', 'Dampener', 'Gauntlets', 'Battery'],
                                    datasets: [{
                                        label: 'Items Owned',
                                        data: [
                                            data.slotDistribution.helmet || 0,
                                            data.slotDistribution.core || 0,
                                            data.slotDistribution.dampener || 0,
                                            data.slotDistribution.gauntlets || 0,
                                            data.slotDistribution.battery || 0
                                        ],
                                        backgroundColor: [
                                            'rgba(0, 240, 255, 0.6)',
                                            'rgba(255, 0, 255, 0.6)',
                                            'rgba(0, 255, 136, 0.6)',
                                            'rgba(255, 170, 0, 0.6)',
                                            'rgba(255, 0, 68, 0.6)'
                                        ],
                                        borderWidth: 2,
                                        borderRadius: 6
                                    }]
                                }}
                                options={chartOptions}
                            />
                        </div>
                    </div>

                    {/* Role Revenue Details */}
                    <div style={styles.chartCard}>
                        <h3 style={styles.chartTitle}>💵 DETAILED BREAKDOWN</h3>
                        <div style={styles.detailsList}>
                            {['civilian', 'hero', 'villain'].map(role => {
                                const roleData = data.revenueByRole[role] || { revenue: 0, count: 0 };
                                const emoji = role === 'hero' ? '🦸' : role === 'villain' ? '🦹' : '👤';
                                const color = role === 'hero' ? '#00f0ff' : role === 'villain' ? '#ff0044' : '#ffaa00';

                                return (
                                    <div key={role} style={styles.detailItem}>
                                        <div style={styles.detailLabel}>
                                            <span style={{ color }}>{emoji} {role.toUpperCase()}</span>
                                        </div>
                                        <div style={styles.detailValues}>
                                            <div>₵{roleData.revenue.toLocaleString()}</div>
                                            <div style={styles.detailCount}>{roleData.count} purchases</div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

const styles = {
    container: {
        fontFamily: 'monospace',
        color: '#e0e0ff'
    },
    header: {
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: '20px',
        flexWrap: 'wrap',
        gap: '15px'
    },
    title: {
        color: '#00f0ff',
        fontSize: '1.1rem',
        margin: 0,
        letterSpacing: '2px'
    },
    tabs: {
        display: 'flex',
        gap: '8px'
    },
    tab: {
        padding: '8px 16px',
        background: 'rgba(0,0,0,0.3)',
        border: '1px solid rgba(0,240,255,0.2)',
        color: '#8888cc',
        cursor: 'pointer',
        borderRadius: '6px',
        fontFamily: 'monospace',
        fontSize: '0.7rem',
        fontWeight: 'bold',
        letterSpacing: '1px'
    },
    tabActive: {
        background: 'rgba(0,240,255,0.15)',
        borderColor: '#00f0ff',
        color: '#00f0ff'
    },
    grid: {
        display: 'grid',
        gridTemplateColumns: 'repeat(2, 1fr)',
        gap: '20px'
    },
    chartCard: {
        padding: '20px',
        background: 'rgba(0,0,0,0.3)',
        border: '1px solid rgba(0,240,255,0.15)',
        borderRadius: '12px'
    },
    chartTitle: {
        color: '#00f0ff',
        fontSize: '0.75rem',
        marginBottom: '15px',
        letterSpacing: '2px',
        marginTop: 0,
        paddingBottom: '10px',
        borderBottom: '1px solid rgba(0,240,255,0.15)'
    },
    chartWrapper: {
        position: 'relative',
        height: '300px'
    },
    loading: {
        textAlign: 'center',
        color: '#8888cc',
        fontSize: '0.85rem',
        padding: '40px'
    },
    error: {
        textAlign: 'center',
        color: '#ff0044',
        fontSize: '0.85rem',
        padding: '40px'
    },
    detailsList: {
        display: 'flex',
        flexDirection: 'column',
        gap: '12px'
    },
    detailItem: {
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        padding: '12px 15px',
        background: 'rgba(0,0,0,0.3)',
        borderRadius: '8px'
    },
    detailLabel: {
        fontSize: '0.8rem',
        fontWeight: 'bold'
    },
    detailValues: {
        textAlign: 'right',
        fontSize: '0.9rem',
        fontWeight: 'bold',
        color: '#00ff88'
    },
    detailCount: {
        fontSize: '0.65rem',
        color: '#8888cc',
        marginTop: '2px',
        fontWeight: 'normal'
    }
};