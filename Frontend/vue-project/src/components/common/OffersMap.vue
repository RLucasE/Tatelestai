<template>
  <div class="offers-map-wrapper">
    <div ref="mapContainer" class="map-container"></div>

    <!-- Indicador flotante dinámico de búsqueda en la zona -->
    <Transition name="fade-badge">
      <div v-if="isSearching" class="map-status-badge searching">
        <span class="spinner-dot"></span>
        Buscando ofertas en esta zona...
      </div>
      <div v-else-if="statusMessage" class="map-status-badge info">
        {{ statusMessage }}
      </div>
    </Transition>

    <div class="map-legend">
      <span v-if="userLat != null && userLng != null" class="legend-item">
        <span class="user-legend-dot"></span>
        Tu ubicación
        <span class="legend-separator">·</span>
      </span>
      <span class="legend-item">
        <span class="store-legend-dot"></span>
        Comercios con ofertas
      </span>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted, toRaw } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import axiosInstance from '@/lib/axios'
import { calculateDistance, formatDistance } from '@/lib/helpers/geo'

function createStoreIcon() {
  return L.divIcon({
    className: 'custom-store-pin',
    html: `
      <div class="store-pin-wrapper">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="42" viewBox="0 0 32 42" fill="none">
          <path d="M16 0C7.16344 0 0 7.16344 0 16C0 25.5 16 42 16 42C16 42 32 25.5 32 16C32 7.16344 24.8366 0 16 0Z" fill="#10b981"/>
          <circle cx="16" cy="15" r="6" fill="#ffffff"/>
        </svg>
      </div>
    `,
    iconSize: [32, 42],
    iconAnchor: [16, 42],
  })
}

function createUserIcon() {
  return L.divIcon({
    className: 'user-marker-icon',
    html: `
      <div class="user-marker-pulse"></div>
      <div class="user-marker-dot"></div>
    `,
    iconSize: [24, 24],
    iconAnchor: [12, 12],
  })
}

