<script setup>
import { ref, onMounted, computed, watch } from "vue";
import axiosInstance from "@/lib/axios";
import MessageDialog from "@/components/common/MessageDialog.vue";
import { useRouter } from "vue-router";
import {useAuthStore} from "@/stores/auth.js";
import { USER_STATES } from "@/constants/userStates.js";

const router = useRouter();
const authStore = useAuthStore();

const form = ref({
  google_place_id: "",
  establishment_type_id: "",
  establishment_photo: null,
  owner_selfie: null,
  additional_info: "",
});

const searchQuery = ref("");
const searchResults = ref([]);
const selectedPlace = ref(null);
const establishmentTypes = ref([]);
const loading = ref(false);
const searchLoading = ref(false);
const error = ref("");
const successVisible = ref(false);
const successMessage = ref("Establecimiento registrado correctamente. Espera la verificación del administrador.");

const establishmentPhotoPreview = ref(null);
const ownerSelfiePreview = ref(null);

const isValid = computed(() => {
  return (
    form.value.google_place_id !== "" &&
    form.value.establishment_type_id !== "" &&
    form.value.establishment_photo !== null &&
    form.value.owner_selfie !== null
  );
});

const fetchEstablishmentTypes = async () => {
  try {
    const res = await axiosInstance.get("/establishment-types");
    establishmentTypes.value = res.data;
  } catch (e) {
    error.value = "Error cargando tipos de establecimiento";
  }
};

// Debounce search
let searchTimeout = null;
watch(searchQuery, (newVal) => {
  if (searchTimeout) clearTimeout(searchTimeout);

  if (newVal.length < 3) {
    searchResults.value = [];
    return;
  }

  searchTimeout = setTimeout(() => {
    searchPlaces(newVal);
  }, 500);
});

const searchPlaces = async (query) => {
  if (!query || query.length < 3) return;

  searchLoading.value = true;
  try {
    const res = await axiosInstance.get("/places/search", {
      params: { query }
    });

    if (res.data.status === "OK") {
      searchResults.value = res.data.results || [];
    } else {
      searchResults.value = [];
    }
  } catch (e) {
    console.error("Error searching places:", e);
    searchResults.value = [];
  } finally {
    searchLoading.value = false;
  }
};

const selectPlace = async (place) => {
  selectedPlace.value = place;
  form.value.google_place_id = place.place_id;
  searchQuery.value = place.name;
  searchResults.value = [];
};

const clearSelection = () => {
  selectedPlace.value = null;
  form.value.google_place_id = "";
  searchQuery.value = "";
};

const handleEstablishmentPhotoChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    form.value.establishment_photo = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      establishmentPhotoPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const handleOwnerSelfieChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    form.value.owner_selfie = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      ownerSelfiePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const resetForm = () => {
  form.value.google_place_id = "";
  form.value.establishment_type_id = "";
  form.value.establishment_photo = null;
  form.value.owner_selfie = null;
  form.value.additional_info = "";
  selectedPlace.value = null;
  searchQuery.value = "";
  establishmentPhotoPreview.value = null;
  ownerSelfiePreview.value = null;
};

const registerEstablishment = async () => {
  loading.value = true;
  error.value = "";
  try {
    const formData = new FormData();
    formData.append("google_place_id", form.value.google_place_id);
    formData.append("establishment_type_id", Number(form.value.establishment_type_id));
    formData.append("establishment_photo", form.value.establishment_photo);
    formData.append("owner_selfie", form.value.owner_selfie);
    if (form.value.additional_info) {
      formData.append("additional_info", form.value.additional_info);
    }

    await axiosInstance.post("/food-establishment", formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });

    successVisible.value = true;
    resetForm();
    authStore.setState(USER_STATES.WAITING_FOR_CONFIRMATION);
    router.push({ name: "waiting-confirmation" });
  } catch (e) {
    if (e.response?.data?.message) {
      error.value = e.response.data.message;
    } else {
      error.value = "Error al registrar el establecimiento";
    }
  } finally {
    loading.value = false;

  }
};

