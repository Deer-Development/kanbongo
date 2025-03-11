<template>
  <div class="complete-step text-center py-4">
    <VIcon
      size="64"
      color="success"
      class="mb-4"
    >
      tabler-circle-check
    </VIcon>
    <h3 class="text-success">Payment Successful</h3>
    <p>The payment has been processed successfully.</p>
    
    <VBtn
      color="primary"
      class="mt-4"
      @click="$emit('back')"
    >
      Done
    </VBtn>
  </div>
</template>

<script setup>
defineEmits(['back'])
</script>

<style lang="scss" scoped>
.complete-step {
  max-width: 400px;
  margin: 0 auto;
  padding: 2rem;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);

  h3 {
    color: #2da44e;
    font-weight: 600;
    margin-bottom: 0.5rem;
  }

  p {
    color: #57606a;
  }
}
</style> 