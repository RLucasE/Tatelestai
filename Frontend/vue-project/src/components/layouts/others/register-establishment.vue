<script setup>
import { ref, onMounted, computed, watch } from "vue";
import axiosInstance from "@/lib/axios";
import MessageDialog from "@/components/common/MessageDialog.vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth.js";
import { USER_STATES } from "@/constants/userStates.js";
import LocationMapPicker from "@/components/common/LocationMapPicker.vue";

const router = useRouter();
const authStore = useAuthStore();

// Modalidad de ubicación: 'google' o 'manual'
const locationMode = ref("google");

const form = ref({
  google_place_id: "",
  name: "",
  address: "",
  latitude: null,
  longitude: null,
  phone: "",
  establishment_type_id: "",
  additional_info: "",
});

const verificationFiles = ref([]);
const maxFiles = 6;
const allowedExtensions = ['png', 'jpg', 'jpeg', 'pdf'];
const maxFileSize = 5 * 1024 * 1024; // 5MB

const documentCategories = [
  { value: "tax_certificate", label: "Constancia CUIT (ARCA / AFIP)" },
  { value: "municipal_permit", label: "Habilitación Municipal u Oblea" },
  { value: "municipal_tax_receipt", label: "Boleta de Tasa Municipal (ARMSa)" },
  { value: "pest_control_certificate", label: "Certificado de Desinfección / Fumigación" },
  { value: "store_counter_photo", label: "Foto de Mostrador / Cocina interior" },
  { value: "facade_photo", label: "Foto de Fachada Exterior" },
  { value: "other", label: "Otro documento adicional" },
];

const searchQuery = ref("");
const searchResults = ref([]);
const selectedPlace = ref(null);
const establishmentTypes = ref([]);
const loading = ref(false);
const searchLoading = ref(false);
const error = ref("");
const successVisible = ref(false);
const successMessage = ref("Establecimiento registrado correctamente. Espera la verificación del administrador.");
const cancelLoading = ref(false);
const isDragging = ref(false);
const dropZoneRef = ref(null);


