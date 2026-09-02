// FRONT_END/main.js
import {
    getCombatant,
    getGearCatalog,
    validateLoadout,
    purchaseGear,
    equipLoadout,
    getInventory,
    getApiBaseUrl
} from './REQUEST/api.js';

// ============================================
// STATE
// ============================================
const state = {
    combatant: null,
    catalog: [],
    inventory: [],
    loadout: {
        helmet: null,
        core: null,
        dampener: null,
        gauntlets: null,
        battery: null
    },
    currentTab: 'armory',
    isLoading: false,
    validationResult: null
};

// ============================================
// DOM REFS
// ============================================
const $ = (id) => document.getElementById(id);

const DOM = {
    // Profile
    combatantName: $('combatantName'),
    factionTag: $('factionTag'),
    bioCapacity: $('bioCapacity'),
    recoveryRate: $('recoveryRate'),
    riskLevel: $('riskLevel'),
    credits: $('credits'),

    // Telemetry
    telemetryFill: $('telemetryFill'),
    telemetryLabel: $('telemetryLabel'),
    telemetryWarnings: $('telemetryWarnings'),
    burnoutFill: $('burnoutFill'),
    burnoutLabel: $('burnoutLabel'),

    // Loadout
    loadoutSlots: $('loadoutSlots'),
    validateBtn: $('validateLoadoutBtn'),
    equipBtn: $('equipLoadoutBtn'),
    clearBtn: $('clearLoadoutBtn'),
    validationResult: $('validationResult'),

    // Marketplace
    marketplaceContent: $('marketplaceContent'),
    tabBtns: document.querySelectorAll('.tab-btn'),

    // Status
    connectionStatus: $('connectionStatus'),
};

// ============================================
// INITIALIZATION
// ============================================
async function init() {
    try {
        // Load combatant data
        await loadCombatant(1);

        // Load gear catalog
        await loadCatalog();

        // Load inventory
        await loadInventory(1);

        // Setup event listeners
        setupEventListeners();

        // Update connection status
        DOM.connectionStatus.textContent = 'CONNECTED ✅';

        console.log('✅ Aegis & Anarchy initialized');
        console.log(`📡 API Base URL: ${getApiBaseUrl()}`);
    } catch (error) {
        console.error('❌ Initialization error:', error);
        DOM.connectionStatus.textContent = 'OFFLINE ⚠️';
    }
}

// ============================================
// DATA LOADING
// ============================================
async function loadCombatant(id) {
    try {
        const combatant = await getCombatant(id);
        state.combatant = combatant;
        renderCombatant(combatant);

        // Load their existing loadout
        if (combatant.loadout) {
            state.loadout = combatant.loadout;
            renderLoadout();
        }

        return combatant;
    } catch (error) {
        console.error('Failed to load combatant:', error);
        throw error;
    }
}

async function loadCatalog() {
    try {
        const catalog = await getGearCatalog();
        state.catalog = catalog;
        renderMarketplace('armory');
        return catalog;
    } catch (error) {
        console.error('Failed to load catalog:', error);
        throw error;
    }
}

async function loadInventory(combatantId) {
    try {
        const inventory = await getInventory(combatantId);
        state.inventory = inventory;
        return inventory;
    } catch (error) {
        console.error('Failed to load inventory:', error);
        state.inventory = [];
        return [];
    }
}

// ============================================
// RENDER FUNCTIONS
// ============================================
function renderCombatant(combatant) {
    DOM.combatantName.textContent = combatant.name?.toUpperCase() || 'UNKNOWN';
    DOM.factionTag.textContent = combatant.faction?.toUpperCase() || 'NEUTRAL';
    DOM.factionTag.className = `faction-tag ${combatant.faction || 'neutral'}`;
    DOM.bioCapacity.textContent = combatant.bioCapacityMax || 0;
    DOM.recoveryRate.textContent = combatant.baseRecovery || 0;
    DOM.riskLevel.textContent = combatant.baseRisk || 0;
    DOM.credits.textContent = combatant.credits || 0;

    // Update telemetry
    updateTelemetry(combatant);
}