const props = defineProps({
  userLat: {
    type: Number,
    default: null,
  },
  userLng: {
    type: Number,
    default: null,
  },
  searchQuery: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['offer-selected'])

const mapContainer = ref(null)
let map = null
let userMarker = null
let offersLayerGroup = null
let offersById = {}

let moveDebounceTimer = null
let abortController = null
let statusTimer = null

const isSearching = ref(false)
const statusMessage = ref('')

const DEFAULT_CENTER = [-24.7885, -65.4105] // Salta Capital
const DEFAULT_ZOOM = 13

const POPUP_STYLES = `
  background-color: #2d2438;
  color: #e8eaf6;
  font-family: inherit;
  padding: 4px 0;
  min-width: 220px;
`

const POPUP_TITLE_STYLES = `
  font-weight: 600;
  font-size: 0.95rem;
  margin: 0 0 2px 0;
  color: #e8eaf6;
`

const POPUP_ADDRESS_STYLES = `
  font-size: 0.78rem;
  color: #a89ec0;
  margin: 0 0 8px 0;
`

const POPUP_OFFER_STYLES = `
  padding: 6px 0;
  border-top: 1px solid #3d3450;
`

const POPUP_OFFER_TITLE_STYLES = `
  font-size: 0.82rem;
  font-weight: 500;
  margin: 0 0 2px 0;
  color: #e8eaf6;
`

const POPUP_META_STYLES = `
  font-size: 0.75rem;
  color: #a89ec0;
  margin: 0 0 4px 0;
`

const POPUP_BTN_STYLES = `
  display: inline-block;
  background-color: #7c3aed;
  color: #fff;
  border: none;
  padding: 4px 12px;
  border-radius: 6px;
  font-size: 0.75rem;
  cursor: pointer;
  margin-top: 2px;
  font-weight: 500;
  transition: background-color 0.2s;
`

function groupOffersByLocation(offers) {
  const groups = {}
  for (const offer of offers) {
    if (offer.establishment_latitude == null || offer.establishment_longitude == null) continue
    const key = `${offer.establishment_latitude},${offer.establishment_longitude}`
    if (!groups[key]) {
      groups[key] = {
        lat: offer.establishment_latitude,
        lng: offer.establishment_longitude,
        name: offer.establishment_name || 'Comercio',
        address: offer.establishment_address || '',
        offers: [],
      }
    }
    groups[key].offers.push(offer)
  }
  return Object.values(groups)
}

function buildPopupContent(group) {
  const hasUserLocation = props.userLat != null && props.userLng != null

  const offerItems = group.offers.map((offer) => {
    let distanceMeta = ''
    if (hasUserLocation) {
      const dist = calculateDistance(props.userLat, props.userLng, group.lat, group.lng)
      distanceMeta = ` · ${formatDistance(dist)}`
    }

    let priceText = 'Ver detalle'
    if (offer.products && offer.products.length > 0 && offer.products[0].price != null) {
      priceText = `$${Number(offer.products[0].price).toLocaleString('es-AR')}`
    }

    return `
      <div style="${POPUP_OFFER_STYLES}">
        <p style="${POPUP_OFFER_TITLE_STYLES}">${escapeHtml(offer.title)}</p>
        <p style="${POPUP_META_STYLES}">${priceText}${distanceMeta}</p>
        <button style="${POPUP_BTN_STYLES}" data-offer-id="${offer.id}"
          onmouseover="this.style.backgroundColor='#6d28d9'"
          onmouseout="this.style.backgroundColor='#7c3aed'">
          Ver detalle
        </button>
      </div>
    `
  }).join('')

  return `
    <div style="${POPUP_STYLES}">
      <p style="${POPUP_TITLE_STYLES}">${escapeHtml(group.name)}</p>
      <p style="${POPUP_ADDRESS_STYLES}">${escapeHtml(group.address)}</p>
      ${offerItems}
    </div>
  `
}

function escapeHtml(text) {
  if (!text) return ''
  const div = document.createElement('div')
  div.textContent = text
  return div.innerHTML
}

function renderMarkers(offers) {
  if (!map || !offersLayerGroup) return

  offersLayerGroup.clearLayers()
  offersById = {}

  const groups = groupOffersByLocation(offers)

  for (const group of groups) {
    for (const offer of group.offers) {
      offersById[offer.id] = offer
    }

    const marker = L.marker([group.lat, group.lng], { icon: createStoreIcon() })
    marker.bindPopup(buildPopupContent(group), {
      maxWidth: 280,
      minWidth: 220,
      className: 'dark-popup',
    })
    offersLayerGroup.addLayer(marker)
  }
}

// Consulta de ofertas dinámicas dentro del área visible (Viewport / Bounding Box)
async function fetchOffersInViewport() {
  if (!map) return

  // Cancelar petición previa en vuelo
  if (abortController) {
    abortController.abort()
  }
  abortController = new AbortController()

  const bounds = map.getBounds()
  const center = bounds.getCenter()
  const northEast = bounds.getNorthEast()

  // Calcular radio que cubre la esquina de la caja de visión
  const radiusKm = Math.max(0.5, Number((center.distanceTo(northEast) / 1000).toFixed(2)))

  isSearching.value = true

  try {
    const params = {
      lat: center.lat,
      lng: center.lng,
      radius: radiusKm,
      per_page: 40,
    }

    if (props.searchQuery && props.searchQuery.trim() !== '') {
      params.search = props.searchQuery.trim()
    }

    const response = await axiosInstance.get('/offers', {
      params,
      signal: abortController.signal,
    })

    const offers = response.data?.data || []
    renderMarkers(offers)

    const count = offers.length
    statusMessage.value = count > 0
      ? `${count} ${count === 1 ? 'oferta encontrada' : 'ofertas encontradas'}`
      : 'No hay ofertas en esta zona'

    clearTimeout(statusTimer)
    statusTimer = setTimeout(() => {
      statusMessage.value = ''
    }, 3000)
  } catch (err) {
    // Ignorar cancelaciones causadas por nuevos movimientos del usuario
    if (err.name !== 'CanceledError' && err.code !== 'ERR_CANCELED') {
      console.error('Error al cargar ofertas del mapa:', err)
      statusMessage.value = 'Error al actualizar mapa'
      clearTimeout(statusTimer)
      statusTimer = setTimeout(() => {
        statusMessage.value = ''
      }, 3000)
    }
  } finally {
    isSearching.value = false
  }
}

function onMapMoveEnd() {
  clearTimeout(moveDebounceTimer)
  moveDebounceTimer = setTimeout(() => {
    fetchOffersInViewport()
  }, 350)
}

function initMap() {
  if (!mapContainer.value) return

  const initialCenter = (props.userLat != null && props.userLng != null)
    ? [props.userLat, props.userLng]
    : DEFAULT_CENTER

  map = L.map(mapContainer.value, {
    center: initialCenter,
    zoom: DEFAULT_ZOOM,
  })

  const cartoApiKey = import.meta.env.VITE_CARTO_API_KEY || 'cb1_30sd_1_3fbfe2ae226659ca1a63373f'
  const tileUrl = `https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png?key=${cartoApiKey}`

  L.tileLayer(tileUrl, {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/">CARTO</a>',
    maxZoom: 19,
  }).addTo(map)

  // Capa para los marcadores de comercios
  offersLayerGroup = L.layerGroup().addTo(map)

  // Marcador del usuario si tiene ubicación activa
  updateUserMarker()

  // Escuchar cuando el usuario termina de mover/arrastrar/zoom en el mapa
  map.on('moveend', onMapMoveEnd)

  // Delegar clics en popups
  map.on('popupopen', (e) => {
    const container = e.popup.getElement()
    if (!container) return

    const buttons = container.querySelectorAll('button[data-offer-id]')
    buttons.forEach((btn) => {
      btn.addEventListener('click', (event) => {
        const offerId = event.currentTarget.getAttribute('data-offer-id')
        const offer = offersById[offerId] || offersById[Number(offerId)]
        if (offer) {
          emit('offer-selected', toRaw(offer))
        }
      })
    })
  })

  // Carga inicial en la vista actual
  setTimeout(() => {
    if (map) {
      map.invalidateSize()
      fetchOffersInViewport()
    }
  }, 150)
}

function updateUserMarker() {
  if (!map) return

  if (props.userLat != null && props.userLng != null) {
    if (userMarker) {
      userMarker.setLatLng([props.userLat, props.userLng])
    } else {
      userMarker = L.marker([props.userLat, props.userLng], {
        icon: createUserIcon(),
        zIndexOffset: 1000,
      }).addTo(map)
      userMarker.bindTooltip('Tu ubicación', {
        permanent: false,
        direction: 'top',
        offset: [0, -10],
      })
    }
  } else if (userMarker) {
    map.removeLayer(userMarker)
    userMarker = null
  }
}

// Reactividad a cambios en la búsqueda
watch(
  () => props.searchQuery,
  () => {
    fetchOffersInViewport()
  }
)

// Reactividad a cambios de ubicación de usuario
watch(
  () => [props.userLat, props.userLng],
  ([newLat, newLng]) => {
    updateUserMarker()
    if (map && newLat != null && newLng != null) {
      map.setView([newLat, newLng], map.getZoom())
    }
  }
)

onMounted(() => {
  initMap()
})

onUnmounted(() => {
  clearTimeout(moveDebounceTimer)
  clearTimeout(statusTimer)
  if (abortController) {
    abortController.abort()
  }

  if (map) {
    map.off()
    map.remove()
    map = null
    userMarker = null
    offersLayerGroup = null
    offersById = {}
  }
})
</script>

<style scoped>
.offers-map-wrapper {
  position: relative;
  width: 100%;
}

.map-container {
  width: 100%;
  height: 520px;
  border-radius: 14px;
  overflow: hidden;
  border: 1px solid var(--color-secondary);
  background-color: var(--color-bg);
}

:deep(.custom-store-pin) {
  background: none !important;
  border: none !important;
}

:deep(.store-pin-wrapper) {
  filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.5));
  transition: transform 0.15s ease;
  cursor: pointer;
  display: flex;
  justify-content: center;
  align-items: center;
}

