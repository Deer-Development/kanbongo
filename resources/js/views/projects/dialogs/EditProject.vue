<script setup>
import { defineProps, nextTick, ref, watch } from 'vue'
import { useToast } from "vue-toastification"

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  projectDetails: {
    type: Object,
    required: false,
  },
})

const emit = defineEmits([
  'update:isDialogVisible',
  'updated',
  'form-submitted',
])

const isFormValid = ref(false)
const refForm = ref()
const name = ref('')
const isActive = ref(false)
const errors = ref({})
const toast = useToast()

const sendData = async () => {
  try {
    const res = await $api(`/project/${props.projectDetails.id}`, {
      method: 'PUT',
      body: {
        name: name.value,
        is_active: !!isActive.value,
      },
      onResponseError({ response }) {
        errors.value = response._data.errors
      },
    })

    if (res) {
      toast.success('Project updated successfully')
    }

    await nextTick(() => {
      emit("update:isDialogVisible", false)
      emit("form-submitted", true)

      refForm.value?.reset()
      refForm.value?.resetValidation()
    })

  } catch (err) {
    console.error(err)
  }
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

watch(() => props.isDialogVisible, async newVal => {
  if (newVal === true) {
    name.value = props.projectDetails.name
    isActive.value = !!props.projectDetails.is_active
  }
})
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    max-width="600"
    persistent
  >
    <DialogCloseBtn @click="closeDialog" />

    <VCard title="Edit Project">
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