function renderLoadout() {
    const slots = ['helmet', 'core', 'dampener', 'gauntlets', 'battery'];

    slots.forEach(slot => {
        const slotElement = document.getElementById(`slot-${slot}`);
        const gear = state.loadout[slot];

        if (gear) {
            slotElement.innerHTML = `
                <div class="gear-item">
                    <span class="gear-name">${gear.name || gear.id}</span>
                    <div class="gear-stats">
                        <span>⚡ ${gear.bioCapacity || 0}</span>
                        <span>🔄 ${gear.recoveryRate || 0}</span>
                        <span>⚠️ ${gear.riskModifier || 0}</span>
                    </div>
                    <button class="remove-gear" data-slot="${slot}">✕ Remove</button>
                </div>
            `;
        } else {
            slotElement.innerHTML = `<span class="empty-slot">Empty</span>`;
        }
    });
}

function renderMarketplace(tab) {
    state.currentTab = tab;

    // Update tabs
    DOM.tabBtns.forEach(btn => {
        btn.classList.toggle('active', btn.dataset.tab === tab);
    });

    let items = [];
    const ownedIds = state.inventory.map(item => item.gearId || item.id);

    if (tab === 'armory') {
        items = state.catalog.filter(item => item.source === 'armory');
    } else if (tab === 'black-market') {
        items = state.catalog.filter(item => item.source === 'black-market');
    } else if (tab === 'inventory') {
        items = state.inventory;
    }

    if (items.length === 0) {
        DOM.marketplaceContent.innerHTML = `
            <div class="loading-spinner">No items available in ${tab.replace('-', ' ')}</div>
        `;
        return;
    }

    let html = '<div class="gear-grid">';

    items.forEach(item => {
        const isOwned = ownedIds.includes(item.gearId || item.id);
        const isEquipped = isOwned && state.inventory.some(
            inv => (inv.gearId === item.id || inv.id === item.id) && inv.equipped
        );

        if (tab === 'inventory') {
            // Inventory view
            html += `
                <div class="gear-card">
                    <div class="gear-name">${item.name}</div>
                    <div class="gear-meta">
                        <span>${item.slot?.toUpperCase() || 'UNKNOWN'}</span>
                        ${isEquipped ? '<span style="color: var(--success-color)">✅ EQUIPPED</span>' : ''}
                    </div>
                    <div class="gear-stats">
                        <span class="stat-bio">⚡ ${item.bioCapacity || 0}</span>
                        <span>🔄 ${item.recoveryRate || 0}</span>
                        <span class="stat-risk">⚠️ ${item.riskModifier || 0}</span>
                    </div>
                    <div class="gear-price">
                        <button class="purchase-btn" onclick="equipFromInventory('${item.gearId || item.id}')">
                            ${isEquipped ? 'EQUIPPED' : 'EQUIP'}
                        </button>
                    </div>
                </div>
            `;
        } else {
            // Marketplace view
            const canAfford = state.combatant?.credits >= item.price;

            html += `
                <div class="gear-card">
                    <div class="gear-name">${item.name}</div>
                    <div class="gear-meta">
                        <span>${item.slot?.toUpperCase() || 'UNKNOWN'}</span>
                        <span>${item.source?.toUpperCase() || 'UNKNOWN'}</span>
                    </div>
                    <div class="gear-stats">
                        <span class="stat-bio">⚡ ${item.bioCapacity || 0}</span>
                        <span>🔄 ${item.recoveryRate || 0}</span>
                        <span class="stat-risk">⚠️ ${item.riskModifier || 0}</span>
                    </div>
                    <div class="gear-price">
                        <span class="price">💰 ${item.price} credits</span>
                        ${isOwned ?
                    '<span class="owned-tag">✅ OWNED</span>' :
                    `<button class="purchase-btn" onclick="handlePurchase('${item.id}')" ${!canAfford ? 'disabled' : ''}>
                                ${canAfford ? 'PURCHASE' : 'INSUFFICIENT CREDITS'}
                            </button>`
                }
                    </div>
                </div>
            `;
        }
    });

    html += '</div>';
    DOM.marketplaceContent.innerHTML = html;
}

