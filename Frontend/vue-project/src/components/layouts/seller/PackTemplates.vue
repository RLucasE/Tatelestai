<script setup>
import { ref, computed, onMounted } from "vue";
import axiosInstance from "@/lib/axios";
import TemplateCard from "./templates/TemplateCard.vue";
import TemplateFormModal from "./templates/TemplateFormModal.vue";
import PublishPackModal from "./templates/PublishPackModal.vue";

const templates = ref([]);
const loading = ref(true);
const errorMessage = ref("");
const searchQuery = ref("");
const successToast = ref("");

// Modales
const showFormModal = ref(false);
const editingTemplate = ref(null);

const showPublishModal = ref(false);
const publishingTemplate = ref(null);

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
    loading.value = true;
    errorMessage.value = "";
    const res = await axiosInstance.get("/pack-templates");
    templates.value = res.data?.data || res.data || [];
  } catch (err) {
    console.error("Error al cargar plantillas:", err);
    errorMessage.value = "No se pudieron cargar las plantillas. Intenta nuevamente.";
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchTemplates();
});

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

const onPackPublished = () => {
  showToast("Oferta diaria publicada exitosamente");
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
  <div class="w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    <!-- Toast Flotante Minimalista -->
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
        class="fixed bottom-6 right-6 z-50 flex items-center gap-2.5 px-4 py-2.5 rounded-xl bg-[#10B981] text-white shadow-lg shadow-[#10B981]/20 font-semibold text-xs sm:text-sm"
      >
        <svg class="w-4 h-4 shrink-0 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <span>{{ successToast }}</span>
      </div>
    </transition>

    <!-- Cabecera de Página Minimalista -->
    <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-white/[0.08]">
      <div>
        <div class="flex items-center gap-2 mb-1.5 flex-wrap">
          <span class="text-[11px] font-bold uppercase tracking-wider text-[#A78BFA]">
            Modelos & Recetas
          </span>
          <span class="text-xs text-[#A5A8C2]">• Gestión Operativa</span>
        </div>

        <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
          Plantillas de Packs
        </h1>
        <p class="text-xs sm:text-sm text-[#A5A8C2] mt-1 max-w-xl">
          Configura recetas y modelos maestros para lanzar bolsas sorpresa en 1 clic cada día, sin volver a escribir descripciones ni alérgenos.
        </p>
      </div>

      <!-- Acción Principal -->
      <button
        type="button"
        class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl text-xs sm:text-sm font-bold bg-[#7C3AED] hover:bg-[#6D28D9] text-white shadow-md shadow-purple-900/30 transition-all hover:scale-[1.02] active:scale-95 cursor-pointer self-start sm:self-center shrink-0"
        @click="openCreateModal"
      >
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Nueva Plantilla</span>
      </button>
    </header>

    <!-- Barra de Filtro y Contador -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
      <!-- Buscador -->
      <div class="relative w-full sm:w-80">
        <svg
          class="w-4 h-4 text-[#787596] absolute left-3.5 top-2.5 pointer-events-none"
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
          placeholder="Buscar por nombre o alérgeno..."
          class="w-full pl-9 pr-8 py-2 rounded-xl bg-[#2D2438] border border-white/[0.08] text-xs sm:text-sm text-white placeholder-[#787596] focus:outline-none focus:border-[#7C3AED] transition"
        />
        <button
          v-if="searchQuery"
          type="button"
          class="absolute right-2.5 top-2.5 text-[#787596] hover:text-white transition cursor-pointer"
          @click="searchQuery = ''"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Contador de Plantillas -->
      <div class="text-xs text-[#A5A8C2] px-3 py-1.5 rounded-xl bg-[#2D2438] border border-white/[0.08] self-end sm:self-auto shrink-0">
        Total: <strong class="text-white">{{ filteredTemplates.length }}</strong>
        {{ filteredTemplates.length === 1 ? "plantilla" : "plantillas" }}
      </div>
    </div>

    <!-- Estado de Error -->
    <div
      v-if="errorMessage"
      class="p-4 rounded-2xl bg-[#EF4444]/10 border border-[#EF4444]/25 flex items-center justify-between gap-4 text-xs text-[#F87171]"
    >
      <div class="flex items-center gap-2">
        <svg class="w-4 h-4 shrink-0 text-[#EF4444]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <span class="font-semibold">{{ errorMessage }}</span>
      </div>
      <button
        type="button"
        class="px-3 py-1 rounded-lg bg-[#EF4444] hover:bg-[#DC2626] text-white font-bold transition cursor-pointer"
        @click="fetchTemplates"
      >
        Reintentar
      </button>
    </div>

    <!-- Estado de Carga (Skeletons minimalistas) -->
    <div
      v-if="loading"
      class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5"
    >
      <div
        v-for="n in 3"
        :key="n"
        class="rounded-2xl bg-[#2D2438] border border-white/[0.04] p-5 space-y-3.5 animate-pulse"
      >
        <div class="flex items-center justify-between">
          <div class="h-4 bg-[#3D3450] rounded-md w-20"></div>
          <div class="h-4 bg-[#3D3450]/60 rounded-md w-12"></div>
        </div>
        <div class="h-5 bg-[#3D3450] rounded w-3/4"></div>
        <div class="space-y-1.5">
          <div class="h-3 bg-[#3D3450]/60 rounded w-full"></div>
          <div class="h-3 bg-[#3D3450]/60 rounded w-2/3"></div>
        </div>
        <div class="pt-3 border-t border-white/[0.04]">
          <div class="h-8 bg-[#3D3450] rounded-xl w-full"></div>
        </div>
      </div>
    </div>

    <!-- Estado Vacío Minimalista -->
    <div
      v-else-if="filteredTemplates.length === 0"
      class="rounded-2xl bg-[#2D2438] border border-dashed border-white/[0.1] p-10 sm:p-12 text-center space-y-3.5 max-w-md mx-auto my-6"
    >
      <div
        class="w-12 h-12 mx-auto rounded-xl bg-[#7C3AED]/10 text-[#A78BFA] flex items-center justify-center"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.5"
            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
          />
        </svg>
      </div>

      <div class="space-y-1">
        <h2 class="text-base font-bold text-white tracking-tight">
          {{ searchQuery ? "No se encontraron plantillas" : "Sin plantillas registradas" }}
        </h2>
        <p class="text-xs text-[#A5A8C2] leading-relaxed max-w-xs mx-auto">
          {{
            searchQuery
              ? "No hay plantillas que coincidan con el término buscado."
              : "Crea tu primera plantilla de bolsa sorpresa y ahorra tiempo publicando lotes diarios."
          }}
        </p>
      </div>

      <div class="pt-1">
        <button
          v-if="!searchQuery"
          type="button"
          class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-[#7C3AED] hover:bg-[#6D28D9] shadow-sm shadow-purple-900/30 transition cursor-pointer"
          @click="openCreateModal"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span>Crear Primera Plantilla</span>
        </button>
        <button
          v-else
          type="button"
          class="px-3.5 py-1.5 rounded-xl text-xs font-semibold text-[#A78BFA] hover:text-white hover:bg-white/[0.06] transition cursor-pointer"
          @click="searchQuery = ''"
        >
          Limpiar búsqueda
        </button>
      </div>
    </div>

    <!-- Grid de Plantillas -->
    <div
      v-else
      class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5"
    >
      <TemplateCard
        v-for="tpl in filteredTemplates"
        :key="tpl.id"
        :template="tpl"
        @publish="openPublishModal"
        @edit="openEditModal"
        @delete="confirmDelete"
      />
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
      @published="onPackPublished"
    />

    <!-- Modal de Confirmación de Eliminación Minimalista -->
    <div
      v-if="showDeleteConfirm"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fade-in"
      @click.self="cancelDelete"
    >
      <div
        class="w-full max-w-sm rounded-2xl bg-[#2D2438] border border-white/[0.1] p-6 shadow-2xl space-y-4"
      >
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-[#EF4444]/10 text-[#EF4444] flex items-center justify-center shrink-0">
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
            <h3 class="text-sm font-bold text-white">¿Eliminar plantilla?</h3>
            <p class="text-xs text-[#A5A8C2] mt-0.5 truncate max-w-[200px]">
              "{{ templateToDelete?.title }}"
            </p>
          </div>
        </div>

        <p class="text-xs text-[#A5A8C2] leading-relaxed">
          Las ofertas ya lanzadas anteriormente se conservarán, pero no podrás volver a generar nuevos packs desde este modelo.
        </p>

        <div class="flex items-center justify-end gap-2 pt-1">
          <button
            type="button"
            class="px-3.5 py-1.5 rounded-xl text-xs font-semibold text-[#A5A8C2] hover:text-white hover:bg-white/[0.06] transition cursor-pointer"
            :disabled="isDeleting"
            @click="cancelDelete"
          >
            Cancelar
          </button>
          <button
            type="button"
            class="px-3.5 py-1.5 rounded-xl text-xs font-bold text-white bg-[#EF4444] hover:bg-[#DC2626] transition cursor-pointer disabled:opacity-50"
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