const isValid = computed(() => {
  const hasValidLocation = locationMode.value === "google"
    ? form.value.google_place_id !== ""
    : (form.value.name.trim() !== "" &&
       form.value.address.trim() !== "" &&
       form.value.latitude !== null &&
       form.value.longitude !== null);

  return (
    hasValidLocation &&
    form.value.establishment_type_id !== "" &&
    verificationFiles.value.length > 0 &&
    verificationFiles.value.length <= maxFiles
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


// Debounce search en Google Places
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

// Validar archivo individual
const validateFile = (file) => {
  const extension = file.name.split('.').pop().toLowerCase();
  if (!allowedExtensions.includes(extension)) {
    return `"${file.name}" no es un formato válido. Solo se permiten: PNG, JPG, PDF.`;
  }
  if (file.size > maxFileSize) {
    return `"${file.name}" supera el tamaño máximo de 5MB.`;
  }
  return null;
};

// Procesar archivos
const processFiles = (files) => {
  error.value = "";
  const fileList = Array.from(files);

  const availableSlots = maxFiles - verificationFiles.value.length;
  if (availableSlots <= 0) {
    error.value = `Ya tienes el máximo de ${maxFiles} archivos.`;
    return;
  }

  if (fileList.length > availableSlots) {
    error.value = `Solo puedes agregar ${availableSlots} archivo(s) más (máximo ${maxFiles}).`;
    return;
  }

  const validationErrors = [];
  for (const file of fileList) {
    const validationError = validateFile(file);
    if (validationError) {
      validationErrors.push(validationError);
    }
  }

  if (validationErrors.length > 0) {
    error.value = validationErrors.join(' ');
    return;
  }

  for (const file of fileList) {
    const fileItem = {
      id: Date.now() + Math.random(),
      file: file,
      preview: null,
      document_category: "tax_certificate",
    };

    if (file.type.startsWith('image/')) {
      const reader = new FileReader();
      reader.onload = (e) => {
        fileItem.preview = e.target.result;
      };
      reader.readAsDataURL(file);
    }

    verificationFiles.value.push(fileItem);
  }
};

// Drag & Drop handlers
const onDragEnter = (e) => {
  e.preventDefault();
  isDragging.value = true;
};

const onDragOver = (e) => {
  e.preventDefault();
  isDragging.value = true;
};

const onDragLeave = (e) => {
  e.preventDefault();
  if (dropZoneRef.value && !dropZoneRef.value.contains(e.relatedTarget)) {
    isDragging.value = false;
  }
};

const onDrop = (e) => {
  e.preventDefault();
  isDragging.value = false;
  if (e.dataTransfer?.files?.length > 0) {
    processFiles(e.dataTransfer.files);
  }
};

const fileInputRef = ref(null);
const triggerFileInput = () => {
  if (verificationFiles.value.length >= maxFiles) {
    error.value = `Ya tienes el máximo de ${maxFiles} archivos.`;
    return;
  }
  fileInputRef.value?.click();
};

const handleFileInputChange = (event) => {
  if (event.target.files?.length > 0) {
    processFiles(event.target.files);
  }
  event.target.value = '';
};

const removeFile = (fileId) => {
  verificationFiles.value = verificationFiles.value.filter(f => f.id !== fileId);
  error.value = "";
};

const getFileExtension = (fileName) => {
  return fileName.split('.').pop().toUpperCase();
};

const getFileSize = (size) => {
  if (size < 1024) return `${size} B`;
  if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`;
  return `${(size / (1024 * 1024)).toFixed(1)} MB`;
};

const resetForm = () => {
  form.value.google_place_id = "";
  form.value.name = "";
  form.value.address = "";
  form.value.latitude = null;
  form.value.longitude = null;
  form.value.phone = "";
  form.value.establishment_type_id = "";
  form.value.additional_info = "";
  selectedPlace.value = null;
  searchQuery.value = "";
  verificationFiles.value = [];
};

const registerEstablishment = async () => {
  loading.value = true;
  error.value = "";
  try {
    const formData = new FormData();
    formData.append("establishment_type_id", Number(form.value.establishment_type_id));

    if (locationMode.value === "google") {
      formData.append("google_place_id", form.value.google_place_id);
    } else {
      formData.append("name", form.value.name);
      formData.append("address", form.value.address);
      formData.append("latitude", form.value.latitude);
      formData.append("longitude", form.value.longitude);
      if (form.value.phone) {
        formData.append("phone", form.value.phone);
      }
    }

    verificationFiles.value.forEach((fileItem, index) => {
      formData.append(`verification_files[${index}][file]`, fileItem.file);
      if (fileItem.document_category) {
        formData.append(`verification_files[${index}][document_category]`, fileItem.document_category);
      }
    });

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
    } else if (e.response?.data?.errors) {
      const errors = Object.values(e.response.data.errors).flat();
      error.value = errors.join(', ');
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

const cancelSellerRegistration = async () => {
  if (!confirm("¿Estás seguro de que deseas cancelar tu registro como vendedor? Volverás al rol por defecto.")) {
    return;
  }

  cancelLoading.value = true;
  error.value = "";
  try {
    await authStore.cancelSellerRegistration();
    router.push({ name: "select-role" });
  } catch (e) {
    if (e.response?.data?.message) {
      error.value = e.response.data.message;
    } else {
      error.value = "Error al cancelar el registro";
    }
  } finally {
    cancelLoading.value = false;
  }
};

onMounted(fetchEstablishmentTypes);
</script>

<template>
  <div class="min-h-[calc(100vh-3.5rem)] bg-[var(--color-bg)] p-6 md:p-8 lg:p-12">
    <div class="w-full max-w-4xl mx-auto">
      <!-- Header Section -->
      <div class="mb-8">
        <h2 class="text-2xl md:text-3xl font-bold text-[var(--color-text)] mb-2">Registrar establecimiento</h2>
        <p class="text-base text-[var(--color-text)]/70">
          Completa la ubicación de tu local y sube los documentos que tengas disponibles. Cuantas más pruebas aportes, más rápido podremos aprobarte.
        </p>
      </div>

      <!-- Guía informativa de requisitos para habilitación -->
      <div class="requirements-guide-card">
        <div class="flex items-start gap-3.5">
          <div class="w-10 h-10 rounded-xl bg-[var(--color-focus)]/20 text-[var(--color-focus)] flex items-center justify-center flex-shrink-0 border border-[var(--color-focus)]/30">
            <svg class="w-5 h-5 text-[var(--color-focus)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
          </div>
          <div class="flex-1">
            <h4 class="font-bold text-white text-base mb-1">Requisitos y documentación para habilitar tu establecimiento</h4>
            <p class="text-xs text-[var(--color-text)]/80 leading-relaxed mb-4">
              Para verificar tu local gastronómico y habilitarlo para la venta de excedentes, nuestro equipo revisará la información que proporciones. Sube los comprobantes que tengas a mano para comenzar.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Columna 1: Documentación que puedes subir -->
              <div class="bg-[var(--color-secondary)]/70 rounded-xl p-3.5 border border-[var(--color-border)]">
                <div class="text-xs font-semibold text-emerald-400 uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                  <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  <span>Documentos que puedes adjuntar</span>
                </div>
                <ul class="space-y-2 text-xs text-[var(--color-text)]/90">
                  <li class="flex items-start gap-2">
                    <span class="text-emerald-400 font-bold mt-0.5">•</span>
                    <span><b>Constancia de CUIT (ARCA / AFIP)</b>: Acredita la titularidad y actividad comercial.</span>
                  </li>
                  <li class="flex items-start gap-2">
                    <span class="text-emerald-400 font-bold mt-0.5">•</span>
                    <span><b>Habilitación Municipal u Oblea (ARMSa)</b>: Permiso o constancia de habilitación en Salta.</span>
                  </li>
                  <li class="flex items-start gap-2">
                    <span class="text-emerald-400 font-bold mt-0.5">•</span>
                    <span><b>Boleta de Tasa Municipal (TISSH)</b>: Comprobante o constancia de pago municipal del local.</span>
                  </li>
                  <li class="flex items-start gap-2">
                    <span class="text-emerald-400 font-bold mt-0.5">•</span>
                    <span><b>Certificado de Fumigación / Desinfección</b>: Certificado sanitario o control de plagas al día.</span>
                  </li>
                  <li class="flex items-start gap-2">
                    <span class="text-emerald-400 font-bold mt-0.5">•</span>
                    <span><b>Fotos del establecimiento</b>: Fachada con cartel y mostrador o cocina interior.</span>
                  </li>
                </ul>
              </div>

              <!-- Columna 2: Qué tener en cuenta -->
              <div class="bg-[var(--color-secondary)]/70 rounded-xl p-3.5 border border-[var(--color-border)]">
                <div class="text-xs font-semibold text-purple-300 uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                  <svg class="w-4 h-4 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span>Qué tener en cuenta para la aprobación</span>
                </div>
                <ul class="space-y-2 text-xs text-[var(--color-text)]/90">
                  <li class="flex items-start gap-2">
                    <span class="text-purple-400 font-bold mt-0.5">•</span>
                    <span><b>Punto de retiro exacto</b>: La dirección y el punto en el mapa deben coincidir con la entrada del local.</span>
                  </li>
                  <li class="flex items-start gap-2">
                    <span class="text-purple-400 font-bold mt-0.5">•</span>
                    <span><b>Archivos claros y legibles</b>: Sube fotos o PDFs nítidos (formatos PDF, JPG o PNG hasta 5MB).</span>
                  </li>
                  <li class="flex items-start gap-2">
                    <span class="text-purple-400 font-bold mt-0.5">•</span>
                    <span><b>Contacto directo</b>: Podemos comunicarnos por teléfono o WhatsApp para coordinar el alta comercial.</span>
                  </li>
                  <li class="flex items-start gap-2">
                    <span class="text-purple-400 font-bold mt-0.5">•</span>
                    <span><b>No es necesario tener todos los papeles juntos</b>: Puedes enviar los que tengas hoy. Cuantos más aportes, más rápida será la revisión.</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Form -->
      <form @submit.prevent="registerEstablishment">
        <div class="space-y-6">

          <!-- Selector de Modo de Ubicación -->
          <div class="space-y-3">
            <label class="block text-sm font-semibold text-[var(--color-text)]">
              ¿Cómo deseas registrar la ubicación de tu local? <span class="text-red-400">*</span>
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <button
                type="button"
                @click="locationMode = 'google'"
                class="location-toggle-btn"
                :class="{ 'location-toggle-btn--active': locationMode === 'google' }"
              >
                <div class="font-semibold text-sm flex items-center gap-2">
                  <svg class="w-4 h-4 text-[var(--color-focus)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                  <span>Buscar en Google Places</span>
                </div>
                <div class="text-xs text-[var(--color-text)]/60 mt-0.5">Recomendado si tu negocio ya figura en Google Maps para agilizar la verificación</div>
              </button>

              <button
                type="button"
                @click="locationMode = 'manual'"
                class="location-toggle-btn"
                :class="{ 'location-toggle-btn--active': locationMode === 'manual' }"
              >
                <div class="font-semibold text-sm flex items-center gap-2">
                  <svg class="w-4 h-4 text-[var(--color-focus)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <span>Ubicar manualmente en mapa</span>
                </div>
                <div class="text-xs text-[var(--color-text)]/60 mt-0.5">Para negocios nuevos o sin perfil comercial en Google</div>
              </button>
            </div>
          </div>

          <!-- MODALIDAD A: Búsqueda de Google Places -->
          <div v-if="locationMode === 'google'" class="space-y-3">
            <label for="place-search" class="block text-sm font-semibold text-[var(--color-text)]">
              Buscar tu establecimiento en Google Places <span class="text-red-400">*</span>
            </label>
            <div class="relative">
              <div class="absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none">
                <svg class="w-5 h-5 text-[var(--color-text)]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <input id="place-search" v-model="searchQuery" type="text" :disabled="selectedPlace !== null"
                placeholder="Escribe el nombre de tu negocio o restaurante..."
                class="w-full pl-12 pr-12 py-3.5 rounded-xl bg-[var(--color-primary)] text-[var(--color-text)] placeholder-[var(--color-text)]/40 border border-[var(--color-border)] focus:outline-none focus:ring-2 focus:ring-[var(--color-focus)] focus:border-transparent transition disabled:opacity-50" />
              <div v-if="searchLoading" class="absolute right-4 top-1/2 -translate-y-1/2">
                <svg class="animate-spin h-5 w-5 text-[var(--color-text)]" viewBox="0 0 24 24">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="4"
                    fill="none" />
                  <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="4" fill="none" />
                </svg>
              </div>
            </div>

            <!-- Resultados de búsqueda -->
            <div v-if="searchResults.length > 0"
              class="mt-2 max-h-64 overflow-y-auto bg-[var(--color-primary)] border border-[var(--color-border)] rounded-xl overflow-hidden shadow-xl">
              <button v-for="place in searchResults" :key="place.place_id" type="button" @click="selectPlace(place)"
                class="w-full text-left px-4 py-3.5 hover:bg-[var(--color-secondary)] transition border-b border-[var(--color-border)] last:border-b-0">
                <div class="font-medium text-[var(--color-text)]">{{ place.name }}</div>
                <div class="text-sm text-[var(--color-text)]/60 mt-0.5">{{ place.formatted_address }}</div>
              </button>
            </div>

            <!-- Lugar Seleccionado -->
            <div v-if="selectedPlace"
              class="mt-2 p-4 bg-[var(--color-focus)]/10 border-l-4 border-[var(--color-focus)] rounded-r-xl flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-2">
                  <svg class="w-5 h-5 text-[var(--color-focus)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <div class="font-semibold text-[var(--color-text)]">{{ selectedPlace.name }}</div>
                </div>
                <div class="text-sm text-[var(--color-text)]/70 mt-1 ml-7">{{ selectedPlace.formatted_address }}</div>
              </div>
              <button type="button" @click="clearSelection"
                class="ml-3 p-1 text-red-400 hover:text-red-300 hover:bg-red-400/10 rounded-lg transition"
                title="Cambiar lugar">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>

          <!-- MODALIDAD B: Ubicación Manual con Mapa -->
          <div v-else class="space-y-4 p-4 rounded-xl bg-[var(--color-primary)] border border-[var(--color-border)]">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-[var(--color-text)] mb-1">
                  Nombre del local <span class="text-red-400">*</span>
                </label>
                <input
                  v-model="form.name"
                  type="text"
                  placeholder="Ej. Panadería La Tradición"
                  class="w-full px-4 py-2.5 rounded-lg bg-[var(--color-secondary)] text-[var(--color-text)] border border-[var(--color-border)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-focus)]"
                />
              </div>

              <div>
                <label class="block text-xs font-semibold text-[var(--color-text)] mb-1">
                  Dirección exacta de retiro <span class="text-red-400">*</span>
                </label>
                <input
                  v-model="form.address"
                  type="text"
                  placeholder="Ej. Av. San Martín 1234, Salta"
                  class="w-full px-4 py-2.5 rounded-lg bg-[var(--color-secondary)] text-[var(--color-text)] border border-[var(--color-border)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-focus)]"
                />
              </div>

              <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-[var(--color-text)] mb-1">
                  Teléfono de contacto comercial
                </label>
                <input
                  v-model="form.phone"
                  type="text"
                  placeholder="Ej. 387 4123456"
                  class="w-full px-4 py-2.5 rounded-lg bg-[var(--color-secondary)] text-[var(--color-text)] border border-[var(--color-border)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-focus)]"
                />
              </div>
            </div>

            <!-- Mapa interactivo modular con tema oscuro -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <label class="text-xs font-semibold text-[var(--color-text)]">
                  Marca el punto exacto en el mapa <span class="text-red-400">*</span>
                </label>
                <span v-if="form.latitude && form.longitude" class="text-xs text-emerald-400 font-medium font-mono">
                  Coordenadas: {{ form.latitude }}, {{ form.longitude }}
                </span>
                <span v-else class="text-xs text-amber-300">
                  Haz clic en el mapa para fijar el pin
                </span>
              </div>
              <LocationMapPicker
                v-model:latitude="form.latitude"
                v-model:longitude="form.longitude"
                height="17rem"
              />
            </div>
          </div>

          <!-- Tipo de Establecimiento -->
          <div class="space-y-3">
            <label for="estab-type" class="block text-sm font-semibold text-[var(--color-text)]">
              Tipo de establecimiento <span class="text-red-400">*</span>
            </label>
            <div class="relative">
              <select id="estab-type" v-model="form.establishment_type_id" required
                class="w-full px-4 py-3.5 rounded-xl bg-[var(--color-primary)] text-[var(--color-text)] border border-[var(--color-border)] focus:outline-none focus:ring-2 focus:ring-[var(--color-focus)] focus:border-transparent transition appearance-none cursor-pointer">
                <option value="" disabled>Seleccione uno</option>
                <option v-for="type in establishmentTypes" :key="type.id" :value="type.id">
                  {{ type.name }}
                </option>
              </select>
              <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                <svg class="w-5 h-5 text-[var(--color-text)]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </div>
          </div>

          <!-- Archivos de Verificación -->
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <label class="block text-sm font-semibold text-[var(--color-text)]">
                Documentos y Fotos de Verificación <span class="text-red-400">*</span>
              </label>
              <span class="text-xs font-normal text-[var(--color-text)]/60">
                ({{ verificationFiles.length }}/{{ maxFiles }} archivos)
              </span>
            </div>

            <!-- Drop Zone -->
            <div
              ref="dropZoneRef"
              class="drop-zone"
              :class="{ 'drop-zone--active': isDragging, 'drop-zone--has-files': verificationFiles.length > 0, 'drop-zone--full': verificationFiles.length >= maxFiles }"
              @dragenter="onDragEnter"
              @dragover="onDragOver"
              @dragleave="onDragLeave"
              @drop="onDrop"
              @click="triggerFileInput"
            >
              <input
                ref="fileInputRef"
                type="file"
                accept=".png,.jpg,.jpeg,.pdf"
                multiple
                class="hidden"
                @change="handleFileInputChange"
                @click.stop
              />

              <div v-if="verificationFiles.length >= maxFiles" class="drop-zone__content">
                <svg class="w-12 h-12 text-[var(--color-text)]/30 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <p class="text-[var(--color-text)]/60 text-sm">Has alcanzado el máximo de {{ maxFiles }} archivos</p>
              </div>

              <div v-else class="drop-zone__content">
                <svg class="w-12 h-12 mx-auto mb-3 transition-colors" :class="isDragging ? 'text-[var(--color-focus)]' : 'text-[var(--color-text)]/30'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                <p class="text-[var(--color-text)] font-medium mb-1">
                  {{ isDragging ? 'Suelta los archivos aquí' : 'Arrastra y suelta tus archivos aquí' }}
                </p>
                <p class="text-sm text-[var(--color-text)]/50 mb-3">o haz clic para seleccionar</p>
                <div class="flex items-center justify-center gap-2 flex-wrap">
                  <span class="file-badge">PNG</span>
                  <span class="file-badge">JPG</span>
                  <span class="file-badge">PDF</span>
                  <span class="text-xs text-[var(--color-text)]/40">máx. 5MB por archivo</span>
                </div>
              </div>
            </div>

            <!-- Lista de Archivos Subidos con Clasificación de Categoría -->
            <div v-if="verificationFiles.length > 0" class="space-y-3">
              <div v-for="fileItem in verificationFiles" :key="fileItem.id" class="file-item">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                  <div class="flex items-center gap-3 min-w-0">
                    <div class="flex-shrink-0">
                      <div v-if="fileItem.preview" class="w-12 h-12 rounded-lg overflow-hidden border border-[var(--color-border)]">
                        <img :src="fileItem.preview" class="w-full h-full object-cover" alt="Preview" />
                      </div>
                      <div v-else class="w-12 h-12 rounded-lg bg-[var(--color-secondary)] flex items-center justify-center">
                        <svg class="w-6 h-6 text-[var(--color-text)]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                      </div>
                    </div>

                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium text-[var(--color-text)] truncate">{{ fileItem.file.name }}</p>
                      <div class="flex items-center gap-2 mt-0.5">
                        <span class="file-badge file-badge--small">{{ getFileExtension(fileItem.file.name) }}</span>
                        <span class="text-xs text-[var(--color-text)]/50">{{ getFileSize(fileItem.file.size) }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- Selector de Tipo de Documento -->
                  <div class="flex items-center gap-2">
                    <select
                      v-model="fileItem.document_category"
                      class="px-3 py-1.5 rounded-lg bg-[var(--color-secondary)] text-[var(--color-text)] text-xs border border-[var(--color-border)] focus:outline-none focus:ring-1 focus:ring-[var(--color-focus)] cursor-pointer"
                    >
                      <option v-for="cat in documentCategories" :key="cat.value" :value="cat.value">
                        {{ cat.label }}
                      </option>
                    </select>

                    <button type="button" @click.stop="removeFile(fileItem.id)"
                      class="p-1.5 text-red-400 hover:text-red-300 hover:bg-red-400/10 rounded-lg transition"
                      title="Eliminar archivo">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Información adicional -->
          <div class="space-y-3">
            <label for="additional-info" class="block text-sm font-semibold text-[var(--color-text)]">
              Información adicional (opcional)
            </label>
            <textarea id="additional-info" v-model="form.additional_info" rows="3" maxlength="1000"
              placeholder="Horarios habituales, notas sobre la entrada o datos relevantes..."
              class="w-full px-4 py-3 rounded-xl bg-[var(--color-primary)] text-[var(--color-text)] placeholder-[var(--color-text)]/40 border border-[var(--color-border)] focus:outline-none focus:ring-2 focus:ring-[var(--color-focus)] focus:border-transparent transition resize-none"></textarea>
          </div>

          <!-- Error Message -->
          <div v-if="error"
            class="flex items-start gap-3 text-sm text-red-300 bg-red-900/20 border-l-4 border-red-500 rounded-r-xl px-4 py-3">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ error }}</span>
          </div>

          <!-- Action Buttons -->
          <div class="pt-4 flex flex-col sm:flex-row justify-between items-center gap-4">
            <button type="button" @click="cancelSellerRegistration" :disabled="cancelLoading || loading"
              class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl text-[var(--color-text)] bg-transparent border-2 border-red-500/50 hover:bg-red-900/20 hover:border-red-500 disabled:opacity-50 disabled:cursor-not-allowed transition font-medium">
              <svg v-if="cancelLoading" class="animate-spin" width="18" height="18" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="4"
                  fill="none" />
                <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="4" fill="none" />
              </svg>
              <span>{{ cancelLoading ? 'Cancelando…' : 'Cancelar registro' }}</span>
            </button>

            <button type="submit" :disabled="loading || !isValid || cancelLoading"
              class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3 rounded-xl text-white bg-[var(--color-focus)] hover:bg-[var(--color-focus)]/90 disabled:opacity-50 disabled:cursor-not-allowed transition font-semibold shadow-lg shadow-[var(--color-focus)]/20">
              <svg v-if="loading" class="animate-spin" width="18" height="18" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="4"
                  fill="none" />
                <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="4" fill="none" />
              </svg>
              <span>{{ loading ? 'Registrando…' : 'Enviar para verificación' }}</span>
            </button>
          </div>
        </div>
      </form>
    </div>

    <MessageDialog :message="successMessage" :visible="successVisible" @accept="handleAcceptSuccess" />
  </div>
</template>

<style scoped>
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

.requirements-guide-card,
.trust-guide-card {
  background: var(--color-primary);
  border-left: 4px solid var(--color-focus);
  padding: 1.25rem;
  margin: 1.5rem 0;
  border-radius: 0.75rem;
  border: 1px solid var(--color-border);
  border-left-width: 4px;
}

.location-toggle-btn {
  text-align: left;
  padding: 1rem;
  border-radius: 0.75rem;
  background: var(--color-primary);
  border: 2px solid var(--color-border);
  color: var(--color-text);
  transition: all 0.2s ease;
  cursor: pointer;
}

.location-toggle-btn:hover {
  border-color: var(--color-focus);
}

.location-toggle-btn--active {
  border-color: var(--color-focus);
  background: color-mix(in srgb, var(--color-focus) 12%, var(--color-primary));
}

.drop-zone {
  position: relative;
  border: 2px dashed var(--color-border);
  border-radius: 0.75rem;
  background: var(--color-primary);
  padding: 2rem 1.5rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.drop-zone:hover {
  border-color: var(--color-focus);
  background: color-mix(in srgb, var(--color-focus) 5%, var(--color-primary));
}

.drop-zone--active {
  border-color: var(--color-focus);
  background: color-mix(in srgb, var(--color-focus) 10%, var(--color-primary));
  border-style: solid;
}

.drop-zone--full {
  cursor: default;
  opacity: 0.7;
}

.drop-zone--full:hover {
  border-color: var(--color-border);
  background: var(--color-primary);
}

.drop-zone__content {
  pointer-events: none;
}

.file-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.125rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.6875rem;
  font-weight: 600;
  letter-spacing: 0.05em;
  background: var(--color-secondary);
  color: var(--color-text);
  border: 1px solid var(--color-border);
}

.file-badge--small {
  padding: 0.0625rem 0.375rem;
  font-size: 0.625rem;
}

.file-item {
  padding: 0.75rem 1rem;
  background: var(--color-primary);
  border: 1px solid var(--color-border);
  border-radius: 0.75rem;
  transition: all 0.15s ease;
}

.file-item:hover {
  border-color: color-mix(in srgb, var(--color-border) 70%, var(--color-text));
}
</style>