:deep(.store-pin-wrapper:hover) {
  transform: scale(1.18);
}

:deep(.leaflet-control-attribution) {
  background-color: rgba(26, 22, 37, 0.8) !important;
  color: var(--color-text-secondary) !important;
}

:deep(.leaflet-control-attribution a) {
  color: var(--color-accent-light) !important;
}

:deep(.leaflet-bar a) {
  background-color: var(--color-primary) !important;
  color: var(--color-text) !important;
  border-bottom: 1px solid var(--color-secondary) !important;
}

:deep(.leaflet-bar a:hover) {
  background-color: var(--color-focus) !important;
}

/* Badge flotante de estado en el centro superior del mapa */
.map-status-badge {
  position: absolute;
  top: 14px;
  left: 50%;
  transform: translateX(-50%);
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background-color: rgba(26, 22, 37, 0.92);
  border: 1px solid var(--color-secondary);
  border-radius: 20px;
  padding: 6px 16px;
  font-size: 0.8rem;
  font-weight: 500;
  color: var(--color-text);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.5);
  z-index: 1000;
  pointer-events: none;
  backdrop-filter: blur(6px);
}

.map-status-badge.searching {
  border-color: var(--color-accent);
  color: var(--color-accent-light);
}

.spinner-dot {
  width: 8px;
  height: 8px;
  background-color: var(--color-accent);
  border-radius: 50%;
  animation: pulse-spinner 1.2s infinite ease-in-out;
}

