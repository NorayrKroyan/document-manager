<template>
  <div class="cd-row">
    <div class="cd-label">{{ label }}</div>

    <div class="cd-field">
      <slot />
      <div v-if="err" class="mt-1 text-xs text-red-600">{{ err }}</div>
      <div v-if="hint" class="mt-1 text-xs text-gray-500">{{ hint }}</div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  label: { type: String, default: '' },
  err: { type: String, default: '' },
  hint: { type: String, default: '' },
})
</script>

<style scoped>
.cd-row{
  display: grid;
  grid-template-columns: 180px auto;
  gap: 0.75rem;
}

.cd-label{
  font-size: 0.95rem;
  font-weight: 700;
  color: #374151;
  white-space: nowrap;

  display: flex;
  align-items: center;     /* vertical center */
  justify-content: flex-end;

  height: 48px;            /* EXACT match to input height */
}



.cd-field{
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: flex-start; /* ✅ IMPORTANT: allows fixed-width children */
}
</style>