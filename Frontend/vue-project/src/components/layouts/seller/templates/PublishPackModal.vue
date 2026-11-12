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
  activeOffers: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(["update:modelValue", "published"]);

const isSubmitting = ref(false);
const formError = ref("");
const activeShortcut = ref("today-afternoon");
const validationErrors = reactive({});

const form = reactive({
  quantity: 5,
  price: 3500,
  minimum_value: 8000,
  pickup_start_datetime: "",
  pickup_end_datetime: "",
  visibility_mode: "now",
  visible_from: "",
});

const pad = (n) => String(n).padStart(2, "0");

const formatDateTimeLocal = (date) => {
  const year = date.getFullYear();
  const month = pad(date.getMonth() + 1);
  const day = pad(date.getDate());
  const hours = pad(date.getHours());
  const minutes = pad(date.getMinutes());
  return `${year}-${month}-${day}T${hours}:${minutes}`;
};

const formatScheduleWindowLabel = (startDt, endDt) => {
  try {
    const s = new Date(startDt);
    const e = new Date(endDt);
    const isToday = s.toDateString() === new Date().toDateString();
    const dayStr = isToday
      ? "Hoy"
      : s.toLocaleDateString("es-AR", { weekday: "short", day: "numeric", month: "short" });
    const sTime = s.toLocaleTimeString("es-AR", { hour: "2-digit", minute: "2-digit" });
    const eTime = e.toLocaleTimeString("es-AR", { hour: "2-digit", minute: "2-digit" });
    return `${dayStr} ${sTime}–${eTime}`;
  } catch {
    return `${startDt} - ${endDt}`;
  }
};

const existingActiveSchedules = computed(() => {
  const now = new Date();
  const map = new Map();
  for (const o of props.activeOffers || []) {
    if (o.state && o.state !== "active") continue;
    if (Number(o.quantity ?? 0) <= 0) continue;
    if (!o.pickup_start_datetime || !o.expiration_datetime) continue;
    const sDate = new Date(o.pickup_start_datetime);
    const eDate = new Date(o.expiration_datetime);
    if (isNaN(sDate.getTime()) || isNaN(eDate.getTime()) || eDate <= now) continue;
    const startLocal = formatDateTimeLocal(sDate);
    const endLocal = formatDateTimeLocal(eDate);
    const key = `${startLocal}|${endLocal}`;
    if (!map.has(key)) {
      map.set(key, {
        key,
        startLocal,
        endLocal,
        label: formatScheduleWindowLabel(sDate, eDate),
        count: 1,
      });
    } else {
      map.get(key).count++;
    }
  }
  return Array.from(map.values());
});

const applyExistingSchedule = (sched) => {
  activeShortcut.value = `existing:${sched.key}`;
  form.pickup_start_datetime = sched.startLocal;
  form.pickup_end_datetime = sched.endLocal;
  delete validationErrors.pickup_start_datetime;
  delete validationErrors.pickup_end_datetime;
};

