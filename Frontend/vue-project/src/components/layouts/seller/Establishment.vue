<script setup>
import { onMounted, ref, computed } from "vue";
import { useRouter } from "vue-router";
import axiosInstance from "@/lib/axios.js";
import { useAuthStore } from "@/stores/auth.js";
import EstablishmentHeroCardVertical from "./EstablishmentHeroCardVertical.vue";
import ManualLocationMapBlock from "@/components/layouts/others/ManualLocationMapBlock.vue";

const router = useRouter();
const authStore = useAuthStore();

const establishment = ref(null);
const establishmentTypes = ref([]);
const loading = ref(true);
const saving = ref(false);
const error = ref(null);
const successMessage = ref("");

const formData = ref({
  name: "",
  establishment_type_id: "",
  address: "",
  phone: "",
  latitude: null,
  longitude: null,
});

const fetchEstablishment = async () => {
  try {
    loading.value = true;
    const { data } = await axiosInstance.get("/my-establishment");
    establishment.value = data || null;
    if (data) {
      populateFormFromData(data);
    }
    error.value = null;
  } catch (err) {
    console.error("Error fetching establishment:", err);
    error.value = "No se pudo cargar la información del establecimiento.";
    establishment.value = null;
  } finally {
    loading.value = false;
  }
};

const fetchEstablishmentTypes = async () => {
  try {
    const { data } = await axiosInstance.get("/establishment-types");
    establishmentTypes.value = data || [];
  } catch (err) {
    console.error("Error fetching establishment types:", err);
  }
};

const populateFormFromData = (data) => {
  formData.value.name = data.name || "";
  formData.value.establishment_type_id =
    data.establishment_type?.id || data.establishment_type_id || "";
  formData.value.address = data.address || "";
  formData.value.phone = data.phone || "+54 387 421-8890";
  formData.value.latitude = data.latitude ? Number(data.latitude) : -24.7859;
  formData.value.longitude = data.longitude ? Number(data.longitude) : -65.4117;
};

const discardChanges = () => {
  error.value = null;
  successMessage.value = "";
  if (establishment.value) {
    populateFormFromData(establishment.value);
  }
};

const previewCategoryName = computed(() => {
  const found = establishmentTypes.value.find(
    (t) => Number(t.id) === Number(formData.value.establishment_type_id)
  );
  if (found) return found.name;
  return establishment.value?.establishment_type?.name || "Gastronomía";
});

const previewEstablishment = computed(() => {
  return {
    ...(establishment.value || {}),
    name: formData.value.name || establishment.value?.name || "Tu Comercio",
    address: formData.value.address || establishment.value?.address || "Dirección comercial",
    establishment_type: {
      name: previewCategoryName.value,
    },
  };
});

const formattedApprovalDate = computed(() => {
  if (!establishment.value?.created_at) return "12/10/2026";
  try {
    return new Date(establishment.value.created_at).toLocaleDateString("es-AR", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
    });
  } catch {
    return "12/10/2026";
  }
});

const updateEstablishment = async () => {
  if (!formData.value.name.trim()) {
    error.value = "El nombre del establecimiento es requerido.";
    return;
  }

  if (!formData.value.establishment_type_id) {
    error.value = "Debés seleccionar un tipo de establecimiento.";
    return;
  }

  if (!formData.value.address.trim()) {
    error.value = "La dirección del establecimiento es requerida.";
    return;
  }

  try {
    saving.value = true;
    error.value = null;
    successMessage.value = "";

    await axiosInstance.put("/my-establishment", {
      name: formData.value.name.trim(),
      establishment_type_id: formData.value.establishment_type_id,
      address: formData.value.address.trim(),
    });

    successMessage.value =
      "Establecimiento actualizado correctamente. Redirigiendo a verificación...";

    // Sincronizar estado de usuario (pasa a waiting_for_confirmation en backend) y redirigir
    await authStore.refreshUser();
    setTimeout(() => {
      router.push("/waiting-confirmation");
    }, 1000);
  } catch (err) {
    console.error("Error updating establishment:", err);
    error.value =
      err.response?.data?.message || "Error al actualizar el establecimiento.";
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  await Promise.all([fetchEstablishment(), fetchEstablishmentTypes()]);
});
</script>

