<template>
  <div class="location-control-container" ref="containerRef">
    <!-- Botón / Pastilla discreta al lado de la barra de búsqueda -->
    <button
      ref="buttonRef"
      class="location-pill-btn"
      :class="{
        'pill-active': locationStore.isActive,
        'popover-open': isOpen
      }"
      @click="togglePopover"
      type="button"
      :aria-expanded="isOpen"
      :title="locationStore.isActive ? `Filtro de proximidad: ${locationStore.radiusKm} km` : 'Seleccionar ubicación'"
    >
      <span class="pin-icon-wrap">
        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
          <circle cx="12" cy="10" r="3"/>
        </svg>
      </span>

      <span class="pill-text">
        {{ locationStore.isActive ? `${locationStore.radiusKm} km` : 'Ubicación' }}
      </span>

      <svg class="chevron-icon" :class="{ rotated: isOpen }" xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="6 9 12 15 18 9"></polyline>
      </svg>
    </button>

    <!-- Popover desplegable con el slider de distancia -->
    <Transition name="popover-anim">
      <div v-if="isOpen" class="location-popover" ref="popoverRef">
        <!-- Estado: Ubicación activa -->
        <div v-if="locationStore.isActive" class="popover-inner">
          <div class="popover-header">
            <span class="popover-title">Distancia máxima</span>
            <span class="radius-badge">{{ tempRadius }} km</span>
          </div>

          <!-- Slider interactivo -->
          <div class="slider-wrapper">
            <input
              type="range"
              min="1"
              max="20"
              step="1"
              v-model.number="tempRadius"
              @input="handleSliderInput"
              @change="handleSliderChange"
              class="range-slider"
            />
            <div class="slider-ticks">
              <span>1 km</span>
              <span>5 km</span>
              <span>10 km</span>
              <span>20 km</span>
            </div>
          </div>

          <!-- Accesos directos rápidos -->
          <div class="quick-chips">
            <button
              v-for="km in [1, 3, 5, 10]"
              :key="km"
              class="chip-btn"
              :class="{ active: tempRadius === km }"
              @click="setQuickRadius(km)"
              type="button"
            >
              {{ km }} km
            </button>
          </div>

          <div class="popover-separator"></div>

          <!-- Opciones de mapa y reinicio -->
          <div class="popover-footer-actions">
            <button class="footer-btn map-btn" @click="openLocationPicker" type="button">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon>
                <line x1="8" y1="2" x2="8" y2="18"></line>
                <line x1="16" y1="6" x2="16" y2="22"></line>
              </svg>
              Cambiar ubicación
            </button>

            <button class="footer-btn remove-btn" @click="handleClear" type="button">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
              Quitar filtro
            </button>
          </div>
        </div>

        <!-- Estado: Sin ubicación seleccionada aún -->
        <div v-else class="popover-inner inactive-box">
          <p class="inactive-desc">
            Selecciona un punto en el mapa para descubrir ofertas cerca tuyo.
          </p>
          <button class="select-map-btn" @click="openLocationPicker" type="button">
            Elegir ubicación en el mapa
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { useLocationStore } from '@/stores/location'

const locationStore = useLocationStore()

const emit = defineEmits(['location-changed', 'open-picker'])

const isOpen = ref(false)
const containerRef = ref(null)
const tempRadius = ref(locationStore.radiusKm || 3)

// Sincronizar tempRadius cuando cambie en el store
watch(
  () => locationStore.radiusKm,
  (newVal) => {
    tempRadius.value = newVal
  }
)

function togglePopover() {
  isOpen.value = !isOpen.value
}

function handleSliderInput() {
  // Feedback visual inmediato en el badge mientras arrastra
}

function handleSliderChange() {
  // Al soltar el slider, confirmar y disparar recarga
  locationStore.setRadius(tempRadius.value)
  emit('location-changed')
}

function setQuickRadius(km) {
  tempRadius.value = km
  locationStore.setRadius(km)
  emit('location-changed')
}

function openLocationPicker() {
  isOpen.value = false
  emit('open-picker')
}

function handleClear() {
  isOpen.value = false
  locationStore.clearLocation()
  emit('location-changed')
}

// Cerrar popover al hacer clic afuera
function handleClickOutside(event) {
  if (containerRef.value && !containerRef.value.contains(event.target)) {
    isOpen.value = false
  }
}

