---
version: "alpha"
name: "Tatelestai"
description: "Dark-mode circular economy and food waste rescue marketplace design system preserving Tatelestai's signature dark violet and plum palette."
author: "Tatelestai Team"

colors:
  # Stitch Core Palette Tokens
  primary: "#7C3AED"            # Electric Violet - Main CTAs, active states, brand accent
  secondary: "#A78BFA"          # Light Violet / Lavender - Secondary actions, badges, tags
  tertiary: "#10B981"           # Emerald Green - Rescued food badge, environmental impact, savings
  neutral: "#1A1625"            # Deep Violet Dark - Main background canvas

  # Structural Surfaces & Theme
  background: "#1A1625"         # Signature canvas: Deep purple-slate
  surface: "#2D2438"            # Primary cards & containers: Dark plum
  surface-elevated: "#3D3450"   # Hover states, dropdowns, modals: Muted violet
  surface-darkest: "#0F0D15"    # Header navbar, bottom navigation: Ultra dark violet

  # Borders & Dividers
  border: "#4A4058"             # Subtle borders, dividers, outlines
  border-hover: "#7C3AED"       # Interactive focus ring / border highlight

  # Typography Colors
  text: "#E8EAF6"               # High-contrast soft lavender white (main body & headings)
  text-primary: "#FFFFFF"       # Pure white for strong titles and pricing
  text-secondary: "#A5A8C2"     # Secondary labels, establishment addresses, subtitles
  text-muted: "#787596"         # Muted captions, timestamps, placeholder text
  text-inverse: "#0F0D15"       # Text on top of light badges

  # Semantic Feedback Tokens
  semantic:
    success: "#10B981"          # Green - Verified, order ready, food rescued
    success-bg: "rgba(16, 185, 129, 0.15)"
    warning: "#F59E0B"          # Amber - Pickup window closing soon, limited stock
    warning-bg: "rgba(245, 158, 11, 0.15)"
    danger: "#EF4444"           # Red - Sold out, expired, critical alerts
    danger-bg: "rgba(239, 68, 68, 0.15)"
    info: "#3B82F6"             # Blue - Tips, location details
    info-bg: "rgba(59, 130, 246, 0.15)"

typography:
  fontFamily:
    sans: "'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"
    heading: "'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif"
    mono: "'JetBrains Mono', monospace"
  fontSize:
    xs: "0.75rem"      # 12px - Distance badges, timestamps
    sm: "0.875rem"     # 14px - Card metadata, secondary text, button sm
    base: "0.9375rem"  # 15px - Main body text (as in Tatelestai base.css)
    lg: "1.125rem"     # 18px - Offer card titles
    xl: "1.375rem"     # 22px - Section headers, modal titles
    "2xl": "1.75rem"   # 28px - Rescued price hero
    "3xl": "2.25rem"   # 36px - Metric highlights, main page titles
  fontWeight:
    normal: 400
    medium: 500
    semibold: 600
    bold: 700
  lineHeight:
    tight: 1.2
    normal: 1.6
    relaxed: 1.75

spacing:
  "0": "0px"
  "1": "4px"
  "2": "8px"
  "3": "12px"
  "4": "16px"
  "5": "20px"
  "6": "24px"
  "8": "32px"
  "10": "40px"
  "12": "48px"

radii:
  none: "0px"
  sm: "6px"
  md: "10px"
  lg: "14px"
  xl: "20px"
  "2xl": "24px"
  full: "9999px"

shadows:
  sm: "0 2px 4px rgba(15, 13, 21, 0.4)"
  md: "0 4px 12px rgba(15, 13, 21, 0.5)"
  lg: "0 8px 25px rgba(15, 13, 21, 0.6)"
  glow-violet: "0 0 20px -2px rgba(124, 58, 237, 0.45)"
  glow-emerald: "0 0 16px -2px rgba(16, 185, 129, 0.35)"