const hasScheduleConflict = computed(() => {
  if (!existingActiveSchedules.value.length) return false;
  if (!form.pickup_start_datetime || !form.pickup_end_datetime) return false;
  const currentKey = `${form.pickup_start_datetime}|${form.pickup_end_datetime}`;
  return existingActiveSchedules.value.some((s) => s.key !== currentKey);
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

const applyScheduleShortcut = (type) => {
  activeShortcut.value = type;
  const today = new Date();
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);

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

watch(
  () => props.modelValue,
  (isOpen) => {
    if (isOpen) {
      formError.value = "";
      Object.keys(validationErrors).forEach((k) => delete validationErrors[k]);
      form.quantity = 5;
      form.price = props.template?.suggested_price ?? 3500;
      form.minimum_value = props.template?.minimum_value ?? 8000;
      form.visibility_mode = "now";
      form.visible_from = "";
      if (existingActiveSchedules.value.length > 0) {
        applyExistingSchedule(existingActiveSchedules.value[0]);
      } else {
        applyScheduleShortcut("today-afternoon");
      }
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
    emit("published", res.data);
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
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-content/50 backdrop-blur-xs"
    @click.self="closeModal"
  >
    <div
      class="w-full max-w-[540px] rounded-[22px] bg-surface border border-border-subtle p-7 sm:p-8 shadow-card flex flex-col gap-6 max-h-[92vh] overflow-y-auto text-content"
    >
      <!-- Header -->
      <div class="flex flex-col gap-1">
        <span class="text-[11px] font-bold uppercase tracking-wider text-brand">
          LANZAMIENTO EN 1 CLIC
        </span>
        <h2 class="text-[22px] font-extrabold text-content leading-tight">
          Publicar Oferta Diaria
        </h2>
        <p class="text-[13px] font-medium text-content-secondary truncate">
          Plantilla base: {{ template?.title || "Bolsa Sorpresa" }}
        </p>
      </div>

      <!-- Form -->
      <form class="flex flex-col gap-5" @submit.prevent="handlePublish">
        <!-- Global error -->
        <div
          v-if="formError"
          class="p-3 rounded-[12px] bg-danger/12 border border-danger/30 text-danger text-xs font-semibold"
        >
          {{ formError }}
        </div>

        <!-- 3 Columns: Precio Cliente, Valor Real Mín., Stock / Cupos -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div class="flex flex-col gap-1.5">
            <label class="text-[12px] font-semibold text-content">
              Precio Cliente *
            </label>
            <div class="relative flex items-center">
              <span class="absolute left-3.5 text-[13.5px] font-semibold text-content-secondary">$</span>
              <input
                v-model.number="form.price"
                type="number"
                min="1"
                step="50"
                class="w-full pl-7 pr-3.5 py-[11px] rounded-[12px] bg-surface-sunken border text-[13.5px] font-semibold text-content focus:outline-none focus:border-brand"
                :class="validationErrors.price ? 'border-danger' : 'border-transparent'"
              />
            </div>
            <p v-if="validationErrors.price" class="text-[11px] text-danger">
              {{ validationErrors.price }}
            </p>
          </div>

          <div class="flex flex-col gap-1.5">
            <label class="text-[12px] font-semibold text-content">
              Valor Real Mín. *
            </label>
            <div class="relative flex items-center">
              <span class="absolute left-3.5 text-[13.5px] font-semibold text-content-secondary">$</span>
              <input
                v-model.number="form.minimum_value"
                type="number"
                min="1"
                step="50"
                class="w-full pl-7 pr-3.5 py-[11px] rounded-[12px] bg-surface-sunken border text-[13.5px] font-semibold text-content focus:outline-none focus:border-brand"
                :class="validationErrors.minimum_value ? 'border-danger' : 'border-transparent'"
              />
            </div>
            <p v-if="validationErrors.minimum_value" class="text-[11px] text-danger">
              {{ validationErrors.minimum_value }}
            </p>
          </div>

          <div class="flex flex-col gap-1.5">
            <label class="text-[12px] font-semibold text-content">
              Stock / Cupos *
            </label>
            <input
              v-model.number="form.quantity"
              type="number"
              min="1"
              max="50"
              class="w-full px-3.5 py-[11px] rounded-[12px] bg-surface-sunken border text-[13.5px] font-semibold text-content focus:outline-none focus:border-brand"
              :class="validationErrors.quantity ? 'border-danger' : 'border-transparent'"
            />
            <p v-if="validationErrors.quantity" class="text-[11px] text-danger">
              {{ validationErrors.quantity }}
            </p>
          </div>
        </div>

        <!-- Discount Text -->
        <p v-if="discountPercentage > 0" class="text-[13px] font-bold text-brand">
          Descuento aplicado: {{ discountPercentage }}% OFF • El comprador ahorra ${{ savingsAmount.toLocaleString("es-AR") }} por bolsa.
        </p>

        <!-- Pickup Window + Shortcuts -->
        <div class="flex flex-col gap-2.5">
          <label class="text-[12px] font-semibold text-content">
            Ventana horaria de retiro *
          </label>

          <!-- Horarios de tus otras ofertas activas -->
          <div
            v-if="existingActiveSchedules.length > 0"
            class="p-3 rounded-[14px] bg-brand/8 border border-brand/25 flex flex-col gap-2"
          >
            <div class="flex items-center justify-between gap-2">
              <span class="text-[11px] font-bold uppercase tracking-[0.04em] text-brand">
                🕒 Coincidir con tus packs activos
              </span>
              <span class="text-[10.5px] text-content-secondary">
                1 clic para unificar retiro
              </span>
            </div>
            <div class="flex flex-wrap gap-1.5">
              <button
                v-for="sched in existingActiveSchedules"
                :key="sched.key"
                type="button"
                class="px-3 py-1.5 rounded-full text-[11.5px] transition-colors cursor-pointer flex items-center gap-1.5 border"
                :class="
                  activeShortcut === `existing:${sched.key}`
                    ? 'bg-brand text-surface font-bold border-brand'
                    : 'bg-surface text-content font-semibold border-border-subtle hover:border-brand/50'
                "
                @click="applyExistingSchedule(sched)"
              >
                <span>{{ sched.label }}</span>
                <span
                  class="px-1.5 py-0.5 rounded-full text-[10px]"
                  :class="
                    activeShortcut === `existing:${sched.key}`
                      ? 'bg-surface/20 text-surface'
                      : 'bg-surface-sunken text-content-secondary'
                  "
                >
                  {{ sched.count }} {{ sched.count === 1 ? "pack" : "packs" }}
                </span>
              </button>
            </div>
          </div>

          <div class="flex flex-wrap gap-2">
            <button
              type="button"
              class="px-3 py-1.5 rounded-full text-[11.5px] transition-colors cursor-pointer"
              :class="
                activeShortcut === 'today-afternoon'
                  ? 'bg-brand/14 text-brand font-bold'
                  : 'bg-surface-sunken text-content-secondary font-medium hover:text-content'
              "
              @click="applyScheduleShortcut('today-afternoon')"
            >
              Hoy Tarde (19:00–20:30)
            </button>
            <button
              type="button"
              class="px-3 py-1.5 rounded-full text-[11.5px] transition-colors cursor-pointer"
              :class="
                activeShortcut === 'today-night'
                  ? 'bg-brand/14 text-brand font-bold'
                  : 'bg-surface-sunken text-content-secondary font-medium hover:text-content'
              "
              @click="applyScheduleShortcut('today-night')"
            >
              Hoy Noche (21:00–22:30)
            </button>
            <button
              type="button"
              class="px-3 py-1.5 rounded-full text-[11.5px] transition-colors cursor-pointer"
              :class="
                activeShortcut === 'tomorrow-lunch'
                  ? 'bg-brand/14 text-brand font-bold'
                  : 'bg-surface-sunken text-content-secondary font-medium hover:text-content'
              "
              @click="applyScheduleShortcut('tomorrow-lunch')"
            >
              Mañana (13:00–14:30)
            </button>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
            <div class="flex flex-col gap-1.5">
              <span class="text-[12px] font-semibold text-content">Inicio de retiro</span>
              <input
                v-model="form.pickup_start_datetime"
                type="datetime-local"
                class="w-full px-3.5 py-[11px] rounded-[12px] bg-surface-sunken border text-[13px] font-semibold text-content focus:outline-none focus:border-brand"
                :class="validationErrors.pickup_start_datetime ? 'border-danger' : 'border-transparent'"
              />
              <p v-if="validationErrors.pickup_start_datetime" class="text-[11px] text-danger">
                {{ validationErrors.pickup_start_datetime }}
              </p>
            </div>

            <div class="flex flex-col gap-1.5">
              <span class="text-[12px] font-semibold text-content">Fin de retiro</span>
              <input
                v-model="form.pickup_end_datetime"
                type="datetime-local"
                class="w-full px-3.5 py-[11px] rounded-[12px] bg-surface-sunken border text-[13px] font-semibold text-content focus:outline-none focus:border-brand"
                :class="validationErrors.pickup_end_datetime ? 'border-danger' : 'border-transparent'"
              />
              <p v-if="validationErrors.pickup_end_datetime" class="text-[11px] text-danger">
                {{ validationErrors.pickup_end_datetime }}
              </p>
            </div>
          </div>

          <!-- Advertencia de horarios distintos -->
          <div
            v-if="hasScheduleConflict"
            class="p-3.5 rounded-[14px] bg-warning/15 border border-warning/40 flex items-start gap-2.5 text-left"
          >
            <span class="text-base leading-none mt-0.5">⚠️</span>
            <div class="flex flex-col gap-1">
              <span class="text-[12px] font-bold text-warning">
                Horario de retiro diferente a tus otras ofertas activas
              </span>
              <p class="text-[11.5px] text-content-secondary leading-[1.4]">
                Ya tenés ofertas activas en otro horario ({{ existingActiveSchedules.map((s) => s.label).join(" / ") }}).
                Los clientes verán los turnos por separado y no podrán retirarlos juntos en un mismo viaje.
              </p>
            </div>
          </div>
        </div>

        <!-- Optional Visibility Mode -->
        <div class="flex flex-col gap-2 pt-1 border-t border-border-subtle">
          <div class="flex items-center justify-between">
            <span class="text-[12px] font-semibold text-content">Visibilidad en catálogo</span>
            <div class="inline-flex rounded-full bg-surface-sunken p-0.5 text-[11.5px]">
              <button
                type="button"
                class="px-3 py-1 rounded-full transition-colors cursor-pointer"
                :class="
                  form.visibility_mode === 'now'
                    ? 'bg-surface text-content font-bold shadow-2xs'
                    : 'text-content-secondary font-medium'
                "
                @click="form.visibility_mode = 'now'"
              >
                Inmediata
              </button>
              <button
                type="button"
                class="px-3 py-1 rounded-full transition-colors cursor-pointer"
                :class="
                  form.visibility_mode === 'scheduled'
                    ? 'bg-surface text-content font-bold shadow-2xs'
                    : 'text-content-secondary font-medium'
                "
                @click="form.visibility_mode = 'scheduled'"
              >
                Programar
              </button>
            </div>
          </div>

          <div v-if="form.visibility_mode === 'scheduled'" class="flex flex-col gap-1">
            <input
              v-model="form.visible_from"
              type="datetime-local"
              class="w-full px-3.5 py-2 rounded-[12px] bg-surface-sunken border text-[13px] font-semibold text-content focus:outline-none focus:border-brand"
              :class="validationErrors.visible_from ? 'border-danger' : 'border-transparent'"
            />
            <p v-if="validationErrors.visible_from" class="text-[11px] text-danger">
              {{ validationErrors.visible_from }}
            </p>
          </div>
        </div>

        <!-- Actions Row -->
        <div class="flex items-center justify-between pt-2">
          <button
            type="button"
            class="text-[13.5px] font-semibold text-content-secondary hover:text-content cursor-pointer"
            :disabled="isSubmitting"
            @click="closeModal"
          >
            Cancelar
          </button>

          <button
            type="submit"
            class="px-[22px] py-3 rounded-[10px] bg-brand hover:bg-brand-hover text-surface text-[13.5px] font-bold transition-all active:scale-95 cursor-pointer disabled:opacity-50"
            :disabled="isSubmitting"
          >
            {{ isSubmitting ? "Publicando..." : "Publicar Oferta Ahora" }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
