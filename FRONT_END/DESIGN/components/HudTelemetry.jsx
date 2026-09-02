import { Zap, Activity, Coins, AlertTriangle } from "lucide-react";

/**
 * HudTelemetry
 * Persistent readout strip: current Bio-Capacity, Recovery Rate, and
 * available Credits, plus a compact Burnout Risk indicator that changes
 * color as risk climbs. Pure display component -- all values are derived
 * upstream in App.jsx from the active loadout.
 */
export default function HudTelemetry({
  combatantName,
  bioCapacity,
  bioCapacityMax,
  recoveryRate,
  credits,
  burnoutRisk,
}) {
  const riskTier =
    burnoutRisk >= 75 ? "crimson" : burnoutRisk >= 40 ? "amber" : "cyan";

  const riskLabel =
    riskTier === "crimson"
      ? "CRITICAL"
      : riskTier === "amber"
      ? "ELEVATED"
      : "STABLE";

  const riskColorClass = {
    cyan: "text-cyan",
    amber: "text-amber",
    crimson: "text-crimson",
  }[riskTier];

  const capacityPct = Math.round((bioCapacity / bioCapacityMax) * 100);

  return (
    <header className="hud-panel">
      <div className="hud-scanline" />
      <div className="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div className="flex items-center gap-3">
          <div className="h-9 w-9 shrink-0 rounded-sm border border-cyan/40 bg-slate-950 clip-corner-sm flex items-center justify-center">
            <Zap className="h-4 w-4 text-cyan" strokeWidth={2.5} />
          </div>
          <div>
            <p className="font-display text-lg font-semibold leading-none tracking-wide text-slate-50">
              {combatantName}
            </p>
            <p className="mt-1 font-data text-[11px] text-slate-500">
              Combatant Link // Active
            </p>
          </div>
        </div>

        <div className="grid grid-cols-2 gap-x-8 gap-y-3 sm:flex sm:items-center sm:gap-8">
          <Metric
            icon={<Zap className="h-3.5 w-3.5" />}
            label="Bio-Capacity"
            value={`${bioCapacity.toLocaleString()} J`}
            sub={`${capacityPct}% of ${bioCapacityMax.toLocaleString()} J`}
          />
          <Metric
            icon={<Activity className="h-3.5 w-3.5" />}
            label="Recovery"
            value={`${recoveryRate >= 0 ? "+" : ""}${recoveryRate} J/s`}
          />
          <Metric
            icon={<Coins className="h-3.5 w-3.5" />}
            label="Credits"
            value={credits.toLocaleString()}
          />
          <div className="flex items-center gap-2">
            <AlertTriangle
              className={`h-3.5 w-3.5 ${riskColorClass} ${
                riskTier === "crimson" ? "animate-pulse-crimson" : ""
              }`}
            />
            <div>
              <p className="font-data text-[10px] uppercase tracking-wider text-slate-500">
                Burnout Risk
              </p>
              <p className={`font-display text-sm font-semibold ${riskColorClass}`}>
                {burnoutRisk}% · {riskLabel}
              </p>
            </div>
          </div>
        </div>
      </div>
    </header>
  );
}

function Metric({ icon, label, value, sub }) {
  return (
    <div className="flex items-center gap-2">
      <span className="text-cyan/70">{icon}</span>
      <div>
        <p className="font-data text-[10px] uppercase tracking-wider text-slate-500">
          {label}
        </p>
        <p className="font-display text-sm font-semibold text-slate-100">
          {value}
        </p>
        {sub && <p className="font-data text-[10px] text-slate-500">{sub}</p>}
      </div>
    </div>
  );
}
