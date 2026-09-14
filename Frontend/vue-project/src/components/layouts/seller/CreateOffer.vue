<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import axiosInstance from "@/lib/axios";

const router = useRouter();

// Pasos del Wizard
const currentStep = ref(1);
const totalSteps = 4;
const highestStepReached = ref(1);

const steps = [
  { number: 1, title: "Tipo de Pack", subtitle: "Nuevo o anterior" },
  { number: 2, title: "Detalles", subtitle: "Contenido y alérgenos" },
  { number: 3, title: "Precios", subtitle: "Valor y cupos" },
  { number: 4, title: "Horario", subtitle: "Ventana de retiro" },
];

// Estado de carga y feedback
const loadingTemplates = ref(true);
const submitting = ref(false);
const templates = ref([]);
const errorMessage = ref("");
const successMessage = ref("");
const validationErrors = reactive({});

// Plantilla seleccionada (null = crear nueva automáticamente)
const selectedTemplateId = ref(null);

// Formulario reactivo
const form = reactive({
  title: "",
  description: "",
  allergens: [],
  estimated_weight_kg: null,
  price: null,
  minimum_value: null,
  quantity: 1,
  pickup_start_datetime: "",
  pickup_end_datetime: "",
});

// Alérgenos comunes disponibles
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

// Métodos para alérgenos
const toggleAllergen = (allergen) => {
  const index = form.allergens.indexOf(allergen);
  if (index > -1) {
    form.allergens.splice(index, 1);
  } else {
    form.allergens.push(allergen);
  }
};

const isAllergenSelected = (allergen) => {
  return form.allergens.includes(allergen);
};

const addCustomAllergen = () => {
  const trimmed = customAllergenInput.value.trim();
  if (trimmed && !form.allergens.includes(trimmed)) {
    form.allergens.push(trimmed);
    customAllergenInput.value = "";
  }
};

const removeAllergen = (index) => {
  form.allergens.splice(index, 1);
};

// Cargar plantillas del comerciante
const fetchTemplates = async () => {
  try {
    loadingTemplates.value = true;
    const response = await axiosInstance.get("/pack-templates");
    templates.value = response.data?.data || response.data || [];
  } catch (err) {
    console.error("Error al cargar plantillas:", err);
  } finally {
    loadingTemplates.value = false;
  }
};

// Seleccionar plantilla
const selectTemplate = (template) => {
  if (!template) {
    clearSelectedTemplate();
    return;
  }

  selectedTemplateId.value = template.id;
  form.title = template.title || "";
  form.description = template.description || "";
  form.allergens = Array.isArray(template.allergens) ? [...template.allergens] : [];
  form.estimated_weight_kg = template.estimated_weight_kg
    ? Number(template.estimated_weight_kg)
    : null;

  delete validationErrors.title;
  delete validationErrors.description;
};

const clearSelectedTemplate = () => {
  selectedTemplateId.value = null;
};

// Helpers de fechas
const padZero = (num) => String(num).padStart(2, "0");

const formatDateTimeLocal = (date) => {
  const year = date.getFullYear();
  const month = padZero(date.getMonth() + 1);
  const day = padZero(date.getDate());
  const hours = padZero(date.getHours());
  const minutes = padZero(date.getMinutes());
  return `${year}-${month}-${day}T${hours}:${minutes}`;
};

const minPickupStart = computed(() => {
  const now = new Date();
  return formatDateTimeLocal(now);
});

// Atajos para configurar ventana de retiro rápido
const applyQuickSchedule = (type) => {
  const now = new Date();
  const start = new Date();
  const end = new Date();

  if (type === "today-afternoon") {
    start.setHours(19, 0, 0, 0);
    end.setHours(20, 30, 0, 0);
    if (now > start) {
      start.setTime(now.getTime() + 15 * 60 * 1000);
      end.setTime(start.getTime() + 90 * 60 * 1000);
    }
  } else if (type === "today-night") {
    start.setHours(21, 0, 0, 0);
    end.setHours(22, 30, 0, 0);
    if (now > start) {
      start.setTime(now.getTime() + 15 * 60 * 1000);
      end.setTime(start.getTime() + 90 * 60 * 1000);
    }
  } else if (type === "tomorrow-lunch") {
    start.setDate(start.getDate() + 1);
    start.setHours(13, 0, 0, 0);
    end.setDate(end.getDate() + 1);
    end.setHours(14, 30, 0, 0);
  }

  form.pickup_start_datetime = formatDateTimeLocal(start);
  form.pickup_end_datetime = formatDateTimeLocal(end);
  delete validationErrors.pickup_start_datetime;
  delete validationErrors.pickup_end_datetime;
};

// Cálculo de porcentaje de ahorro
const discountPercentage = computed(() => {
  const price = Number(form.price);
  const minVal = Number(form.minimum_value);
  if (!price || !minVal || minVal <= price || price <= 0) return 0;
  return Math.round(((minVal - price) / minVal) * 100);
});

const savingsAmount = computed(() => {
  const price = Number(form.price);
  const minVal = Number(form.minimum_value);
  if (!price || !minVal || minVal <= price) return 0;
  return minVal - price;
});

// Formateo de fecha y hora para la vista previa
const formattedPickupPreview = computed(() => {
  if (!form.pickup_start_datetime || !form.pickup_end_datetime) {
    return "Hoy, 19:00 - 20:30 hrs";
  }
  try {
    const start = new Date(form.pickup_start_datetime);
    const end = new Date(form.pickup_end_datetime);

    const isToday = start.toDateString() === new Date().toDateString();
    const dayLabel = isToday
      ? "Hoy"
      : start.toLocaleDateString("es-AR", { weekday: "short", day: "numeric", month: "short" });

    const startTime = start.toLocaleTimeString("es-AR", { hour: "2-digit", minute: "2-digit" });
    const endTime = end.toLocaleTimeString("es-AR", { hour: "2-digit", minute: "2-digit" });

    return `${dayLabel}, ${startTime} - ${endTime} hrs`;
  } catch (e) {
    return "Hoy, 19:00 - 20:30 hrs";
  }
});

// Barra de progreso interactiva (25%, 50%, 75%, 100% como en Figma)
const progressPercentage = computed(() => {
  return Math.round((currentStep.value / totalSteps) * 100);
});

// Nombre de la plantilla actualmente activa
const currentTemplateName = computed(() => {
  if (!selectedTemplateId.value) return "Pack nuevo";
  const found = templates.value.find((t) => t.id === selectedTemplateId.value);
  return found?.title || "Pack anterior";
});

// Navegación entre pasos
const canNavigateToStep = (stepNumber) => {
  return stepNumber <= highestStepReached.value;
};

const goToStep = (stepNumber) => {
  if (canNavigateToStep(stepNumber)) {
    currentStep.value = stepNumber;
  }
};

