<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { useLocationStore } from '@/stores/location';
import BaseIcon from '../ui/atoms/BaseIcon.vue';
import BaseChip from '../ui/atoms/BaseChip.vue';
import BaseButton from '../ui/atoms/BaseButton.vue';

const locationStore = useLocationStore();

const emit = defineEmits(['location-changed', 'open-picker']);

const isOpen = ref(false);
const containerRef = ref(null);
const tempRadius = ref(Math.min(locationStore.radiusKm || 3, 3));

watch(
  () => locationStore.radiusKm,
  (newVal) => {
    tempRadius.value = Math.min(newVal || 3, 3);
  }
);

function togglePopover() {
  isOpen.value = !isOpen.value;
}

function handleSliderChange() {
  locationStore.setRadius(tempRadius.value);
  emit('location-changed');
}

function setQuickRadius(km) {
  tempRadius.value = km;
  locationStore.setRadius(km);
  emit('location-changed');
}

function openLocationPicker() {
  isOpen.value = false;
  emit('open-picker');
}

function handleClear() {
  isOpen.value = false;
  locationStore.clearLocation();
  emit('location-changed');
}

function handleClickOutside(event) {
  if (containerRef.value && !containerRef.value.contains(event.target)) {
    isOpen.value = false;
  }
}

function handleKeyDown(event) {
  if (event.key === 'Escape' && isOpen.value) {
    isOpen.value = false;
  }
}

onMounted(() => {
  window.addEventListener('click', handleClickOutside);
  window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
  window.removeEventListener('click', handleClickOutside);
  window.removeEventListener('keydown', handleKeyDown);
});
</script>

<template>
  <div ref="containerRef" class="relative inline-block">
    <!-- Botón / Pastilla al lado de la barra de búsqueda (Figma: LocationPill h=34) -->
    <button
      type="button"
      class="h-[34px] px-3.5 inline-flex items-center gap-1.5 rounded-full border text-[13px] font-medium cursor-pointer select-none transition-all duration-150 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
      :class="[
        locationStore.isActive
          ? 'bg-canvas border-brand text-brand shadow-xs'
          : 'bg-surface border-border-subtle hover:border-content-secondary text-content hover:bg-canvas',
        isOpen ? 'ring-2 ring-brand/20 border-brand' : '',
      ]"
      :aria-expanded="isOpen"
      :title="locationStore.isActive ? `Filtro de proximidad: ${locationStore.radiusKm} km` : 'Seleccionar ubicación'"
      @click="togglePopover"
    >
      <span class="flex items-center" :class="locationStore.isActive ? 'text-brand' : 'text-content'">
        <BaseIcon name="pin" size="sm" />
      </span>

      <span class="tracking-tight tabular-nums">
        {{ locationStore.isActive ? `${locationStore.radiusKm} km` : 'Ubicación' }}
      </span>

      <BaseIcon
        name="chevron-down"
        size="xs"
        class="text-content-secondary transition-transform duration-200"
        :class="{ 'rotate-180': isOpen }"
      />
    </button>

    <!-- Popover con control de distancia -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="transform -translate-y-2 opacity-0 scale-95"
      enter-to-class="transform translate-y-0 opacity-100 scale-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="transform translate-y-0 opacity-100 scale-100"
      leave-to-class="transform -translate-y-2 opacity-0 scale-95"
    >
      <div
        v-if="isOpen"
        class="absolute left-0 sm:left-auto sm:right-0 mt-2 w-72 sm:w-80 rounded-[20px] bg-surface border border-border-subtle shadow-card p-4 z-50 text-content"
      >
        <!-- Estado: Ubicación activa -->
        <div v-if="locationStore.isActive" class="space-y-3.5">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-content uppercase tracking-wider">Distancia máxima</span>
            <span class="bg-brand text-surface font-extrabold text-xs px-2.5 py-0.5 rounded-full tabular-nums shadow-xs">
              {{ tempRadius }} km
            </span>
          </div>

          <!-- Slider interactivo (1 km a 3 km) -->
          <div class="space-y-1">
            <input
              v-model.number="tempRadius"
              type="range"
              min="1"
              max="3"
              step="0.5"
              class="w-full h-1.5 bg-canvas rounded-lg appearance-none cursor-pointer accent-brand"
              @change="handleSliderChange"
            />
            <div class="flex justify-between text-[11px] text-content-secondary tabular-nums">
              <span>1 km</span>
              <span>2 km</span>
              <span>3 km</span>
            </div>
          </div>

          <!-- Accesos directos rápidos (1 km, 2 km, 3 km) -->
          <div class="flex gap-1.5 pt-1">
            <button
              v-for="km in [1, 2, 3]"
              :key="km"
              type="button"
              class="flex-1 py-1 rounded-full text-xs font-semibold tabular-nums border transition-colors cursor-pointer"
              :class="tempRadius === km ? 'bg-brand text-surface border-brand' : 'bg-surface border-border-subtle text-content hover:border-content-secondary'"
              @click="setQuickRadius(km)"
            >
              {{ km }} km
            </button>
          </div>

          <div class="h-px bg-border-subtle my-2" />

          <!-- Opciones de mapa y reinicio con BaseButton y BaseIcon -->
          <div class="flex items-center gap-2">
            <BaseButton
              variant="secondary"
              size="sm"
              block
              @click="openLocationPicker"
            >
              <template #icon-left>
                <BaseIcon name="map" size="sm" />
              </template>
              Cambiar mapa
            </BaseButton>

            <BaseButton
              variant="outline"
              size="sm"
              block
              @click="handleClear"
            >
              Quitar
            </BaseButton>
          </div>
        </div>

        <!-- Estado: Sin ubicación seleccionada aún -->
        <div v-else class="space-y-3 text-center py-2">
          <p class="text-xs text-content-secondary">
            Selecciona un punto en el mapa para descubrir ofertas y packs cerca de ti.
          </p>
          <BaseButton
            variant="primary"
            size="sm"
            block
            @click="openLocationPicker"
          >
            Elegir ubicación en el mapa
          </BaseButton>
        </div>
      </div>
    </Transition>
  </div>
</template>
