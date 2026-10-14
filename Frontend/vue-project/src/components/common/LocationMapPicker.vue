<script setup>
import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import {
  DEFAULT_SALTA_COORDS,
  DEFAULT_MAP_ZOOM,
  createDarkTileLayer,
  createMapPinIcon,
} from '@/lib/helpers/mapTheme'

const props = defineProps({
  latitude: {
    type: Number,
    default: null,
  },
  longitude: {
    type: Number,
    default: null,
  },
  readonly: {
    type: Boolean,
    default: false,
  },
  height: {
    type: String,
    default: '18rem',
  },
  zoom: {
    type: Number,
    default: DEFAULT_MAP_ZOOM,
  },
  initialCenter: {
    type: Array,
    default: () => DEFAULT_SALTA_COORDS,
  },
  pinColor: {
    type: String,
    default: '#7C3AED',
  },
  allowExpand: {
    type: Boolean,
    default: true,
  },
})

const emit = defineEmits(['update:latitude', 'update:longitude', 'change'])

const mapContainer = ref(null)
const isExpanded = ref(false)

let mapInstance = null
let markerInstance = null
let resizeObserver = null

const hasCoordinates = () => {
  return (
    typeof props.latitude === 'number' &&
    typeof props.longitude === 'number' &&
    !isNaN(props.latitude) &&
    !isNaN(props.longitude)
  )
}

const updateMarkerPosition = (lat, lng, shouldCenter = false) => {
  if (!mapInstance) return

  const pinIcon = createMapPinIcon({ color: props.pinColor, size: 30 })

  if (markerInstance) {
    markerInstance.setLatLng([lat, lng])
  } else {
    markerInstance = L.marker([lat, lng], {
      icon: pinIcon,
      interactive: !props.readonly,
    }).addTo(mapInstance)
  }

  if (shouldCenter) {
    mapInstance.setView([lat, lng], mapInstance.getZoom() || props.zoom)
  }
}

const removeMarker = () => {
  if (markerInstance && mapInstance) {
    mapInstance.removeLayer(markerInstance)
    markerInstance = null
  }
}

const handleMapClick = (e) => {
  if (props.readonly) return

  const lat = Number(e.latlng.lat.toFixed(7))
  const lng = Number(e.latlng.lng.toFixed(7))

  updateMarkerPosition(lat, lng, false)

  emit('update:latitude', lat)
  emit('update:longitude', lng)
  emit('change', { latitude: lat, longitude: lng })
}

const toggleExpand = () => {
  if (!props.allowExpand) return

  isExpanded.value = !isExpanded.value

  if (isExpanded.value) {
    document.body.style.overflow = 'hidden'
    window.addEventListener('keydown', handleKeyDown)
  } else {
    document.body.style.overflow = ''
    window.removeEventListener('keydown', handleKeyDown)
  }

  nextTick(() => {
    mapInstance?.invalidateSize()
    if (hasCoordinates()) {
      mapInstance?.setView([props.latitude, props.longitude], mapInstance.getZoom() || props.zoom)
    }
  })
}

const handleKeyDown = (e) => {
  if (e.key === 'Escape' && isExpanded.value) {
    toggleExpand()
  }
}

const initMap = () => {
  if (!mapContainer.value || mapInstance) return

  const center = hasCoordinates()
    ? [props.latitude, props.longitude]
    : props.initialCenter

  mapInstance = L.map(mapContainer.value, {
    center,
    zoom: props.zoom,
    zoomControl: true,
  })

  // Añadir capa de azulejos oscura de Carto
  createDarkTileLayer().addTo(mapInstance)

  // Escuchar clics para ubicar el marcador
  if (!props.readonly) {
    mapInstance.on('click', handleMapClick)
  }

  // Si ya vienen coordenadas iniciales, colocar el marcador
  if (hasCoordinates()) {
    updateMarkerPosition(props.latitude, props.longitude, false)
  }

  // Refrescar tamaño para prevenir problemas de renderizado en pestañas o v-if
  nextTick(() => {
    mapInstance?.invalidateSize()
  })
}

