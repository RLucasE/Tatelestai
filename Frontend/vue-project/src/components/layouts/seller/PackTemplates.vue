<script setup>
import { ref, computed, onMounted, watch } from "vue";
import axiosInstance from "@/lib/axios";
import TemplateCard from "./templates/TemplateCard.vue";
import StockCard from "./dashboard/StockCard.vue";
import TemplateFormModal from "./templates/TemplateFormModal.vue";
import PublishPackModal from "./templates/PublishPackModal.vue";
import EditActiveOfferModal from "./EditActiveOfferModal.vue";
import { useNotificationStore } from "@/stores/notifications";

const notificationStore = useNotificationStore();

const templates = ref([]);
const offers = ref([]);
const loadingTemplates = ref(true);
const loadingOffers = ref(true);
const errorMessage = ref("");
const searchQuery = ref("");
const offerFilter = ref("all"); // 'all' | 'active' | 'scheduled' | 'exhausted'
const successToast = ref("");

// Modales
const showFormModal = ref(false);
const editingTemplate = ref(null);

const showPublishModal = ref(false);
const publishingTemplate = ref(null);

const showEditOfferModal = ref(false);
const editingOffer = ref(null);

const showDeleteConfirm = ref(false);
const templateToDelete = ref(null);
const isDeleting = ref(false);

const showToast = (msg) => {
  successToast.value = msg;
  setTimeout(() => {
    successToast.value = "";
  }, 3500);
};

const fetchTemplates = async () => {
  try {
    loadingTemplates.value = true;
    errorMessage.value = "";
    const res = await axiosInstance.get("/pack-templates");
    templates.value = res.data?.data || res.data || [];
  } catch (err) {
    console.error("Error al cargar plantillas:", err);
    errorMessage.value = "No se pudieron cargar las plantillas. Intentá nuevamente.";
  } finally {
    loadingTemplates.value = false;
  }
};

const fetchOffers = async (silent = false) => {
  try {
    if (!silent) loadingOffers.value = true;
    const res = await axiosInstance.get("/my-packs");
    offers.value = res.data?.data || res.data || [];
  } catch (err) {
    console.error("Error al cargar bolsas publicadas:", err);
  } finally {
    if (!silent) loadingOffers.value = false;
  }
};

const refreshAll = () => {
  fetchTemplates();
  fetchOffers();
};

onMounted(() => {
  refreshAll();
});

watch(
  () => notificationStore.lastOrderEvent,
  (event) => {
    if (event) fetchOffers(true);
  }
);

const filteredTemplates = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  if (!q) return templates.value;

  return templates.value.filter((t) => {
    const titleMatch = t.title?.toLowerCase().includes(q);
    const descMatch = t.description?.toLowerCase().includes(q);
    const allergensMatch =
      Array.isArray(t.allergens) &&
      t.allergens.some((a) => a.toLowerCase().includes(q));
    return titleMatch || descMatch || allergensMatch;
  });
});

// Helper de estado de cada oferta publicada
const getOfferCategory = (offer) => {
  const qty = Number(offer.quantity ?? 0);
  if (offer.state === "inactive") return "exhausted";
  if (offer.expiration_datetime && new Date(offer.expiration_datetime) < new Date()) {
    return "exhausted";
  }
  if (qty <= 0) return "exhausted";
  if (offer.visible_from && new Date(offer.visible_from) > new Date()) {
    return "scheduled";
  }
  if (offer.pickup_start_datetime && new Date(offer.pickup_start_datetime) > new Date()) {
    // Si inicia más adelante pero ya está visible, es activa o programada según filtro
  }
  return "active";
};

const filteredOffers = computed(() => {
  if (offerFilter.value === "all") return offers.value;
  return offers.value.filter((o) => {
    const cat = getOfferCategory(o);
    if (offerFilter.value === "active") return cat === "active";
    if (offerFilter.value === "scheduled") {
      return (
        cat === "scheduled" ||
        (o.pickup_start_datetime && new Date(o.pickup_start_datetime) > new Date() && Number(o.quantity ?? 0) > 0)
      );
    }
    if (offerFilter.value === "exhausted") return cat === "exhausted";
    return true;
  });
});

// Acciones de Plantilla
const openCreateModal = () => {
  editingTemplate.value = null;
  showFormModal.value = true;
};

const openEditModal = (tpl) => {
  editingTemplate.value = tpl;
  showFormModal.value = true;
};

