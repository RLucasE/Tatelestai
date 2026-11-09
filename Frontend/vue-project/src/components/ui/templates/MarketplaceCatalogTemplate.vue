<template>
  <div class="marketplace-catalog-template max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 pb-12 min-h-screen flex flex-col">
    <!-- Slot: Barra de Búsqueda y Categorías (Figma: OffersToolbarInstance pt=16 pb=32 px=8) -->
    <header class="catalog-toolbar-area">
      <slot name="toolbar" />
    </header>

    <!-- Slot: Barra de Ordenamiento y Conteo (Figma: SortToolbarInstance pt=16 pb=16 px=8) -->
    <section v-if="$slots['sort-toolbar']" class="catalog-sort-toolbar-area">
      <slot name="sort-toolbar" />
    </section>

    <!-- Slot: Contenido Principal (Figma: OffersGrid con gap 24px y Z-Pattern) -->
    <main class="catalog-content-area">
      <slot name="content" />
    </main>

    <!-- Slot: Mensaje Final de Catálogo (Figma: FinalMessage pt=48 pb=24) -->
    <footer v-if="$slots.footer" class="catalog-footer-area pt-12 pb-6 text-center">
      <slot name="footer" />
    </footer>

    <!-- Slot: Modales y Toasts -->
    <aside v-if="$slots.modals">
      <slot name="modals" />
    </aside>
  </div>
</template>

<style scoped>
.marketplace-catalog-template {
  box-sizing: border-box;
}

/* Grilla auto-adaptativa en Z-pattern con 3 columnas en desktop y gap de 24px */
:deep(.catalog-grid) {
  display: grid;
  grid-template-columns: repeat(1, minmax(0, 1fr));
  gap: 1.5rem; /* 24px exacto */
  justify-items: center;
}

@media (min-width: 640px) {
  :deep(.catalog-grid) {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    justify-items: stretch;
  }
}

@media (min-width: 1024px) {
  :deep(.catalog-grid) {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1.5rem;
    justify-items: stretch;
  }
}
</style>
