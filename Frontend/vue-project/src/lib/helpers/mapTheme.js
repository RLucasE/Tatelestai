/**
 * Configuración y utilidades compartidas para mapas Leaflet en Tatelestai.
 * Utiliza OpenStreetMap estándar: sin requerimiento de API Key, sin marcas de agua,
 * con visualización nítida de cuadras, calles, avenidas y alturas.
 */
import L from 'leaflet'

export const DEFAULT_SALTA_COORDS = [-24.7885, -65.4105] // Salta Capital Centro (Plaza 9 de Julio)
export const DEFAULT_MAP_ZOOM = 15

export const OSM_TILE_URL = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'
export const OSM_ATTRIBUTION =
  '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a> contributors'

/**
 * Crea y retorna una instancia de L.tileLayer configurada con OpenStreetMap estándar:
 * - Sin API Key ni restricciones de marcas de agua.
 * - Calles, cuadras, avenidas y manzanas 100% visibles y contrastadas.
 */
export function createMapTileLayer(options = {}) {
  return L.tileLayer(OSM_TILE_URL, {
    attribution: OSM_ATTRIBUTION,
    maxZoom: 19,
    subdomains: ['a', 'b', 'c'],
    ...options,
  })
}

// Alias para mantener compatibilidad con componentes que importen createDarkTileLayer
export function createDarkTileLayer(options = {}) {
  return createMapTileLayer(options)
}

/**
 * Crea un icono de marcador personalizado para selección de punto en el mapa
 */
export function createMapPinIcon({ color = 'var(--color-brand)', size = 32 } = {}) {
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
          border: 2.5px solid var(--color-surface);
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.6);
          display: flex;
          align-items: center;
          justify-content: center;
          color: var(--color-surface);
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
