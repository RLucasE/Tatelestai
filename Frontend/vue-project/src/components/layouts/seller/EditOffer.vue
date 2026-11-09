<script setup>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import axiosInstance from "@/lib/axios";

const props = defineProps({
  id: {
    type: [String, Number],
    required: true,
  },
});

const router = useRouter();

const loading = ref(true);
const error = ref(null);
const offer = ref(null);
const deleting = ref(false);
const saving = ref(false);
const actionMessage = ref("");

const editForm = ref({
  quantity: 1,
  price: 0,
  minimum_value: 0,
});

const isExpired = computed(() => {
  if (!offer.value?.expiration_datetime) return false;
  return new Date(offer.value.expiration_datetime) < new Date();
});

const isInactive = computed(() => {
  return offer.value?.state === "inactive";
});

const discountPercentage = computed(() => {
  const price = Number(offer.value?.price || 0);
  const minVal = Number(offer.value?.minimum_value || 0);
  if (!price || !minVal || minVal <= price) return 0;
  return Math.round(((minVal - price) / minVal) * 100);
});

const formattedPickupWindow = computed(() => {
  if (!offer.value?.pickup_start_datetime && !offer.value?.expiration_datetime) return "-";
  try {
    const start = offer.value.pickup_start_datetime
      ? new Date(offer.value.pickup_start_datetime)
      : null;
    const end = offer.value.expiration_datetime
      ? new Date(offer.value.expiration_datetime)
      : null;

    if (start && end) {
      const isToday = start.toDateString() === new Date().toDateString();
      const dateLabel = isToday
        ? "Hoy"
        : start.toLocaleDateString("es-AR", {
            weekday: "short",
            day: "numeric",
            month: "short",
          });
      const startTime = start.toLocaleTimeString("es-AR", {
        hour: "2-digit",
        minute: "2-digit",
      });
      const endTime = end.toLocaleTimeString("es-AR", {
        hour: "2-digit",
        minute: "2-digit",
      });
      return `${dateLabel}, ${startTime} - ${endTime} hs`;
    }
    return "-";
  } catch {
    return "-";
  }
});

const fetchOffer = async () => {
  try {
    loading.value = true;
    const response = await axiosInstance.get(`/my-packs/${props.id}`);
    offer.value = response.data?.data ?? response.data;
    if (offer.value) {
      editForm.value.quantity = offer.value.quantity;
      editForm.value.price = offer.value.price;
      editForm.value.minimum_value = offer.value.minimum_value;
    }
    error.value = null;
  } catch (err) {
    console.error("Error al cargar la oferta:", err);
    error.value = "No se pudo cargar la oferta";
    offer.value = null;
  } finally {
    loading.value = false;
  }
};

const goBack = () => router.push({ name: "pack-templates" });

const onUpdatePack = async () => {
  if (!offer.value) return;
  try {
    saving.value = true;
    actionMessage.value = "";
    await axiosInstance.patch(`/packs/${offer.value.id}`, {
      quantity: Number(editForm.value.quantity),
      price: Number(editForm.value.price),
      minimum_value: Number(editForm.value.minimum_value),
    });
    actionMessage.value = "Pack actualizado correctamente";
    await fetchOffer();
  } catch (err) {
    console.error("Error al actualizar oferta:", err);
    alert(err.response?.data?.message || "No se pudo actualizar el pack");
  } finally {
    saving.value = false;
  }
};

const onDeleteOffer = async () => {
  if (!offer.value) return;
  const confirmed = window.confirm(
    "¿Seguro que deseás dar de baja este pack? La oferta dejará de estar disponible para los clientes."
  );
  if (!confirmed) return;
  try {
    deleting.value = true;
    await axiosInstance.delete(`/packs/${offer.value.id}`);
    await fetchOffer();
  } catch (err) {
    console.error("Error al deshabilitar pack:", err);
    alert("No se pudo deshabilitar el pack");
  } finally {
    deleting.value = false;
  }
};

onMounted(fetchOffer);
</script>