---

# Tatelestai UI/UX Design System Specification (DESIGN.md)

> **Tatelestai** is an eco-friendly food waste rescue marketplace connecting consumers with local bakeries, cafes, and restaurants to save surplus meals at affordable prices.
>
> **Core Aesthetic**: A distinctive, sleek, and immersive **Dark Violet & Plum identity** (`#1A1625` canvas, `#2D2438` cards, `#7C3AED` electric violet accents, `#E8EAF6` crisp soft text).

This specification acts as the source of truth for **Google Stitch** (`stitch.withgoogle.com`) and front-end implementation in **Vue 3 + Tailwind CSS**.

---

## 1. Visual Identity & Atmosphere

*   **Atmosphere**: Premium dark-mode marketplace. Sophisticated, modern, energetic, and clean—not a generic light app.
*   **Contrast & Depth**:
    *   **Level 0 (Canvas)**: `#1A1625` (Deep slate-violet).
    *   **Level 1 (Card & Containers)**: `#2D2438` (Plum/dark eggplant) with a 1px border of `#4A4058`.
    *   **Level 2 (Hover & Elevated Elements)**: `#3D3450` with subtle violet border glow (`#7C3AED`).
    *   **Level 3 (Top Navigation & Fixed Bars)**: `#0F0D15` (Ultra dark base with 80% opacity and backdrop blur).
*   **Signature Accent**: Electric Violet (`#7C3AED` / hover `#6D28D9` / light `#A78BFA`), accompanied by Emerald Green (`#10B981`) for food rescue indicators and Amber (`#F59E0B`) for countdown timers.

---

## 2. Component Blueprints for Google Stitch

### A. Consumer Offer Card (`CustomerCard`)
*   **Container**:
    *   Background: `#2D2438` (Dark plum).
    *   Border: 1px solid `#4A4058`.
    *   Radius: 14px (`rounded-xl`).
    *   Hover state: Translates up by 4px, background transitions to `#3D3450`, border becomes `#7C3AED` with a soft violet glow.
*   **Image Header**:
    *   16:9 photo banner of the food or establishment with smooth dark gradient overlay at the bottom.
    *   Top-Left Badge: Discount pill (`-50% OFF`) with emerald background (`#10B981`) and white text.
    *   Top-Right Badge: Scarcity indicator (`¡Solo quedan 2!`) in amber (`#F59E0B`).
*   **Body Content**:
    *   Establishment Name + Distance pill: Small avatar + Name (`#A5A8C2`) + Distance (`📍 800m` in `#7C3AED` pill).
    *   Offer Title: High contrast (`#FFFFFF`), bold font, max 2 lines.
    *   Description: Clean muted text (`#A5A8C2`), truncated with ellipsis.
    *   Pickup Schedule: Clock icon with time window: `🕒 Hoy, 19:30 - 20:45 hrs` highlighted with warm amber text.
*   **Price & Action Footer**:
    *   Original Price crossed out (`#787596`, line-through, text-sm).
    *   Rescue Price in large bold text (`#FFFFFF`, text-xl).
    *   Quick-add `+` button in Electric Violet (`#7C3AED`).

### B. Sticky Marketplace Header & Filter Bar
*   **Top Bar**:
    *   Background: `#0F0D15` with `backdrop-blur-md`.
    *   Border-bottom: 1px solid `#4A4058`.
    *   Logo: Tatelestai "T" icon with violet dot accent (`#7C3AED`) + text (`#E8EAF6`).
    *   Center Location Pill: `📍 Providencia • 3 km ▾` (`bg-[#2D2438] border border-[#4A4058] text-[#E8EAF6] rounded-full px-4 py-1.5`).
    *   Search Bar: Integrated input with dark plum background (`#2D2438`), violet focus ring, and clear button.
    *   Right icons: Notification bell, Cart button with violet badge, User avatar.
