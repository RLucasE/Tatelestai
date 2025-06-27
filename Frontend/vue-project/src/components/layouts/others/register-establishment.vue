<script setup>
import { ref, onMounted } from "vue";
import axiosInstance from "@/lib/axios";

const form = ref({
  name: "",
  establishment_type_id: "",
});

const establishmentTypes = ref([]);
const loading = ref(false);
const error = ref("");

const fetchEstablishmentTypes = async () => {
  try {
    const res = await axiosInstance.get("/establishment-types");
    establishmentTypes.value = res.data;
  } catch (e) {
    error.value = "Error loading establishment types";
  }
};

const registerEstablishment = async () => {
  loading.value = true;
  error.value = "";
  try {
    await axiosInstance.post("/food-establishments", form.value);
    alert("Establecimiento registrado correctamente");
    form.value.name = "";
    form.value.establishment_type_id = "";
  } catch (e) {
    error.value = "Error al registrar el establecimiento";
  } finally {
    loading.value = false;
  }
};

onMounted(fetchEstablishmentTypes);
</script>

<template>
  <div>
    <h2>Registrar Establecimiento</h2>
    <form @submit.prevent="registerEstablishment">
      <div>
        <label>Nombre del establecimiento</label>
        <input v-model="form.name" type="text" required />
      </div>
      <div>
        <label>Tipo de establecimiento</label>
        <select v-model="form.establishment_type_id" required>
          <option value="" disabled>Seleccione uno</option>
          <option
            v-for="type in establishmentTypes"
            :key="type.id"
            :value="type.id"
          >
            {{ type.name }}
          </option>
        </select>
      </div>
      <button type="submit" :disabled="loading">Registrar</button>
      <div v-if="error">{{ error }}</div>
    </form>
  </div>
</template>
