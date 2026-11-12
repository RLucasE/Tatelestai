---
version: "2.0"
name: "Tatelestai — Sunset Anime Light"
description: "Warm light-mode circular economy and food waste rescue marketplace design system inspired by golden hour sunset palettes (#F5EFEA canvas, #FFFFFF surfaces, #F24F13 Vermilion Flame brand accent, #F2921D Sunset Amber, and #46334F Midnight Plum typography)."
author: "Tatelestai Team"

colors:
  # Core Sunset Anime Light Palette Tokens
  primary: "#F24F13"            # Vermilion Flame - Main CTAs, active states, brand accent
  secondary: "#F2921D"          # Sunset Amber - Secondary actions, badges, highlights, savings
  tertiary: "#10B981"           # Emerald Green - Verified states, completed milestones
  neutral: "#F5EFEA"            # Warm Sand Canvas - Main background canvas

  # Structural Surfaces & Theme
  background: "#F5EFEA"         # Level 0 Canvas: Warm Sand / Arena Cálida
  surface: "#FFFFFF"            # Level 1 Primary cards & containers: Pure White
  surface-sunken: "#F2ECE4"     # Level 2 Sunken containers, inputs, secondary pills
  surface-elevated: "#FFF8F3"   # Level 3 Active cards, warm highlights, hover states
  surface-darkest: "#46334F"    # High-contrast Midnight Plum surface (selected place, dark badges)

  # Borders & Dividers
  border: "#DDD8E5"             # Subtle lavender-grey hairlines, card borders, dividers
  border-hover: "#F24F13"       # Interactive focus ring / Vermilion border highlight

  # Typography Colors
  text: "#46334F"               # Midnight Plum - Primary body & headings
  text-primary: "#46334F"       # Midnight Plum for strong titles and pricing
  text-secondary: "#8082A6"     # Twilight Lavender - Subtitles, secondary labels, addresses
  text-muted: "#A39BB5"         # Soft Lavender Grey - Muted captions, placeholders, strikethrough prices
  text-inverse: "#FFFFFF"       # Text on top of Vermilion or Midnight Plum surfaces

  # Semantic Feedback Tokens
  semantic:
    success: "#10B981"          # Emerald - Completed stage, verified status
    success-bg: "rgba(16, 185, 129, 0.14)"
    warning: "#F2921D"          # Sunset Amber - Pending review, closing soon, active rescue
    warning-bg: "rgba(242, 146, 29, 0.14)"
    danger: "#EF4444"           # Coral Red - Sold out, expired, rejected, critical alerts
    danger-bg: "rgba(239, 68, 68, 0.12)"
    info: "#8082A6"             # Twilight Lavender - Informational badges and neutral states
    info-bg: "rgba(128, 130, 166, 0.12)"

typography:
  fontFamily:
    sans: "'Inter', 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"
    heading: "'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif"
    mono: "'JetBrains Mono', monospace"
  scale:
    display: "32px / 700 / -0.02em"
    h1: "28px / 800 / -0.02em"
    h2: "22px / 800 / -0.015em"
    h3: "16px / 700 / -0.01em"
    price: "28px / 800 / -0.02em"
    body: "14px / 400 / 1.5"
    body-sm: "13px / 400 / 1.45"
    caption: "12px / 500 / 1.4"
    overline: "11px / 700 / 0.05em (UPPERCASE)"
    mono: "13px / 700 / JetBrains Mono"
    mono-lg: "20px / 700 / JetBrains Mono"

spacing:
  inline-xs: "4px"
  inline-sm: "6px"
  cluster: "8px"
  stack-sm: "10px"
  stack-card: "12px"
  card-pad: "16px"
  toolbar-row: "18px"
  grid-gap: "24px"
  page-top: "24px"
  section: "32px"
  section-lg: "40px"

radii:
  sm: "8px"
  md: "12px"
  lg: "16px"
  xl: "18px"
  "2xl": "22px"
  full: "9999px"

shadows:
  card: "0 10px 30px -5px rgba(70, 51, 79, 0.06), 0 4px 12px -2px rgba(70, 51, 79, 0.04)"
  elevated: "0 16px 36px -6px rgba(70, 51, 79, 0.10), 0 6px 16px -3px rgba(70, 51, 79, 0.06)"
  glow-vermilion: "0 8px 20px -4px rgba(242, 79, 19, 0.30)"
  glow-amber: "0 8px 20px -4px rgba(242, 146, 29, 0.28)"
---

# Tatelestai UI/UX Design System Specification (DESIGN.md)

> **Tatelestai** is a circular-economy food waste rescue marketplace connecting consumers with local bakeries, cafes, and restaurants to save surplus meals (surprise bags/packs) at reduced prices within strict pickup windows.
>
> **Core Aesthetic**: **Sunset Anime Light** — a warm, luminous, high-contrast editorial identity combining a Warm Ivory canvas (`#FAF7F2`), crisp white cards (`#FFFFFF`), Soft Sand sunken surfaces (`#F5EFEA`), Midnight Plum typography (`#46334F`), Twilight Lavender metadata (`#8082A6`), and vibrant Vermilion Flame (`#F24F13`) + Sunset Amber (`#F2921D`) accents.

---