const validateCurrentStep = () => {
  errorMessage.value = "";
  let isValid = true;

  if (currentStep.value === 1) {
    return true;
  }

  if (currentStep.value === 2) {
    delete validationErrors.title;
    delete validationErrors.description;

    if (!form.title.trim()) {
      validationErrors.title = "El título del pack es obligatorio";
      isValid = false;
    }
    if (!form.description.trim()) {
      validationErrors.description = "La descripción del pack es obligatoria";
      isValid = false;
    }
    return isValid;
  }

  if (currentStep.value === 3) {
    delete validationErrors.price;
    delete validationErrors.minimum_value;
    delete validationErrors.quantity;

    if (!form.price || Number(form.price) <= 0) {
      validationErrors.price = "El precio debe ser mayor a 0";
      isValid = false;
    }
    if (!form.minimum_value || Number(form.minimum_value) < Number(form.price)) {
      validationErrors.minimum_value = "El valor real debe ser mayor o igual al precio";
      isValid = false;
    }
    if (!form.quantity || Number(form.quantity) < 1) {
      validationErrors.quantity = "Debes ofrecer al menos 1 pack";
      isValid = false;
    }
    return isValid;
  }

  if (currentStep.value === 4) {
    delete validationErrors.pickup_start_datetime;
    delete validationErrors.pickup_end_datetime;

    if (!form.pickup_start_datetime) {
      validationErrors.pickup_start_datetime = "Indica el inicio de la ventana de retiro";
      isValid = false;
    }
    if (!form.pickup_end_datetime) {
      validationErrors.pickup_end_datetime = "Indica el fin de la ventana de retiro";
      isValid = false;
    }
    if (
      form.pickup_start_datetime &&
      form.pickup_end_datetime &&
      new Date(form.pickup_end_datetime) <= new Date(form.pickup_start_datetime)
    ) {
      validationErrors.pickup_end_datetime = "El fin debe ser posterior al inicio";
      isValid = false;
    }
    return isValid;
  }

  return true;
};

const nextStep = () => {
  if (!validateCurrentStep()) return;

  if (currentStep.value < totalSteps) {
    currentStep.value++;
    if (currentStep.value > highestStepReached.value) {
      highestStepReached.value = currentStep.value;
    }
  }
};

const prevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--;
  }
};

// Envío final del formulario
const handleSubmit = async () => {
  if (!validateCurrentStep()) return;

  errorMessage.value = "";
  successMessage.value = "";
  Object.keys(validationErrors).forEach((key) => delete validationErrors[key]);

  const formatApiDate = (dtLocalStr) => {
    return dtLocalStr.replace("T", " ") + (dtLocalStr.length === 16 ? ":00" : "");
  };

  const payload = {
    price: Number(form.price),
    minimum_value: Number(form.minimum_value),
    quantity: Number(form.quantity),
    pickup_start_datetime: formatApiDate(form.pickup_start_datetime),
    pickup_end_datetime: formatApiDate(form.pickup_end_datetime),
  };

  if (selectedTemplateId.value) {
    payload.pack_template_id = selectedTemplateId.value;
    if (form.title) payload.title = form.title;
    if (form.description) payload.description = form.description;
    if (form.allergens) payload.allergens = form.allergens;
    if (form.estimated_weight_kg) payload.estimated_weight_kg = Number(form.estimated_weight_kg);
  } else {
    payload.title = form.title;
    payload.description = form.description;
    payload.allergens = form.allergens;
    if (form.estimated_weight_kg) {
      payload.estimated_weight_kg = Number(form.estimated_weight_kg);
    }
  }

  try {
    submitting.value = true;
    await axiosInstance.post("/packs", payload);

    successMessage.value = "¡Oferta publicada exitosamente! Ya está visible para los clientes.";

    setTimeout(() => {
      router.push({ name: "my-offers" });
    }, 1200);
  } catch (err) {
    console.error("Error al publicar oferta:", err);
    if (err.response?.status === 422 && err.response?.data?.errors) {
      Object.assign(validationErrors, err.response.data.errors);
      errorMessage.value = "Por favor, revisa los campos señalados.";
    } else {
      errorMessage.value =
        err.response?.data?.message || "Ocurrió un error inesperado al publicar la oferta.";
    }
  } finally {
    submitting.value = false;
  }
};

onMounted(() => {
  fetchTemplates();
  applyQuickSchedule("today-afternoon");
});
</script>

