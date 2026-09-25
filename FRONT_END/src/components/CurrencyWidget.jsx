import { useState, useEffect } from 'react';

const API = 'http://localhost:8080/api';

const CURRENCIES = [
    { code: 'PHP', symbol: '₱', name: 'Philippine Peso' },
    { code: 'USD', symbol: '$', name: 'US Dollar' },
    { code: 'EUR', symbol: '€', name: 'Euro' },
    { code: 'JPY', symbol: '¥', name: 'Japanese Yen' },
    { code: 'GBP', symbol: '£', name: 'British Pound' },
    { code: 'KRW', symbol: '₩', name: 'Korean Won' },
    { code: 'CNY', symbol: '¥', name: 'Chinese Yuan' }
];

export default function CurrencyWidget({ credits }) {
    const [currency, setCurrency] = useState('PHP');
    const [conversion, setConversion] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        loadConversion();
    }, [currency, credits]);

    const loadConversion = async () => {
        setLoading(true);
        setError('');

        try {
            const res = await fetch(`${API}/external/convert-credits`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ credits: credits || 0, currency })
            });
            const data = await res.json();

            if (data.success) {
                setConversion(data.data);
            } else {
                setError(data.message);
            }
        } catch (err) {
            setError('Cannot load currency');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div>
            <h2 style={styles.title}>REAL-WORLD VALUE</h2>

            {/* Currency Selector */}
            <div style={styles.currencyGrid}>
                {CURRENCIES.map(c => (
                    <div
                        key={c.code}
                        onClick={() => setCurrency(c.code)}
                        style={{
                            ...styles.currencyBtn,
                            ...(currency === c.code ? styles.currencyBtnActive : {})
                        }}
                        title={c.name}
                    >
                        <span style={styles.code}>{c.code}</span>
                    </div>
                ))}
            </div>

            {loading ? (
                <div style={styles.loading}>Calculating...</div>
            ) : error ? (
                <div style={styles.error}>{error}</div>
            ) : conversion && (
                <>
                    {/* Main Display */}
                    <div style={styles.mainDisplay}>
                        <div style={styles.box}>
                            <div style={styles.creditsValue}>
                                ₵{(credits || 0).toLocaleString()}
                            </div>
                            <div style={styles.label}>CREDITS</div>
                        </div>

                        <div style={styles.equals}>≈</div>

                        <div style={styles.box}>
                            <div style={styles.realValue}>
                                {conversion.formatted}
                            </div>
                            <div style={styles.label}>
                                {CURRENCIES.find(c => c.code === currency)?.name?.split(' ')[0] || currency}
                            </div>
                        </div>
                    </div>

                    {/* Details */}
                    <div style={styles.details}>
                        <div style={styles.detailRow}>
                            <span>USD Value</span>
                            <span>${conversion.usd_value.toLocaleString()}</span>
                        </div>
                        <div style={styles.detailRow}>
                            <span>Exchange Rate</span>
                            <span>{conversion.exchange_rate}</span>
                        </div>
                        <div style={styles.detailRow}>
                            <span>₵1 Credit</span>
                            <span>$0.10 USD</span>
                        </div>
                    </div>

                    <div style={styles.note}>
                        * Live rates from ExchangeRate API
                    </div>
                </>
            )}
        </div>
    );
}

const styles = {
    title: {
        color: '#00ff88',
        fontSize: '0.9rem',
        marginBottom: '15px',
        letterSpacing: '2px',
        borderBottom: '1px solid rgba(0,255,136,0.2)',
        paddingBottom: '10px',
        marginTop: 0
    },
    currencyGrid: {
        display: 'grid',
        gridTemplateColumns: 'repeat(7, 1fr)',
        gap: '5px',
        marginBottom: '15px'
    },
    currencyBtn: {
        padding: '6px 4px',
        background: 'rgba(0,0,0,0.3)',
        border: '1px solid rgba(0,255,136,0.2)',
        borderRadius: '6px',
        textAlign: 'center',
        cursor: 'pointer',
        transition: 'all 0.2s'
    },
    currencyBtnActive: {
        background: 'rgba(0,255,136,0.2)',
        borderColor: '#00ff88'
    },
    flag: {
        fontSize: '1rem',
        display: 'block'
    },
    code: {
        fontSize: '0.5rem',
        color: '#8888cc',
        display: 'block',
        marginTop: '2px'
    },
    loading: {
        textAlign: 'center',
        color: '#8888cc',
        fontSize: '0.75rem',
        padding: '20px'
    },
    error: {
        textAlign: 'center',
        color: '#ff0044',
        fontSize: '0.75rem',
        padding: '20px'
    },
    mainDisplay: {
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between',
        gap: '10px',
        marginBottom: '15px',
        background: 'rgba(0,255,136,0.05)',
        borderRadius: '10px',
        padding: '15px'
    },
    box: {
        flex: 1,
        textAlign: 'center'
    },
    creditsValue: {
        fontSize: '1.1rem',
        fontWeight: 'bold',
        color: '#00f0ff'
    },
    realValue: {
        fontSize: '1.1rem',
        fontWeight: 'bold',
        color: '#00ff88'
    },
    label: {
        fontSize: '0.55rem',
        color: '#8888cc',
        marginTop: '4px'
    },
    equals: {
        fontSize: '1.3rem',
        color: '#ffaa00',
        fontWeight: 'bold'
    },
    details: {
        background: 'rgba(0,0,0,0.3)',
        borderRadius: '8px',
        padding: '10px 12px',
        marginBottom: '10px'
    },
    detailRow: {
        display: 'flex',
        justifyContent: 'space-between',
        padding: '4px 0',
        fontSize: '0.7rem',
        color: '#8888cc'
    },
    note: {
        fontSize: '0.6rem',
        color: '#666',
        textAlign: 'center',
        fontStyle: 'italic'
    }
};