<script setup>
import { ref, onMounted, computed } from "vue";
import axiosInstance from "@/lib/axios";
import MessageDialog from "@/components/common/MessageDialog.vue";

const form = ref({
  name: "",
  establishment_type_id: "",
});

const establishmentTypes = ref([]);
const loading = ref(false);
const error = ref("");
const successVisible = ref(false);
const successMessage = ref("Establecimiento registrado correctamente");

const isValid = computed(() => {
  return (
    String(form.value.name).trim().length >= 3 &&
    String(form.value.establishment_type_id).trim() !== ""
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

const resetForm = () => {
  form.value.name = "";
  form.value.establishment_type_id = "";
};

const registerEstablishment = async () => {
  loading.value = true;
  error.value = "";
  try {
    const payload = {
      name: form.value.name,
      establishment_type_id: Number(form.value.establishment_type_id),
    };
    await axiosInstance.post("/food-establishment", payload);
    successVisible.value = true;
    resetForm();
  } catch (e) {
    error.value = "Error al registrar el establecimiento";
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
    <div class="w-full max-w-lg">
      <div class="rounded-2xl shadow-xl border border-[var(--color-border)] overflow-hidden">
        <div class="px-6 py-5 bg-[var(--color-secondary)] border-b border-[var(--color-border)]">
          <h2 class="m-0 text-xl font-semibold text-[var(--color-text)]">Registrar establecimiento</h2>
          <p class="mt-1 text-sm text-[var(--color-text)]/80">Crea tu establecimiento para comenzar a publicar ofertas y productos.</p>
        </div>

        <form @submit.prevent="registerEstablishment" class="bg-[var(--color-primary)] px-6 py-6">
          <div class="space-y-5">
            <div>
              <label for="estab-name" class="block text-sm font-medium text-[var(--color-text)] mb-1">Nombre del establecimiento</label>
              <input
                id="estab-name"
                v-model="form.name"
                type="text"
                required
                minlength="3"
                :aria-invalid="String(!isValid && form.name.trim().length < 3)"
                class="w-full px-3 py-2 rounded-lg bg-[var(--color-darkest)] text-[var(--color-text)] placeholder-[var(--color-text)]/40 border border-[var(--color-border)] focus:outline-none focus:ring-2 focus:ring-[var(--color-focus)] focus:border-[var(--color-focus)] transition"
                placeholder="Ej: Panadería San Juan"
              />
            </div>

            <div>
              <label for="estab-type" class="block text-sm font-medium text-[var(--color-text)] mb-1">Tipo de establecimiento</label>
              <select
                id="estab-type"
                v-model="form.establishment_type_id"
                required
                :aria-invalid="String(!isValid && String(form.establishment_type_id).trim() === '')"
                class="w-full px-3 py-2 rounded-lg bg-[var(--color-darkest)] text-[var(--color-text)] border border-[var(--color-border)] focus:outline-none focus:ring-2 focus:ring-[var(--color-focus)] focus:border-[var(--color-focus)] transition"
              >
                <option value="" disabled>Seleccione uno</option>
                <option v-for="type in establishmentTypes" :key="type.id" :value="type.id">
                  {{ type.name }}
                </option>
              </select>
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
                <svg v-if="loading" class="animate-spin" width="16" height="16" viewBox="0 0 24 24" fill="none">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="4" />
                  <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="4" />
                </svg>
                <span>{{ loading ? 'Registrando…' : 'Registrar' }}</span>
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
