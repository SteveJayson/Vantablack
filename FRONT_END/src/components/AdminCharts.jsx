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

const API = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api';

const chartColors = {
    cyan: '#00f0ff',
    purple: '#8b5cf6',
    green: '#00ff88',
    red: '#ff0044',
    gold: '#ffb703',
    blue: '#3b82f6',
    pink: '#ff5ec4',
    orange: '#ff8c42'
};

const currencyFormatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 0
});

const styles = {
    panel: {
        background: 'rgba(10, 10, 30, 0.9)',
        border: '1px solid rgba(0,240,255,0.24)',
        borderRadius: '18px',
        padding: '20px',
        boxShadow: '0 0 20px rgba(0, 240, 255, 0.12)'
    },
    header: {
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: '18px',
        flexWrap: 'wrap',
        gap: '12px'
    },
    title: {
        margin: 0,
        color: '#e0e0ff',
        fontSize: '1.1rem',
        letterSpacing: '0.12em',
        textTransform: 'uppercase'
    },
    tabs: {
        display: 'flex',
        gap: '10px',
        flexWrap: 'wrap'
    },
    tab: {
        background: '#11111d',
        color: '#a5a5d1',
        border: '1px solid rgba(255,255,255,0.1)',
        borderRadius: '999px',
        padding: '8px 14px',
        cursor: 'pointer',
        fontWeight: '700',
        fontSize: '0.72rem',
        letterSpacing: '0.08em',
        textTransform: 'uppercase'
    },
    tabActive: {
        background: 'linear-gradient(90deg, rgba(0,240,255,0.2), rgba(139,92,246,0.2))',
        color: '#ffffff',
        borderColor: 'rgba(0,240,255,0.6)',
        boxShadow: '0 0 18px rgba(0,240,255,0.2)'
    },
    loading: {
        color: '#b8b8ff',
        padding: '30px 0',
        textAlign: 'center'
    },
    error: {
        color: '#ff8ea8',
        padding: '20px 0',
        textAlign: 'center'
    },
    grid: {
        display: 'grid',
        gridTemplateColumns: 'repeat(auto-fit, minmax(260px, 1fr))',
        gap: '18px'
    },
    chartCard: {
        background: 'rgba(17, 17, 29, 0.8)',
        border: '1px solid rgba(255,255,255,0.08)',
        borderRadius: '16px',
        padding: '16px',
        minHeight: '260px'
    },
    chartTitle: {
        margin: '0 0 12px',
        color: '#dfe4ff',
        fontSize: '0.8rem',
        letterSpacing: '0.12em',
        textTransform: 'uppercase'
    },
    chartBox: {
        height: '220px',
        width: '100%'
    },
    tableWrap: {
        overflowX: 'auto'
    },
    table: {
        width: '100%',
        borderCollapse: 'collapse',
        color: '#e8ebff'
    },
    th: {
        textAlign: 'left',
        padding: '10px 12px',
        color: '#9ab7ff',
        borderBottom: '1px solid rgba(255,255,255,0.08)',
        fontSize: '0.72rem',
        letterSpacing: '0.08em',
        textTransform: 'uppercase'
    },
    td: {
        padding: '10px 12px',
        borderBottom: '1px solid rgba(255,255,255,0.06)',
        fontSize: '0.84rem'
    }
};

