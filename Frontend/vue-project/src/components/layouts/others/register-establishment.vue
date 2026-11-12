<script setup>
import { ref, onMounted, computed, watch } from "vue";
import axiosInstance from "@/lib/axios";
import MessageDialog from "@/components/common/MessageDialog.vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth.js";
import { USER_STATES } from "@/constants/userStates.js";
import OnboardingTopNavbar from "./OnboardingTopNavbar.vue";
import ManualLocationMapBlock from "./ManualLocationMapBlock.vue";
import VerificationFileRow from "./VerificationFileRow.vue";

const router = useRouter();
const authStore = useAuthStore();

// Modalidad de ubicación: 'google' o 'manual'
const locationMode = ref("google");

const form = ref({
  google_place_id: "",
  name: "",
  address: "",
  latitude: -24.7859,
  longitude: -65.4117,
  phone: "",
  establishment_type_id: "",
  additional_info: "",
});

const verificationFiles = ref([]);
const maxFiles = 6;
const allowedExtensions = ["png", "jpg", "jpeg", "pdf"];
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
const successMessage = ref(
  "Establecimiento registrado correctamente. Espera la verificación del administrador."
);
const cancelLoading = ref(false);
const isDragging = ref(false);
const dropZoneRef = ref(null);

const isValid = computed(() => {
  const hasValidLocation =
    locationMode.value === "google"
      ? form.value.google_place_id !== ""
      : form.value.name.trim() !== "" &&
        form.value.address.trim() !== "" &&
        form.value.latitude !== null &&
        form.value.longitude !== null;

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
      params: { query },
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
  const extension = file.name.split(".").pop().toLowerCase();
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
    error.value = `Ya tenés el máximo de ${maxFiles} archivos.`;
    return;
  }

  if (fileList.length > availableSlots) {
    error.value = `Solo podés agregar ${availableSlots} archivo(s) más (máximo ${maxFiles}).`;
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
    error.value = validationErrors.join(" ");
    return;
  }

  for (const file of fileList) {
    const fileItem = {
      id: Date.now() + Math.random(),
      file: file,
      preview: null,
      document_category: "tax_certificate",
    };

    if (file.type.startsWith("image/")) {
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
    error.value = `Ya tenés el máximo de ${maxFiles} archivos.`;
    return;
  }
  fileInputRef.value?.click();
};

const handleFileInputChange = (event) => {
  if (event.target.files?.length > 0) {
    processFiles(event.target.files);
  }
  event.target.value = "";
};

const removeFile = (fileId) => {
  verificationFiles.value = verificationFiles.value.filter(
    (f) => f.id !== fileId
  );
  error.value = "";
};

const updateFileCategory = (fileId, newCategory) => {
  const found = verificationFiles.value.find((f) => f.id === fileId);
  if (found) {
    found.document_category = newCategory;
  }
};

const resetForm = () => {
  form.value.google_place_id = "";
  form.value.name = "";
  form.value.address = "";
  form.value.latitude = -24.7859;
  form.value.longitude = -65.4117;
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
    formData.append(
      "establishment_type_id",
      Number(form.value.establishment_type_id)
    );

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
        formData.append(
          `verification_files[${index}][document_category]`,
          fileItem.document_category
        );
      }
    });

    if (form.value.additional_info) {
      formData.append("additional_info", form.value.additional_info);
    }

    await axiosInstance.post("/food-establishment", formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
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
      error.value = errors.join(", ");
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
  if (
    !confirm(
      "¿Estás seguro de que deseás cancelar tu registro como vendedor? Volverás al rol por defecto."
    )
  ) {
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
  <div class="min-h-screen flex flex-col bg-canvas text-content">
    <OnboardingTopNavbar badge-text="PASO 2 DE 2 • ALTA DE ESTABLECIMIENTO" />

    <main class="flex-1 w-full max-w-[840px] mx-auto px-4 sm:px-6 pt-8 pb-16">
      <div class="space-y-8">
        <!-- PageHeader -->
        <header class="space-y-1">
          <span class="block text-[11px] font-bold uppercase tracking-wider text-brand">
            PASO 2 DE 2 • ALTA DE ESTABLECIMIENTO GASTRONÓMICO
          </span>
          <h1 class="text-2xl sm:text-[28px] font-extrabold text-content tracking-tight">
            Registrar establecimiento
          </h1>
          <p class="text-sm text-content-secondary pt-0.5">
            Completá la ubicación de tu local y subí los documentos disponibles. Cuantas más pruebas aportes, más rápido podremos aprobarte.
          </p>
        </header>

        <!-- RequirementsGuideCard -->
        <section class="bg-surface rounded-[18px] border border-border-subtle px-6 sm:px-9 py-7 space-y-6">
          <div class="flex items-start gap-3.5">
            <div class="w-11 h-11 rounded-[12px] bg-brand/15 text-brand flex items-center justify-center shrink-0">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
              </svg>
            </div>
            <div class="space-y-1">
              <h2 class="text-[16px] font-extrabold text-content">
                Requisitos y documentación para habilitar tu establecimiento
              </h2>
              <p class="text-[12.5px] text-content-secondary leading-relaxed">
                Para verificar tu local gastronómico y habilitarlo para la venta de excedentes, nuestro equipo revisará la información que proporciones. Subí los comprobantes que tengas a mano para comenzar.
              </p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-9 pt-1">
            <!-- Columna 1: Documentos que podés adjuntar -->
            <div class="space-y-2.5">
              <div class="text-[11px] font-bold text-brand uppercase tracking-wider">
                DOCUMENTOS QUE PODÉS ADJUNTAR
              </div>
              <ul class="space-y-2 text-[12.5px] text-content">
                <li>• <strong>Constancia de CUIT (ARCA / AFIP)</strong>: Acredita la titularidad comercial.</li>
                <li>• <strong>Habilitación Municipal u Oblea (ARMSa)</strong>: Permiso de habilitación en Salta.</li>
                <li>• <strong>Boleta de Tasa Municipal (TISSH)</strong>: Comprobante municipal del local.</li>
                <li>• <strong>Certificado de Fumigación / Desinfección</strong>: Control sanitario al día.</li>
                <li>• <strong>Fotos del establecimiento</strong>: Fachada exterior y mostrador o cocina interior.</li>
              </ul>
            </div>

            <!-- Columna 2: Qué tener en cuenta -->
            <div class="space-y-2.5">
              <div class="text-[11px] font-bold text-content uppercase tracking-wider">
                QUÉ TENER EN CUENTA PARA LA APROBACIÓN
              </div>
              <ul class="space-y-2 text-[12.5px] text-content">
                <li>• <strong>Punto de retiro exacto</strong>: La dirección y el pin en el mapa deben coincidir con la entrada.</li>
                <li>• <strong>Archivos claros y legibles</strong>: Subí fotos o PDFs nítidos (PDF, JPG o PNG hasta 5MB).</li>
                <li>• <strong>Contacto directo</strong>: Podemos comunicarnos por teléfono para coordinar el alta.</li>
                <li>• <strong>No es necesario tener todo junto</strong>: Podés enviar los que tengas disponibles hoy.</li>
              </ul>
            </div>
          </div>
        </section>

        <!-- EstablishmentRegistrationFormCard -->
        <form
          class="bg-surface rounded-[22px] border border-border-subtle px-6 sm:px-10 py-9 space-y-9"
          @submit.prevent="registerEstablishment"
        >
          <!-- LocationModeSection -->
          <div class="space-y-2.5">
            <label class="block text-[13px] font-bold text-content">
              ¿Cómo deseás registrar la ubicación de tu local? *
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
              <button
                type="button"
                class="text-left p-4 rounded-[14px] border transition cursor-pointer"
                :class="
                  locationMode === 'google'
                    ? 'bg-surface-elevated border-brand ring-1 ring-brand'
                    : 'bg-canvas border-border-subtle hover:border-brand-light'
                "
                @click="locationMode = 'google'"
              >
                <div class="font-bold text-[13.5px] text-content flex items-center gap-2">
                  <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                  <span>Buscar en Google Places</span>
                </div>
                <p class="text-[12px] text-content-secondary mt-1">
                  Recomendado si tu negocio ya figura en Google Maps para agilizar la verificación
                </p>
              </button>

              <button
                type="button"
                class="text-left p-4 rounded-[14px] border transition cursor-pointer"
                :class="
                  locationMode === 'manual'
                    ? 'bg-surface-elevated border-brand ring-1 ring-brand'
                    : 'bg-canvas border-border-subtle hover:border-brand-light'
                "
                @click="locationMode = 'manual'"
              >
                <div class="font-bold text-[13.5px] text-content flex items-center gap-2">
                  <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <span>Ubicar manualmente en mapa</span>
                </div>
                <p class="text-[12px] text-content-secondary mt-1">
                  Para negocios nuevos o sin perfil comercial en Google
                </p>
              </button>
            </div>
          </div>

          <!-- MODALIDAD A: GooglePlacesSearchSection -->
          <div v-if="locationMode === 'google'" class="space-y-2.5">
            <label for="place-search" class="block text-[13px] font-bold text-content">
              Buscar tu establecimiento en Google Places *
            </label>
            <div class="relative">
              <svg
                class="w-4 h-4 text-content-secondary absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <input
                id="place-search"
                v-model="searchQuery"
                type="text"
                :disabled="selectedPlace !== null"
                placeholder="Escribí el nombre de tu negocio o restaurante..."
                class="w-full pl-11 pr-11 py-3.5 rounded-[12px] bg-canvas border border-border-subtle text-[13.5px] text-content placeholder:text-content-secondary focus:outline-none focus:border-brand transition disabled:opacity-60"
              />
              <div v-if="searchLoading" class="absolute right-4 top-1/2 -translate-y-1/2">
                <svg class="animate-spin h-4 w-4 text-brand" viewBox="0 0 24 24">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="4" fill="none" />
                  <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="4" fill="none" />
                </svg>
              </div>
            </div>

            <!-- Resultados de búsqueda -->
            <div
              v-if="searchResults.length > 0"
              class="max-h-60 overflow-y-auto bg-surface border border-border-subtle rounded-[12px] shadow-lg divide-y divide-border-subtle"
            >
              <button
                v-for="place in searchResults"
                :key="place.place_id"
                type="button"
                class="w-full text-left px-4 py-3 hover:bg-surface-elevated transition cursor-pointer"
                @click="selectPlace(place)"
              >
                <div class="font-bold text-[13.5px] text-content">{{ place.name }}</div>
                <div class="text-[12px] text-content-secondary mt-0.5">{{ place.formatted_address }}</div>
              </button>
            </div>

            <!-- SelectedGooglePlaceCard (Figma 4039:2791) -->
            <div
              v-if="selectedPlace"
              class="px-4.5 py-3.5 bg-surface-darkest text-surface rounded-[12px] flex items-center justify-between gap-3"
            >
              <div class="min-w-0">
                <div class="font-bold text-[13.5px] text-surface truncate">
                  ✓ {{ selectedPlace.name }}
                </div>
                <div class="text-[12px] text-surface/75 truncate mt-0.5">
                  {{ selectedPlace.formatted_address }}
                </div>
              </div>
              <button
                type="button"
                class="px-2.5 py-1 rounded-lg bg-surface/15 hover:bg-surface/25 text-xs font-bold text-surface transition cursor-pointer shrink-0"
                @click="clearSelection"
              >
                Cambiar
              </button>
            </div>
          </div>

          <!-- MODALIDAD B: ManualLocationMapBlock -->
          <ManualLocationMapBlock
            v-else
            v-model:name="form.name"
            v-model:address="form.address"
            v-model:phone="form.phone"
            v-model:latitude="form.latitude"
            v-model:longitude="form.longitude"
          />

          <!-- EstablishmentTypeSection -->
          <div class="space-y-2">
            <label for="estab-type" class="block text-[13px] font-bold text-content">
              Tipo de establecimiento *
            </label>
            <div class="relative">
              <select
                id="estab-type"
                v-model="form.establishment_type_id"
                required
                class="w-full px-4 py-3.5 rounded-[12px] bg-canvas border border-border-subtle text-[13.5px] text-content focus:outline-none focus:border-brand transition appearance-none cursor-pointer"
              >
                <option value="" disabled>Seleccioná un rubro gastronómico</option>
                <option v-for="type in establishmentTypes" :key="type.id" :value="type.id">
                  {{ type.name }}
                </option>
              </select>
              <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                <svg class="w-4 h-4 text-content-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </div>
          </div>

          <!-- VerificationFilesSection -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <label class="block text-[13px] font-bold text-content">
                Documentos y Fotos de Verificación *
              </label>
              <span class="text-[12px] text-content-secondary">
                ({{ verificationFiles.length }}/{{ maxFiles }} archivos)
              </span>
            </div>

            <!-- DropZoneUploadBox (Figma 4039:2807) -->
            <div
              ref="dropZoneRef"
              class="rounded-[16px] bg-surface-elevated border-2 border-dashed border-brand-light py-6 px-4 text-center cursor-pointer transition hover:bg-brand-light/10"
              :class="{ 'ring-2 ring-brand': isDragging }"
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

              <div v-if="verificationFiles.length >= maxFiles" class="space-y-1">
                <p class="text-[13.5px] font-bold text-content">
                  Alcanzaste el máximo de {{ maxFiles }} archivos
                </p>
              </div>

              <div v-else class="space-y-2">
                <div class="w-10 h-10 mx-auto rounded-xl bg-brand/15 text-brand flex items-center justify-center">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                  </svg>
                </div>
                <p class="text-[13.5px] font-bold text-content">
                  {{ isDragging ? "Soltá los archivos aquí" : "Arrastrá y soltá tus archivos aquí o hacé clic para explorar" }}
                </p>
                <div class="flex items-center justify-center gap-2 text-[11px] text-content-secondary">
                  <span class="px-2 py-0.5 rounded bg-surface border border-border-subtle font-bold text-content">PNG</span>
                  <span class="px-2 py-0.5 rounded bg-surface border border-border-subtle font-bold text-content">JPG</span>
                  <span class="px-2 py-0.5 rounded bg-surface border border-border-subtle font-bold text-content">PDF</span>
                  <span>• máx. 5MB por archivo</span>
                </div>
              </div>
            </div>

            <!-- Lista de VerificationFileRow -->
            <div v-if="verificationFiles.length > 0" class="space-y-2.5">
              <VerificationFileRow
                v-for="fileItem in verificationFiles"
                :key="fileItem.id"
                :file-item="fileItem"
                :categories="documentCategories"
                @update:category="(cat) => updateFileCategory(fileItem.id, cat)"
                @remove="removeFile(fileItem.id)"
              />
            </div>
          </div>

          <!-- AdditionalInfoSection -->
          <div class="space-y-2">
            <label for="additional-info" class="block text-[13px] font-bold text-content">
              Información adicional (opcional)
            </label>
            <textarea
              id="additional-info"
              v-model="form.additional_info"
              rows="3"
              maxlength="1000"
              placeholder="Horarios habituales, referencias de la fachada o notas relevantes..."
              class="w-full px-4 py-3.5 rounded-[12px] bg-canvas border border-border-subtle text-[13.5px] text-content placeholder:text-content-secondary focus:outline-none focus:border-brand transition resize-none"
            ></textarea>
          </div>

          <!-- Error Message -->
          <div
            v-if="error"
            class="p-4 rounded-[12px] bg-danger/10 border border-danger/30 text-xs font-bold text-danger"
          >
            {{ error }}
          </div>

          <!-- FormFooterActions -->
          <div class="pt-2 border-t border-border-subtle flex flex-col sm:flex-row justify-between items-center gap-4">
            <button
              type="button"
              :disabled="cancelLoading || loading"
              class="w-full sm:w-auto h-[46px] px-6 rounded-[10px] bg-canvas hover:bg-surface-sunken border border-border-subtle text-sm font-semibold text-content transition-colors disabled:opacity-50 cursor-pointer select-none"
              @click="cancelSellerRegistration"
            >
              {{ cancelLoading ? "Cancelando…" : "Cancelar registro" }}
            </button>

            <button
              type="submit"
              :disabled="loading || !isValid || cancelLoading"
              class="w-full sm:w-auto h-[46px] inline-flex items-center justify-center gap-2 px-8 rounded-[10px] bg-brand hover:bg-brand-hover text-surface text-sm font-bold transition-colors shadow-xs disabled:opacity-50 cursor-pointer select-none"
            >
              <svg
                v-if="loading"
                class="animate-spin h-4 w-4 text-surface"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
              </svg>
              <span>{{ loading ? "Registrando…" : "Enviar para verificación →" }}</span>
            </button>
          </div>
        </form>
      </div>
    </main>

    <MessageDialog
      :message="successMessage"
      :visible="successVisible"
      @accept="handleAcceptSuccess"
    />
  </div>
</template>
