<template>
  <div class="message-dialog" v-if="visible">
    <div class="message-container">
      <div class="message-content">
        <p>{{ message }}</p>
      </div>
      <div class="message-actions">
        <button class="accept-button" @click="handleAccept">Aceptar</button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "MessageDialog",
  props: {
    message: {
      type: String,
      required: true,
    },
    visible: {
      type: Boolean,
      default: false,
    },
  },
  methods: {
    handleAccept() {
      this.$emit("accept");
    },
  },
};
</script>

<style scoped>
.message-dialog {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(34, 32, 31, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.message-container {
  background-color: var(--color-bg);
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 8px var(--color-darkest);
  max-width: 400px;
  width: 90%;
}

.message-content {
  margin-bottom: 20px;
  text-align: center;
  color: var(--color-text);
}

.accept-button {
  background-color: var(--color-primary);
  color: var(--color-text);
  border: none;
  padding: 10px 20px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 16px;
  transition: background-color 0.3s;
}

.accept-button:hover {
  background-color: var(--color-secondary);
}

.accept-button:focus {
  outline: none;
  background-color: var(--color-focus);
  box-shadow: 0 0 0 2px var(--color-darkest);
}
</style>
