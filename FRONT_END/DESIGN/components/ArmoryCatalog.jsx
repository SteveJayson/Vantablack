import { useMemo, useState } from "react";
import { ShoppingCart, Check, Skull, Store } from "lucide-react";
import { SLOT_DEFS } from "./GearCustomizer.jsx";

const SOURCE_TABS = [
  { key: "all", label: "All" },
  { key: "armory", label: "Armory", icon: Store },
  { key: "black-market", label: "Black Market", icon: Skull },
];

/**
 * ArmoryCatalog
 * Filterable grid of purchasable gear. Filters by slot and by source
 * (licensed Armory vs. off-books Black Market). Owned items show as
 * owned; everything else shows a buy action that checks against credits.
 */
export default function ArmoryCatalog({ catalog, ownedIds, credits, onPurchase, isPurchasing }) {
  const [slotFilter, setSlotFilter] = useState("all");
  const [sourceFilter, setSourceFilter] = useState("all");

  const filtered = useMemo(
    () =>
      catalog.filter(
        (item) =>
          (slotFilter === "all" || item.slot === slotFilter) &&
          (sourceFilter === "all" || item.source === sourceFilter)
      ),
    [catalog, slotFilter, sourceFilter]
  );

  return (
    <section className="hud-panel p-5">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 className="font-display text-sm font-semibold uppercase tracking-wider text-slate-300">
            Armory &amp; Black Market
          </h2>
          <p className="mt-1 font-body text-sm text-slate-500">
            {filtered.length} listing{filtered.length === 1 ? "" : "s"} available
          </p>
        </div>

        <div className="flex flex-wrap gap-1.5">
          {SOURCE_TABS.map((tab) => (
            <button
              key={tab.key}
              onClick={() => setSourceFilter(tab.key)}
              className={`flex items-center gap-1.5 rounded-sm border px-3 py-1.5 font-display text-xs font-semibold uppercase tracking-wide ${
                sourceFilter === tab.key
                  ? "border-cyan/50 bg-cyan/10 text-cyan"
                  : "border-slate-700 text-slate-400 hover:border-slate-600"
              }`}
            >
              {tab.icon && <tab.icon className="h-3.5 w-3.5" />}
              {tab.label}
            </button>
          ))}
        </div>
      </div>

      <div className="mt-4 flex flex-wrap gap-1.5">
        <button
          onClick={() => setSlotFilter("all")}
          className={`rounded-sm px-2.5 py-1 font-data text-[11px] uppercase tracking-wide ${
            slotFilter === "all"
              ? "bg-slate-700 text-slate-100"
              : "bg-slate-800/60 text-slate-500 hover:text-slate-300"
          }`}
        >
          All slots
        </button>
        {SLOT_DEFS.map((slot) => (
          <button
            key={slot.key}
            onClick={() => setSlotFilter(slot.key)}
            className={`rounded-sm px-2.5 py-1 font-data text-[11px] uppercase tracking-wide ${
              slotFilter === slot.key
                ? "bg-slate-700 text-slate-100"
                : "bg-slate-800/60 text-slate-500 hover:text-slate-300"
            }`}
          >
            {slot.label}
          </button>
        ))}
      </div>

      <div className="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
        {filtered.map((item) => {
          const owned = ownedIds.has(item.id);
          const affordable = credits >= item.price;
          const isBlackMarket = item.source === "black-market";

          return (
            <article
              key={item.id}
              className={`hud-panel flex flex-col justify-between p-4 ${
                isBlackMarket ? "border-crimson/25" : ""
              }`}
            >
              <div>
                <div className="flex items-start justify-between gap-2">
                  <p className="font-display text-sm font-semibold leading-snug text-slate-100">
                    {item.name}
                  </p>
                  {isBlackMarket && (
                    <Skull className="h-3.5 w-3.5 shrink-0 text-crimson/70" />
                  )}
                </div>
                <p className="mt-0.5 font-data text-[11px] uppercase tracking-wide text-slate-500">
                  {SLOT_DEFS.find((s) => s.key === item.slot)?.label}
                </p>

                <div className="mt-3 flex flex-wrap gap-x-3 gap-y-1 font-data text-[11px] text-slate-400">
                  <span>{item.bioCapacity >= 0 ? "+" : ""}{item.bioCapacity} J cap</span>
                  <span>{item.recoveryRate >= 0 ? "+" : ""}{item.recoveryRate} J/s</span>
                  <span className={item.riskModifier > 0 ? "text-amber" : "text-cyan"}>
                    {item.riskModifier >= 0 ? "+" : ""}
                    {item.riskModifier}% risk
                  </span>
                </div>
              </div>

              <div className="mt-4 flex items-center justify-between">
                <span className="font-display text-sm font-semibold text-slate-100">
                  {item.price.toLocaleString()} cr
                </span>

                {owned ? (
                  <span className="flex items-center gap-1 font-display text-xs font-semibold uppercase tracking-wide text-cyan">
                    <Check className="h-3.5 w-3.5" /> Owned
                  </span>
                ) : (
                  <button
                    type="button"
                    disabled={!affordable || isPurchasing === item.id}
                    onClick={() => onPurchase(item)}
                    className={`flex items-center gap-1.5 rounded-sm px-3 py-1.5 font-display text-xs font-semibold uppercase tracking-wide clip-corner-sm ${
                      affordable
                        ? "bg-cyan text-slate-950 hover:bg-cyan/85"
                        : "cursor-not-allowed bg-slate-800 text-slate-600"
                    }`}
                  >
                    <ShoppingCart className="h-3.5 w-3.5" />
                    {isPurchasing === item.id
                      ? "Processing..."
                      : affordable
                      ? "Purchase"
                      : "Insufficient credits"}
                  </button>
                )}
              </div>
            </article>
          );
        })}

        {filtered.length === 0 && (
          <p className="col-span-full py-8 text-center font-body text-sm text-slate-600">
            No listings match this filter. Widen the search.
          </p>
        )}
      </div>
    </section>
  );
}
