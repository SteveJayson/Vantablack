import { useState, useEffect } from 'react';

const API = 'http://localhost:8080/api';

export default function Crafting({ user, onBack, onCraftComplete }) {
    const [recipes, setRecipes] = useState([]);
    const [inventory, setInventory] = useState([]);
    const [selectedRecipe, setSelectedRecipe] = useState(null);
    const [loading, setLoading] = useState(true);
    const [crafting, setCrafting] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [tab, setTab] = useState('craft');

    useEffect(() => {
        loadData();
    }, []);

    const loadData = async () => {
        setLoading(true);
        try {
            const [recipesRes, inventoryRes] = await Promise.all([
                fetch(`${API}/crafting/recipes`),
                fetch(`${API}/marketplace/inventory/${user.id}`)
            ]);

            const recipesData = await recipesRes.json();
            const inventoryData = await inventoryRes.json();

            if (recipesData.success) setRecipes(recipesData.data.recipes || []);
            if (inventoryData.success) setInventory(inventoryData.data.inventory || []);
        } catch (err) {
            setError('Failed to load data');
        } finally {
            setLoading(false);
        }
    };

    const canCraft = (recipe) => {
        if (!recipe) return { ok: false, reason: 'No recipe selected' };

        if (user.credits < recipe.requiredCredits) {
            return { ok: false, reason: `Need ₵${recipe.requiredCredits} credits` };
        }

        for (const mat of recipe.materialDetails) {
            const owned = inventory.filter(i => i.gearId === mat.id).length;
            if (owned < mat.quantity) {
                return { ok: false, reason: `Need ${mat.quantity}x ${mat.name} (have ${owned})` };
            }
        }

        return { ok: true };
    };

    const craftItem = async () => {
        if (!selectedRecipe) return;

        const check = canCraft(selectedRecipe);
        if (!check.ok) {
            setError(check.reason);
            return;
        }

        setCrafting(true);
        setError('');
        setSuccess('');

        try {
            const res = await fetch(`${API}/crafting/craft`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    combatantId: user.id,
                    recipeId: selectedRecipe.id
                })
            });
            const data = await res.json();

            if (data.success) {
                setSuccess(`Crafted ${data.data.crafted.name}! New balance: ₵${data.data.newBalance.toLocaleString()}`);
                setSelectedRecipe(null);
                loadData();
                if (onCraftComplete) onCraftComplete(data.data);
            } else {
                setError(data.message || 'Craft failed');
            }
        } catch (err) {
            setError('Cannot connect to server');
        } finally {
            setCrafting(false);
        }
    };

    const disassembleItem = async (inventoryId) => {
        if (!confirm('Are you sure? You get 40% back.')) return;

        try {
            const res = await fetch(`${API}/crafting/disassemble`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    combatantId: user.id,
                    inventoryId
                })
            });
            const data = await res.json();

            if (data.success) {
                setSuccess(`Disassembled ${data.data.gearName} for ₵${data.data.refund.toLocaleString()}`);
                loadData();
                if (onCraftComplete) onCraftComplete(data.data);
            } else {
                setError(data.message || 'Disassemble failed');
            }
        } catch (err) {
            setError('Cannot connect to server');
        }
    };

    return (
        <div style={styles.container}>
            {/* HEADER */}
            <div style={styles.header}>
                <button onClick={onBack} style={styles.backBtn}>Back</button>
                <h1 style={styles.title}>GEAR CRAFTING</h1>
                <div style={styles.userBadge}>
                    <span>₵{user.credits?.toLocaleString()}</span>
                </div>
            </div>

            {/* TABS */}
            <div style={styles.tabs}>
                <button
                    onClick={() => setTab('craft')}
                    style={{ ...styles.tab, ...(tab === 'craft' ? styles.tabActive : {}) }}
                >
                    CRAFT
                </button>
                <button
                    onClick={() => setTab('disassemble')}
                    style={{ ...styles.tab, ...(tab === 'disassemble' ? styles.tabActive : {}) }}
                >
                    DISASSEMBLE
                </button>
            </div>

            {error && <div style={styles.errorBox}>{error}</div>}
            {success && <div style={styles.successBox}>{success}</div>}

            {loading ? (
                <div style={styles.loading}>Loading...</div>
            ) : tab === 'craft' ? (
                <div style={styles.grid}>
                    {/* RECIPES LIST */}
                    <div style={styles.panel}>
                        <h2 style={styles.panelTitle}>AVAILABLE RECIPES</h2>
                        <div style={styles.recipeList}>
                            {recipes.map(r => (
                                <div
                                    key={r.id}
                                    onClick={() => setSelectedRecipe(r)}
                                    style={{
                                        ...styles.recipeCard,
                                        borderColor: selectedRecipe?.id === r.id ? '#d1d5db' : 'rgba(148, 163, 184, 0.25)',
                                        background: selectedRecipe?.id === r.id ? 'rgba(148, 163, 184, 0.08)' : 'rgba(0,0,0,0.3)'
                                    }}
                                >
                                    <div style={styles.recipeHeader}>
                                        <div style={styles.recipeName}>{r.resultName}</div>
                                        <div style={styles.tierBadge}>T{r.tier}</div>
                                    </div>
                                    <div style={styles.recipeStats}>
                                        <span>{r.bioCapacity}</span>
                                        <span>{r.recoveryRate}</span>
                                        <span>{r.riskModifier}</span>
                                    </div>
                                    <div style={styles.recipeCost}>
                                        ₵{r.requiredCredits.toLocaleString()}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* RECIPE DETAIL */}
                    <div style={styles.panel}>
                        <h2 style={styles.panelTitle}>CRAFTING DETAILS</h2>

                        {!selectedRecipe ? (
                            <div style={styles.loading}>Select a recipe to craft</div>
                        ) : (
                            <div>
                                <div style={styles.detailCard}>
                                    <h3 style={styles.resultName}>{selectedRecipe.resultName}</h3>
                                    <div style={styles.resultStats}>
                                        <div style={styles.resultStat}>
                                            <div style={styles.statValue}>{selectedRecipe.bioCapacity}</div>
                                            <div style={styles.statLabel}>BIO CAP</div>
                                        </div>
                                        <div style={styles.resultStat}>
                                            <div style={styles.statValue}>{selectedRecipe.recoveryRate}</div>
                                            <div style={styles.statLabel}>RECOVERY</div>
                                        </div>
                                        <div style={styles.resultStat}>
                                            <div style={styles.statValue}>{selectedRecipe.riskModifier}</div>
                                            <div style={styles.statLabel}>RISK</div>
                                        </div>
                                    </div>
                                </div>

                                <h4 style={styles.sectionTitle}>REQUIRED MATERIALS</h4>
                                <div style={styles.materialsList}>
                                    {selectedRecipe.materialDetails.map(mat => {
                                        const owned = inventory.filter(i => i.gearId === mat.id).length;
                                        const hasEnough = owned >= mat.quantity;

                                        return (
                                            <div key={mat.id} style={{
                                                ...styles.materialItem,
                                                borderColor: hasEnough ? 'rgba(148, 163, 184, 0.35)' : 'rgba(148, 163, 184, 0.2)',
                                                background: hasEnough ? 'rgba(148, 163, 184, 0.06)' : 'rgba(255,255,255,0.02)'
                                            }}>
                                                <div>
                                                    <div style={styles.matName}>{mat.name}</div>
                                                    <div style={styles.matId}>{mat.id}</div>
                                                </div>
                                                <div style={{
                                                    color: hasEnough ? '#d1d5db' : '#e5e7eb',
                                                    fontWeight: 'bold'
                                                }}>
                                                    {owned} / {mat.quantity} {hasEnough ? 'OK' : 'LOW'}
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>

                                <h4 style={styles.sectionTitle}>CREDITS</h4>
                                <div style={styles.creditsRow}>
                                    <span>Required:</span>
                                    <span style={{ color: user.credits >= selectedRecipe.requiredCredits ? '#d1d5db' : '#e5e7eb', fontWeight: 'bold' }}>
                                        ₵{selectedRecipe.requiredCredits.toLocaleString()}
                                    </span>
                                </div>
                                <div style={styles.creditsRow}>
                                    <span>Your balance:</span>
                                    <span>₵{user.credits?.toLocaleString()}</span>
                                </div>

                                <button
                                    onClick={craftItem}
                                    disabled={crafting || !canCraft(selectedRecipe).ok}
                                    style={{
                                        ...styles.craftBtn,
                                        opacity: (crafting || !canCraft(selectedRecipe).ok) ? 0.5 : 1,
                                        cursor: (crafting || !canCraft(selectedRecipe).ok) ? 'not-allowed' : 'pointer'
                                    }}
                                >
                                    {crafting ? 'CRAFTING...' : 'CRAFT NOW'}
                                </button>

                                {!canCraft(selectedRecipe).ok && (
                                    <div style={styles.reasonBox}>
                                        {canCraft(selectedRecipe).reason}
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            ) : (
                /* DISASSEMBLE TAB */
                <div style={styles.panel}>
                    <h2 style={styles.panelTitle}>DISASSEMBLE GEAR</h2>
                    <p style={styles.disassembleNote}>
                        Break down gear for 40% of its value
                    </p>

                    <div style={styles.gearGrid}>
                        {inventory.length === 0 ? (
                            <div style={styles.loading}>No gear in inventory</div>
                        ) : (
                            inventory.map(item => (
                                <div key={item.inventoryId} style={styles.gearCard}>
                                    <div style={styles.gearName}>{item.name}</div>
                                    <div style={styles.gearMeta}>
                                        {item.slot?.toUpperCase()} • T{item.tier || 1}
                                    </div>
                                    <div style={styles.gearPrice}>
                                        <span style={{ color: '#d1d5db' }}>
                                            +₵{Math.floor(item.price * 0.4).toLocaleString()}
                                        </span>
                                        <button
                                            onClick={() => disassembleItem(item.inventoryId)}
                                            style={styles.disassembleBtn}
                                        >
                                            BREAK DOWN
                                        </button>
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}

const styles = {
    container: { minHeight: '100vh', background: '#0a0a1a', color: '#e0e0ff', fontFamily: 'monospace', padding: '20px' },
    header: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '20px', background: 'rgba(15, 23, 42, 0.85)', border: '1px solid rgba(148, 163, 184, 0.25)', borderRadius: '12px', marginBottom: '20px', flexWrap: 'wrap', gap: '15px' },
    backBtn: { padding: '8px 16px', background: 'rgba(148, 163, 184, 0.12)', border: '1px solid #d1d5db', color: '#d1d5db', borderRadius: '6px', cursor: 'pointer', fontFamily: 'monospace', fontWeight: 'bold' },
    title: { color: '#d1d5db', fontSize: '1.5rem', margin: 0, letterSpacing: '4px' },
    userBadge: { padding: '8px 16px', background: 'rgba(148, 163, 184, 0.08)', border: '1px solid #d1d5db', color: '#d1d5db', borderRadius: '6px', fontWeight: 'bold' },
    tabs: { display: 'flex', gap: '10px', marginBottom: '20px' },
    tab: { flex: 1, padding: '12px', background: 'rgba(0,0,0,0.3)', border: '1px solid rgba(148, 163, 184, 0.25)', color: '#8888cc', borderRadius: '8px', cursor: 'pointer', fontFamily: 'monospace', fontWeight: 'bold', letterSpacing: '2px' },
    tabActive: { background: 'rgba(148, 163, 184, 0.12)', borderColor: '#d1d5db', color: '#d1d5db' },
    errorBox: { padding: '12px', background: 'rgba(148, 163, 184, 0.08)', border: '1px solid #9ca3af', color: '#e5e7eb', borderRadius: '8px', marginBottom: '20px', fontSize: '0.85rem' },
    successBox: { padding: '12px', background: 'rgba(148, 163, 184, 0.08)', border: '1px solid #d1d5db', color: '#d1d5db', borderRadius: '8px', marginBottom: '20px', fontSize: '0.85rem' },
    loading: { textAlign: 'center', color: '#8888cc', fontSize: '0.85rem', padding: '30px' },
    grid: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px' },
    panel: { background: 'rgba(15, 23, 42, 0.85)', border: '1px solid rgba(148, 163, 184, 0.2)', borderRadius: '12px', padding: '20px' },
    panelTitle: { color: '#d1d5db', fontSize: '0.85rem', marginBottom: '15px', letterSpacing: '2px', marginTop: 0, borderBottom: '1px solid rgba(148, 163, 184, 0.2)', paddingBottom: '10px' },
    recipeList: { display: 'flex', flexDirection: 'column', gap: '10px', maxHeight: '600px', overflowY: 'auto' },
    recipeCard: { padding: '12px', border: '2px solid', borderRadius: '8px', cursor: 'pointer', transition: 'all 0.3s' },
    recipeHeader: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' },
    recipeName: { fontWeight: 'bold', fontSize: '0.9rem', color: '#e0e0ff' },
    tierBadge: { padding: '2px 8px', background: 'rgba(148, 163, 184, 0.12)', color: '#cbd5e1', borderRadius: '8px', fontSize: '0.65rem', fontWeight: 'bold' },
    recipeStats: { display: 'flex', gap: '10px', fontSize: '0.7rem', marginBottom: '6px' },
    recipeCost: { fontSize: '0.75rem', color: '#00ff88', fontWeight: 'bold' },
    detailCard: { padding: '20px', background: 'rgba(148, 163, 184, 0.06)', borderRadius: '10px', marginBottom: '20px', textAlign: 'center' },
    resultName: { color: '#d1d5db', fontSize: '1.2rem', marginBottom: '15px', marginTop: 0 },
    resultStats: { display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '10px' },
    resultStat: { padding: '10px', background: 'rgba(0,0,0,0.3)', borderRadius: '6px', textAlign: 'center' },
    statValue: { fontSize: '1rem', fontWeight: 'bold', color: '#d1d5db' },
    statLabel: { fontSize: '0.6rem', color: '#8888cc', marginTop: '4px' },
    sectionTitle: { color: '#d1d5db', fontSize: '0.75rem', marginTop: '20px', marginBottom: '10px', letterSpacing: '2px' },
    materialsList: { display: 'flex', flexDirection: 'column', gap: '8px' },
    materialItem: { padding: '10px 14px', border: '1px solid', borderRadius: '6px', display: 'flex', justifyContent: 'space-between', alignItems: 'center', fontSize: '0.8rem' },
    matName: { fontWeight: 'bold', color: '#e0e0ff' },
    matId: { fontSize: '0.6rem', color: '#8888cc' },
    creditsRow: { display: 'flex', justifyContent: 'space-between', padding: '8px 0', fontSize: '0.85rem' },
    craftBtn: { width: '100%', padding: '15px', background: '#d1d5db', color: '#111827', border: 'none', borderRadius: '8px', fontSize: '0.9rem', fontWeight: 'bold', cursor: 'pointer', fontFamily: 'monospace', letterSpacing: '2px', marginTop: '20px' },
    reasonBox: { padding: '10px', background: 'rgba(148, 163, 184, 0.08)', border: '1px solid #cbd5e1', color: '#d1d5db', borderRadius: '6px', fontSize: '0.75rem', marginTop: '10px', textAlign: 'center' },
    disassembleNote: { fontSize: '0.75rem', color: '#8888cc', marginBottom: '15px' },
    gearGrid: { display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '10px' },
    gearCard: { padding: '12px', background: 'rgba(0,0,0,0.3)', border: '1px solid rgba(148, 163, 184, 0.15)', borderRadius: '8px' },
    gearName: { fontWeight: 'bold', fontSize: '0.85rem', marginBottom: '4px' },
    gearMeta: { fontSize: '0.65rem', color: '#8888cc', marginBottom: '8px' },
    gearPrice: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', paddingTop: '8px', borderTop: '1px solid rgba(148, 163, 184, 0.12)' },
    disassembleBtn: { padding: '6px 12px', background: 'rgba(148, 163, 184, 0.12)', border: '1px solid #cbd5e1', color: '#d1d5db', borderRadius: '6px', cursor: 'pointer', fontFamily: 'monospace', fontWeight: 'bold', fontSize: '0.65rem' }
};