<template>
  <div class="create-offer-wrapper">
    <!-- Encabezado de la página (Figma Page Header) -->
    <header class="page-header">
      <div class="header-titles">
        <h1 class="page-title">Publicar Bolsa Sorpresa</h1>
        <p class="page-subtitle">
          Sigue los pasos para configurar y lanzar tu pack de rescate culinario al marketplace en tiempo real.
        </p>
      </div>
      <button
        type="button"
        @click="router.push({ name: 'my-offers' })"
        class="back-button"
      >
        <span>← Volver a mis ofertas</span>
      </button>
    </header>

    <!-- STEPPER PROGRESS SECTION (Figma StepperProgressSection) -->
    <section class="stepper-card">
      <div class="stepper-meta-header">
        <div class="stepper-meta-left">
          <span class="stepper-label-tag">PROGRESO DEL ASISTENTE</span>
          <span class="stepper-dot">•</span>
          <span class="stepper-status-text">
            Paso {{ currentStep }} de {{ totalSteps }}:
            <strong>{{ steps[currentStep - 1].title }}</strong>
          </span>
        </div>
      </div>

      <!-- Barra de progreso lineal de 6px con extremos redondeados -->
      <div class="linear-progress-track">
        <div
          class="linear-progress-fill"
          :style="{ width: `${progressPercentage}%` }"
        ></div>
      </div>

      <!-- Grid interactivo con los 4 pasos (Figma Stepper Item Grid) -->
      <div class="stepper-grid">
        <button
          v-for="step in steps"
          :key="step.number"
          type="button"
          class="step-item"
          :class="{
            active: currentStep === step.number,
            completed: currentStep > step.number,
            disabled: !canNavigateToStep(step.number),
          }"
          :disabled="!canNavigateToStep(step.number)"
          @click="goToStep(step.number)"
        >
          <!-- Squircle icon container -->
          <div class="step-squircle">
            <span v-if="currentStep > step.number" class="step-check-mark">✓</span>
            <span v-else class="step-num">{{ step.number }}</span>
          </div>
          <div class="step-text-group">
            <span class="step-kicker">PASO {{ step.number }}</span>
            <span class="step-heading">{{ step.title }}</span>
            <span class="step-caption">{{ step.subtitle }}</span>
          </div>
        </button>
      </div>
    </section>

    <!-- Feedback Alerts -->
    <div v-if="successMessage" class="feedback-alert success">
      <span class="alert-icon">✓</span>
      <div>{{ successMessage }}</div>
    </div>
    <div v-if="errorMessage" class="feedback-alert error">
      <span class="alert-icon">⚠</span>
      <div>{{ errorMessage }}</div>
    </div>

    <!-- MAIN 12-COLUMN LAYOUT (Figma FormAndPreviewLayout) -->
    <div class="form-preview-layout">
      <!-- COLUMNA IZQUIERDA: Formulario del Paso Activo (7 Cols) -->
      <div class="form-column">
        <!-- PANTALLA PASO 1: SELECCIÓN DE PACK (Figma Card Selection Grid) -->
        <section v-if="currentStep === 1" class="step-form-card">
          <!-- Título y narrativa de la sección -->
          <div class="section-narrative">
            <h2 class="section-title">Paso 1: ¿Qué pack vas a ofrecer hoy?</h2>
            <p class="section-desc">
              Puedes reutilizar los datos de un pack que creaste anteriormente para ahorrar tiempo o comenzar una nueva plantilla desde cero.
            </p>
          </div>

          <!-- Pack Cards Selection Grid (2 Cols) -->
          <div class="pack-cards-grid">
            <!-- Tarjeta 1: Crear un pack nuevo (Selected por defecto o cuando selectedTemplateId es null) -->
            <div
              class="pack-card new-pack-card"
              :class="{ selected: selectedTemplateId === null }"
              @click="clearSelectedTemplate"
            >
              <div class="pack-card-top">
                <div class="pack-icon-squircle violet-glow">
                  <!-- plus-icon.svg de Figma -->
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M5 12H19" stroke="#A78BFA" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M12 5V19" stroke="#A78BFA" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </div>

                <div v-if="selectedTemplateId === null" class="selected-badge-pill">
                  <!-- check-badge.svg de Figma -->
                  <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 3L4.5 8.5L2 6" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  <span>SELECCIONADO</span>
                </div>
              </div>

              <h3 class="pack-card-title">Crear un pack nuevo</h3>
              <p class="pack-card-desc">
                Comienza desde cero con un pack personalizado, definiendo nombre, precio y alérgenos hoy.
              </p>
            </div>

            <!-- Tarjetas de Packs Guardados / Reutilizables -->
            <div
              v-for="tmpl in templates"
              :key="tmpl.id"
              class="pack-card reusable-pack-card"
              :class="{ selected: selectedTemplateId === tmpl.id }"
              @click="selectTemplate(tmpl)"
            >
              <div class="pack-card-top">
                <div class="pack-icon-squircle amber-glow">
                  <span class="emoji-icon">🛍️</span>
                </div>

                <div v-if="selectedTemplateId === tmpl.id" class="selected-badge-pill">
                  <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 3L4.5 8.5L2 6" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  <span>SELECCIONADO</span>
                </div>
                <div v-else class="reusable-badge-pill">
                  <span>Reutilizable</span>
                </div>
              </div>

              <h3 class="pack-card-title">{{ tmpl.title }}</h3>
              <p class="pack-card-desc line-clamp-2">
                {{ tmpl.description || "Plantilla guardada de bolsa sorpresa." }}
              </p>

              <!-- Tags / Badges de alérgenos -->
              <div v-if="tmpl.allergens?.length" class="pack-card-allergens-row">
                <span
                  v-for="(alg, i) in tmpl.allergens.slice(0, 3)"
                  :key="i"
                  class="allergen-tag-pill"
                >
                  {{ alg }}
                </span>
                <span v-if="tmpl.allergens.length > 3" class="allergen-tag-more">
                  +{{ tmpl.allergens.length - 3 }}
                </span>
              </div>
            </div>

            <!-- Estado cuando no hay plantillas aún -->
            <div
              v-if="!loadingTemplates && templates.length === 0"
              class="pack-card empty-templates-card"
            >
              <div class="pack-card-top">
                <div class="pack-icon-squircle muted-glow">
                  <span class="emoji-icon">✨</span>
                </div>
                <div class="reusable-badge-pill">
                  <span>Primer pack</span>
                </div>
              </div>
              <h3 class="pack-card-title text-slate-400">Tus futuros packs</h3>
              <p class="pack-card-desc">
                Una vez que publiques tu primer pack sorpresa, podrás reutilizarlo directamente desde este panel con un solo clic.
              </p>
            </div>
          </div>

          <!-- Bottom Action Buttons (Figma Bottom Action Buttons) -->
          <div class="step-bottom-bar">
            <div class="editable-info-note">
              <!-- info-clock.svg (shield check green) de Figma -->
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M13.3333 8.66664C13.3333 12 11 13.6666 8.22666 14.6333C8.08144 14.6825 7.92369 14.6802 7.78 14.6266C5 13.6666 2.66666 12 2.66666 8.66664V3.99997C2.66666 3.63203 2.96539 3.33331 3.33333 3.33331C4.66666 3.33331 6.33333 2.53331 7.49333 1.51997C7.78511 1.27069 8.21488 1.27069 8.50666 1.51997C9.67333 2.53997 11.3333 3.33331 12.6667 3.33331C13.0346 3.33331 13.3333 3.63203 13.3333 3.99997V8.66664" stroke="#34D399" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M6 7.99996L7.33333 9.33329L10 6.66663" stroke="#34D399" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span>Configuración editable hasta la hora de inicio</span>
            </div>

            <button
              type="button"
              class="primary-cta-btn"
              @click="nextStep"
            >
              <span>Continuar a Detalles</span>
              <!-- arrow-right.svg de Figma -->
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3.33331 8H12.6666" stroke="white" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8 3.33337L12.6667 8.00004L8 12.6667" stroke="white" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </div>
        </section>

        <!-- PANTALLA PASO 2: DETALLES Y ALÉRGENOS -->
        <section v-else-if="currentStep === 2" class="step-form-card">
          <div class="section-narrative">
            <h2 class="section-title">Paso 2: Información del Pack</h2>
            <p class="section-desc">
              Describe el contenido estimado de la bolsa y marca los ingredientes sensibles para la seguridad de tus clientes.
            </p>
          </div>

          <!-- Título del Pack -->
          <div class="custom-form-group">
            <label for="title" class="custom-label">
              Título de la Bolsa Sorpresa <span class="required-star">*</span>
            </label>
            <input
              id="title"
              v-model="form.title"
              type="text"
              class="custom-input"
              :class="{ 'has-error': validationErrors.title }"
              placeholder="Ej: Bolsa Sorpresa Panadería y Facturas"
              maxlength="100"
            />
            <span v-if="validationErrors.title" class="custom-field-error">
              {{ validationErrors.title }}
            </span>
          </div>

          <!-- Descripción -->
          <div class="custom-form-group">
            <label for="description" class="custom-label">
              Descripción detallada <span class="required-star">*</span>
            </label>
            <textarea
              id="description"
              v-model="form.description"
              class="custom-textarea"
              :class="{ 'has-error': validationErrors.description }"
              placeholder="Describe el surtido general. Ej: Surtido variado de facturas, medialunas rellenas y panes artesanales del día. ¡Frescura y calidad garantizadas!"
              rows="3"
              maxlength="1000"
            ></textarea>
            <span v-if="validationErrors.description" class="custom-field-error">
              {{ validationErrors.description }}
            </span>
          </div>

          <!-- Alérgenos -->
          <div class="custom-form-group">
            <label class="custom-label">
              Alérgenos e ingredientes sensibles
              <span class="label-muted-hint">(Haz clic para marcar o desmarcar)</span>
            </label>
            <div class="allergen-chips-wrap">
              <button
                v-for="allergen in commonAllergens"
                :key="allergen"
                type="button"
                class="chip-button"
                :class="{ active: isAllergenSelected(allergen) }"
                @click="toggleAllergen(allergen)"
              >
                <span v-if="isAllergenSelected(allergen)" class="chip-check">✓</span>
                <span>{{ allergen }}</span>
              </button>
            </div>

            <!-- Agregar alérgeno personalizado -->
            <div class="custom-tag-row">
              <input
                v-model="customAllergenInput"
                type="text"
                placeholder="Otro alérgeno (ej: Apio, Mostaza)..."
                class="custom-input-sm"
                @keydown.enter.prevent="addCustomAllergen"
              />
              <button
                type="button"
                class="btn-sm-secondary"
                @click="addCustomAllergen"
              >
                + Agregar
              </button>
            </div>

            <!-- Tags activos -->
            <div v-if="form.allergens.length" class="active-tags-banner">
              <span class="active-tags-label">Alérgenos marcados:</span>
              <div class="flex flex-wrap gap-1.5">
                <span
                  v-for="(alg, index) in form.allergens"
                  :key="index"
                  class="active-tag-pill"
                >
                  {{ alg }}
                  <button
                    type="button"
                    class="tag-del-btn"
                    @click="removeAllergen(index)"
                  >
                    ×
                  </button>
                </span>
              </div>
            </div>
          </div>

          <!-- Peso Estimado -->
          <div class="custom-form-group">
            <label for="estimated_weight_kg" class="custom-label">
              Peso Estimado (kg)
              <span class="label-muted-hint">(Aproximado, opcional)</span>
            </label>
            <div class="weight-control-row">
              <input
                id="estimated_weight_kg"
                v-model.number="form.estimated_weight_kg"
                type="number"
                step="0.05"
                min="0.05"
                max="50"
                class="custom-input"
                placeholder="Ej: 1.2"
              />
              <div class="weight-presets-bar">
                <button
                  type="button"
                  class="weight-preset-btn"
                  @click="form.estimated_weight_kg = 0.5"
                >
                  0.5 kg
                </button>
                <button
                  type="button"
                  class="weight-preset-btn"
                  @click="form.estimated_weight_kg = 1.0"
                >
                  1.0 kg
                </button>
                <button
                  type="button"
                  class="weight-preset-btn"
                  @click="form.estimated_weight_kg = 1.5"
                >
                  1.5 kg
                </button>
                <button
                  type="button"
                  class="weight-preset-btn"
                  @click="form.estimated_weight_kg = 2.0"
                >
                  2.0 kg
                </button>
              </div>
            </div>
          </div>

          <!-- Navegación Paso 2 -->
          <div class="step-bottom-bar space-between">
            <button type="button" class="btn-secondary-prev" @click="prevStep">
              <span>← Volver al Paso 1</span>
            </button>
            <button type="button" class="primary-cta-btn" @click="nextStep">
              <span>Continuar a Precios</span>
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3.33331 8H12.6666" stroke="white" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8 3.33337L12.6667 8.00004L8 12.6667" stroke="white" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </div>
        </section>

        <!-- PANTALLA PASO 3: PRECIOS, DESCUENTO Y STOCK -->
        <section v-else-if="currentStep === 3" class="step-form-card">
          <div class="section-narrative">
            <h2 class="section-title">Paso 3: Precios, Descuento y Stock</h2>
            <p class="section-desc">
              Define el precio reducido, el valor mínimo original de la comida y la cantidad de bolsas a vender hoy.
            </p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- Precio al cliente -->
            <div class="custom-form-group">
              <label for="price" class="custom-label">
                Precio de Venta ($ ARS) <span class="required-star">*</span>
              </label>
              <input
                id="price"
                v-model.number="form.price"
                type="number"
                min="1"
                class="custom-input"
                :class="{ 'has-error': validationErrors.price }"
                placeholder="2500"
              />
              <span v-if="validationErrors.price" class="custom-field-error">
                {{ validationErrors.price }}
              </span>
              <span class="custom-field-hint">Lo que paga el consumidor por la bolsa.</span>
            </div>

            <!-- Valor mínimo original -->
            <div class="custom-form-group">
              <label for="minimum_value" class="custom-label">
                Valor Real Garantizado ($ ARS) <span class="required-star">*</span>
              </label>
              <input
                id="minimum_value"
                v-model.number="form.minimum_value"
                type="number"
                min="1"
                class="custom-input"
                :class="{ 'has-error': validationErrors.minimum_value }"
                placeholder="7500"
              />
              <span v-if="validationErrors.minimum_value" class="custom-field-error">
                {{ validationErrors.minimum_value }}
              </span>
              <span class="custom-field-hint">Precio habitual de venta en el local.</span>
            </div>
          </div>

          <!-- Card de Ahorro Calculado en Tiempo Real -->
          <div v-if="discountPercentage > 0" class="realtime-savings-box">
            <div class="savings-pill-highlight">
              <span class="savings-percent-text">{{ discountPercentage }}% de Ahorro</span>
            </div>
            <div class="savings-text-detail">
              El cliente ahorra <strong>${{ savingsAmount.toLocaleString('es-AR') }}</strong> en comparación al precio habitual en góndola.
            </div>
          </div>

          <!-- Cantidad de Cupos (Stock) -->
          <div class="custom-form-group mt-6">
            <label for="quantity" class="custom-label">
              Bolsas disponibles hoy (Cupos) <span class="required-star">*</span>
            </label>
            <div class="stepper-quantity-wrap">
              <button
                type="button"
                class="quantity-step-btn"
                :disabled="form.quantity <= 1"
                @click="form.quantity = Math.max(1, form.quantity - 1)"
              >
                -
              </button>
              <input
                id="quantity"
                v-model.number="form.quantity"
                type="number"
                min="1"
                max="100"
                class="quantity-number-input"
              />
              <button
                type="button"
                class="quantity-step-btn"
                @click="form.quantity = form.quantity + 1"
              >
                +
              </button>
              <span class="quantity-unit-label">unidades disponibles para rescate</span>
            </div>
            <span v-if="validationErrors.quantity" class="custom-field-error">
              {{ validationErrors.quantity }}
            </span>
          </div>

          <!-- Navegación Paso 3 -->
          <div class="step-bottom-bar space-between">
            <button type="button" class="btn-secondary-prev" @click="prevStep">
              <span>← Volver a Detalles</span>
            </button>
            <button type="button" class="primary-cta-btn" @click="nextStep">
              <span>Continuar a Horarios</span>
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3.33331 8H12.6666" stroke="white" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8 3.33337L12.6667 8.00004L8 12.6667" stroke="white" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </div>
        </section>

        <!-- PANTALLA PASO 4: HORARIOS Y CONFIRMACIÓN -->
        <section v-else-if="currentStep === 4" class="step-form-card">
          <div class="section-narrative">
            <h2 class="section-title">Paso 4: Ventana de Retiro y Publicación</h2>
            <p class="section-desc">
              Establece el intervalo estricto para que los clientes retiren su pedido en tu local gastronómico.
            </p>
          </div>

          <!-- Atajos rápidos -->
          <div class="quick-shortcuts-panel">
            <span class="quick-shortcuts-title">Atajos rápidos de retiro:</span>
            <div class="quick-shortcuts-list">
              <button
                type="button"
                class="quick-pill-button"
                @click="applyQuickSchedule('today-afternoon')"
              >
                Hoy Tarde (19:00 - 20:30)
              </button>
              <button
                type="button"
                class="quick-pill-button"
                @click="applyQuickSchedule('today-night')"
              >
                Hoy Noche (21:00 - 22:30)
              </button>
              <button
                type="button"
                class="quick-pill-button"
                @click="applyQuickSchedule('tomorrow-lunch')"
              >
                Mañana Almuerzo (13:00 - 14:30)
              </button>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- Inicio de Retiro -->
            <div class="custom-form-group">
              <label for="pickup_start_datetime" class="custom-label">
                Inicio de Retiro <span class="required-star">*</span>
              </label>
              <input
                id="pickup_start_datetime"
                v-model="form.pickup_start_datetime"
                type="datetime-local"
                :min="minPickupStart"
                class="custom-input"
                :class="{ 'has-error': validationErrors.pickup_start_datetime }"
              />
              <span v-if="validationErrors.pickup_start_datetime" class="custom-field-error">
                {{ validationErrors.pickup_start_datetime }}
              </span>
            </div>

            <!-- Fin de Retiro -->
            <div class="custom-form-group">
              <label for="pickup_end_datetime" class="custom-label">
                Fin de Retiro (Expiración) <span class="required-star">*</span>
              </label>
              <input
                id="pickup_end_datetime"
                v-model="form.pickup_end_datetime"
                type="datetime-local"
                :min="form.pickup_start_datetime || minPickupStart"
                class="custom-input"
                :class="{ 'has-error': validationErrors.pickup_end_datetime }"
              />
              <span v-if="validationErrors.pickup_end_datetime" class="custom-field-error">
                {{ validationErrors.pickup_end_datetime }}
              </span>
            </div>
          </div>

          <!-- Resumen de Confirmación -->
          <div class="order-summary-panel">
            <h4 class="order-summary-title">Resumen de la Oferta a Publicar</h4>
            <div class="order-summary-grid">
              <div class="summary-cell">
                <span class="cell-label">Título:</span>
                <span class="cell-value font-semibold">{{ form.title || "-" }}</span>
              </div>
              <div class="summary-cell">
                <span class="cell-label">Precio al Cliente:</span>
                <span class="cell-value font-bold text-emerald-400">
                  ${{ Number(form.price || 0).toLocaleString("es-AR") }}
                </span>
              </div>
              <div class="summary-cell">
                <span class="cell-label">Valor Garantizado:</span>
                <span class="cell-value">
                  ${{ Number(form.minimum_value || 0).toLocaleString("es-AR") }}
                </span>
              </div>
              <div class="summary-cell">
                <span class="cell-label">Cupos:</span>
                <span class="cell-value font-semibold">{{ form.quantity }} unidades</span>
              </div>
              <div class="summary-cell col-span-2">
                <span class="cell-label">Horario de retiro:</span>
                <span class="cell-value text-purple-300 font-medium">{{ formattedPickupPreview }}</span>
              </div>
              <div class="summary-cell col-span-2">
                <span class="cell-label">Tipo de pack:</span>
                <span class="cell-value text-slate-300">
                  {{
                    selectedTemplateId
                      ? "Reutiliza datos de pack anterior"
                      : "Pack nuevo creado desde cero"
                  }}
                </span>
              </div>
            </div>
          </div>

          <!-- Acciones de Envío Final -->
          <div class="step-bottom-bar space-between">
            <button type="button" class="btn-secondary-prev" @click="prevStep">
              <span>← Volver a Precios</span>
            </button>

            <button
              type="button"
              class="primary-cta-btn publish-cta"
              :disabled="submitting"
              @click="handleSubmit"
            >
              <span v-if="submitting" class="loading-spinner-circle"></span>
              <span v-else>Publicar Oferta Ahora</span>
            </button>
          </div>
        </section>
      </div>

      <!-- COLUMNA DERECHA: Vista Previa para el Cliente (5 Cols) (Figma Customer Live Preview) -->
      <aside class="preview-column">
        <div class="preview-sticky-wrap">
          <!-- Preview Header Banner -->
          <div class="preview-header-banner">
            <div class="live-indicator-group">
              <div class="pulse-indicator">
                <span class="pulse-ring"></span>
                <span class="pulse-dot"></span>
              </div>
              <h3 class="live-heading">VISTA PREVIA PARA EL CLIENTE</h3>
            </div>
            <div class="realtime-badge-pill">
              <span>Actualizada en tiempo real</span>
            </div>
          </div>

          <!-- Preview Phone / Card Mockup (Figma Mockup Card) -->
          <div class="preview-mockup-card">
            <!-- Visual Mockup Hero Image Area (176px de Figma) -->
            <div class="mockup-hero-area">
              <div class="mockup-hero-center">
                <div class="hero-surprise-badge">
                  <span class="hero-badge-emoji">🛍️</span>
                  <span class="hero-badge-text">
                    {{ form.title || "Bolsa Sorpresa Especial" }}
                  </span>
                </div>
                <span class="hero-subtitle">Contenido revelado al retirar</span>
              </div>

              <!-- Store avatar bar -->
              <div class="mockup-store-bar">
                <div class="store-circle-avatar">T</div>
                <span class="store-name-label">Tu Comercio en Tatelestai</span>
              </div>
            </div>

            <!-- Card Body Content -->
            <div class="mockup-body-content">
              <div class="mockup-title-text">
                {{ form.title || "Nombre de tu bolsa sorpresa" }}
              </div>
              <div class="mockup-desc-text">
                {{
                  form.description ||
                  "Aquí verás la descripción del pack con el surtido estimado que recibirán los clientes al retirar en el local."
                }}
              </div>

              <!-- Alérgenos -->
              <div v-if="form.allergens.length" class="mockup-allergens-wrap">
                <span class="mockup-allergens-label">Alérgenos:</span>
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="(alg, idx) in form.allergens"
                    :key="idx"
                    class="mockup-tag-pill"
                  >
                    {{ alg }}
                  </span>
                </div>
              </div>

              <!-- Peso estimado opcional -->
              <div v-if="form.estimated_weight_kg" class="mockup-weight-info">
                Aprox. {{ form.estimated_weight_kg }} kg
              </div>

              <!-- Time Pickup Window Chip (Figma Time Pickup Window Chip) -->
              <div class="mockup-pickup-chip">
                <!-- clock.svg de Figma -->
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M1.33331 7.99998C1.33331 11.6794 4.32055 14.6666 7.99998 14.6666C11.6794 14.6666 14.6666 11.6794 14.6666 7.99998C14.6666 4.32055 11.6794 1.33331 7.99998 1.33331C4.32055 1.33331 1.33331 4.32055 1.33331 7.99998V7.99998" stroke="#A78BFA" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M8 4V8L10.6667 9.33333" stroke="#A78BFA" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <div class="pickup-text-inline">
                  <span class="pickup-prefix">Retiro:</span>
                  <strong class="pickup-value">{{ formattedPickupPreview }}</strong>
                </div>
              </div>

              <!-- Price & Inventory Row (Figma Price & Inventory Row) -->
              <div class="mockup-price-stock-row">
                <div class="mockup-prices-box">
                  <div class="mockup-active-price">
                    ${{ form.price ? Number(form.price).toLocaleString("es-AR") : "0" }}
                  </div>
                  <div class="mockup-original-strikethrough">
                    Valor original estimado: ${{ form.minimum_value ? Number(form.minimum_value).toLocaleString("es-AR") : "0" }}
                  </div>
                </div>

                <div class="mockup-stock-pill">
                  {{ form.quantity || 1 }} disponible{{ (form.quantity || 1) > 1 ? "s" : "" }}
                </div>
              </div>
            </div>

            <!-- Preview Footer Meta Table (Figma Preview Footer Meta Table) -->
            <div class="mockup-footer-table">
              <div class="meta-table-row">
                <span class="meta-table-label">Tipo seleccionado:</span>
                <span class="meta-table-value">{{ currentTemplateName }}</span>
              </div>
              <div class="meta-table-row">
                <span class="meta-table-label">Cupos a publicar:</span>
                <span class="meta-table-value">{{ form.quantity || 1 }} unidad{{ (form.quantity || 1) > 1 ? "es" : "" }}</span>
              </div>
              <div v-if="discountPercentage > 0" class="meta-table-row">
                <span class="meta-table-label">Ahorro cliente:</span>
                <span class="meta-table-value text-emerald-400 font-bold">
                  {{ discountPercentage }}% OFF
                </span>
              </div>
            </div>
          </div>
        </div>
      </aside>
    </div>
  </div>