<template>
  <div class="seller-content-container w-full max-w-[1420px] mx-auto px-4 sm:px-6 lg:px-8 seller:px-0 min-[1420px]:px-0 pt-9 pb-14 space-y-6 text-content">
    <!-- Header -->
    <div class="flex items-center justify-between gap-4">
      <button
        type="button"
        class="px-4 py-2 rounded-[10px] bg-surface border border-border-subtle text-xs font-semibold text-content hover:bg-surface-sunken transition cursor-pointer"
        @click="goBack"
      >
        ← Volver a Packs y Plantillas
      </button>
      <div v-if="offer && !isInactive">
        <button
          type="button"
          class="px-4 py-2 rounded-[10px] bg-danger/10 border border-danger text-xs font-bold text-danger hover:bg-danger/20 transition cursor-pointer"
          :disabled="deleting"
          @click="onDeleteOffer"
        >
          {{ deleting ? "Dando de baja..." : "Deshabilitar Pack" }}
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="py-16 text-center space-y-3">
      <div class="w-10 h-10 border-3 border-brand border-t-transparent rounded-full animate-spin mx-auto"></div>
      <p class="text-xs text-content-secondary">Cargando detalles de la oferta...</p>
    </div>

    <!-- Error -->
    <div
      v-else-if="error"
      class="p-6 rounded-[18px] bg-surface border border-danger text-center space-y-3"
    >
      <p class="text-sm font-semibold text-danger">{{ error }}</p>
      <button
        type="button"
        class="px-4 py-2 rounded-[10px] bg-brand text-surface text-xs font-bold cursor-pointer"
        @click="fetchOffer"
      >
        Reintentar
      </button>
    </div>

    <!-- Contenido -->
    <div v-else-if="offer" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
      <!-- Tarjeta Principal de Información -->
      <section class="lg:col-span-7 p-6 sm:p-8 rounded-[22px] bg-surface border border-border-subtle space-y-5">
        <div class="flex items-start justify-between gap-4">
          <div class="space-y-1.5">
            <span class="px-3 py-1 rounded-full bg-surface-sunken text-[11px] font-bold text-content">
              🛍️ Bolsa Sorpresa
            </span>
            <h1 class="text-2xl font-extrabold text-content">
              {{ offer.title }}
            </h1>
          </div>
          <div>
            <span
              v-if="isInactive"
              class="px-3 py-1 rounded-full bg-content-secondary/15 text-content-secondary text-xs font-bold uppercase"
            >
              Inactiva
            </span>
            <span
              v-else-if="isExpired"
              class="px-3 py-1 rounded-full bg-danger/15 text-danger text-xs font-bold uppercase"
            >
              Expirada
            </span>
            <span
              v-else
              class="px-3 py-1 rounded-full bg-brand/15 text-brand text-xs font-bold uppercase"
            >
              Activa
            </span>
          </div>
        </div>

        <p class="text-sm text-content-secondary leading-relaxed">
          {{ offer.description }}
        </p>

        <!-- Alérgenos -->
        <div v-if="offer.allergens?.length" class="space-y-1.5">
          <span class="text-xs font-bold text-content-secondary block">Alérgenos e ingredientes:</span>
          <div class="flex flex-wrap gap-1.5">
            <span
              v-for="(alg, idx) in offer.allergens"
              :key="idx"
              class="px-2.5 py-1 rounded-full bg-surface-sunken border border-border-subtle text-xs font-semibold text-content"
            >
              {{ alg }}
            </span>
          </div>
        </div>

        <!-- Peso estimado -->
        <div v-if="offer.estimated_weight_kg" class="text-xs text-content-secondary">
          Peso estimado: <strong class="text-content">~{{ offer.estimated_weight_kg }} kg</strong>
        </div>

        <!-- Ventana de retiro -->
        <div class="p-4 rounded-[14px] bg-surface-sunken border border-border-subtle flex items-center gap-3">
          <span class="text-xl">🕒</span>
          <div>
            <div class="text-[11px] font-bold uppercase text-content-secondary">Ventana de Retiro</div>
            <div class="text-sm font-extrabold text-content">{{ formattedPickupWindow }}</div>
          </div>
        </div>

        <!-- Precios y Descuento -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div class="p-3.5 rounded-[14px] bg-surface-sunken border border-border-subtle">
            <span class="text-[11px] text-content-secondary block">Precio al cliente</span>
            <span class="text-lg font-extrabold text-content">
              ${{ Number(offer.price).toLocaleString("es-AR") }}
            </span>
          </div>
          <div class="p-3.5 rounded-[14px] bg-surface-sunken border border-border-subtle">
            <span class="text-[11px] text-content-secondary block">Valor mínimo original</span>
            <span class="text-lg font-extrabold text-content-secondary">
              ${{ Number(offer.minimum_value).toLocaleString("es-AR") }}
            </span>
          </div>
          <div class="p-3.5 rounded-[14px] bg-surface-elevated border border-warning/40">
            <span class="text-[11px] text-content-secondary block">Ahorro para el cliente</span>
            <span class="text-lg font-extrabold text-brand">
              {{ discountPercentage }}% OFF
            </span>
          </div>
        </div>
      </section>

      <!-- Panel de Ajustes Rápidos -->
      <aside class="lg:col-span-5 p-6 sm:p-8 rounded-[22px] bg-surface border border-border-subtle space-y-4">
        <div class="space-y-1">
          <h3 class="text-lg font-extrabold text-content">Modificar Cupos y Precios</h3>
          <p class="text-xs text-content-secondary">
            Ajustá la cantidad disponible o el precio de esta oferta mientras esté activa.
          </p>
        </div>

        <div
          v-if="actionMessage"
          class="p-3 rounded-[12px] bg-success/12 border border-success/30 text-xs font-bold text-success"
        >
          {{ actionMessage }}
        </div>

        <form class="space-y-4" @submit.prevent="onUpdatePack">
          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-content">Cupos Disponibles</label>
            <input
              v-model.number="editForm.quantity"
              type="number"
              min="0"
              :disabled="isInactive"
              class="w-full px-4 py-2.5 rounded-[10px] bg-surface-sunken border border-border-subtle text-sm font-bold text-content focus:outline-none focus:border-brand"
            />
          </div>

          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-content">Precio ($ ARS)</label>
            <input
              v-model.number="editForm.price"
              type="number"
              min="1"
              :disabled="isInactive"
              class="w-full px-4 py-2.5 rounded-[10px] bg-surface-sunken border border-border-subtle text-sm font-bold text-content focus:outline-none focus:border-brand"
            />
          </div>

          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-content">Valor Original ($ ARS)</label>
            <input
              v-model.number="editForm.minimum_value"
              type="number"
              min="1"
              :disabled="isInactive"
              class="w-full px-4 py-2.5 rounded-[10px] bg-surface-sunken border border-border-subtle text-sm font-bold text-content focus:outline-none focus:border-brand"
            />
          </div>

          <button
            type="submit"
            :disabled="saving || isInactive"
            class="w-full py-3 rounded-[10px] bg-brand hover:bg-brand-hover text-surface text-xs font-bold transition cursor-pointer disabled:opacity-50"
          >
            {{ saving ? "Guardando..." : "Actualizar Oferta" }}
          </button>
        </form>
      </aside>
    </div>
  </div>
</template>
