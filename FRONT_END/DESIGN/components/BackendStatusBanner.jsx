import { useState } from "react";
import {
  Wifi,
  WifiOff,
  RefreshCw,
  Server,
  ChevronDown,
  ChevronUp,
  Terminal,
  Database,
  CheckCircle2,
  AlertTriangle,
  X
} from "lucide-react";

/**
 * BackendStatusBanner
 * A cyberpunk-styled tactical telemetry banner informing the user
 * about the PHP Backend API status (Online, Offline, or Checking).
 */
export default function BackendStatusBanner({
  backendStatus,
  onRetry,
  isChecking
}) {
  const [showInstructions, setShowInstructions] = useState(false);
  const [isMinimized, setIsMinimized] = useState(false);

  // If status is not determined yet
  if (!backendStatus || backendStatus.online === null) {
    return (
      <div className="flex items-center justify-between rounded-sm border border-cyan/20 bg-slate-900/80 px-4 py-2.5 font-data text-xs text-cyan">
        <div className="flex items-center gap-2">
          <RefreshCw className="h-3.5 w-3.5 animate-spin text-cyan" />
          <span>INITIALIZING TELEMETRY // Pinging Backend API ({backendStatus?.url || "http://localhost:8080/api"})...</span>
        </div>
      </div>
    );
  }

  // If Backend is OFFLINE
  if (!backendStatus.online) {
    if (isMinimized) {
      return (
        <div className="flex items-center justify-between rounded-sm border border-crimson/40 bg-slate-950/90 px-4 py-2 text-xs font-data shadow-glow-crimson transition-all">
          <div className="flex items-center gap-2 text-crimson">
            <span className="relative flex h-2.5 w-2.5">
              <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-crimson opacity-75"></span>
              <span className="relative inline-flex h-2.5 w-2.5 rounded-full bg-crimson"></span>
            </span>
            <span className="font-semibold uppercase tracking-wider">
              Backend Offline // Sim Mode Active
            </span>
            <span className="text-slate-500 hidden sm:inline">({backendStatus.url})</span>
          </div>
          <div className="flex items-center gap-2">
            <button
              onClick={onRetry}
              disabled={isChecking}
              className="flex items-center gap-1 rounded-sm border border-cyan/40 bg-slate-900 px-2 py-1 text-[11px] font-semibold text-cyan hover:bg-cyan/10 disabled:opacity-50"
            >
              <RefreshCw className={`h-3 w-3 ${isChecking ? "animate-spin" : ""}`} />
              <span>Retry</span>
            </button>
            <button
              onClick={() => setIsMinimized(false)}
              className="text-slate-400 hover:text-slate-200 text-[11px] underline"
            >
              Expand Details
            </button>
          </div>
        </div>
      );
    }

    return (
      <div className="relative overflow-hidden rounded-sm border border-crimson/50 bg-slate-950/95 p-4 shadow-glow-crimson transition-all clip-corner-sm">
        <div className="hud-scanline opacity-10" />
        
        <div className="flex flex-col gap-3">
          {/* Header row */}
          <div className="flex flex-wrap items-center justify-between gap-2 border-b border-crimson/20 pb-2.5">
            <div className="flex items-center gap-2.5">
              <div className="flex h-7 w-7 items-center justify-center rounded-sm border border-crimson/60 bg-crimson/10 text-crimson">
                <WifiOff className="h-4 w-4 animate-pulse" />
              </div>
              <div>
                <div className="flex items-center gap-2">
                  <span className="font-display text-sm font-bold tracking-wider text-crimson">
                    BACKEND API SERVER OFFLINE
                  </span>
                  <span className="rounded bg-crimson/20 px-1.5 py-0.5 font-data text-[10px] uppercase font-semibold text-crimson">
                    Fallback Demo Mode
                  </span>
                </div>
                <p className="font-data text-[11px] text-slate-400">
                  Target: <span className="text-slate-300">{backendStatus.url}</span>
                  {backendStatus.error && (
                    <span className="text-crimson/80 ml-2">[{backendStatus.error}]</span>
                  )}
                </p>
              </div>
            </div>

            <div className="flex items-center gap-2">
              <button
                onClick={onRetry}
                disabled={isChecking}
                className="flex items-center gap-1.5 rounded-sm border border-cyan/50 bg-slate-900 px-3 py-1.5 font-display text-xs font-semibold text-cyan hover:bg-cyan/10 hover:border-cyan shadow-glow-cyan transition-all disabled:opacity-50"
                title="Ping backend server now"
              >
                <RefreshCw className={`h-3.5 w-3.5 ${isChecking ? "animate-spin" : ""}`} />
                <span>{isChecking ? "PINGING..." : "RETRY CONNECTION"}</span>
              </button>
              
              <button
                onClick={() => setIsMinimized(true)}
                className="p-1 text-slate-400 hover:text-slate-200"
                title="Minimize alert"
              >
                <X className="h-4 w-4" />
              </button>
            </div>
          </div>

          {/* Description */}
          <div className="flex flex-col gap-2 font-body text-xs text-slate-300">
            <p>
              The frontend is unable to reach the PHP Slim backend. All armory catalog browsing, stats calculation, and loadout configuration are running on <span className="font-semibold text-cyan">local client simulation data</span>.
            </p>

            <button
              onClick={() => setShowInstructions((prev) => !prev)}
              className="flex items-center gap-1 text-[11px] font-data text-cyan hover:underline w-fit"
            >
              <Terminal className="h-3.5 w-3.5" />
              <span>{showInstructions ? "Hide" : "Show"} How to Start the Backend Server</span>
              {showInstructions ? <ChevronUp className="h-3 w-3" /> : <ChevronDown className="h-3 w-3" />}
            </button>

            {showInstructions && (
              <div className="mt-1 rounded-sm border border-slate-700 bg-slate-900/90 p-3 font-data text-[11px] text-slate-300 space-y-2">
                <p className="text-amber font-semibold">⚡ Quick Start Options:</p>
                <div className="space-y-1 pl-2">
                  <p><span className="text-cyan font-bold">Option 1:</span> Run the Windows launcher batch script:</p>
                  <pre className="bg-slate-950 p-2 rounded text-emerald-400 select-all border border-slate-800">
                    start_all.bat  (or  cd backend &amp;&amp; start_backend.bat)
                  </pre>
                  <p className="pt-1"><span className="text-cyan font-bold">Option 2:</span> Start PHP Built-in Server manually:</p>
                  <pre className="bg-slate-950 p-2 rounded text-emerald-400 select-all border border-slate-800">
                    cd backend\CODE_PHP &amp;&amp; php -S localhost:8080 -t public
                  </pre>
                </div>
                <p className="text-slate-400 text-[10px] pt-1">
                  💡 Tip: Make sure Apache / MySQL is running in Laragon or XAMPP if database sync is required.
                </p>
              </div>
            )}
          </div>
        </div>
      </div>
    );
  }

  // If Backend is ONLINE
  return (
    <div className="flex flex-wrap items-center justify-between gap-2 rounded-sm border border-emerald-500/30 bg-slate-950/80 px-4 py-2 font-data text-xs shadow-[0_0_10px_rgba(16,185,129,0.15)]">
      <div className="flex items-center gap-2.5">
        <span className="relative flex h-2.5 w-2.5">
          <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
          <span className="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
        </span>
        <span className="font-display font-semibold tracking-wider text-emerald-400 uppercase">
          Neural Link Active // Backend API Connected
        </span>
        <span className="text-slate-400 hidden sm:inline">
          ({backendStatus.url} · {backendStatus.latency}ms)
        </span>
        {backendStatus.data?.database && (
          <span className="rounded bg-emerald-500/10 px-1.5 py-0.5 text-[10px] text-emerald-400 border border-emerald-500/20">
            DB: {backendStatus.data.database}
          </span>
        )}
      </div>

      <div className="flex items-center gap-2">
        <button
          onClick={onRetry}
          disabled={isChecking}
          className="flex items-center gap-1 text-[11px] text-slate-400 hover:text-cyan transition-colors"
          title="Re-verify backend link"
        >
          <RefreshCw className={`h-3 w-3 ${isChecking ? "animate-spin text-cyan" : ""}`} />
          <span className="hidden sm:inline">Ping</span>
        </button>
      </div>
    </div>
  );
}