*   **Filter & Switcher Row**:
    *   Category Pills: `🌟 Todas`, `🛍️ Packs Sorpresa`, `🥐 Panaderías`, `🍱 Platos del Día`, `🥗 Vegano`, `🍰 Pastelería`. Inactive chips have `#2D2438` background; active chip is filled with `#7C3AED` and glowing shadow.
    *   View Toggle: Segmented button `[ 📋 Lista | 🗺️ Mapa ]` with smooth active slider.

### C. Interactive Dark Map View (`OffersMap`)
*   **Map Styling**: Dark Matter cartographic style matching the `#1A1625` canvas.
*   **Map Pins**: Violet pill markers showing the rescue price (`$3.500`). Selected pins enlarge and glow with emerald or bright violet border.
*   **Floating Bottom Card**: Swipeable carousel on mobile showing the selected shop's details, distance, and quick reservation button.

### D. Offer Detail Modal & Bottom Sheet (`OfferModal`)
*   **Backdrop**: `rgba(15, 13, 21, 0.75)` with blur.
*   **Modal Container**: `#2D2438` background, `#4A4058` border, rounded top on mobile (bottom sheet) and 20px radius on desktop.
*   **Hero Section**: Photo of the local shop with close button (X) and favorite heart.
*   **Pickup Time Notice**: Prominent banner with countdown: `⏳ Retiro disponible hoy de 20:00 a 21:00 (Faltan 45 min)`.
*   **Included Products Section**: Card list inside `#3D3450` containers with product names, quantities (`x2 Croissants`), and expiry dates.
*   **Sticky Bottom Purchase Bar**:
    *   Quantity Stepper: `[-]  1  [+]` with `#4A4058` borders.
    *   Total Price: `$3.990` (was `$9.500`).
    *   CTA Button: Full-width Electric Violet button (`bg-[#7C3AED] hover:bg-[#6D28D9] text-white font-bold py-3.5 rounded-xl shadow-lg shadow-purple-900/40`).

### E. Digital Pickup Pass (`PurchaseConfirmation`)
*   **Visual Style**: Digital boarding pass / coupon card in `#2D2438` with perforated dashed line divider.
*   **QR Code Section**: Crisp, high-contrast white card containing the 200x200px QR Code, readable in any store lighting.
*   **Backup Code**: Large monospace PIN: `PIN: 7 4 9 2 - B` with a one-touch copy button.
*   **Store Navigation**: Address with direct button `Abrir en Google Maps / Waze`.
*   **Status Indicator**: Pulsing green dot with `Listo para retiro en el local`.

### F. Seller Hub & Express Counter Validator
*   **Optimized for**: Fast tablet & mobile interaction for shop clerks during busy closing shifts.
*   **Prominent Button**: `📷 Escanear QR de Cliente` in electric violet with camera viewfinder.
*   **Quick Stock Steppers**: Instant stock adjustment `[- 3 +]` for each surplus offer without opening complex forms.
*   **Daily Metric Cards**:
    *   `Packs Salvados`: 14 / 15.
    *   `Ingresos Recuperados`: $42.000.
    *   `CO₂ Evitado`: 18.5 kg.

---

## 3. Stitch & AI Prompting Rules

1.  **Strict Color Faithfulness**:
    *   Never default to light white backgrounds unless specifically requested. Always generate with `#1A1625` as canvas, `#2D2438` as card surfaces, and `#7C3AED` as the primary action accent.
2.  **Typography Contrast**:
    *   Titles must be `#FFFFFF` or `#E8EAF6`.
    *   Subtitles must be `#A5A8C2`.
    *   Borders must use `#4A4058`.
3.  **Tailwind CSS Integration**:
    *   Generate code using Tailwind classes or arbitrary hex values (`bg-[#1a1625]`, `bg-[#2d2438]`, `bg-[#7c3aed]`, `text-[#e8eaf6]`, `border-[#4a4058]`).
