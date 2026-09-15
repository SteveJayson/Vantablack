/** @type {import('tailwindcss').Config} */
export default {
  content: ["./index.html", "./FRONT_END/src/**/*.{js,jsx}", "./FRONT_END/DESIGN/**/*.{js,jsx}"],
  theme: {
    extend: {
      colors: {
        cyan: {
          DEFAULT: "#00f3ff",
          dim: "#0a8f99",
        },
        amber: {
          DEFAULT: "#ffaa00",
          dim: "#996600",
        },
        crimson: {
          DEFAULT: "#ff0055",
          dim: "#991a3d",
        },
        slate: {
          950: "#070a10",
          900: "#0c121b",
          800: "#131b27",
          700: "#1c2735",
        },
      },
      fontFamily: {
        display: ["'Rajdhani'", "sans-serif"],
        body: ["'Inter'", "sans-serif"],
        data: ["'JetBrains Mono'", "monospace"],
      },
      boxShadow: {
        "glow-cyan": "0 0 12px rgba(0, 243, 255, 0.45)",
        "glow-amber": "0 0 12px rgba(255, 170, 0, 0.45)",
        "glow-crimson": "0 0 16px rgba(255, 0, 85, 0.55)",
      },
      keyframes: {
        "pulse-crimson": {
          "0%, 100%": { opacity: 1, boxShadow: "0 0 8px rgba(255,0,85,0.6)" },
          "50%": { opacity: 0.75, boxShadow: "0 0 22px rgba(255,0,85,0.9)" },
        },
        scan: {
          "0%": { transform: "translateY(-100%)" },
          "100%": { transform: "translateY(100%)" },
        },
        flicker: {
          "0%, 100%": { opacity: 1 },
          "92%": { opacity: 1 },
          "93%": { opacity: 0.4 },
          "94%": { opacity: 1 },
        },
      },
      animation: {
        "pulse-crimson": "pulse-crimson 1.1s ease-in-out infinite",
        scan: "scan 4s linear infinite",
        flicker: "flicker 6s linear infinite",
      },
    },
  },
  plugins: [],
};
