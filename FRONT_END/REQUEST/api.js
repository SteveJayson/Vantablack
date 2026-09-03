// FRONT_END/REQUEST/api.js
// API Configuration and Service Layer

// Base URL - change this to match your backend
const API_BASE_URL = import.meta?.env?.VITE_API_BASE_URL || 'http://localhost:8080/api';

// Auth token management
const getToken = () => localStorage.getItem('aegis_token');
const setToken = (token) => localStorage.setItem('aegis_token', token);

// Helper for API requests
const apiRequest = async (endpoint, options = {}) => {
  const url = `${API_BASE_URL}${endpoint}`;
  const token = getToken();

  const defaultOptions = {
    headers: {
      'Content-Type': 'application/json',
      ...(token && { 'Authorization': `Bearer ${token}` }),
    },
  };

  const config = {
    ...defaultOptions,
    ...options,
    headers: {
      ...defaultOptions.headers,
      ...options.headers,
    },
  };

  try {
    const response = await fetch(url, config);
    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || `HTTP error! status: ${response.status}`);
    }

    return data;
  } catch (error) {
    console.error('API Error:', error);
    throw error;
  }
};

// Health check for backend availability
export const checkBackendHealth = async (timeoutMs = 3000) => {
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), timeoutMs);
  const startTime = performance.now();

  try {
    const response = await fetch(`${API_BASE_URL}/health`, {
      method: 'GET',
      signal: controller.signal,
      headers: {
        'Accept': 'application/json',
      },
    });
    clearTimeout(timeoutId);

    const latency = Math.round(performance.now() - startTime);

    if (response.ok) {
      let data = {};
      try {
        data = await response.json();
      } catch (e) {
        data = { message: 'OK' };
      }
      return {
        online: true,
        latency,
        url: API_BASE_URL,
        message: data.message || 'API connected',
        data: data.data || data,
      };
    }

    return {
      online: false,
      latency,
      url: API_BASE_URL,
      error: `HTTP ${response.status}: ${response.statusText || 'Error'}`,
    };
  } catch (err) {
    clearTimeout(timeoutId);
    const latency = Math.round(performance.now() - startTime);
    return {
      online: false,
      latency,
      url: API_BASE_URL,
      error: err.name === 'AbortError' ? 'Connection timed out (3s)' : (err.message || 'Server unreachable'),
    };
  }
};

// ============================================
// API ENDPOINTS
// ============================================

// 1. GET /combatant/:id
export const getCombatant = async (id = 1) => {
  try {
    const response = await apiRequest(`/combatants/${id}`);
    // Handle both raw and wrapped responses
    const combatant = response.data?.combatant || response.data || response;
    return combatant;
  } catch (error) {
    console.error('Failed to fetch combatant:', error);
    // Return fallback demo data
    return getFallbackCombatant();
  }
};

// 2. GET /gear/catalog
export const getGearCatalog = async (filters = {}) => {
  try {
    const queryString = new URLSearchParams(filters).toString();
    const endpoint = `/gear${queryString ? `?${queryString}` : ''}`;
    const response = await apiRequest(endpoint);
    // Handle both raw array and wrapped response
    const catalog = response.data?.catalog || response.data || response;
    return Array.isArray(catalog) ? catalog : [];
  } catch (error) {
    console.error('Failed to fetch gear catalog:', error);
    return getFallbackGearCatalog();
  }
};

// 3. POST /loadout/validate
export const validateLoadout = async (combatantId, loadout) => {
  try {
    const response = await apiRequest('/loadouts/validate', {
      method: 'POST',
      body: JSON.stringify({ combatantId, loadout })
    });
    return response.data || response;
  } catch (error) {
    console.error('Failed to validate loadout:', error);
    // Return fallback validation
    return getFallbackValidation(loadout);
  }
};

// 4. POST /gear/purchase
export const purchaseGear = async (combatantId, gearId, source) => {
  try {
    const response = await apiRequest('/marketplace/purchase', {
      method: 'POST',
      body: JSON.stringify({ combatantId, gearId, source })
    });
    return response.data || response;
  } catch (error) {
    console.error('Failed to purchase gear:', error);
    throw error;
  }
};

