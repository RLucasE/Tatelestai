<script setup>
import { ref, reactive, watch } from "vue";
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

const emit = defineEmits(["update:modelValue", "saved"]);

const commonAllergens = [
  "Gluten",
  "Lácteos",
  "Huevo",
  "Frutos secos",
  "Maní",
  "Soja",
  "Pescado",
  "Mariscos",
  "Sulfitos",
  "Sésamo",
];

const customAllergenInput = ref("");
const isSubmitting = ref(false);
const formError = ref("");
const validationErrors = reactive({});

const form = reactive({
  title: "",
  description: "",
  allergens: [],
  estimated_weight_kg: null,
});

// Sincronizar datos al abrir o cambiar de plantilla
watch(
  () => props.template,
  (newVal) => {
    formError.value = "";
    Object.keys(validationErrors).forEach((k) => delete validationErrors[k]);
    if (newVal) {
      form.title = newVal.title || "";
      form.description = newVal.description || "";
      form.allergens = Array.isArray(newVal.allergens) ? [...newVal.allergens] : [];
      form.estimated_weight_kg = newVal.estimated_weight_kg ?? null;
    } else {
      form.title = "";
      form.description = "";
      form.allergens = [];
      form.estimated_weight_kg = null;
    }
  },
  { immediate: true }
);

const closeModal = () => {
  emit("update:modelValue", false);
};

const toggleAllergen = (allergen) => {
  const idx = form.allergens.indexOf(allergen);
  if (idx > -1) {
    form.allergens.splice(idx, 1);
  } else {
    form.allergens.push(allergen);
  }
};

const addCustomAllergen = () => {
  const trimmed = customAllergenInput.value.trim();
  if (trimmed && !form.allergens.includes(trimmed)) {
    form.allergens.push(trimmed);
    customAllergenInput.value = "";
  }
};

const removeAllergen = (idx) => {
  form.allergens.splice(idx, 1);
};

const validateForm = () => {
  let valid = true;
  Object.keys(validationErrors).forEach((k) => delete validationErrors[k]);

  if (!form.title.trim()) {
    validationErrors.title = "El nombre de la plantilla es obligatorio";
    valid = false;
  }
  if (!form.description.trim()) {
    validationErrors.description = "La descripción de la bolsa es obligatoria";
    valid = false;
  }
  return valid;
};

