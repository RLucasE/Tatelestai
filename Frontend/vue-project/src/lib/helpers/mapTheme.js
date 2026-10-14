/**
 * Configuración y utilidades compartidas para mapas Leaflet con tema oscuro (Dark Theme)
 * utilizando Carto Basemaps y la paleta Dark Violet de Tatelestai.
 */
import L from 'leaflet'

export const DEFAULT_SALTA_COORDS = [-24.7885, -65.4105] // Salta Capital
export const DEFAULT_MAP_ZOOM = 14

export const CARTO_API_KEY = import.meta.env.VITE_CARTO_API_KEY || 'cb1_30sd_1_3fbfe2ae226659ca1a63373f'
export const CARTO_DARK_TILE_URL = `https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png?key=${CARTO_API_KEY}`

export const CARTO_ATTRIBUTION =
  '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/">CARTO</a>'

/**
 * Crea y retorna una instancia de L.tileLayer configurada con el tema oscuro de Carto.
 */
export function createDarkTileLayer(options = {}) {
  return L.tileLayer(CARTO_DARK_TILE_URL, {
    attribution: CARTO_ATTRIBUTION,
    maxZoom: 19,
    subdomains: 'abcd',
    ...options,
  })
}

/**
 * Crea un icono de marcador personalizado para selección de punto en el mapa
 */
export function createMapPinIcon({ color = '#7C3AED', size = 32 } = {}) {
  return L.divIcon({
    className: 'custom-map-pin',
    html: `
      <div style="
        position: relative;
        width: ${size}px;
        height: ${size}px;
        display: flex;
        align-items: center;
        justify-content: center;
      ">
        <div style="
          position: absolute;
          width: ${size}px;
          height: ${size}px;
          border-radius: 50%;
          background-color: ${color};
          opacity: 0.35;
          animation: map-pin-pulse 2s infinite ease-out;
        "></div>
        <div style="
          width: ${size - 6}px;
          height: ${size - 6}px;
          border-radius: 50%;
          background-color: ${color};
          border: 2.5px solid #FFFFFF;
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.6);
          display: flex;
          align-items: center;
          justify-content: center;
          color: #FFFFFF;
          z-index: 2;
        ">
          <svg width="${Math.round(size * 0.45)}" height="${Math.round(size * 0.45)}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
            <circle cx="12" cy="10" r="3"></circle>
          </svg>
        </div>
      </div>
    `,
    iconSize: [size, size],
    iconAnchor: [size / 2, size / 2],
  })
}