// Sincronizar si las props de coordenadas cambian desde el componente padre
watch(
  () => [props.latitude, props.longitude],
  ([newLat, newLng]) => {
    if (
      typeof newLat === 'number' &&
      typeof newLng === 'number' &&
      !isNaN(newLat) &&
      !isNaN(newLng)
    ) {
      if (
        !markerInstance ||
        markerInstance.getLatLng().lat !== newLat ||
        markerInstance.getLatLng().lng !== newLng
      ) {
        updateMarkerPosition(newLat, newLng, true)
      }
    } else {
      removeMarker()
    }
  }
)

onMounted(() => {
  initMap()

  if (mapContainer.value && window.ResizeObserver) {
    resizeObserver = new ResizeObserver(() => {
      mapInstance?.invalidateSize()
    })
    resizeObserver.observe(mapContainer.value)
  }
})

onUnmounted(() => {
  if (isExpanded.value) {
    document.body.style.overflow = ''
    window.removeEventListener('keydown', handleKeyDown)
  }

  if (resizeObserver) {
    resizeObserver.disconnect()
    resizeObserver = null
  }

  if (mapInstance) {
    mapInstance.off()
    mapInstance.remove()
    mapInstance = null
    markerInstance = null
  }
})

defineExpose({
  invalidateSize: () => mapInstance?.invalidateSize(),
  centerOn: (lat, lng, zoom) => mapInstance?.setView([lat, lng], zoom || props.zoom),
  toggleExpand,
  isExpanded,
})
</script>

<template>
  <div
    class="location-map-picker"
    :class="{ 'location-map-picker--expanded': isExpanded }"
  >
    <!-- Barra superior en modo ampliado -->
    <div v-if="isExpanded" class="expanded-top-bar">
      <div class="flex items-center gap-3 min-w-0">
        <div class="w-8 h-8 rounded-lg bg-[var(--color-focus)]/20 text-[var(--color-focus)] flex items-center justify-center flex-shrink-0">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </div>
        <div class="min-w-0">
          <h5 class="text-sm font-bold text-white truncate">Ubicar punto de retiro en el mapa</h5>
          <p class="text-xs text-[var(--color-text)]/70 truncate">Haz clic o acércate para fijar la entrada exacta de tu establecimiento</p>
        </div>
      </div>

      <div class="flex items-center gap-3 flex-shrink-0">
        <button
          type="button"
          @click="toggleExpand"
          class="px-4 py-2 rounded-xl bg-[var(--color-focus)] hover:bg-[var(--color-focus)]/90 text-white text-xs font-semibold shadow-lg transition flex items-center gap-2 cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
          </svg>
          <span>Listo / Confirmar</span>
        </button>
      </div>
    </div>

    <!-- Contenedor del mapa Leaflet -->
    <div
      ref="mapContainer"
      class="map-surface"
      :style="{ height: isExpanded ? 'calc(100vh - 4.25rem)' : props.height }"
    ></div>

    <!-- Botón de acción para expandir / contraer en la esquina superior derecha -->
    <div v-if="props.allowExpand && !isExpanded" class="map-top-action-btn">
      <button
        type="button"
        @click="toggleExpand"
        class="expand-toggle-btn"
        title="Ampliar mapa a pantalla completa"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
        </svg>
        <span class="text-xs font-medium hidden sm:inline">Ampliar mapa</span>
      </button>
    </div>

    <!-- Barra de estado y coordenadas -->
    <div class="map-overlay-badge">
      <div v-if="hasCoordinates()" class="badge-status badge-status--active">
        <span class="status-dot status-dot--active"></span>
        <span class="status-text font-mono">
          {{ props.latitude.toFixed(5) }}, {{ props.longitude.toFixed(5) }}
        </span>
      </div>
      <div v-else class="badge-status badge-status--idle">
        <span class="status-dot status-dot--idle"></span>
        <span class="status-text">
          {{ props.readonly ? 'Sin ubicación fijada' : 'Haz clic en el mapa para marcar el punto exacto' }}
        </span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.location-map-picker {
  position: relative;
  width: 100%;
  border-radius: 0.75rem;
  overflow: hidden;
  border: 1px solid var(--color-border);
  background-color: var(--color-bg);
  transition: all 0.2s ease;
}

