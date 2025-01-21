<script setup>
import { defineProps, nextTick, ref, watch } from 'vue'
import { useToast } from "vue-toastification"

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
})

const emit = defineEmits([
  'update:isDialogVisible',
  'created',
])

const isFormValid = ref(false)
const refForm = ref()
const name = ref('')
const isActive = ref(false)
const toast = useToast()
const errors = ref({})

const sendData = async () => {
  try {
    const res = await $api(`/project`, {
      method: 'POST',
      body: {
        name: name.value,
        is_active: !!isActive.value,
      },
      onResponseError({ response }) {
        errors.value = response._data.errors
      },
    })

    if (res) {
      toast.success('Project created successfully')
    }

    emit("update:isDialogVisible", false)
    emit("created", true)

    await nextTick(() => {
      refForm.value?.reset()
      refForm.value?.resetValidation()
      resetErrors()
    })

  } catch (err) {
    console.error(err)
  }
}

const resetErrors = () => {
  errors.value = {}
}

const submitForm = () => {
  refForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid)
      sendData()
  })
}

const closeDialog = () => {
  emit('update:isDialogVisible', false)
}

watch(name, resetErrors)
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    max-width="600"
    persistent
  >
    <DialogCloseBtn @click="closeDialog" />

    <VCard title="Create Project">
      <VCardText>
        <VForm
          ref="refForm"
          v-model="isFormValid"
          @submit.prevent="submitForm"
        >
          <VRow>
            <VCol cols="12">
              <AppTextField
                v-model="name"
                :rules="[requiredValidator]"
                :error-messages="errors.name || []"
                label="Name"
                name="name"
                placeholder="Name..."
              />
            </VCol>
          </VRow>
          <VRow>
            <VCol cols="6">
              <VCheckbox
                v-model="isActive"
                label="Is Active"
              />
            </VCol>
          </VRow>
          <VCol
            cols="12"
            class="d-flex justify-end flex-wrap gap-3 mt-5"
          >
            <VBtn
              type="submit"
              class="me-3"
            >
              Submit
            </VBtn>
            <VBtn
              type="reset"
              variant="tonal"
              color="error"
              @click="closeDialog"
            >
              Cancel
            </VBtn>
          </VCol>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>

