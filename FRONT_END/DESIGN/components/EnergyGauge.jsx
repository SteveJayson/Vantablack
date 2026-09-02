import { Flame, ShieldCheck, TriangleAlert } from "lucide-react";

/**
 * EnergyGauge
 * Visual bar reflecting projected burnout risk for the current loadout.
 * Transitions cyan -> amber -> pulsing crimson as risk climbs, and shows
 * a plain-language readout of what that means for the combatant.
 */
export default function EnergyGauge({ burnoutRisk }) {
  const clamped = Math.min(100, Math.max(0, burnoutRisk));

  const tier =
    clamped >= 75 ? "crimson" : clamped >= 40 ? "amber" : "cyan";

  const barColor = {
    cyan: "bg-cyan shadow-glow-cyan",
    amber: "bg-amber shadow-glow-amber",
    crimson: "bg-crimson shadow-glow-crimson animate-pulse-crimson",
  }[tier];

  const message = {
    cyan: {
      icon: <ShieldCheck className="h-4 w-4" />,
      title: "Metabolic load within safe bounds",
      body: "Dampeners are absorbing power draw faster than it accumulates. Cleared for extended combat use.",
    },
    amber: {
      icon: <TriangleAlert className="h-4 w-4" />,
      title: "Load climbing toward the redline",
      body: "Bio-Joule draw is outpacing dampener throughput. Add a Power Dampener or Bio-Battery before sustained engagement.",
    },
    crimson: {
      icon: <Flame className="h-4 w-4" />,
      title: "Cellular collapse imminent",
      body: "Current loadout cannot dissipate power draw. Disengage or swap gear immediately — this suit will burn out its wearer.",
    },
  }[tier];

  const textColor = {
    cyan: "text-cyan",
    amber: "text-amber",
    crimson: "text-crimson",
  }[tier];

  return (
    <section className="hud-panel p-5">
      <div className="flex items-center justify-between">
        <h2 className="font-display text-sm font-semibold uppercase tracking-wider text-slate-300">
          Burnout Risk
        </h2>
        <span className={`font-data text-sm font-semibold ${textColor}`}>
          {clamped}%
        </span>
      </div>

      <div className="mt-3 h-3 w-full overflow-hidden rounded-sm bg-slate-800">
        <div
          className={`h-full rounded-sm transition-all duration-500 ease-out ${barColor}`}
          style={{ width: `${clamped}%` }}
          role="progressbar"
          aria-valuenow={clamped}
          aria-valuemin={0}
          aria-valuemax={100}
          aria-label="Burnout risk"
        />
      </div>

      <div className={`mt-4 flex items-start gap-2.5 ${textColor}`}>
        {message.icon}
        <div>
          <p className="font-display text-sm font-semibold leading-tight text-slate-100">
            {message.title}
          </p>
          <p className="mt-1 font-body text-sm leading-snug text-slate-400">
            {message.body}
          </p>
        </div>
      </div>
    </section>
  );
}