// 5. POST /loadout/equip
export const equipLoadout = async (combatantId, loadout) => {
  try {
    const response = await apiRequest('/loadouts/equip', {
      method: 'POST',
      body: JSON.stringify({ combatantId, loadout })
    });
    return response.data || response;
  } catch (error) {
    console.error('Failed to equip loadout:', error);
    throw error;
  }
};

// 6. GET /marketplace/inventory/:id
export const getInventory = async (combatantId) => {
  try {
    const response = await apiRequest(`/marketplace/inventory/${combatantId}`);
    return response.data?.inventory || response.data || [];
  } catch (error) {
    console.error('Failed to fetch inventory:', error);
    return [];
  }
};

// 7. POST /marketplace/sell
export const sellGear = async (combatantId, inventoryId) => {
  try {
    const response = await apiRequest('/marketplace/sell', {
      method: 'POST',
      body: JSON.stringify({ combatantId, inventoryId })
    });
    return response.data || response;
  } catch (error) {
    console.error('Failed to sell gear:', error);
    throw error;
  }
};

// ============================================
// FALLBACK DATA (when backend is unreachable)
// ============================================

const getFallbackCombatant = () => ({
  id: 1,
  name: 'Vantablack',
  bioCapacityMax: 1400,
  baseRecovery: 3,
  baseRisk: 12,
  credits: 8200,
  faction: 'hero',
  clearanceLevel: 3,
  loadout: {
    helmet: { id: 'h3-01', name: 'Cerebral Interface Helmet', bioCapacity: 200, recoveryRate: 3, riskModifier: -10 },
    core: { id: 'c3-01', name: 'Fusion Core', bioCapacity: 400, recoveryRate: 5, riskModifier: -15 },
    dampener: { id: 'd2-01', name: 'Energy Dampener V2', bioCapacity: 80, recoveryRate: 6, riskModifier: -12 },
    gauntlets: { id: 'g3-01', name: 'Energy Blade Gauntlets', bioCapacity: 180, recoveryRate: 3, riskModifier: -8 },
    battery: { id: 'b3-01', name: 'Quantum Battery', bioCapacity: 250, recoveryRate: 2, riskModifier: 5 }
  }
});