<template>
  <div class="seller-content-container w-full max-w-[1420px] mx-auto px-4 sm:px-6 lg:px-8 seller:px-0 min-[1420px]:px-0 pt-9 pb-14 text-content">
    <!-- Estado de Carga -->
    <div v-if="loading" class="py-20 text-center space-y-3">
      <div class="w-10 h-10 border-3 border-brand border-t-transparent rounded-full animate-spin mx-auto"></div>
      <p class="text-xs text-content-secondary">Cargando información de tu comercio...</p>
    </div>

    <!-- Error Crítico sin Datos -->
    <div
      v-else-if="error && !establishment"
      class="p-8 rounded-[22px] bg-surface border border-danger text-center space-y-4 max-w-lg mx-auto my-8"
    >
      <p class="text-sm font-bold text-danger">{{ error }}</p>
      <button
        type="button"
        class="px-5 py-2.5 rounded-[10px] bg-brand text-surface text-xs font-bold cursor-pointer"
        @click="fetchEstablishment"
      >
        Reintentar
      </button>
    </div>

    <!-- Layout Principal de 2 Columnas (Figma StorePreviewAndConfigColumns: exact 620px y 752px con 48px gap) -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-[minmax(0,620px)_minmax(0,752px)] gap-12 items-start">
      <!-- COLUMNA IZQUIERDA: Vitrina Pública de tu Comercio (Figma ColLeft 620px) -->
      <div class="space-y-6">
        <div class="space-y-1">
          <h1 class="text-[22px] font-extrabold text-content tracking-tight">
            Vitrina Pública de tu Comercio
          </h1>
          <p class="text-[13.5px] text-content-secondary">
            Así ven tu identidad y reputación los compradores en Tatelestai
          </p>
        </div>

        <!-- Tarjeta Hero Vertical en Vivo -->
        <EstablishmentHeroCardVertical
          :establishment="previewEstablishment"
          :name="previewEstablishment.name"
          :category="previewCategoryName"
          :address="previewEstablishment.address"
          :phone="formData.phone"
        />

        <!-- Metadatos de Habilitación Comercial -->
        <div class="space-y-1.5 pt-1">
          <span class="block text-[11px] font-bold uppercase tracking-wider text-content-secondary">
            ESTADO DE HABILITACIÓN COMERCIAL
          </span>
          <p class="text-[13.5px] font-semibold text-content">
            Comercio Verificado • ID #{{ establishment?.id || "104" }} • Alta aprobada el {{ formattedApprovalDate }}
          </p>
          <p class="text-[12.5px] text-content-secondary leading-relaxed">
            Importante: Si modificás el nombre comercial, el rubro o la dirección física, tu establecimiento volverá brevemente a revisión administrativa para validar los cambios.
          </p>
        </div>
      </div>

      <!-- COLUMNA DERECHA: Datos del Establecimiento (Figma ColRight 752px) -->
      <div class="space-y-6">
        <div class="space-y-1">
          <h2 class="text-[22px] font-extrabold text-content tracking-tight">
            Datos del Establecimiento
          </h2>
          <p class="text-[13.5px] text-content-secondary">
            Actualizá la información comercial y el punto de retiro de tus bolsas
          </p>
        </div>

        <!-- Mensajes de Estado -->
        <div
          v-if="successMessage"
          class="p-4 rounded-[14px] bg-success/12 border border-success/30 text-xs font-bold text-success"
        >
          {{ successMessage }}
        </div>

        <div
          v-if="error"
          class="p-4 rounded-[14px] bg-danger/10 border border-danger/30 text-xs font-bold text-danger"
        >
          {{ error }}
        </div>

        <form class="space-y-6" @submit.prevent="updateEstablishment">
          <!-- Fila 1: Nombre y Tipo -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-2">
              <label for="est-name" class="block text-[13px] font-semibold text-content">
                Nombre del Establecimiento *
              </label>
              <input
                id="est-name"
                v-model="formData.name"
                type="text"
                required
                :disabled="saving"
                placeholder="Ej. Panadería San José"
                class="w-full px-4 py-3 rounded-[14px] bg-surface border border-border-subtle text-[13.5px] text-content placeholder-content-secondary focus:outline-none focus:border-brand transition disabled:opacity-60"
              />
            </div>

            <div class="space-y-2">
              <label for="est-type" class="block text-[13px] font-semibold text-content">
                Tipo de Establecimiento *
              </label>
              <select
                id="est-type"
                v-model="formData.establishment_type_id"
                required
                :disabled="saving"
                class="w-full px-4 py-3 rounded-[14px] bg-surface border border-border-subtle text-[13.5px] text-content focus:outline-none focus:border-brand transition cursor-pointer disabled:opacity-60"
              >
                <option value="">Seleccioná un rubro</option>
                <option
                  v-for="type in establishmentTypes"
                  :key="type.id"
                  :value="type.id"
                >
                  {{ type.name }}
                </option>
              </select>
            </div>
          </div>

          <!-- Dirección Principal -->
          <div class="space-y-2">
            <label for="est-address" class="block text-[13px] font-semibold text-content">
              Dirección del Establecimiento *
            </label>
            <input
              id="est-address"
              v-model="formData.address"
              type="text"
              required
              :disabled="saving"
              placeholder="Ej. Av. Belgrano 520, Centro, Salta Capital"
              class="w-full px-4 py-3 rounded-[14px] bg-surface border border-border-subtle text-[13.5px] text-content placeholder-content-secondary focus:outline-none focus:border-brand transition disabled:opacity-60"
            />
          </div>

          <!-- Bloque de Ubicación y Mapa Interactivo (Figma ManualLocationMapBlock) -->
          <ManualLocationMapBlock
            v-model:name="formData.name"
            v-model:address="formData.address"
            v-model:phone="formData.phone"
            v-model:latitude="formData.latitude"
            v-model:longitude="formData.longitude"
            :show-text-inputs="false"
          />

          <!-- Botones de Acción (Descartar cambios / Guardar Cambios) -->
          <div class="pt-1 flex items-center justify-between gap-4">
            <button
              type="button"
              class="text-[13.5px] font-semibold text-content-secondary hover:text-content transition cursor-pointer"
              :disabled="saving"
              @click="discardChanges"
            >
              Descartar cambios
            </button>

            <button
              type="submit"
              :disabled="saving"
              class="px-6 py-3 rounded-[10px] bg-brand hover:bg-brand-hover text-surface text-[13.5px] font-bold transition shadow-sm cursor-pointer disabled:opacity-60"
            >
              {{ saving ? "Guardando..." : "Guardar Cambios" }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>