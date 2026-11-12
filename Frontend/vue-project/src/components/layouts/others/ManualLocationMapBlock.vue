<script setup>
import LocationMapPicker from '@/components/common/LocationMapPicker.vue';

const nameModel = defineModel('name', { type: String, default: '' });
const addressModel = defineModel('address', { type: String, default: '' });
const phoneModel = defineModel('phone', { type: String, default: '' });
const latitudeModel = defineModel('latitude', { type: Number, default: null });
const longitudeModel = defineModel('longitude', { type: Number, default: null });

defineProps({
  showTextInputs: {
    type: Boolean,
    default: true,
  },
  showPhoneInput: {
    type: Boolean,
    default: true,
  },
  readonly: {
    type: Boolean,
    default: false,
  },
  mapHeight: {
    type: String,
    default: '16rem',
  },
});
</script>

<template>
  <div class="rounded-[16px] bg-canvas border border-border-subtle p-5 flex flex-col gap-4">
    <!-- ManualFieldsRow1 -->
    <div v-if="showTextInputs" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div class="flex flex-col gap-1.5">
        <label class="text-[12px] font-bold text-content">
          Nombre del local *
        </label>
        <input
          v-model="nameModel"
          type="text"
          :disabled="readonly"
          placeholder="Ej: Panadería & Confitería San José"
          class="w-full px-3.5 py-[11px] rounded-[10px] bg-surface border border-border-subtle text-[13px] text-content placeholder:text-content-muted focus:outline-none focus:border-brand"
        />
      </div>

      <div class="flex flex-col gap-1.5">
        <label class="text-[12px] font-bold text-content">
          Dirección exacta de retiro *
        </label>
        <input
          v-model="addressModel"
          type="text"
          :disabled="readonly"
          placeholder="Ej: Av. Santa Fe 2341, Palermo"
          class="w-full px-3.5 py-[11px] rounded-[10px] bg-surface border border-border-subtle text-[13px] text-content placeholder:text-content-muted focus:outline-none focus:border-brand"
        />
      </div>
    </div>

    <!-- Phone Row -->
    <div v-if="showPhoneInput" class="flex flex-col gap-1.5">
      <label class="text-[12px] font-bold text-content">
        Teléfono de contacto comercial
      </label>
      <input
        v-model="phoneModel"
        type="text"
        :disabled="readonly"
        placeholder="+54 11 4822-9104"
        class="w-full px-3.5 py-[11px] rounded-[10px] bg-surface border border-border-subtle text-[13px] text-content placeholder:text-content-muted focus:outline-none focus:border-brand"
      />
    </div>

    <!-- MapPickerHeader -->
    <div class="flex items-center justify-between gap-2 flex-wrap">
      <span class="text-[12px] font-bold text-content">
        Marca el punto exacto en el mapa *
      </span>
      <span
        v-if="typeof latitudeModel === 'number' && typeof longitudeModel === 'number'"
        class="text-[11px] font-bold text-success font-mono"
      >
        Coordenadas: {{ latitudeModel.toFixed(4) }}, {{ longitudeModel.toFixed(4) }}
      </span>
      <span v-else class="text-[11px] font-semibold text-warning">
        Sin punto seleccionado en el mapa
      </span>
    </div>

    <!-- InteractiveMapPreview -->
    <div class="rounded-[12px] overflow-hidden border border-border-subtle bg-surface-sunken">
      <LocationMapPicker
        v-model:latitude="latitudeModel"
        v-model:longitude="longitudeModel"
        :readonly="readonly"
        :height="mapHeight"
      />
    </div>
  </div>
</template>
