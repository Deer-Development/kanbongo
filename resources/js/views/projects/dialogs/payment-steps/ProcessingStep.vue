<template>
  <div class="processing-step text-center py-4">
    <VProgressCircular
      indeterminate
      size="64"
      color="primary"
      class="mb-4"
    />
    <h3>Processing Payment</h3>
    <p>Please wait while we process your payment...</p>
  </div>
</template>

<style lang="scss" scoped>
.processing-step {
  max-width: 400px;
  margin: 0 auto;
  padding: 2rem;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);

  h3 {
    color: #24292f;
    font-weight: 600;
    margin-bottom: 0.5rem;
  }

  p {
    color: #57606a;
  }
}
</style> 