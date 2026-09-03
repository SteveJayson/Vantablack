import { useEffect, useMemo, useState, useCallback, useRef } from "react";
import HudTelemetry from "./components/HudTelemetry.jsx";
import EnergyGauge from "./components/EnergyGauge.jsx";
import GearCustomizer from "./components/GearCustomizer.jsx";
import ArmoryCatalog from "./components/ArmoryCatalog.jsx";
import BackendStatusBanner from "./components/BackendStatusBanner.jsx";
import {
  getCombatant,
  getGearCatalog,
  validateLoadout,
  purchaseGear,
  checkBackendHealth,
  getApiBaseUrl
} from "../REQUEST/api.js";

const COMBATANT_ID = "demo-001";

// Local fallback data. The app calls the real PHP endpoints first; if the
// backend isn't reachable (e.g. running this frontend standalone for a
// demo), it falls back to this so the UI is never empty.
const FALLBACK_COMBATANT = {
  name: "Vantablack",
  bioCapacityMax: 1400,
  baseRecovery: 3,
  baseRisk: 12,
  credits: 8200,
};

const FALLBACK_CATALOG = [
  { id: "hlm-01", slot: "helmet", source: "armory", name: "Aegis Mk.II Optic Helm", price: 900, bioCapacity: 40, recoveryRate: 0, riskModifier: 2 },
  { id: "hlm-02", slot: "helmet", source: "black-market", name: "Ghostline Neural Visor", price: 1750, bioCapacity: 60, recoveryRate: 1, riskModifier: 9 },
  { id: "cor-01", slot: "core", source: "armory", name: "Aegis Mk.II Exosuit Frame", price: 2200, bioCapacity: 260, recoveryRate: 1, riskModifier: 4 },
  { id: "cor-02", slot: "core", source: "black-market", name: "Ferrox Overclock Weave", price: 3400, bioCapacity: 420, recoveryRate: -1, riskModifier: 22 },
  { id: "dmp-01", slot: "dampener", source: "armory", name: "Standard Power Dampener", price: 1100, bioCapacity: 0, recoveryRate: 2, riskModifier: -15 },
  { id: "dmp-02", slot: "dampener", source: "armory", name: "Twin-Coil Dampener Array", price: 2600, bioCapacity: 0, recoveryRate: 4, riskModifier: -28 },
  { id: "gnt-01", slot: "gauntlets", source: "armory", name: "Conduit Gauntlets, Standard", price: 700, bioCapacity: 20, recoveryRate: 1, riskModifier: 3 },
  { id: "gnt-02", slot: "gauntlets", source: "black-market", name: "Redline Discharge Gauntlets", price: 1950, bioCapacity: 90, recoveryRate: -2, riskModifier: 18 },
  { id: "bat-01", slot: "battery", source: "armory", name: "Aegis Reserve Cell", price: 1300, bioCapacity: 300, recoveryRate: 1, riskModifier: 1 },
  { id: "bat-02", slot: "battery", source: "black-market", name: "Blackout Overcharge Cell", price: 2950, bioCapacity: 520, recoveryRate: -3, riskModifier: 24 },
];

const EMPTY_LOADOUT = { helmet: null, core: null, dampener: null, gauntlets: null, battery: null };
const BASE_BIO_CAPACITY = 100;

