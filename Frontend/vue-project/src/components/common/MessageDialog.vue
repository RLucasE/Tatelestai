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
  background-color: var(--color-surface);
  border: 1px solid var(--color-border-subtle);
  padding: 24px;
  border-radius: 16px;
  box-shadow: 0 16px 36px -6px rgba(70, 51, 79, 0.15);
  max-width: 400px;
  width: 90%;
}

.message-content {
  margin-bottom: 20px;
  text-align: center;
  color: var(--color-content);
  font-size: 15px;
  line-height: 1.5;
}

.accept-button {
  background-color: var(--color-brand);
  color: var(--color-surface);
  border: none;
  padding: 10px 24px;
  border-radius: 10px;
  cursor: pointer;
  font-size: 15px;
  font-weight: 600;
  transition: all 0.2s;
  width: 100%;
}

.accept-button:hover {
  background-color: var(--color-brand-hover);
}

.accept-button:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(242, 79, 19, 0.3);
}
</style>