const handleAcceptSuccess = () => {
  successVisible.value = false;
};

onMounted(fetchEstablishmentTypes);
</script>

<template>
  <div class="min-h-[calc(100vh-3.5rem)] flex items-center justify-center bg-[var(--color-bg)] p-4">
    <div class="w-full max-w-2xl">
      <div class="rounded-2xl shadow-xl border border-[var(--color-border)] overflow-hidden">
        <div class="px-6 py-5 bg-[var(--color-secondary)] border-b border-[var(--color-border)]">
          <h2 class="m-0 text-xl font-semibold text-[var(--color-text)]">Registrar establecimiento</h2>
          <p class="mt-1 text-sm text-[var(--color-text)]/80">Busca tu establecimiento en Google Places y completa la verificación con fotos.</p>
        </div>

        <form @submit.prevent="registerEstablishment" class="bg-[var(--color-primary)] px-6 py-6">
          <div class="space-y-5">
            <!-- Google Places Search -->
            <div>
              <label for="place-search" class="block text-sm font-medium text-[var(--color-text)] mb-1">
                Buscar tu establecimiento en Google Places <span class="text-red-400">*</span>
              </label>
              <div class="relative">
                <input
                  id="place-search"
                  v-model="searchQuery"
                  type="text"
                  :disabled="selectedPlace !== null"
                  placeholder="Busca tu negocio..."
                  class="w-full px-3 py-2 rounded-lg bg-[var(--color-darkest)] text-[var(--color-text)] placeholder-[var(--color-text)]/40 border border-[var(--color-border)] focus:outline-none focus:ring-2 focus:ring-[var(--color-focus)] focus:border-[var(--color-focus)] transition disabled:opacity-50"
                />
                <div v-if="searchLoading" class="absolute right-3 top-2.5">
                  <svg class="animate-spin h-5 w-5 text-[var(--color-text)]" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="4" fill="none"/>
                    <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="4" fill="none"/>
                  </svg>
                </div>
              </div>

              <!-- Search Results -->
              <div v-if="searchResults.length > 0" class="mt-2 max-h-64 overflow-y-auto bg-[var(--color-darkest)] border border-[var(--color-border)] rounded-lg">
                <button
                  v-for="place in searchResults"
                  :key="place.place_id"
                  type="button"
                  @click="selectPlace(place)"
                  class="w-full text-left px-4 py-3 hover:bg-[var(--color-secondary)] transition border-b border-[var(--color-border)] last:border-b-0"
                >
                  <div class="font-medium text-[var(--color-text)]">{{ place.name }}</div>
                  <div class="text-sm text-[var(--color-text)]/60">{{ place.formatted_address }}</div>
                </button>
              </div>

              <!-- Selected Place -->
              <div v-if="selectedPlace" class="mt-2 p-3 bg-[var(--color-secondary)] border border-[var(--color-focus)] rounded-lg flex items-start justify-between">
                <div class="flex-1">
                  <div class="font-medium text-[var(--color-text)]">{{ selectedPlace.name }}</div>
                  <div class="text-sm text-[var(--color-text)]/70 mt-1">{{ selectedPlace.formatted_address }}</div>
                </div>
                <button
                  type="button"
                  @click="clearSelection"
                  class="ml-2 text-red-400 hover:text-red-300"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>
            </div>

            <!-- Establishment Type -->
            <div>
              <label for="estab-type" class="block text-sm font-medium text-[var(--color-text)] mb-1">
                Tipo de establecimiento <span class="text-red-400">*</span>
              </label>
              <select
                id="estab-type"
                v-model="form.establishment_type_id"
                required
                class="w-full px-3 py-2 rounded-lg bg-[var(--color-darkest)] text-[var(--color-text)] border border-[var(--color-border)] focus:outline-none focus:ring-2 focus:ring-[var(--color-focus)] focus:border-[var(--color-focus)] transition"
              >
                <option value="" disabled>Seleccione uno</option>
                <option v-for="type in establishmentTypes" :key="type.id" :value="type.id">
                  {{ type.name }}
                </option>
              </select>
            </div>

            <!-- Establishment Photo -->
            <div>
              <label class="block text-sm font-medium text-[var(--color-text)] mb-1">
                Foto del establecimiento <span class="text-red-400">*</span>
              </label>
              <p class="text-xs text-[var(--color-text)]/60 mb-2">Una foto clara de la fachada o interior de tu negocio</p>
              <input
                type="file"
                accept="image/jpeg,image/png,image/jpg"
                @change="handleEstablishmentPhotoChange"
                class="hidden"
                id="establishment-photo"
              />
              <label
                for="establishment-photo"
                class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 rounded-lg text-[var(--color-text)] bg-[var(--color-darkest)] border border-[var(--color-border)] hover:border-[var(--color-focus)] transition"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Seleccionar foto
              </label>
              <div v-if="establishmentPhotoPreview" class="mt-3">
                <img :src="establishmentPhotoPreview" class="w-full max-w-xs rounded-lg border border-[var(--color-border)]" alt="Vista previa del establecimiento"/>
              </div>
            </div>

            <!-- Owner Selfie -->
            <div>
              <label class="block text-sm font-medium text-[var(--color-text)] mb-1">
                Selfie del propietario <span class="text-red-400">*</span>
              </label>
              <p class="text-xs text-[var(--color-text)]/60 mb-2">Una selfie tuya para verificar tu identidad</p>
              <input
                type="file"
                accept="image/jpeg,image/png,image/jpg"
                @change="handleOwnerSelfieChange"
                class="hidden"
                id="owner-selfie"
              />
              <label
                for="owner-selfie"
                class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 rounded-lg text-[var(--color-text)] bg-[var(--color-darkest)] border border-[var(--color-border)] hover:border-[var(--color-focus)] transition"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Tomar/Seleccionar selfie
              </label>
              <div v-if="ownerSelfiePreview" class="mt-3">
                <img :src="ownerSelfiePreview" class="w-full max-w-xs rounded-lg border border-[var(--color-border)]" alt="Vista previa de selfie"/>
              </div>
            </div>

            <!-- Additional Info (Optional) -->
            <div>
              <label for="additional-info" class="block text-sm font-medium text-[var(--color-text)] mb-1">
                Información adicional (opcional)
              </label>
              <textarea
                id="additional-info"
                v-model="form.additional_info"
                rows="3"
                maxlength="1000"
                placeholder="Agrega cualquier información adicional que consideres relevante..."
                class="w-full px-3 py-2 rounded-lg bg-[var(--color-darkest)] text-[var(--color-text)] placeholder-[var(--color-text)]/40 border border-[var(--color-border)] focus:outline-none focus:ring-2 focus:ring-[var(--color-focus)] focus:border-[var(--color-focus)] transition resize-none"
              ></textarea>
            </div>

            <div v-if="error" class="text-sm text-red-300 bg-red-900/30 border border-red-900/60 rounded-lg px-3 py-2">
              {{ error }}
            </div>

            <div class="pt-2 flex justify-end">
              <button
                type="submit"
                :disabled="loading || !isValid"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-[var(--color-text)] bg-[var(--color-darkest)] border border-[var(--color-focus)] hover:bg-[var(--color-focus)] disabled:opacity-60 disabled:cursor-not-allowed transition"
              >
                <svg v-if="loading" class="animate-spin" width="16" height="16" viewBox="0 0 24 24">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="4" fill="none"/>
                  <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="4" fill="none"/>
                </svg>
                <span>{{ loading ? 'Registrando…' : 'Enviar para verificación' }}</span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <MessageDialog :message="successMessage" :visible="successVisible" @accept="handleAcceptSuccess" />
  </div>
</template>

<style scoped>
/* Spinner animation utility (fallback if Tailwind not available) */
@keyframes spin { to { transform: rotate(360deg) } }
.animate-spin { animation: spin 1s linear infinite; }
</style>