function updateTelemetry(combatant) {
    // Calculate telemetry based on combatant stats
    const bioCapacity = combatant.bioCapacityMax || 1000;
    const risk = combatant.baseRisk || 12;
    const recovery = combatant.baseRecovery || 3;

    // Calculate percentages
    const capacityPercent = Math.min(100, (bioCapacity / 2000) * 100);
    const riskPercent = Math.min(100, risk);
    const recoveryPercent = Math.min(100, (recovery / 10) * 100);

    // Update telemetry bar
    DOM.telemetryFill.style.width = `${capacityPercent}%`;
    DOM.telemetryLabel.textContent = `Bio Energy: ${Math.round(capacityPercent)}%`;

    // Update burnout gauge
    DOM.burnoutFill.style.width = `${riskPercent}%`;
    DOM.burnoutLabel.textContent = `${Math.round(riskPercent)}%`;

    // Update warnings
    let warnings = [];
    if (riskPercent > 70) {
        warnings.push('⚠️ CRITICAL: Extreme burnout risk detected!');
    } else if (riskPercent > 50) {
        warnings.push('⚠️ WARNING: Elevated burnout risk');
    } else if (riskPercent > 30) {
        warnings.push('⚠️ Moderate risk level');
    } else {
        warnings.push('✅ LOW RISK: System stable');
    }

    if (capacityPercent > 80) {
        warnings.push('⚡ High energy output');
    }

    DOM.telemetryWarnings.textContent = warnings.join(' | ');
}

// ============================================
// ACTIONS
// ============================================
async function handlePurchase(gearId) {
    if (!state.combatant) {
        alert('Combatant not loaded');
        return;
    }

    const gear = state.catalog.find(g => g.id === gearId);
    if (!gear) {
        alert('Gear not found');
        return;
    }

    if (state.combatant.credits < gear.price) {
        alert('Insufficient credits!');
        return;
    }

    try {
        const result = await purchaseGear(state.combatant.id, gearId);
        alert(`✅ Purchased ${gear.name}!`);

        // Refresh data
        await loadCombatant(state.combatant.id);
        await loadInventory(state.combatant.id);
        renderMarketplace(state.currentTab);

    } catch (error) {
        alert(`❌ Purchase failed: ${error.message}`);
        console.error('Purchase error:', error);
    }
}

async function validateCurrentLoadout() {
    if (!state.combatant) {
        alert('Combatant not loaded');
        return;
    }

    // Check if any slots are empty
    const slots = ['helmet', 'core', 'dampener', 'gauntlets', 'battery'];
    const hasAllSlots = slots.every(slot => state.loadout[slot] !== null);

    if (!hasAllSlots) {
        DOM.validationResult.className = 'validation-result warning';
        DOM.validationResult.textContent = '⚠️ Not all slots are filled. Please equip all 5 slots.';
        DOM.validationResult.style.display = 'block';
        return;
    }

    try {
        const result = await validateLoadout(state.combatant.id, state.loadout);
        state.validationResult = result;
        displayValidationResult(result);
    } catch (error) {
        DOM.validationResult.className = 'validation-result error';
        DOM.validationResult.textContent = `❌ Validation failed: ${error.message}`;
        DOM.validationResult.style.display = 'block';
    }
}

function displayValidationResult(result) {
    const status = result.isFatal ? 'fatal' : (result.isFeasible ? 'success' : 'error');
    const statusClass = status === 'fatal' ? 'error' : (status === 'success' ? 'success' : 'error');

    const warnings = result.warnings?.join('\n') || 'No warnings';
    const stats = result.stats || {};

    DOM.validationResult.className = `validation-result ${statusClass}`;
    DOM.validationResult.innerHTML = `
        <div><strong>${status === 'fatal' ? '❌ FATAL' : (status === 'success' ? '✅ FEASIBLE' : '⚠️ WARNING')}</strong></div>
        <div style="font-size: 0.65rem; margin-top: 5px; color: var(--text-secondary);">
            <div>Bio Capacity: ${stats.finalBioCapacity || 0}</div>
            <div>Energy Drain: ${stats.effectiveDrain || 0} kW</div>
            <div>Burnout Risk: ${stats.burnoutRisk || 0}%</div>
            <div style="margin-top: 4px; white-space: pre-line;">${warnings}</div>
        </div>
    `;
    DOM.validationResult.style.display = 'block';
}