const onTemplateSaved = () => {
  showToast(
    editingTemplate.value?.id
      ? "Plantilla actualizada con éxito"
      : "Nueva plantilla creada con éxito"
  );
  fetchTemplates();
};

const openPublishModal = (tpl) => {
  publishingTemplate.value = tpl;
  showPublishModal.value = true;
};

const onPackPublished = (resPayload) => {
  const warningMsg = resPayload?.schedule_warning?.warning_message;
  if (warningMsg) {
    showToast(`Oferta publicada • ${warningMsg}`);
  } else {
    showToast("Oferta diaria publicada exitosamente");
  }
  fetchTemplates();
  fetchOffers();
};

// Acciones sobre Ofertas Publicadas (StockCard)
const openAdjustOffer = (offer) => {
  editingOffer.value = offer;
  showEditOfferModal.value = true;
};

const onOfferSaved = () => {
  showToast("Bolsa activa actualizada correctamente");
  fetchOffers(true);
};

// Confirmación de Borrado
const confirmDelete = (tpl) => {
  templateToDelete.value = tpl;
  showDeleteConfirm.value = true;
};

const cancelDelete = () => {
  templateToDelete.value = null;
  showDeleteConfirm.value = false;
};

const executeDelete = async () => {
  if (!templateToDelete.value?.id) return;

  isDeleting.value = true;
  try {
    await axiosInstance.delete(`/pack-templates/${templateToDelete.value.id}`);
    showToast("Plantilla eliminada correctamente");
    templates.value = templates.value.filter((t) => t.id !== templateToDelete.value.id);
    cancelDelete();
  } catch (err) {
    console.error("Error al eliminar plantilla:", err);
    errorMessage.value = "No se pudo eliminar la plantilla";
  } finally {
    isDeleting.value = false;
  }
};
</script>

