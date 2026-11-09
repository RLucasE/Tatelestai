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
  "Soja",
  "Maní",
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

watch(
  () => [props.template, props.modelValue],
  ([newVal]) => {
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
      form.allergens = ["Gluten", "Lácteos", "Huevo"];
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
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-content/50 backdrop-blur-xs"
    @click.self="closeModal"
  >
    <div
      class="w-full max-w-[520px] rounded-[22px] bg-surface border border-border-subtle p-7 sm:p-8 shadow-card flex flex-col gap-[22px] max-h-[92vh] overflow-y-auto text-content"
    >
      <!-- Header -->
      <div class="flex flex-col gap-1">
        <span class="text-[11px] font-bold uppercase tracking-wider text-brand">
          PLANTILLAS MAESTRAS
        </span>
        <h2 class="text-[22px] font-extrabold text-content leading-tight">
          {{ template?.id ? "Editar Plantilla de Pack" : "Nueva Plantilla de Pack" }}
        </h2>
      </div>

      <!-- Form -->
      <form class="flex flex-col gap-5" @submit.prevent="handleSave">
        <!-- Global error -->
        <div
          v-if="formError"
          class="p-3 rounded-[12px] bg-danger/12 border border-danger/30 text-danger text-xs font-semibold"
        >
          {{ formError }}
        </div>

        <!-- Nombre de la Plantilla -->
        <div class="flex flex-col gap-1.5">
          <label class="text-[12px] font-semibold text-content">
            Nombre de la Plantilla *
          </label>
          <input
            v-model="form.title"
            type="text"
            maxlength="100"
            placeholder="Bolsa Sorpresa Panadería y Facturas"
            class="w-full px-3.5 py-[11px] rounded-[12px] bg-surface-sunken border text-[13.5px] font-semibold text-content placeholder-content-secondary focus:outline-none focus:border-brand"
            :class="validationErrors.title ? 'border-danger' : 'border-transparent'"
          />
          <p v-if="validationErrors.title" class="text-[11px] text-danger">
            {{ validationErrors.title }}
          </p>
        </div>

        <!-- Descripción habitual del contenido -->
        <div class="flex flex-col gap-1.5">
          <label class="text-[12px] font-semibold text-content">
            Descripción habitual del contenido *
          </label>
          <textarea
            v-model="form.description"
            rows="3"
            maxlength="1000"
            placeholder="Selección variada de panes de masa madre, facturas y medialunas del día."
            class="w-full px-3.5 py-[11px] rounded-[12px] bg-surface-sunken border text-[13.5px] font-semibold text-content placeholder-content-secondary focus:outline-none focus:border-brand resize-none leading-relaxed"
            :class="validationErrors.description ? 'border-danger' : 'border-transparent'"
          ></textarea>
          <p v-if="validationErrors.description" class="text-[11px] text-danger">
            {{ validationErrors.description }}
          </p>
        </div>

        <!-- Peso estimado aproximado -->
        <div class="flex flex-col gap-1.5">
          <label class="text-[12px] font-semibold text-content">
            Peso estimado aproximado (opcional)
          </label>
          <div class="relative flex items-center">
            <input
              v-model="form.estimated_weight_kg"
              type="number"
              step="0.05"
              min="0.1"
              placeholder="1.2"
              class="w-full px-3.5 py-[11px] rounded-[12px] bg-surface-sunken border border-transparent text-[13.5px] font-semibold text-content placeholder-content-secondary focus:outline-none focus:border-brand"
            />
            <span class="absolute right-3.5 text-xs font-semibold text-content-secondary">kg</span>
          </div>
        </div>

        <!-- Alérgenos habituales -->
        <div class="flex flex-col gap-2">
          <label class="text-[12px] font-semibold text-content">
            Alérgenos habituales
          </label>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="allergen in commonAllergens"
              :key="allergen"
              type="button"
              class="px-3 py-1.5 rounded-full text-[12px] transition-colors cursor-pointer"
              :class="
                form.allergens.includes(allergen)
                  ? 'bg-brand text-surface font-bold'
                  : 'bg-surface-sunken text-content font-medium hover:bg-canvas'
              "
              @click="toggleAllergen(allergen)"
            >
              {{ allergen }}
            </button>
          </div>

          <!-- Custom Allergen Input -->
          <div class="flex gap-2 pt-1">
            <input
              v-model="customAllergenInput"
              type="text"
              placeholder="Otro alérgeno..."
              class="flex-1 px-3 py-1.5 rounded-[10px] bg-surface-sunken border border-transparent text-xs font-medium text-content placeholder-content-secondary focus:outline-none focus:border-brand"
              @keydown.enter.prevent="addCustomAllergen"
            />
            <button
              type="button"
              class="px-3 py-1.5 rounded-[10px] bg-surface-sunken hover:bg-canvas text-xs font-semibold text-content transition cursor-pointer"
              @click="addCustomAllergen"
            >
              + Agregar
            </button>
          </div>

          <!-- Extra custom allergen chips -->
          <div
            v-if="form.allergens.some((a) => !commonAllergens.includes(a))"
            class="flex flex-wrap gap-1.5 pt-1"
          >
            <span
              v-for="(customA, idx) in form.allergens.filter((a) => !commonAllergens.includes(a))"
              :key="idx"
              class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-brand text-surface"
            >
              {{ customA }}
              <button
                type="button"
                class="hover:opacity-80 cursor-pointer"
                @click="removeAllergen(form.allergens.indexOf(customA))"
              >
                ×
              </button>
            </span>
          </div>
        </div>

        <!-- Actions Row -->
        <div class="flex items-center justify-between pt-1">
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
            {{ isSubmitting ? "Guardando..." : "Guardar Plantilla" }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
