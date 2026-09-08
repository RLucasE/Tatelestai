<template>
  <Transition name="modal">
    <div v-if="isVisible" class="modal-overlay" @click.self="$emit('close')">
      <div class="modal-content">
        <div class="modal-header">
          <h2>Seleccionar ubicación</h2>
          <button class="close-button" @click="$emit('close')" type="button">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18" />
              <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
          </button>
        </div>
        <div class="modal-body">
          <p class="modal-instructions">
            Hacé clic en el mapa para fijar tu punto de búsqueda. Podés arrastrar el marcador para ajustarlo.
          </p>
          <div ref="mapContainer" class="map-container"></div>
          <p v-if="selectedLat !== null" class="coords-info">
            Ubicación: {{ selectedLat.toFixed(4) }}, {{ selectedLng.toFixed(4) }}
          </p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-cancel" @click="$emit('close')" type="button">
            Cancelar
          </button>
          <button
            class="btn btn-confirm"
            :disabled="selectedLat === null"
            @click="handleConfirm"
            type="button"
          >
            Confirmar ubicación
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, watch, nextTick, onUnmounted } from 'vue'
import L from 'leaflet'
import { useLocationStore } from '@/stores/location'

function createPickerIcon() {
  return L.divIcon({
    className: 'custom-picker-pin',
    html: `
      <div class="pin-wrapper">
        <svg xmlns="http://www.w3.org/2000/svg" width="34" height="44" viewBox="0 0 34 44" fill="none">
          <path d="M17 0C7.61116 0 0 7.61116 0 17C0 27.5 17 44 17 44C17 44 34 27.5 34 17C34 7.61116 26.3888 0 17 0Z" fill="#7c3aed"/>
          <circle cx="17" cy="17" r="6.5" fill="#ffffff"/>
        </svg>
      </div>
    `,
    iconSize: [34, 44],
    iconAnchor: [17, 44],
  })
}

const props = defineProps({
  isVisible: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['close', 'confirmed'])

const locationStore = useLocationStore()

const mapContainer = ref(null)
let map = null
let marker = null

const selectedLat = ref(null)
const selectedLng = ref(null)

const DEFAULT_CENTER = [-24.7885, -65.4105] // Salta Capital, Argentina
const DEFAULT_ZOOM = 13

function destroyMap() {
  if (map) {
    map.off()
    map.remove()
    map = null
  }
  marker = null
}

function initMap() {
  if (!mapContainer.value) return

  // Limpiar instancia previa para asegurar un montaje limpio
  destroyMap()

  const hasSelected = selectedLat.value !== null && selectedLng.value !== null
  const center = hasSelected
    ? [selectedLat.value, selectedLng.value]
    : (locationStore.isActive ? [locationStore.latitude, locationStore.longitude] : DEFAULT_CENTER)

  map = L.map(mapContainer.value, {
    center,
    zoom: DEFAULT_ZOOM,
  })

  const cartoApiKey = import.meta.env.VITE_CARTO_API_KEY || 'cb1_30sd_1_3fbfe2ae226659ca1a63373f'
  const tileUrl = `https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png?key=${cartoApiKey}`

  L.tileLayer(tileUrl, {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/">CARTO</a>',
    maxZoom: 19,
  }).addTo(map)

  map.on('click', (e) => {
    placeMarker(e.latlng.lat, e.latlng.lng)
  })

  // Si ya hay ubicación seleccionada o activa, colocar el marcador
  if (hasSelected) {
    placeMarker(selectedLat.value, selectedLng.value)
  } else if (locationStore.isActive) {
    placeMarker(locationStore.latitude, locationStore.longitude)
  }

  setTimeout(() => {
    if (map) {
      map.invalidateSize()
    }
  }, 100)
}

function placeMarker(lat, lng) {
  selectedLat.value = lat
  selectedLng.value = lng

  if (!map) return

  if (marker && map.hasLayer(marker)) {
    marker.setLatLng([lat, lng])
  } else {
    if (marker) {
      map.removeLayer(marker)
    }
    marker = L.marker([lat, lng], { draggable: true, icon: createPickerIcon() }).addTo(map)
    marker.on('dragend', () => {
      const pos = marker.getLatLng()
      selectedLat.value = pos.lat
      selectedLng.value = pos.lng
    })
  }
}

function handleConfirm() {
  if (selectedLat.value !== null && selectedLng.value !== null) {
    locationStore.setLocation(selectedLat.value, selectedLng.value)
    emit('confirmed')
    emit('close')
  }
}

watch(
  () => props.isVisible,
  async (visible) => {
    if (visible) {
      destroyMap()

      // Cargar coordenadas guardadas
      if (locationStore.isActive) {
        selectedLat.value = locationStore.latitude
        selectedLng.value = locationStore.longitude
      } else {
        selectedLat.value = null
        selectedLng.value = null
      }

      await nextTick()
      setTimeout(() => {
        if (mapContainer.value) {
          initMap()
        }
      }, 150)
    } else {
      destroyMap()
    }
  }
)

onUnmounted(() => {
  destroyMap()
})
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(15, 13, 21, 0.75);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
  padding: 16px;
}

.modal-content {
  background-color: var(--color-bg);
  border: 1px solid var(--color-secondary);
  border-radius: 12px;
  width: 100%;
  max-width: 640px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid var(--color-secondary);
}

.modal-header h2 {
  margin: 0;
  font-size: 1.15rem;
  color: var(--color-text);
  font-weight: 600;
}

.close-button {
  background: none;
  border: none;
  color: var(--color-text-secondary);
  cursor: pointer;
  padding: 4px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.close-button:hover {
  color: var(--color-text);
  background-color: var(--color-secondary);
}

.modal-body {
  padding: 16px 20px;
}

.modal-instructions {
  margin: 0 0 12px 0;
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  line-height: 1.4;
}

.map-container {
  width: 100%;
  height: 400px;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid var(--color-secondary);
  background-color: var(--color-bg);
}

:deep(.custom-picker-pin) {
  background: none !important;
  border: none !important;
}

:deep(.pin-wrapper) {
  filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.6));
  cursor: grab;
  display: flex;
  justify-content: center;
  align-items: center;
  transition: transform 0.15s ease;
}

:deep(.pin-wrapper:hover) {
  transform: scale(1.15);
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

.coords-info {
  margin: 10px 0 0 0;
  font-size: 0.825rem;
  color: var(--color-accent-light);
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 12px 20px 16px;
  border-top: 1px solid var(--color-secondary);
}

.btn {
  padding: 9px 18px;
  border: none;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-cancel {
  background-color: var(--color-secondary);
  color: var(--color-text);
}

.btn-cancel:hover {
  background-color: var(--color-focus);
}

.btn-confirm {
  background-color: var(--color-accent);
  color: #fff;
}

.btn-confirm:hover:not(:disabled) {
  background-color: var(--color-accent-hover);
}

.btn-confirm:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

/* Transition */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.25s ease;
}

.modal-enter-active .modal-content,
.modal-leave-active .modal-content {
  transition: transform 0.25s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal-content {
  transform: scale(0.95) translateY(10px);
}

.modal-leave-to .modal-content {
  transform: scale(0.95) translateY(10px);
}

@media (max-width: 480px) {
  .modal-content {
    max-width: 100%;
    border-radius: 10px;
  }

  .map-container {
    height: 55vh;
  }

  .modal-footer {
    flex-direction: column;
  }

  .btn {
    width: 100%;
    text-align: center;
  }
}
</style>
