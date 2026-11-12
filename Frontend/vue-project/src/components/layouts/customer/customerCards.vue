<script setup>
import { useRouter } from 'vue-router';
import { useCustomerOffers } from '@/composables/useCustomerOffers';
import MarketplaceCatalogTemplate from '@/components/ui/templates/MarketplaceCatalogTemplate.vue';
import OffersToolbar from '@/components/ui/organisms/OffersToolbar.vue';
import SortToolbar from '@/components/ui/organisms/SortToolbar.vue';
import OfferCard from '@/components/ui/organisms/OfferCard.vue';
import OfferCardSkeleton from '@/components/ui/molecules/OfferCardSkeleton.vue';
import EmptyStateCard from '@/components/ui/molecules/EmptyStateCard.vue';
import ToastAlert from '@/components/ui/molecules/ToastAlert.vue';
import BaseButton from '@/components/ui/atoms/BaseButton.vue';
import OfferModal from '@/components/common/OfferModal.vue';
import LocationPickerModal from '@/components/common/LocationPickerModal.vue';

const router = useRouter();

const {
  filteredOffers,
  loading,
  loadingMore,
  hasMorePages,
  error,
  searchQuery,
  activeCategory,
  sortBy,
  categories,
  sortOptions,
  selectedOffer,
  isModalOpen,
  showLocationPicker,
  notification,
  locationStore,
  getOffers,
  handleSearchResults,
  handleSearchError,
  handleSearchClear,
  handleQuickAdd,
  addOfferToCart,
  buyOffer,
  openOfferModal,
  closeOfferModal,
  closeNotification,
  resetFilters,
} = useCustomerOffers();

const handleLocationChanged = () => {
  getOffers(1, false);
};

const handleLocationConfirmed = () => {
  showLocationPicker.value = false;
  getOffers(1, false);
};

const handleOfferClick = (offer) => {
  router.push({
    name: 'customer-offer-detail',
    params: { id: offer.id },
  });
};
</script>

<template>
  <MarketplaceCatalogTemplate>
    <!-- 1. Toolbar de Exploración y Categorías (Figma: OffersToolbarInstance h=44) -->
    <template #toolbar>
      <OffersToolbar
        v-model:activeCategory="activeCategory"
        :categories="categories"
        @search-results="handleSearchResults"
        @search-error="handleSearchError"
        @search-clear="handleSearchClear"
        @location-changed="handleLocationChanged"
        @open-picker="showLocationPicker = true"
      />
    </template>

    <!-- 2. Toolbar de Ordenamiento y Contador (Figma: SortToolbarInstance h=36) -->
    <template #sort-toolbar>
      <SortToolbar
        v-model:sortBy="sortBy"
        :sort-options="sortOptions"
        :total-offers="filteredOffers.length"
        @reset-filters="resetFilters"
      />
    </template>

    <!-- 3. Contenido Principal: Grilla de Ofertas en Patrón Z (Figma: OffersGrid) -->
    <template #content>
      <!-- Estado de Carga (Skeletons) -->
      <div v-if="loading && !loadingMore" class="catalog-grid">
        <OfferCardSkeleton v-for="n in 6" :key="n" />
      </div>

      <!-- Estado de Error -->
      <EmptyStateCard
        v-else-if="error"
        variant="error"
        :title="error"
        description="Ocurrió un problema de conexión al cargar el catálogo de ofertas. Puedes reintentar ahora."
      >
        <template #actions>
          <BaseButton variant="primary" size="sm" @click="getOffers(1, false)">
            Reintentar carga
          </BaseButton>
        </template>
      </EmptyStateCard>

      <!-- Estado Vacío: Con filtro de ubicación activo -->
      <EmptyStateCard
        v-else-if="filteredOffers.length === 0 && locationStore.isActive"
        variant="location"
        :title="`No encontramos packs dentro de ${locationStore.radiusKm} km`"
        description="Puedes ampliar tu radio de búsqueda o cambiar tu ubicación en el mapa para explorar comercios cercanos."
      >
        <template #actions>
          <BaseButton
            v-if="locationStore.radiusKm < 3"
            variant="primary"
            size="sm"
            @click="locationStore.setRadius(3); handleLocationChanged()"
          >
            Ampliar radio a 3 km
          </BaseButton>
          <BaseButton
            variant="secondary"
            size="sm"
            @click="showLocationPicker = true"
          >
            Cambiar ubicación
          </BaseButton>
          <BaseButton
            variant="ghost"
            size="sm"
            @click="locationStore.clearLocation(); handleLocationChanged()"
          >
            Ver todo el catálogo
          </BaseButton>
        </template>
      </EmptyStateCard>

      <!-- Estado Vacío: Sin resultados de categoría/búsqueda -->
      <EmptyStateCard
        v-else-if="filteredOffers.length === 0"
        variant="empty"
        title="No hay packs disponibles en esta categoría"
        description="Prueba seleccionando otra categoría gastronómica o limpiando los filtros de búsqueda aplicados."
      >
        <template #actions>
          <BaseButton
            v-if="activeCategory !== 'all'"
            variant="primary"
            size="sm"
            @click="activeCategory = 'all'"
          >
            Ver todas las ofertas
          </BaseButton>
        </template>
      </EmptyStateCard>

      <!-- Grilla de Ofertas Responsiva y Elástica en Patrón Z (Figma 14:501) -->
      <div v-else class="catalog-grid">
        <OfferCard
          v-for="offer in filteredOffers"
          :key="offer.id"
          :offer="offer"
          :distance="offer._distanceFormatted"
          @click="handleOfferClick(offer)"
        />
      </div>
    </template>

    <!-- 4. Footer: Mensaje de Fin de Catálogo (Figma 14:589: FinalMessage) -->
    <template #footer>
      <!-- Indicador de Carga Más Ofertas -->
      <div v-if="loadingMore" class="flex items-center justify-center gap-2 text-xs text-brand-light">
        <div class="w-4 h-4 border-2 border-brand border-t-transparent rounded-full animate-spin" />
        <span>Cargando más ofertas gastronómicas...</span>
      </div>

      <!-- Mensaje de Fin de Catálogo exacto Figma ("Parece que se acabaron las ofertas") -->
      <div
        v-else-if="!hasMorePages && filteredOffers.length > 0"
        class="text-[15px] text-content-muted font-normal select-none"
      >
        Parece que se acabaron las ofertas
      </div>
    </template>

    <!-- 5. Modales y Notificaciones -->
    <template #modals>
      <LocationPickerModal
        :isVisible="showLocationPicker"
        @close="showLocationPicker = false"
        @confirmed="handleLocationConfirmed"
        @cleared="handleLocationConfirmed"
      />

      <OfferModal
        :isVisible="isModalOpen"
        :offer="selectedOffer"
        @close="closeOfferModal"
        @offerAction="addOfferToCart"
        @buyOffer="buyOffer"
      />

      <ToastAlert
        :show="notification.show"
        :message="notification.message"
        :type="notification.type"
        @close="closeNotification"
      />
    </template>
  </MarketplaceCatalogTemplate>
</template>