export default function AdminCharts({ adminId }) {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [activeTab, setActiveTab] = useState('overview');

    const loadData = async () => {
        setLoading(true);
        setError('');

        try {
            const res = await fetch(`${API}/admin/charts?adminId=${adminId}`);
            const result = await res.json();

            if (result.success) {
                setData(result.data);
            } else {
                setError(result.message || 'Failed to load chart data');
            }
        } catch (err) {
            setError('Failed to load chart data');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        if (adminId) {
            loadData();
        }
    }, [adminId]);

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
                backgroundColor: '#0a0a1a',
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
                grid: { color: '#1a1a2e' },
                border: { color: '#2a2a4a' }
            },
            y: {
                beginAtZero: true,
                ticks: {
                    color: '#8888cc',
                    font: { family: 'monospace', size: 10 }
                },
                grid: { color: '#1a1a2e' },
                border: { color: '#2a2a4a' }
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
                backgroundColor: '#0a0a1a',
                borderColor: '#00f0ff',
                borderWidth: 1,
                titleColor: '#00f0ff',
                bodyColor: '#e0e0ff',
                titleFont: { family: 'monospace' },
                bodyFont: { family: 'monospace' }
            }
        }
    };

    if (loading) {
        return (
            <div style={styles.panel}>
                <div style={styles.loading}>Loading admin metrics...</div>
            </div>
        );
    }

    if (error) {
        return (
            <div style={styles.panel}>
                <div style={styles.error}>{error}</div>
            </div>
        );
    }

    if (!data) {
        return (
            <div style={styles.panel}>
                <div style={styles.loading}>No chart data available</div>
            </div>
        );
    }

    const daily = data.daily || { labels: [], purchases: [], sells: [], revenue: [] };
    const roleDistribution = {
        civilian: data.roleDistribution?.civilian || 0,
        hero: data.roleDistribution?.hero || 0,
        villain: data.roleDistribution?.villain || 0,
        admin: data.roleDistribution?.admin || 0
    };
    const slotDistribution = {
        helmet: data.slotDistribution?.helmet || 0,
        core: data.slotDistribution?.core || 0,
        dampener: data.slotDistribution?.dampener || 0,
        gauntlets: data.slotDistribution?.gauntlets || 0,
        battery: data.slotDistribution?.battery || 0
    };
    const revenueByRole = data.revenueByRole || {};
    const topSpenders = data.topSpenders || [];

    const lineData = {
        labels: daily.labels.map((date) => {
            const d = new Date(date);
            return d.toLocaleDateString('en', { month: 'short', day: 'numeric' });
        }),
        datasets: [
            {
                label: 'Purchases',
                data: daily.purchases,
                borderColor: '#00f0ff',
                backgroundColor: '#00f0ff22',
                fill: true,
                tension: 0.35,
                pointBackgroundColor: '#00f0ff',
                pointBorderColor: '#0a0a1a',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            },
            {
                label: 'Sells',
                data: daily.sells,
                borderColor: '#00ff88',
                backgroundColor: '#00ff8822',
                fill: true,
                tension: 0.35,
                pointBackgroundColor: '#00ff88',
                pointBorderColor: '#0a0a1a',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            },
            {
                label: 'Revenue',
                data: daily.revenue,
                borderColor: '#ffb703',
                backgroundColor: '#ffb70322',
                fill: true,
                tension: 0.35,
                pointBackgroundColor: '#ffb703',
                pointBorderColor: '#0a0a1a',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                yAxisID: 'y1'
            }
        ]
    };

    const pieData = {
        labels: ['Civilian', 'Hero', 'Villain', 'Admin'],
        datasets: [{
            data: [
                roleDistribution.civilian,
                roleDistribution.hero,
                roleDistribution.villain,
                roleDistribution.admin
            ],
            backgroundColor: ['#ffaa00', '#00f0ff', '#ff0044', '#8b5cf6'],
            borderColor: '#0a0a1a',
            borderWidth: 2
        }]
    };

    const barData = {
        labels: topSpenders.map((s) => (s.combatant_name || 'Unknown').slice(0, 12)),
        datasets: [{
            label: 'Total Spent',
            data: topSpenders.map((s) => Number(s.total_spent) || 0),
            backgroundColor: topSpenders.map((s) => {
                if (s.combatant_role === 'hero') return '#00f0ff';
                if (s.combatant_role === 'villain') return '#ff0044';
                return '#ffaa00';
            }),
            borderColor: '#0a0a1a',
            borderWidth: 1,
            borderRadius: 4
        }]
    };

    const doughnutData = {
        labels: ['Civilian', 'Hero', 'Villain', 'Admin'],
        datasets: [{
            data: [
                revenueByRole.civilian?.revenue || 0,
                revenueByRole.hero?.revenue || 0,
                revenueByRole.villain?.revenue || 0,
                revenueByRole.admin?.revenue || 0
            ],
            backgroundColor: ['#ffaa00', '#00f0ff', '#ff0044', '#8b5cf6'],
            borderColor: '#0a0a1a',
            borderWidth: 2
        }]
    };

    const gearData = {
        labels: ['Helmet', 'Core', 'Dampener', 'Gauntlets', 'Battery'],
        datasets: [{
            label: 'Items Owned',
            data: [
                slotDistribution.helmet,
                slotDistribution.core,
                slotDistribution.dampener,
                slotDistribution.gauntlets,
                slotDistribution.battery
            ],
            backgroundColor: ['#00f0ff', '#8b5cf6', '#00ff88', '#ff8c42', '#ff5ec4'],
            borderColor: '#0a0a1a',
            borderWidth: 1,
            borderRadius: 4
        }]
    };

    return (
        <div style={styles.panel}>
            <div style={styles.header}>
                <h2 style={styles.title}>Admin Analytics</h2>
                <div style={styles.tabs}>
                    <button
                        type="button"
                        onClick={() => setActiveTab('overview')}
                        style={{ ...styles.tab, ...(activeTab === 'overview' ? styles.tabActive : {}) }}
                    >
                        Overview
                    </button>
                    <button
                        type="button"
                        onClick={() => setActiveTab('detailed')}
                        style={{ ...styles.tab, ...(activeTab === 'detailed' ? styles.tabActive : {}) }}
                    >
                        Detailed
                    </button>
                </div>
            </div>

            {activeTab === 'overview' ? (
                <div style={styles.grid}>
                    <div style={styles.chartCard}>
                        <h3 style={styles.chartTitle}>Daily Transactions</h3>
                        <div style={styles.chartBox}>
                            <Line
                                data={lineData}
                                options={{
                                    ...chartOptions,
                                    scales: {
                                        ...chartOptions.scales,
                                        y1: {
                                            beginAtZero: true,
                                            position: 'right',
                                            grid: { drawOnChartArea: false },
                                            ticks: {
                                                color: '#8888cc',
                                                font: { family: 'monospace', size: 10 }
                                            },
                                            border: { color: '#2a2a4a' }
                                        }
                                    }
                                }}
                            />
                        </div>
                    </div>

                    <div style={styles.chartCard}>
                        <h3 style={styles.chartTitle}>Role Distribution</h3>
                        <div style={styles.chartBox}>
                            <Doughnut data={pieData} options={pieOptions} />
                        </div>
                    </div>

                    <div style={{ ...styles.chartCard, gridColumn: '1 / -1' }}>
                        <h3 style={styles.chartTitle}>Top Spenders</h3>
                        <div style={styles.chartBox}>
                            <Bar data={barData} options={chartOptions} />
                        </div>
                    </div>
                </div>
            ) : (
                <div style={styles.grid}>
                    <div style={styles.chartCard}>
                        <h3 style={styles.chartTitle}>Revenue by Role</h3>
                        <div style={styles.chartBox}>
                            <Bar data={doughnutData} options={chartOptions} />
                        </div>
                    </div>

                    <div style={styles.chartCard}>
                        <h3 style={styles.chartTitle}>Gear Slot Distribution</h3>
                        <div style={styles.chartBox}>
                            <Pie data={gearData} options={pieOptions} />
                        </div>
                    </div>

                    <div style={{ ...styles.chartCard, gridColumn: '1 / -1' }}>
                        <h3 style={styles.chartTitle}>Top Spenders</h3>
                        <div style={styles.tableWrap}>
                            <table style={styles.table}>
                                <thead>
                                    <tr>
                                        <th style={styles.th}>Combatant</th>
                                        <th style={styles.th}>Role</th>
                                        <th style={styles.th}>Spent</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {topSpenders.map((entry, index) => (
                                        <tr key={`${entry.combatant_name || 'unknown'}-${index}`}>
                                            <td style={styles.td}>{entry.combatant_name || 'Unknown'}</td>
                                            <td style={styles.td}>{entry.combatant_role || 'Unknown'}</td>
                                            <td style={styles.td}>{currencyFormatter.format(Number(entry.total_spent) || 0)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}