@keyframes pulse-spinner {
  0%, 100% {
    transform: scale(0.6);
    opacity: 0.4;
  }
  50% {
    transform: scale(1.3);
    opacity: 1;
  }
}

.fade-badge-enter-active,
.fade-badge-leave-active {
  transition: all 0.25s ease;
}

.fade-badge-enter-from,
.fade-badge-leave-to {
  opacity: 0;
  transform: translate(-50%, -10px);
}

/* Leyenda inferior */
.map-legend {
  position: absolute;
  bottom: 12px;
  right: 12px;
  display: flex;
  align-items: center;
  gap: 8px;
  background-color: rgba(26, 22, 37, 0.9);
  border: 1px solid var(--color-secondary);
  border-radius: 8px;
  padding: 6px 14px;
  font-size: 0.75rem;
  color: var(--color-text-secondary);
  z-index: 500;
  pointer-events: none;
  backdrop-filter: blur(4px);
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 6px;
}

.user-legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background-color: #3b82f6;
  border: 1.5px solid #fff;
  box-shadow: 0 0 4px rgba(59, 130, 246, 0.8);
}

.store-legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background-color: #10b981;
  border: 1.5px solid #fff;
  box-shadow: 0 0 4px rgba(16, 185, 129, 0.8);
}

.legend-separator {
  color: var(--color-focus);
  margin-left: 2px;
}

/* Estilos para el marcador del usuario */
:deep(.user-marker-icon) {
  background: none !important;
  border: none !important;
}

:deep(.user-marker-dot) {
  width: 14px;
  height: 14px;
  background-color: #3b82f6;
  border: 3px solid #fff;
  border-radius: 50%;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  box-shadow: 0 0 6px rgba(59, 130, 246, 0.6);
  z-index: 2;
}

:deep(.user-marker-pulse) {
  width: 24px;
  height: 24px;
  background-color: rgba(59, 130, 246, 0.3);
  border-radius: 50%;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  animation: pulse-ring 2s ease-out infinite;
  z-index: 1;
}

@keyframes pulse-ring {
  0% {
    transform: translate(-50%, -50%) scale(1);
    opacity: 0.6;
  }
  100% {
    transform: translate(-50%, -50%) scale(2.8);
    opacity: 0;
  }
}

/* Popup de Leaflet personalizado */
:deep(.leaflet-popup-content-wrapper) {
  background-color: #2d2438 !important;
  border: 1px solid #3d3450 !important;
  border-radius: 12px !important;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5) !important;
  padding: 0 !important;
}

:deep(.leaflet-popup-content) {
  margin: 12px 16px !important;
  line-height: 1.4 !important;
}

:deep(.leaflet-popup-tip) {
  background-color: #2d2438 !important;
  border: 1px solid #3d3450 !important;
}

:deep(.leaflet-popup-close-button) {
  color: #a89ec0 !important;
  padding: 6px 8px 0 0 !important;
}

:deep(.leaflet-popup-close-button:hover) {
  color: #fff !important;
}
</style>