</template>

<style scoped>
/* CONTENEDOR GENERAL (Canvas Dark Violet de Tatelestai) */
.create-offer-wrapper {
  width: 100%;
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 0 64px;
  color: #e8eaf6;
  font-family: "Plus Jakarta Sans", "Inter", -apple-system, sans-serif;
  align-self: flex-start;
}

/* ENCABEZADO DE LA PÁGINA (Figma Page Header) */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  gap: 1.5rem;
}

.header-titles {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.page-title {
  font-size: 2.25rem; /* 36px en Figma */
  font-weight: 800;
  color: #ffffff;
  margin: 0;
  line-height: 2.5rem; /* 40px */
  letter-spacing: -0.025em;
}

.page-subtitle {
  font-size: 1rem; /* 16px */
  color: #94a3b8;
  margin: 0;
  line-height: 1.5rem; /* 24px */
}

.back-button {
  background: transparent;
  color: #94a3b8;
  border: 1px solid #2d2443;
  padding: 0.55rem 1.15rem;
  border-radius: 10px;
  cursor: pointer;
  font-size: 0.875rem;
  font-weight: 500;
  white-space: nowrap;
  transition: all 0.2s ease;
}

.back-button:hover {
  color: #ffffff;
  border-color: #7c3aed;
  background: rgba(124, 58, 237, 0.1);
}

/* STEPPER PROGRESS SECTION (Figma StepperProgressSection) */
.stepper-card {
  background: #151120;
  border: 1px solid rgba(45, 36, 67, 0.8);
  border-radius: 16px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0px 8px 30px -8px rgba(0, 0, 0, 0.6);
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.stepper-meta-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.stepper-meta-left {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.75rem;
}

.stepper-label-tag {
  color: #a78bfa;
  font-weight: 600;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.stepper-dot {
  color: #64748b;
  font-size: 1rem;
}

.stepper-status-text {
  color: #cbd5e1;
  font-weight: 500;
}

.stepper-status-text strong {
  color: #ffffff;
  font-weight: 700;
}

/* Barra de progreso de 6px */
.linear-progress-track {
  width: 100%;
  height: 6px;
  background: #221c33;
  border-radius: 9999px;
  overflow: hidden;
}

.linear-progress-fill {
  height: 100%;
  background: #7c3aed;
  border-radius: 9999px;
  transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Grid de los 4 pasos */
.stepper-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 1rem;
  padding-top: 0.5rem;
}

.step-item {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 0.5rem 0.75rem;
  border-radius: 12px;
  background: transparent;
  border: 1px solid transparent;
  cursor: pointer;
  text-align: left;
  transition: all 0.2s ease;
  opacity: 0.6;
}

.step-item:not(.disabled):hover {
  opacity: 1;
  background: rgba(45, 36, 67, 0.3);
}

.step-item.active {
  background: rgba(124, 58, 237, 0.1);
  border-color: rgba(139, 92, 246, 0.4);
  opacity: 1;
}

.step-item.completed {
  opacity: 0.9;
}

.step-item.disabled {
  cursor: not-allowed;
}

/* Squircle 36x36px */
.step-squircle {
  width: 36px;
  height: 36px;
  border-radius: 12px;
  background: #221c33;
  border: 1px solid #2d2443;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.25s ease;
}

.step-num {
  font-size: 0.875rem;
  font-weight: 600;
  color: #94a3b8;
}

.step-item.active .step-squircle {
  background: #7c3aed;
  border-color: transparent;
  box-shadow: 0px 4px 20px -2px rgba(124, 58, 237, 0.45);
}

.step-item.active .step-num {
  color: #ffffff;
  font-weight: 700;
}

.step-item.completed .step-squircle {
  background: rgba(16, 185, 129, 0.2);
  border-color: #10b981;
}

.step-check-mark {
  color: #10b981;
  font-weight: 800;
  font-size: 0.875rem;
}

.step-text-group {
  display: flex;
  flex-direction: column;
}

.step-kicker {
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.025em;
  text-transform: uppercase;
  color: #94a3b8;
}

.step-item.active .step-kicker {
  color: #f1f5f9;
}

.step-heading {
  font-size: 0.875rem;
  font-weight: 600;
  color: #e2e8f0;
  line-height: 1.25rem;
}

.step-item.active .step-heading {
  color: #ffffff;
  font-weight: 700;
}

.step-caption {
  font-size: 0.6875rem; /* 11px */
  color: #64748b;
  line-height: 0.875rem;
}

.step-item.active .step-caption {
  color: #94a3b8;
}

@media (max-width: 900px) {
  .stepper-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 550px) {
  .step-caption {
    display: none;
  }
}

/* ALERTAS DE FEEDBACK */
.feedback-alert {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1.25rem;
  border-radius: 12px;
  margin-bottom: 1.5rem;
  font-size: 0.9375rem;
  font-weight: 500;
}

.feedback-alert.success {
  background: rgba(16, 185, 129, 0.15);
  border: 1px solid #10b981;
  color: #6ee7b7;
}

.feedback-alert.error {
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid #ef4444;
  color: #fca5a5;
}

.alert-icon {
  font-size: 1.125rem;
}

/* LAYOUT PRINCIPAL DE 12 COLUMNAS (Figma FormAndPreviewLayout) */
.form-preview-layout {
  display: grid;
  grid-template-columns: 1fr;
  gap: 2rem;
  align-items: start;
}

@media (min-width: 1024px) {
  .form-preview-layout {
    grid-template-columns: 7fr 5fr; /* Proporción 7 cols y 5 cols */
  }
}

/* TARJETA DEL FORMULARIO (Columna Izquierda 7 Cols) */
.step-form-card {
  background: #151120;
  border: 1px solid #2d2443;
  border-radius: 24px;
  padding: 2rem;
  box-shadow: 0px 8px 10px -6px rgba(0, 0, 0, 0.1), 0px 20px 25px -5px rgba(0, 0, 0, 0.1);
  display: flex;
  flex-direction: column;
  gap: 1.75rem;
}

/* Narrativa y título de sección */
.section-narrative {
  padding-bottom: 1.5rem;
  border-bottom: 1px solid rgba(45, 36, 67, 0.7);
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.section-title {
  font-size: 1.5rem; /* 24px */
  font-weight: 700;
  color: #ffffff;
  letter-spacing: -0.025em;
  margin: 0;
  line-height: 2rem;
}

.section-desc {
  font-size: 0.875rem; /* 14px */
  color: #94a3b8;
  margin: 0;
  line-height: 1.42;
}

/* GRID DE SELECCIÓN DE PACKS (Paso 1) */
.pack-cards-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

@media (max-width: 640px) {
  .pack-cards-grid {
    grid-template-columns: 1fr;
  }
}

.pack-card {
  background: #1a1528;
  border: 1px solid rgba(45, 36, 67, 0.8);
  border-radius: 16px;
  padding: 1.25rem;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  transition: all 0.25s ease;
  position: relative;
}

.pack-card:hover {
  border-color: #7c3aed;
  transform: translateY(-2px);
  background: #1f1931;
}

.pack-card.selected {
  border-color: #8b5cf6;
  box-shadow: 0px 0px 20px -2px rgba(124, 58, 237, 0.5), 0px 0px 0px 2px rgba(124, 58, 237, 1);
  background: #1a1528;
}

.pack-card-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.25rem;
}

.pack-icon-squircle {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.pack-icon-squircle.violet-glow {
  background: rgba(124, 58, 237, 0.2);
  border: 1px solid rgba(139, 92, 246, 0.4);
}

.pack-icon-squircle.amber-glow {
  background: rgba(245, 158, 11, 0.1);
  border: 1px solid rgba(245, 158, 11, 0.2);
}

.pack-icon-squircle.muted-glow {
  background: rgba(100, 116, 139, 0.15);
  border: 1px solid rgba(100, 116, 139, 0.25);
}

.emoji-icon {
  font-size: 1.5rem;
}

/* Pills de badges */
.selected-badge-pill {
  background: #7c3aed;
  border-radius: 9999px;
  padding: 0.25rem 0.65rem;
  display: flex;
  align-items: center;
  gap: 0.35rem;
  box-shadow: 0px 1px 2px 0px rgba(0, 0, 0, 0.05);
}

.selected-badge-pill span {
  font-size: 0.6875rem; /* 11px */
  font-weight: 700;
  color: #ffffff;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.reusable-badge-pill {
  background: rgba(45, 36, 67, 0.6);
  border: 1px solid rgba(61, 50, 89, 0.4);
  border-radius: 6px;
  padding: 0.15rem 0.6rem;
}

.reusable-badge-pill span {
  font-size: 0.6875rem; /* 11px */
  font-weight: 500;
  color: #94a3b8;
}

.pack-card-title {
  font-size: 1rem; /* 16px */
  font-weight: 700;
  color: #ffffff;
  margin: 0;
  line-height: 1.5rem;
}

.pack-card-desc {
  font-size: 0.75rem; /* 12px */
  color: #94a3b8;
  margin: 0;
  line-height: 1.25rem;
}

.pack-card-allergens-row {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding-top: 0.5rem;
  border-top: 1px solid rgba(45, 36, 67, 0.5);
  margin-top: 0.25rem;
}

.allergen-tag-pill {
  background: #2d2443;
  color: #cbd5e1;
  font-size: 0.625rem; /* 10px */
  font-weight: 500;
  padding: 0.15rem 0.5rem;
  border-radius: 6px;
}

.allergen-tag-more {
  background: rgba(45, 36, 67, 0.7);
  color: #94a3b8;
  font-size: 0.625rem;
  font-weight: 500;
  padding: 0.15rem 0.4rem;
  border-radius: 6px;
}

/* BARRA INFERIOR DE ACCIONES (Figma Bottom Action Buttons) */
.step-bottom-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 1.5rem;
  border-top: 1px solid rgba(45, 36, 67, 0.7);
  gap: 1rem;
  flex-wrap: wrap;
}

.step-bottom-bar.space-between {
  justify-content: space-between;
}

.editable-info-note {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #94a3b8;
  font-size: 0.75rem; /* 12px */
}

.primary-cta-btn {
  background: #7c3aed;
  border-radius: 12px;
  padding: 0.875rem 1.75rem;
  color: #ffffff;
  font-size: 0.875rem; /* 14px */
  font-weight: 700;
  border: none;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0px 0px 25px -4px rgba(124, 58, 237, 0.45);
  transition: all 0.2s ease;
  white-space: nowrap;
}

.primary-cta-btn:hover:not(:disabled) {
  background: #6d28d9;
  transform: translateY(-2px);
  box-shadow: 0px 0px 30px -2px rgba(124, 58, 237, 0.65);
}

.primary-cta-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary-prev {
  background: transparent;
  color: #94a3b8;
  border: 1px solid #2d2443;
  padding: 0.8rem 1.4rem;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-secondary-prev:hover {
  color: #ffffff;
  border-color: #7c3aed;
  background: rgba(124, 58, 237, 0.1);
}

/* CAMPOS DE FORMULARIO (Pasos 2, 3, 4) */
.custom-form-group {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.custom-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #e8eaf6;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.required-star {
  color: #ef4444;
}

.label-muted-hint {
  font-size: 0.75rem;
  color: #94a3b8;
  font-weight: 400;
}

.custom-input,
.custom-textarea {
  width: 100%;
  background: #1a1528;
  border: 1px solid #2d2443;
  color: #ffffff;
  padding: 0.75rem 1rem;
  border-radius: 12px;
  font-size: 0.875rem;
  outline: none;
  transition: all 0.2s ease;
  font-family: inherit;
}

.custom-input:focus,
.custom-textarea:focus {
  border-color: #7c3aed;
  box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.25);
}

.custom-input.has-error,
.custom-textarea.has-error {
  border-color: #ef4444;
}

.custom-field-error {
  font-size: 0.75rem;
  color: #f87171;
  font-weight: 500;
}

.custom-field-hint {
  font-size: 0.75rem;
  color: #64748b;
}

/* Ocultar botones de selector (spinner) en inputs type="number" */
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

input[type="number"] {
  -moz-appearance: textfield;
  appearance: textfield;
}

/* CHIPS DE ALÉRGENOS */
.allergen-chips-wrap {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 0.25rem;
}

.chip-button {
  background: #1a1528;
  border: 1px solid #2d2443;
  color: #94a3b8;
  padding: 0.4rem 0.85rem;
  border-radius: 9999px;
  font-size: 0.8125rem;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  transition: all 0.2s ease;
}

.chip-button:hover {
  border-color: #7c3aed;
  color: #ffffff;
}

.chip-button.active {
  background: #7c3aed;
  border-color: #7c3aed;
  color: #ffffff;
  font-weight: 600;
  box-shadow: 0px 2px 8px rgba(124, 58, 237, 0.35);
}

.chip-check {
  font-size: 0.75rem;
  font-weight: 800;
}

.custom-tag-row {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.75rem;
}

.custom-input-sm {
  flex: 1;
  background: #1a1528;
  border: 1px solid #2d2443;
  color: #ffffff;
  padding: 0.5rem 0.85rem;
  border-radius: 10px;
  font-size: 0.8125rem;
  outline: none;
}

.custom-input-sm:focus {
  border-color: #7c3aed;
}

.btn-sm-secondary {
  background: #2d2443;
  color: #e8eaf6;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 10px;
  font-size: 0.8125rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-sm-secondary:hover {
  background: #3d3450;
  color: #ffffff;
}

.active-tags-banner {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #1a1528;
  padding: 0.6rem 0.85rem;
  border-radius: 10px;
  border: 1px solid #2d2443;
  margin-top: 0.5rem;
}

.active-tags-label {
  font-size: 0.75rem;
  color: #94a3b8;
}

.active-tag-pill {
  background: rgba(124, 58, 237, 0.2);
  border: 1px solid #7c3aed;
  color: #c4b5fd;
  font-size: 0.6875rem;
  padding: 0.2rem 0.5rem;
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.tag-del-btn {
  background: transparent;
  border: none;
  color: #c4b5fd;
  cursor: pointer;
  font-size: 0.875rem;
  line-height: 1;
  padding: 0;
}

.tag-del-btn:hover {
  color: #ffffff;
}

/* PESO ESTIMADO PRESETS */
.weight-control-row {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.weight-presets-bar {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.weight-preset-btn {
  background: #1a1528;
  border: 1px solid #2d2443;
  color: #94a3b8;
  padding: 0.35rem 0.75rem;
  border-radius: 8px;
  font-size: 0.75rem;
  cursor: pointer;
  transition: all 0.2s;
}

.weight-preset-btn:hover {
  border-color: #7c3aed;
  color: #ffffff;
}

/* CARD DE AHORRO EN TIEMPO REAL */
.realtime-savings-box {
  background: rgba(16, 185, 129, 0.12);
  border: 1.5px solid #10b981;
  border-radius: 14px;
  padding: 1rem 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-top: 0.5rem;
}

.savings-pill-highlight {
  background: #10b981;
  color: #064e3b;
  font-weight: 800;
  font-size: 0.875rem;
  padding: 0.35rem 0.85rem;
  border-radius: 9999px;
  white-space: nowrap;
}

.savings-text-detail {
  color: #d1fae5;
  font-size: 0.875rem;
}

/* CONTROL DE STOCK (Cantidad) */
.stepper-quantity-wrap {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.quantity-step-btn {
  width: 44px;
  height: 44px;
  background: #2d2443;
  border: 1px solid #3d3450;
  color: #ffffff;
  border-radius: 12px;
  font-size: 1.25rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.quantity-step-btn:hover:not(:disabled) {
  background: #7c3aed;
  border-color: #7c3aed;
}

.quantity-step-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.quantity-number-input {
  width: 70px;
  height: 44px;
  background: #1a1528;
  border: 1px solid #2d2443;
  color: #ffffff;
  border-radius: 12px;
  text-align: center;
  font-weight: 800;
  font-size: 1.125rem;
  outline: none;
}

.quantity-unit-label {
  font-size: 0.875rem;
  color: #94a3b8;
}

/* ATAJOS RÁPIDOS DE HORARIO */
.quick-shortcuts-panel {
  background: #1a1528;
  border: 1px solid #2d2443;
  border-radius: 12px;
  padding: 0.875rem 1.15rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.quick-shortcuts-title {
  font-size: 0.75rem;
  color: #94a3b8;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.quick-shortcuts-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.quick-pill-button {
  background: #2d2443;
  border: 1px solid #3d3450;
  color: #cbd5e1;
  padding: 0.4rem 0.85rem;
  border-radius: 8px;
  font-size: 0.8125rem;
  cursor: pointer;
  transition: all 0.2s;
}

.quick-pill-button:hover {
  background: #7c3aed;
  border-color: #7c3aed;
  color: #ffffff;
}

/* PANEL DE RESUMEN DE ORDEN */
.order-summary-panel {
  background: #1a1528;
  border: 1px solid #2d2443;
  border-radius: 16px;
  padding: 1.25rem;
  margin-top: 0.5rem;
}

.order-summary-title {
  font-size: 1rem;
  font-weight: 700;
  color: #ffffff;
  margin: 0 0 1rem 0;
}

.order-summary-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.875rem;
}

.summary-cell {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.cell-label {
  font-size: 0.6875rem;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.cell-value {
  font-size: 0.875rem;
  color: #ffffff;
}

.loading-spinner-circle {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #ffffff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* COLUMNA DERECHA: Vista Previa para el Cliente (Figma Customer Live Preview) */
.preview-column {
  position: relative;
}

.preview-sticky-wrap {
  position: sticky;
  top: 5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

/* Preview Header Banner */
.preview-header-banner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 0.5rem;
}

.live-indicator-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.pulse-indicator {
  position: relative;
  width: 10px;
  height: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.pulse-ring {
  position: absolute;
  width: 100%;
  height: 100%;
  background: #34d399;
  border-radius: 9999px;
  opacity: 0.75;
  animation: pulsePing 1.5s cubic-bezier(0, 0, 0.2, 1) infinite;
}

.pulse-dot {
  position: relative;
  width: 10px;
  height: 10px;
  background: #10b981;
  border-radius: 9999px;
}

@keyframes pulsePing {
  75%, 100% {
    transform: scale(2);
    opacity: 0;
  }
}

.live-heading {
  font-size: 0.875rem; /* 14px */
  font-weight: 700;
  letter-spacing: 0.025em;
  text-transform: uppercase;
  color: #cbd5e1;
  margin: 0;
}

.realtime-badge-pill {
  background: rgba(139, 92, 246, 0.1);
  border: 1px solid rgba(139, 92, 246, 0.2);
  border-radius: 9999px;
  padding: 0.15rem 0.65rem;
}

.realtime-badge-pill span {
  font-size: 0.6875rem; /* 11px */
  font-weight: 500;
  color: #a78bfa;
}

/* Mockup Card (Figma Preview Phone / Card Mockup) */
.preview-mockup-card {
  background: #151120;
  border: 1px solid #2d2443;
  border-radius: 24px;
  box-shadow: 0px 25px 50px -12px rgba(0, 0, 0, 0.25);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

/* Visual Mockup Hero Image Area (176px en Figma) */
.mockup-hero-area {
  height: 176px;
  background: #1a1528;
  border-bottom: 1px solid rgba(45, 36, 67, 0.6);
  padding: 1rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  align-items: center;
  position: relative;
}

.mockup-hero-center {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  margin-top: 1.5rem;
}

.hero-surprise-badge {
  background: #7c3aed;
  border: 1px solid rgba(167, 139, 250, 0.3);
  border-radius: 16px;
  padding: 0.5rem 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0px 4px 20px -2px rgba(124, 58, 237, 0.25);
}

.hero-badge-emoji {
  font-size: 0.875rem;
}

.hero-badge-text {
  font-size: 0.875rem; /* 14px */
  font-weight: 700;
  color: #ffffff;
}

.hero-subtitle {
  font-size: 0.6875rem; /* 11px */
  color: #94a3b8;
  margin-top: 0.35rem;
}

.mockup-store-bar {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  align-self: flex-start;
}

.store-circle-avatar {
  width: 20px;
  height: 20px;
  background: #10b981;
  border-radius: 9999px;
  color: #000000;
  font-weight: 800;
  font-size: 0.625rem; /* 10px */
  display: flex;
  align-items: center;
  justify-content: center;
}

.store-name-label {
  font-size: 0.75rem; /* 12px */
  font-weight: 600;
  color: #cbd5e1;
}

/* Mockup Body Content */
.mockup-body-content {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.mockup-title-text {
  font-size: 1.125rem; /* 18px */
  font-weight: 700;
  color: #ffffff;
  line-height: 1.75rem; /* 28px */
}

.mockup-desc-text {
  font-size: 0.75rem; /* 12px */
  color: #94a3b8;
  line-height: 1.25rem; /* 19.5px */
}

.mockup-allergens-wrap {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  flex-wrap: wrap;
}

.mockup-allergens-label {
  font-size: 0.6875rem;
  color: #94a3b8;
}

.mockup-tag-pill {
  font-size: 0.625rem;
  background: #2d2443;
  color: #cbd5e1;
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
}

.mockup-weight-info {
  font-size: 0.75rem;
  color: #cbd5e1;
  font-weight: 500;
}

/* Time Pickup Window Chip (Figma Time Pickup Window Chip) */
.mockup-pickup-chip {
  background: rgba(34, 28, 51, 0.9);
  border: 1px solid rgba(45, 36, 67, 0.8);
  border-radius: 12px;
  padding: 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.625rem;
}

.pickup-text-inline {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.75rem; /* 12px */
}

.pickup-prefix {
  color: #94a3b8;
}

.pickup-value {
  color: #ffffff;
  font-weight: 600;
}

/* Price & Inventory Row (Figma Price & Inventory Row) */
.mockup-price-stock-row {
  border-top: 1px solid rgba(45, 36, 67, 0.5);
  padding-top: 0.75rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.mockup-prices-box {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}

.mockup-active-price {
  font-size: 1.5rem; /* 24px */
  font-weight: 800;
  color: #ffffff;
  line-height: 2rem;
  letter-spacing: -0.025em;
}

.mockup-original-strikethrough {
  font-size: 0.6875rem; /* 11px */
  color: #64748b;
  text-decoration: line-through;
}

.mockup-stock-pill {
  background: rgba(16, 185, 129, 0.15);
  border: 1px solid rgba(16, 185, 129, 0.3);
  border-radius: 8px;
  padding: 0.25rem 0.625rem;
  color: #34d399;
  font-size: 0.75rem; /* 12px */
  font-weight: 700;
}

/* Preview Footer Meta Table (Figma Preview Footer Meta Table) */
.mockup-footer-table {
  background: #1a1528;
  border-top: 1px solid #2d2443;
  padding: 0.875rem 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.meta-table-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.75rem; /* 12px */
}

.meta-table-label {
  color: #94a3b8;
}

.meta-table-value {
  color: #e2e8f0;
  font-weight: 600;
}
</style>
