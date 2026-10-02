<script setup>
import { ref, reactive, watch, computed } from "vue";
import axiosInstance from "@/lib/axios";

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  template: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["update:modelValue", "published"]);

const isSubmitting = ref(false);
const formError = ref("");
const validationErrors = reactive({});

const form = reactive({
  quantity: 3,
  price: 3000,
  minimum_value: 9000,
  pickup_start_datetime: "",
  pickup_end_datetime: "",
  visibility_mode: "now",
  visible_from: "",
});

const discountPercentage = computed(() => {
  const p = Number(form.price);
  const m = Number(form.minimum_value);
  if (!p || !m || m <= p) return 0;
  return Math.round(((m - p) / m) * 100);
});

const savingsAmount = computed(() => {
  const p = Number(form.price);
  const m = Number(form.minimum_value);
  if (!p || !m || m <= p) return 0;
  return m - p;
});

// Atajos de horario
const applyScheduleShortcut = (type) => {
  const today = new Date();
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);

  const pad = (n) => String(n).padStart(2, "0");
  const formatDatePart = (d) =>
    `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

  if (type === "today-afternoon") {
    const base = formatDatePart(today);
    form.pickup_start_datetime = `${base}T19:00`;
    form.pickup_end_datetime = `${base}T20:30`;
  } else if (type === "today-night") {
    const base = formatDatePart(today);
    form.pickup_start_datetime = `${base}T21:00`;
    form.pickup_end_datetime = `${base}T22:30`;
  } else if (type === "tomorrow-lunch") {
    const base = formatDatePart(tomorrow);
    form.pickup_start_datetime = `${base}T13:00`;
    form.pickup_end_datetime = `${base}T14:30`;
  }
};

// Reset al abrir modal
watch(
  () => props.modelValue,
  (isOpen) => {
    if (isOpen) {
      formError.value = "";
      Object.keys(validationErrors).forEach((k) => delete validationErrors[k]);
      form.quantity = 3;
      form.price = 3000;
      form.minimum_value = 9000;
      form.visibility_mode = "now";
      form.visible_from = "";
      applyScheduleShortcut("today-afternoon");
    }
  }
);

const closeModal = () => {
  emit("update:modelValue", false);
};

const formatApiDate = (dtLocalStr) => {
  return dtLocalStr.replace("T", " ") + (dtLocalStr.length === 16 ? ":00" : "");
};

const validateForm = () => {
  let valid = true;
  Object.keys(validationErrors).forEach((k) => delete validationErrors[k]);

  if (!form.price || Number(form.price) <= 0) {
    validationErrors.price = "El precio debe ser mayor a 0";
    valid = false;
  }
  if (!form.minimum_value || Number(form.minimum_value) < Number(form.price)) {
    validationErrors.minimum_value = "El valor real debe ser mayor o igual al precio";
    valid = false;
  }
  if (!form.quantity || Number(form.quantity) < 1) {
    validationErrors.quantity = "Debes ofrecer al menos 1 pack";
    valid = false;
  }
  if (!form.pickup_start_datetime) {
    validationErrors.pickup_start_datetime = "Indica el inicio de retiro";
    valid = false;
  }
  if (!form.pickup_end_datetime) {
    validationErrors.pickup_end_datetime = "Indica el fin de retiro";
    valid = false;
  }
  if (
    form.pickup_start_datetime &&
    form.pickup_end_datetime &&
    new Date(form.pickup_end_datetime) <= new Date(form.pickup_start_datetime)
  ) {
    validationErrors.pickup_end_datetime = "El fin de retiro debe ser posterior al inicio";
    valid = false;
  }

  if (form.visibility_mode === "scheduled") {
    if (!form.visible_from) {
      validationErrors.visible_from = "Indica cuándo se hará visible";
      valid = false;
    } else if (
      form.pickup_end_datetime &&
      new Date(form.visible_from) >= new Date(form.pickup_end_datetime)
    ) {
      validationErrors.visible_from = "La visibilidad debe ser anterior al fin de retiro";
      valid = false;
    }
  }

  return valid;
};

const handlePublish = async () => {
  if (!validateForm() || !props.template?.id) return;

  isSubmitting.value = true;
  formError.value = "";

  const payload = {
    pack_template_id: props.template.id,
    price: Number(form.price),
    minimum_value: Number(form.minimum_value),
    quantity: Number(form.quantity),
    pickup_start_datetime: formatApiDate(form.pickup_start_datetime),
    pickup_end_datetime: formatApiDate(form.pickup_end_datetime),
    visible_from:
      form.visibility_mode === "scheduled" && form.visible_from
        ? formatApiDate(form.visible_from)
        : null,
  };

  try {
    const res = await axiosInstance.post("/packs", payload);
    emit("published", res.data?.data || res.data);
    closeModal();
  } catch (err) {
    console.error("Error al publicar pack desde plantilla:", err);
    if (err.response?.status === 422 && err.response?.data?.errors) {
      Object.assign(validationErrors, err.response.data.errors);
    } else {
      formError.value =
        err.response?.data?.message || "Ocurrió un error al publicar la oferta";
    }
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<template>
  <div
    v-if="modelValue"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fade-in"
    @click.self="closeModal"
  >
    <div
      class="w-full max-w-xl rounded-2xl bg-[#2D2438] border border-white/[0.1] shadow-2xl overflow-hidden flex flex-col max-h-[92vh]"
    >
      <!-- Cabecera -->
      <header
        class="flex items-center justify-between px-6 py-4 border-b border-white/[0.08] bg-[#1A1625]/50"
      >
        <div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-[#A78BFA] block mb-0.5">
            Lanzamiento en 1 Clic
          </span>
          <h2 class="text-base sm:text-lg font-bold text-white tracking-tight">
            Publicar Oferta Diaria
          </h2>
          <p class="text-xs text-[#A5A8C2] truncate max-w-sm sm:max-w-md mt-0.5">
            Modelo base: <strong class="text-white">{{ template?.title }}</strong>
          </p>
        </div>
        <button
          type="button"
          class="p-1.5 rounded-lg text-[#A5A8C2] hover:text-white hover:bg-white/[0.08] transition cursor-pointer"
          @click="closeModal"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </header>

      <!-- Formulario -->
      <form @submit.prevent="handlePublish" class="p-6 space-y-4 overflow-y-auto flex-1">
        <!-- Error global -->
        <div
          v-if="formError"
          class="p-3 rounded-xl bg-[#EF4444]/15 border border-[#EF4444]/30 text-[#F87171] text-xs font-medium flex items-center gap-2"
        >
          <svg class="w-4 h-4 shrink-0 text-[#EF4444]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>{{ formError }}</span>
        </div>

        <!-- Precios y Cupos en 3 columnas -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <!-- Precio -->
          <div class="space-y-1">
            <label class="text-xs font-semibold text-[#E8EAF6] block">
              Precio al Cliente <span class="text-[#EF4444]">*</span>
            </label>
            <div class="relative">
              <span class="absolute left-3 top-2.5 text-xs text-[#787596] font-medium">$</span>
              <input
                v-model.number="form.price"
                type="number"
                min="1"
                step="50"
                class="w-full pl-7 pr-3 py-2 rounded-xl bg-[#1A1625] border text-sm text-white font-semibold focus:outline-none focus:border-[#7C3AED] transition"
                :class="validationErrors.price ? 'border-[#EF4444]' : 'border-white/[0.1]'"
              />
            </div>
            <p v-if="validationErrors.price" class="text-[11px] text-[#EF4444]">
              {{ validationErrors.price }}
            </p>
          </div>

          <!-- Valor Mínimo -->
          <div class="space-y-1">
            <label class="text-xs font-semibold text-[#E8EAF6] block">
              Valor Real Estimado <span class="text-[#EF4444]">*</span>
            </label>
            <div class="relative">
              <span class="absolute left-3 top-2.5 text-xs text-[#787596] font-medium">$</span>
              <input
                v-model.number="form.minimum_value"
                type="number"
                min="1"
                step="50"
                class="w-full pl-7 pr-3 py-2 rounded-xl bg-[#1A1625] border text-sm text-white focus:outline-none focus:border-[#7C3AED] transition"
                :class="validationErrors.minimum_value ? 'border-[#EF4444]' : 'border-white/[0.1]'"
              />
            </div>
            <p v-if="validationErrors.minimum_value" class="text-[11px] text-[#EF4444]">
              {{ validationErrors.minimum_value }}
            </p>
          </div>

          <!-- Cantidad de Cupos -->
          <div class="space-y-1">
            <label class="text-xs font-semibold text-[#E8EAF6] block">
              Cupos a Lanzar <span class="text-[#EF4444]">*</span>
            </label>
            <input
              v-model.number="form.quantity"
              type="number"
              min="1"
              max="50"
              class="w-full px-3 py-2 rounded-xl bg-[#1A1625] border text-sm text-white font-semibold focus:outline-none focus:border-[#7C3AED] transition"
              :class="validationErrors.quantity ? 'border-[#EF4444]' : 'border-white/[0.1]'"
            />
            <p v-if="validationErrors.quantity" class="text-[11px] text-[#EF4444]">
              {{ validationErrors.quantity }}
            </p>
          </div>
        </div>

        <!-- Indicador de Descuento / Ahorro -->
        <div
          v-if="discountPercentage > 0"
          class="flex items-center gap-2 px-3 py-2 rounded-xl bg-[#10B981]/10 border border-[#10B981]/20 text-[#10B981] text-xs font-medium"
        >
          <svg class="w-4 h-4 shrink-0 text-[#10B981]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>
            Descuento de rescate: <strong>{{ discountPercentage }}% OFF</strong>
            (Ahorro de ${{ savingsAmount.toLocaleString("es-AR") }} por bolsa)
          </span>
        </div>

        <!-- Ventana de Retiro con Atajos -->
        <div class="space-y-2 pt-1">
          <div class="flex items-center justify-between flex-wrap gap-1">
            <label class="text-xs font-semibold text-[#E8EAF6] block">
              Horario de Retiro de Hoy <span class="text-[#EF4444]">*</span>
            </label>
            <!-- Atajos rápidos -->
            <div class="flex gap-1.5">
              <button
                type="button"
                class="px-2.5 py-0.5 rounded-lg text-[11px] font-medium bg-white/[0.04] hover:bg-white/[0.1] text-[#A5A8C2] hover:text-white border border-white/[0.06] transition cursor-pointer"
                @click="applyScheduleShortcut('today-afternoon')"
              >
                Tarde (19:00)
              </button>
              <button
                type="button"
                class="px-2.5 py-0.5 rounded-lg text-[11px] font-medium bg-white/[0.04] hover:bg-white/[0.1] text-[#A5A8C2] hover:text-white border border-white/[0.06] transition cursor-pointer"
                @click="applyScheduleShortcut('today-night')"
              >
                Noche (21:00)
              </button>
              <button
                type="button"
                class="px-2.5 py-0.5 rounded-lg text-[11px] font-medium bg-white/[0.04] hover:bg-white/[0.1] text-[#A5A8C2] hover:text-white border border-white/[0.06] transition cursor-pointer"
                @click="applyScheduleShortcut('tomorrow-lunch')"
              >
                Mañana (13:00)
              </button>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <span class="text-[11px] text-[#A5A8C2] block mb-1">Inicio de retiro</span>
              <input
                v-model="form.pickup_start_datetime"
                type="datetime-local"
                class="w-full px-3 py-2 rounded-xl bg-[#1A1625] border text-xs text-white focus:outline-none focus:border-[#7C3AED] transition"
                :class="validationErrors.pickup_start_datetime ? 'border-[#EF4444]' : 'border-white/[0.1]'"
              />
              <p v-if="validationErrors.pickup_start_datetime" class="text-[11px] text-[#EF4444] mt-0.5">
                {{ validationErrors.pickup_start_datetime }}
              </p>
            </div>
            <div>
              <span class="text-[11px] text-[#A5A8C2] block mb-1">Límite de retiro</span>
              <input
                v-model="form.pickup_end_datetime"
                type="datetime-local"
                class="w-full px-3 py-2 rounded-xl bg-[#1A1625] border text-xs text-white focus:outline-none focus:border-[#7C3AED] transition"
                :class="validationErrors.pickup_end_datetime ? 'border-[#EF4444]' : 'border-white/[0.1]'"
              />
              <p v-if="validationErrors.pickup_end_datetime" class="text-[11px] text-[#EF4444] mt-0.5">
                {{ validationErrors.pickup_end_datetime }}
              </p>
            </div>
          </div>
        </div>

        <!-- Visibilidad de la Oferta (Sprint 1) -->
        <div class="space-y-2 pt-2 border-t border-white/[0.06]">
          <label class="text-xs font-semibold text-[#E8EAF6] block">
            Publicación en Catálogo
          </label>
          <div class="grid grid-cols-2 gap-2">
            <button
              type="button"
              class="flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl text-xs font-semibold border transition cursor-pointer"
              :class="
                form.visibility_mode === 'now'
                  ? 'bg-[#7C3AED]/20 border-[#7C3AED] text-white'
                  : 'bg-[#1A1625] border-white/[0.08] text-[#A5A8C2] hover:border-white/20'
              "
              @click="form.visibility_mode = 'now'"
            >
              <svg class="w-3.5 h-3.5 text-[#A78BFA]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              <span>Inmediata</span>
            </button>
            <button
              type="button"
              class="flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl text-xs font-semibold border transition cursor-pointer"
              :class="
                form.visibility_mode === 'scheduled'
                  ? 'bg-[#7C3AED]/20 border-[#7C3AED] text-white'
                  : 'bg-[#1A1625] border-white/[0.08] text-[#A5A8C2] hover:border-white/20'
              "
              @click="form.visibility_mode = 'scheduled'"
            >
              <svg class="w-3.5 h-3.5 text-[#A78BFA]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>Programar</span>
            </button>
          </div>

          <div
            v-if="form.visibility_mode === 'scheduled'"
            class="pt-1.5 space-y-1"
          >
            <span class="text-[11px] text-[#A5A8C2] block">
              Visible en catálogo a partir de:
            </span>
            <input
              v-model="form.visible_from"
              type="datetime-local"
              class="w-full px-3 py-2 rounded-xl bg-[#1A1625] border text-xs text-white focus:outline-none focus:border-[#7C3AED] transition"
              :class="validationErrors.visible_from ? 'border-[#EF4444]' : 'border-white/[0.1]'"
            />
            <p v-if="validationErrors.visible_from" class="text-[11px] text-[#EF4444]">
              {{ validationErrors.visible_from }}
            </p>
            <p class="text-[11px] text-[#787596]">
              Los clientes no podrán ver ni comprar la bolsa hasta que llegue este horario.
            </p>
          </div>
        </div>
      </form>

      <!-- Pie del Modal -->
      <footer
        class="flex items-center justify-end gap-2.5 px-6 py-4 border-t border-white/[0.08] bg-[#1A1625]/50"
      >
        <button
          type="button"
          class="px-4 py-2 rounded-xl text-xs font-semibold text-[#A5A8C2] hover:text-white hover:bg-white/[0.06] transition cursor-pointer"
          :disabled="isSubmitting"
          @click="closeModal"
        >
          Cancelar
        </button>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-[#7C3AED] hover:bg-[#6D28D9] shadow-sm shadow-purple-900/30 transition cursor-pointer disabled:opacity-50"
          :disabled="isSubmitting"
          @click="handlePublish"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
          <span v-if="isSubmitting">Publicando...</span>
          <span v-else>Confirmar y Publicar</span>
        </button>
      </footer>
    </div>
  </div>
</template>
