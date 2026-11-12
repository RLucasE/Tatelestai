<script setup>
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import axiosInstance from "@/lib/axios.js";
import { useAuthStore } from "@/stores/auth.js";
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
  latitude: -24.7859,
  longitude: -65.4117,
});

const fetchEstablishment = async () => {
  try {
    loading.value = true;
    const { data } = await axiosInstance.get("/my-establishment");
    establishment.value = data || null;

    if (data) {
      formData.value.name = data.name || "";
      formData.value.establishment_type_id = data.establishment_type?.id || "";
      formData.value.address = data.address || "";
      formData.value.phone = data.phone || "";
      if (data.latitude) formData.value.latitude = Number(data.latitude);
      if (data.longitude) formData.value.longitude = Number(data.longitude);
    }

    error.value = null;
  } catch (err) {
    console.error("Error fetching establishment:", err);
    error.value = "No se pudo cargar el establecimiento";
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

const updateEstablishment = async () => {
  if (!formData.value.name.trim()) {
    error.value = "El nombre del establecimiento es requerido";
    return;
  }

  if (!formData.value.establishment_type_id) {
    error.value = "Debe seleccionar un tipo de establecimiento";
    return;
  }

  if (!formData.value.address.trim()) {
    error.value = "La dirección del establecimiento es requerida";
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

    successMessage.value = "Establecimiento actualizado correctamente";

    await authStore.refreshUser();
    setTimeout(() => {
      router.push("/waiting-confirmation");
    }, 1000);
  } catch (err) {
    console.error("Error updating establishment:", err);
    error.value =
      err.response?.data?.message || "Error al actualizar el establecimiento";
  } finally {
    saving.value = false;
  }
};

const cancelEdit = () => {
  router.push("/seller/establishment");
};

onMounted(async () => {
  await Promise.all([fetchEstablishment(), fetchEstablishmentTypes()]);
});
</script>

<template>
  <div class="seller-content-container w-full max-w-[1420px] mx-auto px-4 sm:px-6 lg:px-8 seller:px-0 min-[1420px]:px-0 pt-9 pb-14 space-y-6 text-content">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-[22px] font-extrabold text-content tracking-tight">
          Editar Establecimiento
        </h1>
        <p class="text-[13.5px] text-content-secondary">
          Actualizá la información comercial y el punto de retiro de tus bolsas
        </p>
      </div>
      <button
        type="button"
        class="px-4 py-2 rounded-[10px] bg-surface border border-border-subtle text-xs font-semibold text-content hover:bg-canvas transition cursor-pointer"
        @click="cancelEdit"
      >
        ← Volver
      </button>
    </div>

    <div v-if="loading" class="py-16 text-center space-y-3">
      <div class="w-10 h-10 border-3 border-brand border-t-transparent rounded-full animate-spin mx-auto"></div>
      <p class="text-xs text-content-secondary">Cargando información del establecimiento...</p>
    </div>

    <div
      v-else-if="error && !establishment"
      class="p-6 rounded-[18px] bg-surface border border-danger text-center space-y-3"
    >
      <p class="text-sm font-semibold text-danger">{{ error }}</p>
      <button
        type="button"
        class="px-4 py-2 rounded-[10px] bg-brand text-surface text-xs font-bold cursor-pointer"
        @click="fetchEstablishment"
      >
        Reintentar
      </button>
    </div>

    <form
      v-else
      class="p-6 sm:p-8 rounded-[22px] bg-surface border border-border-subtle space-y-6 shadow-xs"
      @submit.prevent="updateEstablishment"
    >
      <div
        v-if="successMessage"
        class="p-3.5 rounded-[14px] bg-success/12 border border-success/30 text-xs font-bold text-success"
      >
        {{ successMessage }}
      </div>

      <div
        v-if="error"
        class="p-3.5 rounded-[14px] bg-danger/10 border border-danger/30 text-xs font-bold text-danger"
      >
        {{ error }}
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="space-y-2">
          <label for="establishment-name" class="block text-[13px] font-semibold text-content">
            Nombre del Establecimiento *
          </label>
          <input
            id="establishment-name"
            v-model="formData.name"
            type="text"
            required
            :disabled="saving"
            placeholder="Ingresa el nombre del establecimiento"
            class="w-full px-4 py-3 rounded-[14px] bg-surface border border-border-subtle text-[13.5px] text-content placeholder-content-secondary focus:outline-none focus:border-brand transition"
          />
        </div>

        <div class="space-y-2">
          <label for="establishment-type" class="block text-[13px] font-semibold text-content">
            Tipo de Establecimiento *
          </label>
          <select
            id="establishment-type"
            v-model="formData.establishment_type_id"
            required
            :disabled="saving"
            class="w-full px-4 py-3 rounded-[14px] bg-surface border border-border-subtle text-[13.5px] text-content focus:outline-none focus:border-brand transition cursor-pointer"
          >
            <option value="">Selecciona un tipo de establecimiento</option>
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

      <div class="space-y-2">
        <label for="establishment-address" class="block text-[13px] font-semibold text-content">
          Dirección del Establecimiento *
        </label>
        <input
          id="establishment-address"
          v-model="formData.address"
          type="text"
          required
          :disabled="saving"
          placeholder="Ingresa la dirección del establecimiento"
          class="w-full px-4 py-3 rounded-[14px] bg-surface border border-border-subtle text-[13.5px] text-content placeholder-content-secondary focus:outline-none focus:border-brand transition"
        />
      </div>

      <ManualLocationMapBlock
        v-model:name="formData.name"
        v-model:address="formData.address"
        v-model:phone="formData.phone"
        v-model:latitude="formData.latitude"
        v-model:longitude="formData.longitude"
        :show-text-inputs="false"
      />

      <div class="pt-2 flex items-center justify-between gap-4">
        <button
          type="button"
          class="text-[13.5px] font-semibold text-content-secondary hover:text-content transition cursor-pointer"
          :disabled="saving"
          @click="cancelEdit"
        >
          Descartar cambios
        </button>
        <button
          type="submit"
          class="px-6 py-3 rounded-[10px] bg-brand hover:bg-brand-hover text-surface text-[13.5px] font-bold transition cursor-pointer disabled:opacity-60"
          :disabled="saving"
        >
          <span v-if="saving">Guardando...</span>
          <span v-else>Guardar Cambios</span>
        </button>
      </div>
    </form>
  </div>
</template>