const handleSave = async () => {
  if (!validateForm()) return;

  isSubmitting.value = true;
  formError.value = "";

  const payload = {
    title: form.title.trim(),
    description: form.description.trim(),
    allergens: form.allergens,
    estimated_weight_kg: form.estimated_weight_kg ? Number(form.estimated_weight_kg) : null,
  };

  try {
    let res;
    if (props.template?.id) {
      res = await axiosInstance.patch(`/pack-templates/${props.template.id}`, payload);
    } else {
      res = await axiosInstance.post("/pack-templates", payload);
    }
    emit("saved", res.data?.data || res.data);
    closeModal();
  } catch (err) {
    console.error("Error guardando plantilla:", err);
    if (err.response?.status === 422 && err.response?.data?.errors) {
      Object.assign(validationErrors, err.response.data.errors);
    } else {
      formError.value =
        err.response?.data?.message || "Ocurrió un error al guardar la plantilla";
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
      class="w-full max-w-lg rounded-2xl bg-[#2D2438] border border-white/[0.1] shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
    >
      <!-- Cabecera del Modal -->
      <header
        class="flex items-center justify-between px-6 py-4 border-b border-white/[0.08] bg-[#1A1625]/50"
      >
        <div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-[#A78BFA] block mb-0.5">
            Plantillas Maestras
          </span>
          <h2 class="text-base sm:text-lg font-bold text-white tracking-tight">
            {{ template?.id ? "Editar Plantilla" : "Nueva Plantilla" }}
          </h2>
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

      <!-- Formulario con scroll suave -->
      <form @submit.prevent="handleSave" class="p-6 space-y-4 overflow-y-auto flex-1">
        <!-- Error global si ocurre -->
        <div
          v-if="formError"
          class="p-3 rounded-xl bg-[#EF4444]/15 border border-[#EF4444]/30 text-[#F87171] text-xs font-medium flex items-center gap-2"
        >
          <svg class="w-4 h-4 shrink-0 text-[#EF4444]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>{{ formError }}</span>
        </div>

        <!-- Título -->
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-[#E8EAF6] block">
            Nombre de la Plantilla <span class="text-[#EF4444]">*</span>
          </label>
          <input
            v-model="form.title"
            type="text"
            maxlength="100"
            placeholder="Ej: Bolsa Sorpresa Panadería y Bollería"
            class="w-full px-3.5 py-2.5 rounded-xl bg-[#1A1625] border text-sm text-white placeholder-[#787596] focus:outline-none focus:border-[#7C3AED] transition"
            :class="validationErrors.title ? 'border-[#EF4444]' : 'border-white/[0.1]'"
          />
          <p v-if="validationErrors.title" class="text-xs text-[#EF4444]">
            {{ validationErrors.title }}
          </p>
        </div>

        <!-- Descripción -->
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-[#E8EAF6] block">
            Descripción habitual del contenido <span class="text-[#EF4444]">*</span>
          </label>
          <textarea
            v-model="form.description"
            rows="3"
            maxlength="1000"
            placeholder="Ej: Selección variada de panes de masa madre, facturas y medialunas elaboradas en la jornada."
            class="w-full px-3.5 py-2.5 rounded-xl bg-[#1A1625] border text-sm text-white placeholder-[#787596] focus:outline-none focus:border-[#7C3AED] transition resize-none leading-relaxed"
            :class="validationErrors.description ? 'border-[#EF4444]' : 'border-white/[0.1]'"
          ></textarea>
          <p v-if="validationErrors.description" class="text-xs text-[#EF4444]">
            {{ validationErrors.description }}
          </p>
        </div>

        <!-- Peso Estimado -->
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-[#E8EAF6] block">
            Peso estimado aproximado (opcional)
          </label>
          <div class="relative">
            <input
              v-model="form.estimated_weight_kg"
              type="number"
              step="0.05"
              min="0.1"
              placeholder="Ej: 1.2"
              class="w-full px-3.5 py-2.5 rounded-xl bg-[#1A1625] border border-white/[0.1] text-sm text-white placeholder-[#787596] focus:outline-none focus:border-[#7C3AED] transition"
            />
            <span class="absolute right-3.5 top-2.5 text-xs text-[#787596] font-medium">kg</span>
          </div>
        </div>

        <!-- Alérgenos -->
        <div class="space-y-2 pt-1">
          <label class="text-xs font-semibold text-[#E8EAF6] block">
            Alérgenos habituales
          </label>
          <div class="flex flex-wrap gap-1.5">
            <button
              v-for="allergen in commonAllergens"
              :key="allergen"
              type="button"
              class="px-2.5 py-1 rounded-lg text-xs font-medium border transition cursor-pointer"
              :class="
                form.allergens.includes(allergen)
                  ? 'bg-[#7C3AED]/20 border-[#7C3AED]/60 text-[#DDD6FE]'
                  : 'bg-[#1A1625] border-white/[0.08] text-[#A5A8C2] hover:border-white/20'
              "
              @click="toggleAllergen(allergen)"
            >
              {{ allergen }}
            </button>
          </div>

          <!-- Alérgeno personalizado -->
          <div class="flex gap-2 pt-1.5">
            <input
              v-model="customAllergenInput"
              type="text"
              placeholder="Otro alérgeno..."
              class="flex-1 px-3 py-1.5 rounded-lg bg-[#1A1625] border border-white/[0.1] text-xs text-white placeholder-[#787596] focus:outline-none focus:border-[#7C3AED]"
              @keydown.enter.prevent="addCustomAllergen"
            />
            <button
              type="button"
              class="px-3 py-1.5 rounded-lg bg-white/[0.06] hover:bg-white/[0.12] text-xs font-semibold text-white transition cursor-pointer"
              @click="addCustomAllergen"
            >
              + Agregar
            </button>
          </div>

          <!-- Tags activos adicionales que no estén en la lista rápida -->
          <div
            v-if="form.allergens.some((a) => !commonAllergens.includes(a))"
            class="flex flex-wrap gap-1.5 pt-1"
          >
            <span
              v-for="(customA, idx) in form.allergens.filter((a) => !commonAllergens.includes(a))"
              :key="idx"
              class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-xs bg-[#7C3AED]/15 border border-[#7C3AED]/30 text-[#C4B5FD]"
            >
              {{ customA }}
              <button
                type="button"
                class="hover:text-white cursor-pointer"
                @click="removeAllergen(form.allergens.indexOf(customA))"
              >
                ×
              </button>
            </span>
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
          @click="handleSave"
        >
          <span v-if="isSubmitting">Guardando...</span>
          <span v-else>{{ template?.id ? "Guardar Cambios" : "Crear Plantilla" }}</span>
        </button>
      </footer>
    </div>
  </div>
</template>
