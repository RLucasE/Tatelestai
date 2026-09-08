import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useLocationStore = defineStore(
  'location',
  () => {
    const latitude = ref(null)
    const longitude = ref(null)
    const radiusKm = ref(3)

    const isActive = computed(() => latitude.value !== null && longitude.value !== null)

    function setLocation(lat, lng) {
      latitude.value = lat
      longitude.value = lng
    }

    function setRadius(km) {
      radiusKm.value = km
    }

    function clearLocation() {
      latitude.value = null
      longitude.value = null
    }

    return { latitude, longitude, radiusKm, isActive, setLocation, setRadius, clearLocation }
  },
  {
    persist: {
      key: 'location',
      storage: sessionStorage,
    },
  }
)