/* Modo ampliado / Pantalla completa */
.location-map-picker--expanded {
  position: fixed !important;
  inset: 0 !important;
  z-index: 9999 !important;
  width: 100vw !important;
  height: 100vh !important;
  border-radius: 0 !important;
  border: none !important;
  display: flex !important;
  flex-direction: column !important;
  background-color: #1a1625 !important;
}

.expanded-top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.75rem 1.25rem;
  background-color: #2d2438;
  border-bottom: 1px solid var(--color-border);
  z-index: 1000;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
}

.map-surface {
  width: 100%;
  background-color: #1a1625;
  transition: height 0.2s ease;
}

.location-map-picker--expanded .map-surface {
  flex: 1;
}

/* Botón flotante para ampliar */
.map-top-action-btn {
  position: absolute;
  top: 0.75rem;
  right: 0.75rem;
  z-index: 500;
}

.expand-toggle-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.4rem 0.75rem;
  background-color: rgba(45, 36, 56, 0.9);
  color: var(--color-text);
  border: 1px solid var(--color-border);
  border-radius: 0.5rem;
  cursor: pointer;
  backdrop-filter: blur(8px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.35);
  transition: all 0.2s ease;
}

.expand-toggle-btn:hover {
  background-color: #3d3450;
  color: #ffffff;
  border-color: var(--color-focus);
  transform: translateY(-1px);
}

.map-overlay-badge {
  position: absolute;
  bottom: 0.75rem;
  left: 0.75rem;
  z-index: 500;
  pointer-events: none;
}

.badge-status {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.35rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  backdrop-filter: blur(8px);
  border: 1px solid var(--color-border);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
}

.badge-status--active {
  background-color: rgba(45, 36, 56, 0.9);
  color: #10b981;
  border-color: rgba(16, 185, 129, 0.4);
}

.badge-status--idle {
  background-color: rgba(45, 36, 56, 0.85);
  color: var(--color-text);
  opacity: 0.9;
}

.status-dot {
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 50%;
  flex-shrink: 0;
}

.status-dot--active {
  background-color: #10b981;
  box-shadow: 0 0 6px #10b981;
}

.status-dot--idle {
  background-color: #f59e0b;
  box-shadow: 0 0 6px #f59e0b;
}

/* Estilos de controles Leaflet en modo oscuro */
:deep(.leaflet-bar) {
  border: 1px solid var(--color-border) !important;
  border-radius: 0.5rem !important;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3) !important;
}

:deep(.leaflet-bar a) {
  background-color: #2d2438 !important;
  color: #e8eaf6 !important;
  border-bottom: 1px solid var(--color-border) !important;
  transition: all 0.15s ease;
}

:deep(.leaflet-bar a:hover) {
  background-color: #3d3450 !important;
  color: #ffffff !important;
}

:deep(.leaflet-control-attribution) {
  background: rgba(26, 22, 37, 0.8) !important;
  color: rgba(232, 234, 246, 0.6) !important;
  font-size: 0.65rem !important;
  padding: 0 0.5rem !important;
  backdrop-filter: blur(4px);
}

:deep(.leaflet-control-attribution a) {
  color: #a78bfa !important;
}

/* Animación de pulso para el pin */
@keyframes map-pin-pulse {
  0% {
    transform: scale(0.8);
    opacity: 0.7;
  }
  100% {
    transform: scale(2.4);
    opacity: 0;
  }
}
</style>