async function equipCurrentLoadout() {
    if (!state.combatant) {
        alert('Combatant not loaded');
        return;
    }

    const slots = ['helmet', 'core', 'dampener', 'gauntlets', 'battery'];
    const hasAllSlots = slots.every(slot => state.loadout[slot] !== null);

    if (!hasAllSlots) {
        alert('⚠️ Please fill all 5 slots before equipping.');
        return;
    }

    // Validate first
    try {
        const validation = await validateLoadout(state.combatant.id, state.loadout);
        if (validation.isFatal) {
            alert('❌ FATAL: This loadout would exceed bio capacity!');
            displayValidationResult(validation);
            return;
        }
    } catch (error) {
        alert(`❌ Validation failed: ${error.message}`);
        return;
    }

    if (!confirm('Equip this loadout?')) return;

    try {
        await equipLoadout(state.combatant.id, state.loadout);
        alert('✅ Loadout equipped successfully!');
        await loadCombatant(state.combatant.id);
        renderLoadout();
    } catch (error) {
        alert(`❌ Failed to equip: ${error.message}`);
    }
}

function clearLoadout() {
    const slots = ['helmet', 'core', 'dampener', 'gauntlets', 'battery'];
    slots.forEach(slot => {
        state.loadout[slot] = null;
    });
    renderLoadout();
    DOM.validationResult.style.display = 'none';
}

function addGearToSlot(gear) {
    const slot = gear.slot;
    state.loadout[slot] = gear;
    renderLoadout();
    DOM.validationResult.style.display = 'none';
}

function removeGearFromSlot(slot) {
    state.loadout[slot] = null;
    renderLoadout();
    DOM.validationResult.style.display = 'none';
}

// ============================================
// EVENT LISTENERS
// ============================================
function setupEventListeners() {
    // Validation
    DOM.validateBtn.addEventListener('click', validateCurrentLoadout);

    // Equip
    DOM.equipBtn.addEventListener('click', equipCurrentLoadout);

    // Clear
    DOM.clearBtn.addEventListener('click', clearLoadout);

    // Remove gear from slot (event delegation)
    DOM.loadoutSlots.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-gear')) {
            const slot = e.target.dataset.slot;
            removeGearFromSlot(slot);
        }
    });

    // Tab switching
    DOM.tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            renderMarketplace(btn.dataset.tab);
        });
    });

    // Gear slot clicking - open marketplace filtered by slot
    document.querySelectorAll('.slot').forEach(slotEl => {
        slotEl.addEventListener('click', (e) => {
            // Ignore clicks on buttons inside
            if (e.target.closest('.remove-gear')) return;

            const slot = slotEl.dataset.slot;
            const itemsForSlot = state.catalog.filter(item => item.slot === slot);

            if (itemsForSlot.length === 0) {
                alert(`No gear available for ${slot} slot`);
                return;
            }

            // Show a quick selection dialog
            const options = itemsForSlot.map((item, index) =>
                `${index + 1}. ${item.name} (⚡${item.bioCapacity} ⚠️${item.riskModifier} 💰${item.price})`
            ).join('\n');

            const choice = prompt(
                `Select gear for ${slot.toUpperCase()} slot:\n\n${options}\n\nEnter number (0 to cancel):`
            );

            if (choice && !isNaN(choice)) {
                const idx = parseInt(choice) - 1;
                if (idx >= 0 && idx < itemsForSlot.length) {
                    addGearToSlot(itemsForSlot[idx]);
                }
            }
        });
    });
}

// ============================================
// GLOBAL FUNCTIONS (for inline onclick)
// ============================================
window.handlePurchase = handlePurchase;
window.equipFromInventory = async (gearId) => {
    // Find inventory item
    const item = state.inventory.find(inv => (inv.gearId || inv.id) === gearId);
    if (!item) return;

    const gear = state.catalog.find(g => g.id === (item.gearId || item.id));
    if (!gear) return;

    // Add to loadout
    addGearToSlot(gear);
};

// ============================================
// START
// ============================================
document.addEventListener('DOMContentLoaded', init);

console.log('🚀 Aegis & Anarchy Frontend Loaded');