<template>
  <div class="seller-content-container w-full max-w-[1420px] mx-auto px-4 sm:px-6 lg:px-8 seller:px-0 min-[1420px]:px-0 pt-9 pb-14 space-y-8 text-content">
    <!-- Toast Flotante -->
    <transition
      enter-active-class="transform ease-out duration-300 transition"
      enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
      enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="successToast"
        class="fixed bottom-6 right-6 z-50 flex items-center gap-2.5 px-4 py-2.5 rounded-xl bg-success text-surface shadow-lg shadow-success/20 font-semibold text-xs sm:text-sm"
      >
        <svg class="w-4 h-4 shrink-0 text-surface" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <span>{{ successToast }}</span>
      </div>
    </transition>

    <!-- Estado de Error -->
    <div
      v-if="errorMessage"
      class="p-4 rounded-2xl bg-danger/10 border border-danger/25 flex items-center justify-between gap-4 text-xs text-danger"
    >
      <div class="flex items-center gap-2">
        <svg class="w-4 h-4 shrink-0 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <span class="font-semibold">{{ errorMessage }}</span>
      </div>
      <button
        type="button"
        class="px-3 py-1.5 rounded-[10px] bg-danger hover:bg-danger/90 text-surface font-bold transition cursor-pointer"
        @click="refreshAll"
      >
        Reintentar
      </button>
    </div>

    <!-- Layout Principal de 2 Columnas (Figma PacksTwoColumnsGrid) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-9 items-start">
      <!-- COLUMNA IZQUIERDA: Plantillas Maestras -->
      <section class="space-y-6">
        <div class="space-y-1">
          <h1 class="text-[22px] font-extrabold text-content tracking-tight">
            Plantillas Maestras ({{ filteredTemplates.length }})
          </h1>
          <p class="text-[13.5px] text-content-secondary">
            Modelos reutilizables para publicar en 1 clic
          </p>
        </div>

        <!-- Barra de búsqueda y botón + Nueva Plantilla -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
          <div class="relative flex-1">
            <svg
              class="w-4 h-4 text-content-secondary absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
              />
            </svg>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Buscar plantilla por nombre o alérgeno..."
              class="w-full pl-10 pr-8 py-2.5 rounded-full bg-surface border border-border-subtle text-[13px] text-content placeholder-content-secondary focus:outline-none focus:border-brand transition"
            />
            <button
              v-if="searchQuery"
              type="button"
              class="absolute right-3.5 top-1/2 -translate-y-1/2 text-content-secondary hover:text-content transition cursor-pointer"
              @click="searchQuery = ''"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <button
            type="button"
            class="inline-flex items-center justify-center gap-1.5 px-[18px] py-2.5 rounded-[10px] text-[13px] font-bold bg-brand hover:bg-brand-hover text-surface transition-all cursor-pointer shrink-0"
            @click="openCreateModal"
          >
            <span>+ Nueva Plantilla</span>
          </button>
        </div>

        <!-- Skeletons de Plantillas -->
        <div v-if="loadingTemplates" class="space-y-4">
          <div
            v-for="n in 3"
            :key="n"
            class="rounded-[18px] bg-surface border border-border-subtle p-6 space-y-3.5 animate-pulse"
          >
            <div class="flex items-center justify-between">
              <div class="h-4 bg-surface-sunken rounded-md w-24"></div>
              <div class="h-4 bg-surface-sunken rounded-md w-14"></div>
            </div>
            <div class="h-5 bg-surface-sunken rounded w-3/4"></div>
            <div class="h-3 bg-surface-sunken rounded w-full"></div>
            <div class="h-9 bg-surface-sunken rounded-full w-40 pt-2"></div>
          </div>
        </div>

        <!-- Estado Vacío de Plantillas -->
        <div
          v-else-if="filteredTemplates.length === 0"
          class="rounded-[18px] bg-surface border border-dashed border-border-subtle p-10 text-center space-y-3"
        >
          <div class="w-12 h-12 mx-auto rounded-2xl bg-brand/10 text-brand flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
          </div>
          <div class="space-y-1">
            <h2 class="text-base font-bold text-content">
              {{ searchQuery ? "No se encontraron plantillas" : "Sin plantillas registradas" }}
            </h2>
            <p class="text-xs text-content-secondary max-w-xs mx-auto">
              {{
                searchQuery
                  ? "No hay plantillas que coincidan con el término buscado."
                  : "Creá tu primera plantilla maestra para publicar bolsas sorpresa en 1 clic."
              }}
            </p>
          </div>
          <div class="pt-1">
            <button
              v-if="!searchQuery"
              type="button"
              class="inline-flex items-center gap-1.5 px-4 py-2 rounded-[10px] text-xs font-bold text-surface bg-brand hover:bg-brand-hover transition cursor-pointer"
              @click="openCreateModal"
            >
              <span>+ Crear Primera Plantilla</span>
            </button>
            <button
              v-else
              type="button"
              class="px-3.5 py-1.5 rounded-[10px] text-xs font-semibold text-brand hover:bg-brand/10 transition cursor-pointer"
              @click="searchQuery = ''"
            >
              Limpiar búsqueda
            </button>
          </div>
        </div>

        <!-- Lista de Plantillas -->
        <div v-else class="space-y-5">
          <TemplateCard
            v-for="tpl in filteredTemplates"
            :key="tpl.id"
            :template="tpl"
            @publish="openPublishModal"
            @edit="openEditModal"
            @delete="confirmDelete"
          />
        </div>
      </section>

      <!-- COLUMNA DERECHA: Bolsas Publicadas -->
      <section class="space-y-6">
        <div class="space-y-2">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <h2 class="text-[22px] font-extrabold text-content tracking-tight">
              Bolsas Publicadas ({{ filteredOffers.length }})
            </h2>

            <!-- Filtros en píldora (Figma: Todas, Activas, Programadas, Agotadas) -->
            <div class="flex items-center gap-2 flex-wrap">
              <button
                type="button"
                class="px-3 py-1 rounded-full text-[12px] transition cursor-pointer"
                :class="
                  offerFilter === 'all'
                    ? 'bg-content text-surface font-bold'
                    : 'bg-surface border border-border-subtle text-content-secondary font-semibold hover:text-content'
                "
                @click="offerFilter = 'all'"
              >
                Todas
              </button>
              <button
                type="button"
                class="px-3 py-1 rounded-full text-[12px] transition cursor-pointer"
                :class="
                  offerFilter === 'active'
                    ? 'bg-content text-surface font-bold'
                    : 'bg-surface border border-border-subtle text-content-secondary font-semibold hover:text-content'
                "
                @click="offerFilter = 'active'"
              >
                Activas
              </button>
              <button
                type="button"
                class="px-3 py-1 rounded-full text-[12px] transition cursor-pointer"
                :class="
                  offerFilter === 'scheduled'
                    ? 'bg-content text-surface font-bold'
                    : 'bg-surface border border-border-subtle text-content-secondary font-semibold hover:text-content'
                "
                @click="offerFilter = 'scheduled'"
              >
                Programadas
              </button>
              <button
                type="button"
                class="px-3 py-1 rounded-full text-[12px] transition cursor-pointer"
                :class="
                  offerFilter === 'exhausted'
                    ? 'bg-content text-surface font-bold'
                    : 'bg-surface border border-border-subtle text-content-secondary font-semibold hover:text-content'
                "
                @click="offerFilter = 'exhausted'"
              >
                Agotadas
              </button>
            </div>
          </div>

          <p class="text-[13.5px] text-content-secondary">
            Lotes activos hoy y programados para las próximas horas
          </p>
        </div>

        <!-- Skeletons de Bolsas Publicadas -->
        <div v-if="loadingOffers" class="space-y-4">
          <div
            v-for="n in 3"
            :key="n"
            class="rounded-[16px] bg-surface border border-border-subtle p-6 space-y-4 animate-pulse"
          >
            <div class="flex justify-between">
              <div class="h-5 bg-surface-sunken rounded w-1/2"></div>
              <div class="h-5 bg-surface-sunken rounded w-16"></div>
            </div>
            <div class="h-1.5 bg-surface-sunken rounded-full w-full"></div>
            <div class="h-4 bg-surface-sunken rounded w-1/3"></div>
          </div>
        </div>

        <!-- Estado Vacío de Bolsas Publicadas -->
        <div
          v-else-if="filteredOffers.length === 0"
          class="rounded-[18px] bg-surface border border-dashed border-border-subtle p-10 text-center space-y-3"
        >
          <div class="w-12 h-12 mx-auto rounded-2xl bg-warning/15 text-brand flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>
          </div>
          <div class="space-y-1">
            <h3 class="text-base font-bold text-content">
              No hay bolsas en esta categoría
            </h3>
            <p class="text-xs text-content-secondary max-w-xs mx-auto">
              Seleccioná una plantilla maestra de la columna izquierda y hacé clic en "Publicar Hoy" para lanzar un lote.
            </p>
          </div>
        </div>

        <!-- Lista de StockCards -->
        <div v-else class="space-y-4">
          <StockCard
            v-for="offer in filteredOffers"
            :key="offer.id"
            :offer="offer"
            @edit="(_id, offerObj) => openAdjustOffer(offerObj || offer)"
          />
        </div>
      </section>
    </div>

    <!-- Modales -->
    <TemplateFormModal
      v-model="showFormModal"
      :template="editingTemplate"
      @saved="onTemplateSaved"
    />

    <PublishPackModal
      v-model="showPublishModal"
      :template="publishingTemplate"
      :active-offers="offers"
      @published="onPackPublished"
    />

    <EditActiveOfferModal
      v-model="showEditOfferModal"
      :offer-id="editingOffer?.offer_id ?? editingOffer?.id"
      :offer="editingOffer"
      @saved="onOfferSaved"
      @deactivated="onOfferSaved"
    />

    <!-- Modal de Confirmación de Eliminación -->
    <div
      v-if="showDeleteConfirm"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-content/55 backdrop-blur-xs"
      @click.self="cancelDelete"
    >
      <div
        class="w-full max-w-sm rounded-[22px] bg-surface border border-border-subtle p-6 shadow-2xl space-y-4 text-content"
      >
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-danger/12 text-danger flex items-center justify-center shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
              />
            </svg>
          </div>
          <div>
            <h3 class="text-sm font-extrabold text-content">¿Eliminar plantilla?</h3>
            <p class="text-xs text-content-secondary mt-0.5 truncate max-w-[200px]">
              "{{ templateToDelete?.title }}"
            </p>
          </div>
        </div>

        <p class="text-xs text-content-secondary leading-relaxed">
          Las ofertas ya lanzadas anteriormente se conservarán, pero no podrás volver a generar nuevos packs desde este modelo.
        </p>

        <div class="flex items-center justify-end gap-2 pt-1">
          <button
            type="button"
            class="px-4 py-2 rounded-[10px] text-xs font-semibold text-content-secondary hover:text-content hover:bg-canvas transition cursor-pointer"
            :disabled="isDeleting"
            @click="cancelDelete"
          >
            Cancelar
          </button>
          <button
            type="button"
            class="px-4 py-2 rounded-[10px] text-xs font-bold text-surface bg-danger hover:bg-danger/90 transition cursor-pointer disabled:opacity-50"
            :disabled="isDeleting"
            @click="executeDelete"
          >
            <span v-if="isDeleting">Eliminando...</span>
            <span v-else>Sí, Eliminar</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
