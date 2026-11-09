<script setup>
import { ref, computed, watch, nextTick, onUnmounted } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import {
  DEFAULT_SALTA_COORDS,
  DEFAULT_MAP_ZOOM,
  createMapTileLayer,
} from '@/lib/helpers/mapTheme'
import { useLocationStore } from '@/stores/location'
import BaseButton from '@/components/ui/atoms/BaseButton.vue'
import BaseIcon from '@/components/ui/atoms/BaseIcon.vue'

const props = defineProps({
  isVisible: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['close', 'confirmed', 'cleared'])

const locationStore = useLocationStore()

const mapContainer = ref(null)
let map = null
let marker = null
let circle = null
let resizeObserver = null
let initTimer = null

const selectedLat = ref(null)
const selectedLng = ref(null)
const selectedRadius = ref(3)
const isLocating = ref(false)
const isSearchingAddress = ref(false)
const addressQuery = ref('')
const locationError = ref('')

const hasSelection = computed(() => {
  return (
    typeof selectedLat.value === 'number' &&
    typeof selectedLng.value === 'number' &&
    !isNaN(selectedLat.value) &&
    !isNaN(selectedLng.value)
  )
})

function createCustomPinIcon() {
  return L.divIcon({
    className: 'custom-picker-pin-wrapper',
    html: `
      <div style="position: relative; width: 36px; height: 44px; display: flex; align-items: center; justify-content: center; cursor: grab;">
        <svg width="36" height="44" viewBox="0 0 36 44" fill="none" xmlns="http://www.w3.org/2000/svg" style="filter: drop-shadow(0 4px 8px rgba(70, 51, 79, 0.35));">
          <path d="M18 0C8.05888 0 0 8.05888 0 18C0 29.5 18 44 18 44C18 44 36 29.5 36 18C36 8.05888 27.9411 0 18 0Z" fill="#F24F13"/>
          <circle cx="18" cy="18" r="7" fill="#FFFFFF"/>
          <circle cx="18" cy="18" r="3.5" fill="#F24F13"/>
        </svg>
      </div>
    `,
    iconSize: [36, 44],
    iconAnchor: [18, 44],
  })
}

function updateCircle(lat, lng, radiusKm) {
  if (!map) return
  const radiusMeters = radiusKm * 1000

  if (circle) {
    circle.setLatLng([lat, lng])
    circle.setRadius(radiusMeters)
  } else {
    circle = L.circle([lat, lng], {
      radius: radiusMeters,
      color: '#F24F13',
      fillColor: '#F24F13',
      fillOpacity: 0.12,
      weight: 1.5,
      dashArray: '5, 5',
    }).addTo(map)
  }
}

function placeMarker(lat, lng, shouldCenter = false) {
  selectedLat.value = Number(lat.toFixed(6))
  selectedLng.value = Number(lng.toFixed(6))

  if (!map) return

  if (marker && map.hasLayer(marker)) {
    marker.setLatLng([lat, lng])
  } else {
    if (marker) {
      map.removeLayer(marker)
    }
    marker = L.marker([lat, lng], {
      draggable: true,
      icon: createCustomPinIcon(),
    }).addTo(map)

    marker.on('drag', (e) => {
      const pos = e.target.getLatLng()
      if (circle) {
        circle.setLatLng(pos)
      }
    })

    marker.on('dragend', () => {
      const pos = marker.getLatLng()
      selectedLat.value = Number(pos.lat.toFixed(6))
      selectedLng.value = Number(pos.lng.toFixed(6))
      if (circle) {
        circle.setLatLng(pos)
      }
    })
  }

  updateCircle(lat, lng, selectedRadius.value)

  if (shouldCenter) {
    map.setView([lat, lng], map.getZoom() || DEFAULT_MAP_ZOOM, { animate: true })
  }
}

function updateRadius(km) {
  selectedRadius.value = km
  if (hasSelection.value) {
    updateCircle(selectedLat.value, selectedLng.value, km)
    if (circle && map) {
      map.fitBounds(circle.getBounds(), { padding: [30, 30], maxZoom: 16 })
    }
  }
}

function centerOnDefault() {
  const [lat, lng] = DEFAULT_SALTA_COORDS
  placeMarker(lat, lng, true)
}

function useCurrentLocation() {
  if (!navigator.geolocation) {
    locationError.value = 'Tu navegador no soporta geolocalización.'
    return
  }

  isLocating.value = true
  locationError.value = ''

  navigator.geolocation.getCurrentPosition(
    (position) => {
      isLocating.value = false
      const lat = Number(position.coords.latitude.toFixed(6))
      const lng = Number(position.coords.longitude.toFixed(6))
      placeMarker(lat, lng, true)
    },
    (err) => {
      isLocating.value = false
      console.warn('Geolocation error:', err)
      if (err.code === 1) {
        locationError.value = 'Permiso de ubicación denegado en el navegador. Puedes hacer clic en el mapa para fijar tu ubicación.'
      } else {
        locationError.value = 'No se pudo obtener tu ubicación actual. Intenta seleccionarla en el mapa.'
      }
    },
    { enableHighAccuracy: true, timeout: 8000 }
  )
}

async function searchAddress() {
  const query = addressQuery.value.trim()
  if (!query) return

  isSearchingAddress.value = true
  locationError.value = ''

  try {
    const fullQuery = /(salta|argentina)/i.test(query) ? query : `${query}, Salta, Argentina`
    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(fullQuery)}&limit=1&addressdetails=1`

    const response = await fetch(url, {
      headers: {
        Accept: 'application/json',
      },
    })

    if (!response.ok) throw new Error('Error al buscar dirección')

    const data = await response.json()
    if (data && data.length > 0) {
      const lat = parseFloat(data[0].lat)
      const lon = parseFloat(data[0].lon)
      placeMarker(lat, lon, true)
      map?.setView([lat, lon], 16, { animate: true })
    } else {
      locationError.value = 'No se encontró la dirección. Intenta marcarla directamente en el mapa.'
    }
  } catch (err) {
    console.warn('Geocoding error:', err)
    locationError.value = 'No se pudo buscar la dirección. Por favor selecciona el punto en el mapa.'
  } finally {
    isSearchingAddress.value = false
  }
}

function destroyMap() {
  if (initTimer) {
    clearTimeout(initTimer)
    initTimer = null
  }

  if (resizeObserver) {
    resizeObserver.disconnect()
    resizeObserver = null
  }

  if (map) {
    map.off()
    map.remove()
    map = null
  }

  marker = null
  circle = null
}

function initMap() {
  if (!mapContainer.value) return
  destroyMap()

  const initialCenter = hasSelection.value
    ? [selectedLat.value, selectedLng.value]
    : locationStore.isActive
      ? [locationStore.latitude, locationStore.longitude]
      : DEFAULT_SALTA_COORDS

  map = L.map(mapContainer.value, {
    center: initialCenter,
    zoom: DEFAULT_MAP_ZOOM,
    zoomControl: true,
  })

  createMapTileLayer().addTo(map)

  map.on('click', (e) => {
    placeMarker(e.latlng.lat, e.latlng.lng)
  })

  if (hasSelection.value) {
    placeMarker(selectedLat.value, selectedLng.value, false)
  } else if (locationStore.isActive) {
    placeMarker(locationStore.latitude, locationStore.longitude, false)
  }

  if (window.ResizeObserver && mapContainer.value) {
    resizeObserver = new ResizeObserver(() => {
      map?.invalidateSize()
    })
    resizeObserver.observe(mapContainer.value)
  }

  nextTick(() => {
    map?.invalidateSize()
  })
}

function handleAfterEnter() {
  if (map) {
    map.invalidateSize()
    if (circle) {
      map.fitBounds(circle.getBounds(), { padding: [24, 24], maxZoom: 16 })
    }
  }
}

function handleConfirm() {
  if (hasSelection.value) {
    locationStore.setLocation(selectedLat.value, selectedLng.value)
    locationStore.setRadius(selectedRadius.value)
    emit('confirmed')
    emit('close')
  }
}

function handleClearLocation() {
  locationStore.clearLocation()
  selectedLat.value = null
  selectedLng.value = null
  if (marker && map) {
    map.removeLayer(marker)
    marker = null
  }
  if (circle && map) {
    map.removeLayer(circle)
    circle = null
  }
  emit('cleared')
  emit('close')
}

function handleClose() {
  emit('close')
}

function handleKeyDown(e) {
  if (e.key === 'Escape' && props.isVisible) {
    handleClose()
  }
}

watch(
  () => props.isVisible,
  async (visible) => {
    if (visible) {
      locationError.value = ''
      addressQuery.value = ''
      window.addEventListener('keydown', handleKeyDown)

      if (locationStore.isActive) {
        selectedLat.value = locationStore.latitude
        selectedLng.value = locationStore.longitude
        selectedRadius.value = Math.min(locationStore.radiusKm || 3, 3)
      } else {
        selectedLat.value = null
        selectedLng.value = null
        selectedRadius.value = Math.min(locationStore.radiusKm || 3, 3)
      }

      await nextTick()
      initTimer = setTimeout(() => {
        if (mapContainer.value) {
          initMap()
        }
      }, 50)
    } else {
      window.removeEventListener('keydown', handleKeyDown)
      destroyMap()
    }
  }
)

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown)
  destroyMap()
})
</script>

<template>
  <Teleport to="body">
    <Transition name="modal" @after-enter="handleAfterEnter">
      <div
        v-if="isVisible"
        class="fixed inset-0 z-[9999] flex items-center justify-center p-3 sm:p-4 bg-black/70 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-labelledby="location-modal-title"
        @click.self="handleClose"
      >
        <div
          class="relative w-full max-w-2xl bg-surface border border-border-subtle rounded-[20px] shadow-card flex flex-col max-h-[92vh] overflow-hidden text-content"
          @click.stop
        >
          <!-- Header -->
          <div class="px-5 py-4 border-b border-border-subtle flex items-center justify-between gap-3 bg-surface shrink-0">
            <div class="flex items-center gap-3 min-w-0">
              <div class="w-9 h-9 rounded-xl bg-brand/10 text-brand flex items-center justify-center shrink-0">
                <BaseIcon name="map" size="sm" />
              </div>
              <div class="min-w-0">
                <h2 id="location-modal-title" class="text-base sm:text-lg font-bold text-content truncate font-heading">
                  Elegir ubicación en el mapa
                </h2>
                <p class="text-xs text-content-secondary truncate">
                  Fija el punto central para descubrir ofertas y packs cerca de ti
                </p>
              </div>
            </div>

            <button
              type="button"
              class="w-8 h-8 rounded-lg flex items-center justify-center text-content-secondary hover:text-content hover:bg-surface-sunken transition-colors cursor-pointer shrink-0"
              title="Cerrar modal"
              @click="handleClose"
            >
              <BaseIcon name="close" size="xs" />
            </button>
          </div>

          <!-- Body -->
          <div class="p-4 sm:p-5 flex flex-col gap-3.5 overflow-y-auto">
            <!-- Barra de Acciones Rápidas (GPS + Presets + Radio) -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
              <!-- Botones de localización rápida -->
              <div class="flex items-center gap-2 flex-wrap">
                <button
                  type="button"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border transition-all cursor-pointer disabled:opacity-50"
                  :class="isLocating ? 'bg-brand/10 text-brand border-brand' : 'bg-surface border-border-subtle hover:border-brand hover:text-brand text-content'"
                  :disabled="isLocating"
                  @click="useCurrentLocation"
                >
                  <BaseIcon v-if="!isLocating" name="compass" size="xs" />
                  <span v-else class="w-3.5 h-3.5 border-2 border-brand border-t-transparent rounded-full animate-spin" />
                  <span>{{ isLocating ? 'Detectando GPS...' : 'Mi ubicación' }}</span>
                </button>

                <button
                  type="button"
                  class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-medium border border-border-subtle bg-surface text-content hover:border-content-secondary transition-colors cursor-pointer"
                  @click="centerOnDefault"
                >
                  <span>Salta Centro</span>
                </button>
              </div>

              <!-- Selector de Radio de Búsqueda (1 km, 2 km, 3 km) -->
              <div class="flex items-center gap-1.5">
                <span class="text-[11px] font-bold text-content-secondary uppercase tracking-wider shrink-0">Radio:</span>
                <div class="flex items-center gap-1">
                  <button
                    v-for="km in [1, 2, 3]"
                    :key="km"
                    type="button"
                    class="px-2.5 py-1 rounded-full text-xs font-semibold tabular-nums border transition-all cursor-pointer"
                    :class="selectedRadius === km
                      ? 'bg-brand text-surface border-brand shadow-xs'
                      : 'bg-surface border-border-subtle text-content hover:border-content-secondary'"
                    @click="updateRadius(km)"
                  >
                    {{ km }} km
                  </button>
                </div>
              </div>
            </div>

            <!-- Buscador de calle / dirección opcional -->
            <form @submit.prevent="searchAddress" class="relative flex items-center">
              <input
                v-model="addressQuery"
                type="text"
                placeholder="Buscar calle o barrio (ej: Mitre 500, Tres Cerritos)..."
                class="w-full h-9 pl-9 pr-20 rounded-xl bg-canvas border border-border-subtle text-xs text-content placeholder:text-content-muted focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand transition-all"
              />
              <div class="absolute left-3 top-1/2 -translate-y-1/2 text-content-secondary pointer-events-none">
                <BaseIcon name="search" size="xs" />
              </div>
              <button
                type="submit"
                :disabled="isSearchingAddress || !addressQuery.trim()"
                class="absolute right-1.5 top-1/2 -translate-y-1/2 px-2.5 py-1 rounded-lg bg-brand hover:bg-brand-hover text-surface text-[11px] font-bold transition-all disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer flex items-center gap-1"
              >
                <span v-if="isSearchingAddress" class="w-3 h-3 border-2 border-surface border-t-transparent rounded-full animate-spin"></span>
                <span>Buscar</span>
              </button>
            </form>

            <!-- Alerta informativa / error si falla geolocalización o búsqueda -->
            <div
              v-if="locationError"
              class="px-3.5 py-2 rounded-xl bg-danger/10 border border-danger/20 text-danger text-xs flex items-center justify-between gap-2"
            >
              <span>{{ locationError }}</span>
              <button type="button" class="underline text-[11px] cursor-pointer" @click="locationError = ''">
                Descartar
              </button>
            </div>

            <!-- Contenedor del Mapa Leaflet -->
            <div class="relative rounded-2xl overflow-hidden border border-border-subtle bg-surface-sunken">
              <div
                ref="mapContainer"
                class="w-full h-[320px] sm:h-[380px] bg-canvas transition-opacity duration-200"
              />

              <!-- Overlay flotante con coordenadas y radio actual -->
              <div class="absolute bottom-2.5 left-2.5 z-[500] pointer-events-none">
                <div
                  v-if="hasSelection"
                  class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-surface/95 backdrop-blur-md border border-border-subtle shadow-card text-xs text-content"
                >
                  <span class="w-2 h-2 rounded-full bg-success shadow-xs"></span>
                  <span class="font-mono font-medium tabular-nums">
                    {{ selectedLat.toFixed(4) }}, {{ selectedLng.toFixed(4) }}
                  </span>
                  <span class="text-content-secondary">·</span>
                  <span class="font-semibold text-brand tabular-nums">{{ selectedRadius }} km a la redonda</span>
                </div>
                <div
                  v-else
                  class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-surface/95 backdrop-blur-md border border-border-subtle shadow-card text-xs text-content-secondary"
                >
                  <span class="w-2 h-2 rounded-full bg-warning"></span>
                  <span>Haz clic en el mapa para ubicar tu punto</span>
                </div>
              </div>
            </div>

            <p class="text-[12px] text-content-secondary leading-relaxed">
              💡 <strong>Tip:</strong> Puedes hacer clic en cualquier parte del mapa o arrastrar el marcador para mover el centro de búsqueda.
            </p>
          </div>

          <!-- Footer -->
          <div class="px-5 py-3.5 border-t border-border-subtle bg-surface flex items-center justify-between gap-3 shrink-0">
            <div>
              <BaseButton
                v-if="locationStore.isActive"
                variant="ghost"
                size="sm"
                @click="handleClearLocation"
              >
                Quitar filtro
              </BaseButton>
            </div>

            <div class="flex items-center gap-2">
              <BaseButton
                variant="outline"
                size="sm"
                @click="handleClose"
              >
                Cancelar
              </BaseButton>

              <BaseButton
                variant="primary"
                size="sm"
                :disabled="!hasSelection"
                @click="handleConfirm"
              >
                Confirmar ubicación
              </BaseButton>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
:deep(.custom-picker-pin-wrapper) {
  background: none !important;
  border: none !important;
}

:deep(.leaflet-bar) {
  border: 1px solid var(--color-border-subtle) !important;
  border-radius: 12px !important;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(70, 51, 79, 0.08) !important;
}

:deep(.leaflet-bar a) {
  background-color: var(--color-surface) !important;
  color: var(--color-content) !important;
  border-bottom: 1px solid var(--color-border-subtle) !important;
  transition: all 0.15s ease;
}

:deep(.leaflet-bar a:hover) {
  background-color: var(--color-surface-sunken) !important;
  color: var(--color-brand) !important;
}

:deep(.leaflet-control-attribution) {
  background: rgba(255, 255, 255, 0.85) !important;
  color: var(--color-content-secondary) !important;
  font-size: 0.65rem !important;
  padding: 0 0.5rem !important;
  backdrop-filter: blur(4px);
}

:deep(.leaflet-control-attribution a) {
  color: var(--color-brand) !important;
}

/* Transiciones del Modal */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
