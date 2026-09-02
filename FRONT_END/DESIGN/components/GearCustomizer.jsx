import { useState } from "react";
import { HardHat, Shirt, Waves, Hand, BatteryCharging, X } from "lucide-react";

export const SLOT_DEFS = [
  { key: "helmet", label: "Helmet / HUD", icon: HardHat },
  { key: "core", label: "Core Exosuit", icon: Shirt },
  { key: "dampener", label: "Power Dampeners", icon: Waves },
  { key: "gauntlets", label: "Conduit Gauntlets", icon: Hand },
  { key: "battery", label: "Bio-Batteries", icon: BatteryCharging },
];

/**
 * GearCustomizer
 * Five-slot loadout editor. Each slot shows what's equipped (or empty)
 * and, on click, opens an inline picker listing owned gear for that slot
 * only. Selecting a piece equips it; the X unequips.
 */
export default function GearCustomizer({ loadout, inventory, onEquip, onUnequip }) {
  const [openSlot, setOpenSlot] = useState(null);

  return (
    <section className="hud-panel p-5">
      <h2 className="font-display text-sm font-semibold uppercase tracking-wider text-slate-300">
        Loadout Configuration
      </h2>
      <p className="mt-1 font-body text-sm text-slate-500">
        Five slots. Every swap re-runs the burnout projection instantly.
      </p>

      <div className="mt-4 flex flex-col divide-y divide-cyan/10">
        {SLOT_DEFS.map((slot) => {
          const equipped = loadout[slot.key];
          const owned = inventory.filter((g) => g.slot === slot.key);
          const isOpen = openSlot === slot.key;
          const Icon = slot.icon;

          return (
            <div key={slot.key} className="py-3">
              <div className="flex items-center gap-3">
                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-sm border border-cyan/25 bg-slate-950 clip-corner-sm">
                  <Icon
                    className={`h-4 w-4 ${equipped ? "text-cyan" : "text-slate-600"}`}
                  />
                </div>

                <div className="min-w-0 flex-1">
                  <p className="font-data text-[10px] uppercase tracking-wider text-slate-500">
                    {slot.label}
                  </p>
                  <p
                    className={`truncate font-display text-sm font-semibold ${
                      equipped ? "text-slate-100" : "text-slate-600"
                    }`}
                  >
                    {equipped ? equipped.name : "Slot empty"}
                  </p>
                </div>

                {equipped && (
                  <button
                    type="button"
                    onClick={() => onUnequip(slot.key)}
                    className="rounded-sm p-1.5 text-slate-500 hover:text-crimson"
                    aria-label={`Unequip ${equipped.name}`}
                  >
                    <X className="h-4 w-4" />
                  </button>
                )}

                <button
                  type="button"
                  onClick={() => setOpenSlot(isOpen ? null : slot.key)}
                  className="shrink-0 rounded-sm border border-cyan/30 px-3 py-1.5 font-display text-xs font-semibold uppercase tracking-wide text-cyan hover:bg-cyan/10"
                >
                  {isOpen ? "Close" : owned.length ? "Swap" : "None owned"}
                </button>
              </div>

              {equipped && (
                <div className="ml-[52px] mt-2 flex flex-wrap gap-3 font-data text-[11px] text-slate-500">
                  <span>{equipped.bioCapacity >= 0 ? "+" : ""}{equipped.bioCapacity} J cap</span>
                  <span>{equipped.recoveryRate >= 0 ? "+" : ""}{equipped.recoveryRate} J/s</span>
                  <span>
                    {equipped.riskModifier >= 0 ? "+" : ""}
                    {equipped.riskModifier}% risk
                  </span>
                </div>
              )}

              {isOpen && (
                <div className="ml-[52px] mt-3 flex flex-col gap-1.5">
                  {owned.length === 0 && (
                    <p className="font-body text-xs text-slate-600">
                      Nothing in inventory for this slot yet — check the Armory below.
                    </p>
                  )}
                  {owned.map((item) => (
                    <button
                      key={item.id}
                      type="button"
                      onClick={() => {
                        onEquip(slot.key, item);
                        setOpenSlot(null);
                      }}
                      className={`flex items-center justify-between rounded-sm border px-3 py-2 text-left transition-colors ${
                        equipped?.id === item.id
                          ? "border-cyan/50 bg-cyan/10"
                          : "border-slate-700 hover:border-cyan/30 hover:bg-slate-800/60"
                      }`}
                    >
                      <span className="font-display text-sm text-slate-100">
                        {item.name}
                      </span>
                      <span className="font-data text-[11px] text-slate-500">
                        {item.riskModifier >= 0 ? "+" : ""}
                        {item.riskModifier}% risk
                      </span>
                    </button>
                  ))}
                </div>
              )}
            </div>
          );
        })}
      </div>
    </section>
  );
}