const getFallbackGearCatalog = () => [
  // Helmet (Tier 1-5)
  { id: 'h1-01', slot: 'helmet', source: 'armory', name: 'Standard Issue Helmet', price: 500, bioCapacity: 50, recoveryRate: 1, riskModifier: -2 },
  { id: 'h2-01', slot: 'helmet', source: 'armory', name: 'Tactical Command Helmet', price: 1200, bioCapacity: 120, recoveryRate: 2, riskModifier: -5 },
  { id: 'h3-01', slot: 'helmet', source: 'armory', name: 'Cerebral Interface Helmet', price: 2500, bioCapacity: 200, recoveryRate: 3, riskModifier: -10 },
  { id: 'h4-01', slot: 'helmet', source: 'armory', name: 'Psi-Shield Helmet', price: 5000, bioCapacity: 350, recoveryRate: 4, riskModifier: -15 },
  { id: 'h5-01', slot: 'helmet', source: 'black-market', name: 'Chronos Helmet', price: 10000, bioCapacity: 500, recoveryRate: 5, riskModifier: -25 },

  // Core
  { id: 'c1-01', slot: 'core', source: 'armory', name: 'Basic Core Unit', price: 800, bioCapacity: 100, recoveryRate: 2, riskModifier: -3 },
  { id: 'c2-01', slot: 'core', source: 'armory', name: 'Enhanced Core Unit', price: 2000, bioCapacity: 250, recoveryRate: 4, riskModifier: -8 },
  { id: 'c3-01', slot: 'core', source: 'armory', name: 'Fusion Core', price: 4000, bioCapacity: 400, recoveryRate: 5, riskModifier: -15 },
  { id: 'c4-01', slot: 'core', source: 'armory', name: 'Antimatter Core', price: 8000, bioCapacity: 600, recoveryRate: 6, riskModifier: -25 },
  { id: 'c5-01', slot: 'core', source: 'black-market', name: 'Singularity Core', price: 15000, bioCapacity: 800, recoveryRate: 8, riskModifier: -40 },
  { id: 'cor-02', slot: 'core', source: 'black-market', name: 'Ferrox Overclock Weave', price: 3400, bioCapacity: 420, recoveryRate: -1, riskModifier: 22 },

  // Dampener
  { id: 'd1-01', slot: 'dampener', source: 'armory', name: 'Energy Dampener V1', price: 600, bioCapacity: 30, recoveryRate: 3, riskModifier: -5 },
  { id: 'd2-01', slot: 'dampener', source: 'armory', name: 'Energy Dampener V2', price: 1500, bioCapacity: 80, recoveryRate: 6, riskModifier: -12 },
  { id: 'd3-01', slot: 'dampener', source: 'armory', name: 'Quantum Dampener', price: 3500, bioCapacity: 150, recoveryRate: 8, riskModifier: -20 },
  { id: 'd4-01', slot: 'dampener', source: 'armory', name: 'Void Dampener', price: 7000, bioCapacity: 250, recoveryRate: 10, riskModifier: -30 },
  { id: 'd5-01', slot: 'dampener', source: 'black-market', name: 'Nexus Dampener', price: 12000, bioCapacity: 350, recoveryRate: 12, riskModifier: -50 },

  // Gauntlets
  { id: 'g1-01', slot: 'gauntlets', source: 'armory', name: 'Starter Gauntlets', price: 400, bioCapacity: 40, recoveryRate: 1, riskModifier: -1 },
  { id: 'g2-01', slot: 'gauntlets', source: 'armory', name: 'Combat Gauntlets MK2', price: 1000, bioCapacity: 100, recoveryRate: 2, riskModifier: -3 },
  { id: 'g3-01', slot: 'gauntlets', source: 'armory', name: 'Energy Blade Gauntlets', price: 2800, bioCapacity: 180, recoveryRate: 3, riskModifier: -8 },
  { id: 'g4-01', slot: 'gauntlets', source: 'armory', name: 'Titan Gauntlets', price: 6000, bioCapacity: 300, recoveryRate: 4, riskModifier: -12 },
  { id: 'g5-01', slot: 'gauntlets', source: 'black-market', name: 'Phantom Gauntlets', price: 11000, bioCapacity: 400, recoveryRate: 5, riskModifier: -20 },

  // Battery
  { id: 'b1-01', slot: 'battery', source: 'armory', name: 'Standard Battery', price: 300, bioCapacity: 60, recoveryRate: 0, riskModifier: 0 },
  { id: 'b2-01', slot: 'battery', source: 'armory', name: 'High-Capacity Battery', price: 800, bioCapacity: 150, recoveryRate: 1, riskModifier: 2 },
  { id: 'b3-01', slot: 'battery', source: 'armory', name: 'Quantum Battery', price: 2000, bioCapacity: 250, recoveryRate: 2, riskModifier: 5 },
  { id: 'b4-01', slot: 'battery', source: 'armory', name: 'Infinite Battery', price: 5000, bioCapacity: 400, recoveryRate: 3, riskModifier: 10 },
  { id: 'b5-01', slot: 'battery', source: 'black-market', name: 'Void Battery', price: 9000, bioCapacity: 500, recoveryRate: 4, riskModifier: 15 },
];

const getFallbackValidation = (loadout) => ({
  isFeasible: true,
  isFatal: false,
  stats: {
    finalBioCapacity: 1000,
    finalRecovery: 5,
    finalRisk: 25,
    burnoutRisk: 25,
    energyDrain: 150,
    dampeningFactor: 30,
    effectiveDrain: 120,
    capacityRemaining: 880
  },
  warnings: ['✅ LOW RISK: Loadout is well within safe parameters'],
  loadout: loadout
});

// Export base URL for debugging
export const getApiBaseUrl = () => API_BASE_URL;