onMounted(() => {
  window.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  window.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.location-control-container {
  position: relative;
  display: inline-block;
}

/* Botón principal discreto (Pastilla) */
.location-pill-btn {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  height: 44px;
  padding: 0 16px;
  background-color: #221C30;
  border: 1px solid #3D3450;
  border-radius: 9999px;
  color: #CBD5E1;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  white-space: nowrap;
  user-select: none;
  box-sizing: border-box;
}

.location-pill-btn:hover {
  background-color: #261F36;
  border-color: #4E4264;
  color: #FFFFFF;
}

/* Estado activo con ubicación establecida */
.pill-active {
  background-color: rgba(124, 58, 237, 0.16);
  border-color: #7C3AED;
  color: #FFFFFF;
}

.pill-active:hover {
  background-color: rgba(124, 58, 237, 0.24);
}

.popover-open {
  border-color: #7C3AED;
  box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.18);
}

.pin-icon-wrap {
  display: flex;
  align-items: center;
  color: #A78BFA;
}

.pill-text {
  font-weight: 600;
  letter-spacing: 0.2px;
}

.chevron-icon {
  color: #8E8BA7;
  transition: transform 0.2s ease;
}

.chevron-icon.rotated {
  transform: rotate(180deg);
}

/* Popover flotante */
.location-popover {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  width: 305px;
  background-color: #221C30;
  border: 1px solid #3D3450;
  border-radius: 16px;
  box-shadow: 0 16px 36px rgba(0, 0, 0, 0.65);
  z-index: 1050;
  overflow: hidden;
}

.popover-inner {
  padding: 16px;
}

.popover-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 14px;
}

.popover-title {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-text);
}

.radius-badge {
  background-color: var(--color-accent);
  color: #ffffff;
  padding: 2px 9px;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 700;
}

/* Slider de rango */
.slider-wrapper {
  margin-bottom: 14px;
}

.range-slider {
  -webkit-appearance: none;
  appearance: none;
  width: 100%;
  height: 6px;
  background: var(--color-focus, #4a4058);
  border-radius: 4px;
  outline: none;
  margin: 8px 0;
  cursor: pointer;
}

.range-slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: var(--color-accent, #7c3aed);
  border: 2px solid #ffffff;
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.4);
  transition: transform 0.15s ease, background-color 0.15s ease;
}

.range-slider::-webkit-slider-thumb:hover {
  transform: scale(1.2);
  background: var(--color-accent-hover, #6d28d9);
}

.range-slider::-moz-range-thumb {
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: var(--color-accent, #7c3aed);
  border: 2px solid #ffffff;
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.4);
}

.slider-ticks {
  display: flex;
  justify-content: space-between;
  font-size: 0.7rem;
  color: var(--color-text-secondary);
  padding: 0 2px;
}

/* Chips rápidos */
.quick-chips {
  display: flex;
  gap: 6px;
  margin-bottom: 12px;
}

.chip-btn {
  flex: 1;
  padding: 4px 0;
  background-color: var(--color-bg);
  border: 1px solid var(--color-secondary);
  border-radius: 8px;
  color: var(--color-text-secondary);
  font-size: 0.75rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s ease;
  text-align: center;
}

.chip-btn:hover {
  border-color: var(--color-accent);
  color: var(--color-text);
}

.chip-btn.active {
  background-color: var(--color-accent);
  border-color: var(--color-accent);
  color: #ffffff;
  font-weight: 600;
}

.popover-separator {
  height: 1px;
  background-color: var(--color-secondary);
  margin: 10px 0 12px 0;
}

/* Botones de acción inferiores */
.popover-footer-actions {
  display: flex;
  gap: 8px;
}

.footer-btn {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  padding: 7px 10px;
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  border: none;
}

.map-btn {
  background-color: var(--color-secondary);
  color: var(--color-text);
}

.map-btn:hover {
  background-color: var(--color-focus);
}

.remove-btn {
  background-color: transparent;
  color: var(--color-text-secondary);
  border: 1px solid var(--color-secondary);
}

.remove-btn:hover {
  background-color: rgba(239, 68, 68, 0.15);
  border-color: var(--color-danger, #ef4444);
  color: var(--color-danger, #ef4444);
}

/* Estado inactivo */
.inactive-box {
  display: flex;
  flex-direction: column;
  gap: 12px;
  text-align: center;
}

.inactive-desc {
  margin: 0;
  font-size: 0.82rem;
  color: var(--color-text-secondary);
  line-height: 1.4;
}

.select-map-btn {
  padding: 9px 16px;
  background-color: var(--color-accent);
  color: #ffffff;
  border: none;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.select-map-btn:hover {
  background-color: var(--color-accent-hover);
}

/* Transición del Popover */
.popover-anim-enter-active,
.popover-anim-leave-active {
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.popover-anim-enter-from,
.popover-anim-leave-to {
  opacity: 0;
  transform: translateY(-8px) scale(0.96);
}

@media (max-width: 480px) {
  .location-popover {
    right: auto;
    left: 0;
    width: 270px;
  }
}
</style>