export default function App() {
  const [combatant, setCombatant] = useState(FALLBACK_COMBATANT);
  const [catalog, setCatalog] = useState(FALLBACK_CATALOG);
  const [inventory, setInventory] = useState([]);
  const [loadout, setLoadout] = useState(EMPTY_LOADOUT);
  const [credits, setCredits] = useState(FALLBACK_COMBATANT.credits);
  const [purchasingId, setPurchasingId] = useState(null);
  const [toast, setToast] = useState(null);

  // Backend Health Telemetry State
  const [backendStatus, setBackendStatus] = useState({
    online: null, // null = checking initially
    latency: null,
    url: getApiBaseUrl(),
    message: null,
    error: null,
    data: null,
    lastChecked: null,
  });
  const [isCheckingBackend, setIsCheckingBackend] = useState(false);
  const prevOnlineRef = useRef(null);

  // Verify backend health and sync real data if online
  const verifyBackend = useCallback(async (isManualRetry = false) => {
    setIsCheckingBackend(true);
    try {
      const health = await checkBackendHealth(3000);
      setBackendStatus({
        online: health.online,
        latency: health.latency ?? null,
        url: health.url,
        message: health.message ?? null,
        error: health.error ?? null,
        data: health.data ?? null,
        lastChecked: new Date(),
      });

      if (health.online) {
        // Backend is reachable - load live data from API
        try {
          const [combatantRes, catalogRes] = await Promise.all([
            getCombatant(COMBATANT_ID),
            getGearCatalog(),
          ]);
          setCombatant(combatantRes.combatant ?? combatantRes);
          setCatalog(catalogRes.catalog ?? catalogRes);
          setCredits((combatantRes.combatant ?? combatantRes).credits);
        } catch (e) {
          console.warn("Backend reachable but data fetch failed:", e);
        }

        // If backend was offline and just came online or manual retry succeeded
        if (prevOnlineRef.current === false || isManualRetry) {
          setToast(`⚡ Backend online (${health.latency}ms) — Live sync active`);
          window.setTimeout(() => setToast(null), 3000);
        }
      } else {
        if (isManualRetry) {
          setToast(`⚠️ Backend is offline (${health.error || "Unreachable"})`);
          window.setTimeout(() => setToast(null), 3500);
        }
      }
      prevOnlineRef.current = health.online;
    } catch (err) {
      setBackendStatus({
        online: false,
        latency: null,
        url: getApiBaseUrl(),
        error: err.message || "Connection failed",
        data: null,
        lastChecked: new Date(),
      });
      prevOnlineRef.current = false;
    } finally {
      setIsCheckingBackend(false);
    }
  }, []);

  // Initial check on mount + background polling every 10 seconds
  useEffect(() => {
    verifyBackend(false);

    const intervalId = setInterval(() => {
      verifyBackend(false);
    }, 10000);

    return () => clearInterval(intervalId);
  }, [verifyBackend]);

  const equippedItems = useMemo(
    () => Object.values(loadout).filter(Boolean),
    [loadout]
  );

  const bioCapacity = useMemo(
    () => equippedItems.reduce((sum, item) => sum + item.bioCapacity, BASE_BIO_CAPACITY),
    [equippedItems]
  );

  const recoveryRate = useMemo(
    () =>
      combatant.baseRecovery +
      equippedItems.reduce((sum, item) => sum + item.recoveryRate, 0),
    [equippedItems, combatant]
  );

  const burnoutRisk = useMemo(() => {
    const raw =
      combatant.baseRisk +
      equippedItems.reduce((sum, item) => sum + item.riskModifier, 0);
    return Math.min(100, Math.max(0, raw));
  }, [equippedItems, combatant]);

  // Best-effort server-side validation whenever the loadout changes.
  // Purely advisory here -- the client-computed gauge above is what
  // drives the UI, so a failed/offline validate call never blocks demo use.
  useEffect(() => {
    if (equippedItems.length === 0) return;
    validateLoadout(COMBATANT_ID, loadout).catch(() => {});
  }, [loadout]);

  function handleEquip(slotKey, item) {
    setLoadout((prev) => ({ ...prev, [slotKey]: item }));
  }

  function handleUnequip(slotKey) {
    setLoadout((prev) => ({ ...prev, [slotKey]: null }));
  }

  async function handlePurchase(item) {
    if (credits < item.price) return;
    setPurchasingId(item.id);

    try {
      await purchaseGear(COMBATANT_ID, item.id, item.source);
    } catch (err) {
      // Offline demo mode: proceed with an optimistic local purchase so
      // the flow is still demonstrable without a live backend.
    }

    setCredits((c) => c - item.price);
    setInventory((inv) => [...inv, item]);
    setPurchasingId(null);
    setToast(`${item.name} acquired`);
    window.setTimeout(() => setToast(null), 2600);
  }

  const ownedIds = useMemo(() => new Set(inventory.map((i) => i.id)), [inventory]);

  return (
    <div className="min-h-screen px-4 py-6 sm:px-8 sm:py-8">
      <div className="mx-auto flex max-w-6xl flex-col gap-5">
        <BackendStatusBanner
          backendStatus={backendStatus}
          onRetry={() => verifyBackend(true)}
          isChecking={isCheckingBackend}
        />

        <HudTelemetry
          combatantName={combatant.name}
          bioCapacity={bioCapacity}
          bioCapacityMax={combatant.bioCapacityMax}
          recoveryRate={recoveryRate}
          credits={credits}
          burnoutRisk={burnoutRisk}
          backendStatus={backendStatus}
          onRetryBackend={() => verifyBackend(true)}
          isCheckingBackend={isCheckingBackend}
        />

        <div className="grid grid-cols-1 gap-5 lg:grid-cols-[1.1fr_1.4fr]">
          <div className="flex flex-col gap-5">
            <EnergyGauge burnoutRisk={burnoutRisk} />
            <GearCustomizer
              loadout={loadout}
              inventory={inventory}
              onEquip={handleEquip}
              onUnequip={handleUnequip}
            />
          </div>

          <ArmoryCatalog
            catalog={catalog}
            ownedIds={ownedIds}
            credits={credits}
            onPurchase={handlePurchase}
            isPurchasing={purchasingId}
          />
        </div>
      </div>

      {toast && (
        <div className="fixed bottom-6 left-1/2 z-50 -translate-x-1/2 rounded-sm border border-cyan/40 bg-slate-900 px-5 py-3 font-display text-sm font-semibold text-cyan shadow-glow-cyan">
          {toast}
        </div>
      )}
    </div>
  );
}