## 1. Visual Identity & Brand System ("La Medialuna Minimalista")

*   **Logo Architecture**:
    *   **Isotipo**: Minimalist 3-segment geometric crescent ("La Medialuna Minimalista") combining `#F2921D` (outer segments) and `#F24F13` (center core).
    *   **Logotipo**: `Tatelestai` in `Plus Jakarta Sans ExtraBold` (`800`), tracking `-0.03em`, where the dot of the final `i` is replaced by a Vermilion solar circle (`#F24F13`).
    *   **Imagotipo**: Horizontal or vertical lockup of Isotipo + Logotipo, adaptable across `light` (`#46334F` text), `dark` (`#FAF7F2` text), and `Flame` (`#FFFFFF` monochrome) themes.
*   **Surface Hierarchy**:
    *   **Level 0 (Canvas)**: `#FAF7F2` (Warm Ivory) or `#F5EFEA` (Soft Sand).
    *   **Level 1 (Cards & Modals)**: `#FFFFFF` with `1px solid #DDD8E5` border and soft plum-tinted shadow.
    *   **Level 2 (Inputs & Sunken Blocks)**: `#F5EFEA` with `1px solid #DDD8E5`.
    *   **Level 3 (Selected / Highlighted Surface)**: `#FFF8F3` with `1.5px solid #F24F13` or `#F2921D`.

---

## 2. Seller Operational Experience (4 Main Views)

### 1. Panel Operativo (`/seller/dashboard`)
*   **Top Navigation (`SellerTopNavbar`)**: White bar (`#FFFFFF`, border `#DDD8E5`) with BrandLogo on the left and 4 pill navigation items (`Panel`, `Packs`, `Ventas`, `Mi Comercio`), notification bell, and user initials avatar (`#46334F` border).
*   **Validation Hero Banner**: Inline pickup code input pill (`#F5EFEA` border `#DDD8E5`), primary `Validar →` CTA (`#F24F13`), and secondary `Escanear QR` pill (`#46334F`).
*   **KPI Strip**: 3 clean typographic metrics (`RETIROS PENDIENTES` in `#F24F13`, `STOCK ACTIVO HOY` in `#46334F`, `INGRESOS DE HOY` in `#F2921D`).
*   **2-Column Operational Grid**:
    *   **Left (`Entregas de Hoy`)**: Filterable list of `PickupCard` components (`Pendientes`, `Entregados`) with expandable details and `✓ Entregar` button.
    *   **Right (`Mi Stock en Vivo`)**: Active offers rendered as `StockCard` components with progress bars (`#F2921D` / `#F24F13`) and `Ajustar` modal trigger.

### 2. Packs y Plantillas (`/seller/templates`)
*   **Unified 2-Column Workspace**:
    *   **Left (`Plantillas Maestras`)**: Search bar + `+ Nueva Plantilla` (`#F24F13`) + list of `TemplateCard` items with 1-click `Publicar Hoy`, `Editar`, and `Eliminar`.
    *   **Right (`Bolsas Publicadas`)**: Filter pills (`Todas`, `Activas`, `Programadas`, `Agotadas`) + live list of `StockCard` items connected to `EditActiveOfferModal`.

### 3. Ventas y Finanzas (`/seller/ventas`)
*   **5-Column Impact & Finance KPI Row**: `GANANCIA NETA (85%)` (`#F24F13`), `TOTAL BRUTO / COMISIÓN`, `BOLSAS RESCATADAS`, `COMIDA SALVADA` (`#F2921D`), and `TASA DE ENTREGA`.
*   **Charts & Breakdown Section**: 7-bar weekly net income chart (`#F2921D` regular days, `#F24F13` peak day) + horizontal progress breakdown by bag type.
*   **Sales History List**: Searchable, filterable list of `SellerSaleRowCard` rows displaying order metadata, bag items, 15% commission / 85% net split, and status pill.

### 4. Mi Comercio (`/seller/establishment`)
*   **Left (`Vitrina Pública de tu Comercio`)**: Live `EstablishmentHeroCardVertical` previewing cover image, category badge, rating, rescue metrics, and verification status.
*   **Right (`Datos del Establecimiento`)**: Inline form with name, category, address, and `ManualLocationMapBlock` (`LocationMapPicker` with `#F24F13` pin). Saving refreshes auth state and routes to `/waiting-confirmation`.

---

## 3. Seller Onboarding Flow (4 Screens)

1.  **Selección de Rol (`/select-role`)**: `OnboardingTopNavbar` + `RoleSelectorCard` with `Comprador` and `Vendedor` `RoleOptionCard`s.
2.  **Registro de Establecimiento (`/register-establishment`)**: `RequirementsGuideCard` + location mode toggle (`Google Places` vs `ManualLocationMapBlock`) + `DropZoneUploadBox` (`#FFF8F3`, dashed `#F2921D`) + `VerificationFileRow` list.
3.  **Espera de Confirmación (`/waiting-confirmation`)**: `WaitingConfirmationCard` (`#F2921D` border) with 3-stage `ReviewTimelineBox`.
4.  **Solicitud Observada / Rechazada (`/denied-confirmation`)**: `DeniedConfirmationCard` (`#EF4444` border) with `ObservationDetailsSection` and `Reiniciar registro` CTA.
