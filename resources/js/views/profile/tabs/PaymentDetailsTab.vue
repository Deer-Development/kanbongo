<script setup>
import { ref, onMounted, computed } from 'vue'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@core/stores/authStore'

const toast = useToast()
const authStore = useAuthStore()
const loading = ref(false)
const isOwner = computed(() => authStore.getCurrentUser?.isSuperAdmin)
const showApiConfig = computed(() => 
  isOwner.value && paymentDetails.value.profile_type === 'business'
)

const paymentDetails = ref({
  // Personal/Business Profile
  profile_type: 'personal', // or 'business'
  full_name: '',
  registration_number: '',
  date_of_birth: '',
  
  // Address
  address_line1: '',
  address_line2: '',
  city: '',
  state: '',
  postal_code: '',
  country: '',
  
  // Bank Details
  account_holder_name: '',
  account_number: '',
  sort_code: '',
  iban: '',
  bic: '',
  routing_number: '',
  bank_code: '',
  
  // Business Details
  business_name: '',
  business_category: '',
  business_subcategory: '',
  
  // Currency Settings
  default_currency: 'USD',
  supported_currencies: [],
  
  // API Configuration (for owners)
  wise_api_key: '',
  wise_profile_id: '',
  wise_environment: 'sandbox', // or 'production'
})

const availableCurrencies = [
  { title: 'US Dollar', value: 'USD' },
  { title: 'Euro', value: 'EUR' },
  { title: 'British Pound', value: 'GBP' },
  // Add more currencies as needed
]

const businessCategories = [
  'Technology',
  'Consulting',
  'E-commerce',
  // Add more categories
]

onMounted(async () => {
  try {
    const response = await $api('/user/payment-details')
    paymentDetails.value = {
      ...paymentDetails.value,
      ...response.data
    }
  } catch (error) {
    console.error('Failed to load payment details:', error)
    toast.error('Failed to load payment details')
  }
})

const savePaymentDetails = async () => {
  loading.value = true
  try {
    await $api('/user/payment-details', {
      method: 'POST',
      body: paymentDetails.value
    })
    toast.success('Payment details updated successfully')
  } catch (error) {
    console.error('Failed to update payment details:', error)
    toast.error('Failed to update payment details')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <VCard>
    <VCardText>
      <h3 class="text-h6 mb-4">
        Payment Details
      </h3>

      <VForm @submit.prevent="savePaymentDetails">
        <!-- Profile Type Selection -->
        <VRow>
          <VCol cols="12">
            <div class="mb-4">
              <VRadioGroup
                v-model="paymentDetails.profile_type"
                inline
                class="mb-4"
              >
                <VRadio
                  value="personal"
                  label="Personal Account"
                />
                <VRadio
                  value="business"
                  label="Business Account"
                />
              </VRadioGroup>
            </div>
          </VCol>
        </VRow>

        <!-- Personal/Business Information -->
        <VRow>
          <VCol
            cols="12"
            md="6"
          >
            <VTextField
              v-model="paymentDetails.full_name"
              label="Full Name"
              placeholder="John Doe"
              variant="outlined"
              density="comfortable"
            />
          </VCol>

          <VCol
            cols="12"
            md="6"
          >
            <VTextField
              v-model="paymentDetails.date_of_birth"
              label="Date of Birth"
              type="date"
              variant="outlined"
              density="comfortable"
            />
          </VCol>
        </VRow>

        <!-- Business Details (if business type) -->
        <VRow v-if="paymentDetails.profile_type === 'business'">
          <VCol
            cols="12"
            md="6"
          >
            <VTextField
              v-model="paymentDetails.business_name"
              label="Business Name"
              variant="outlined"
              density="comfortable"
            />
          </VCol>

          <VCol
            cols="12"
            md="6"
          >
            <VTextField
              v-model="paymentDetails.registration_number"
              label="Registration Number"
              variant="outlined"
              density="comfortable"
            />
          </VCol>

          <VCol
            cols="12"
            md="6"
          >
            <VSelect
              v-model="paymentDetails.business_category"
              :items="businessCategories"
              label="Business Category"
              variant="outlined"
              density="comfortable"
            />
          </VCol>
        </VRow>

        <!-- Address Information -->
        <VDivider class="my-4" />
        <h4 class="text-h6 mb-4">
          Address Details
        </h4>

        <VRow>
          <VCol cols="12">
            <VTextField
              v-model="paymentDetails.address_line1"
              label="Address Line 1"
              variant="outlined"
              density="comfortable"
            />
          </VCol>

          <VCol cols="12">
            <VTextField
              v-model="paymentDetails.address_line2"
              label="Address Line 2"
              variant="outlined"
              density="comfortable"
            />
          </VCol>

          <VCol
            cols="12"
            md="6"
          >
            <VTextField
              v-model="paymentDetails.city"
              label="City"
              variant="outlined"
              density="comfortable"
            />
          </VCol>

          <VCol
            cols="12"
            md="6"
          >
            <VTextField
              v-model="paymentDetails.state"
              label="State/Province"
              variant="outlined"
              density="comfortable"
            />
          </VCol>

          <VCol
            cols="12"
            md="6"
          >
            <VTextField
              v-model="paymentDetails.postal_code"
              label="Postal Code"
              variant="outlined"
              density="comfortable"
            />
          </VCol>

          <VCol
            cols="12"
            md="6"
          >
            <VTextField
              v-model="paymentDetails.country"
              label="Country"
              variant="outlined"
              density="comfortable"
            />
          </VCol>
        </VRow>

        <!-- Bank Account Details -->
        <VDivider class="my-4" />
        <h4 class="text-h6 mb-4">
          Bank Account Details
        </h4>

        <VRow>
          <VCol
            cols="12"
            md="6"
          >
            <VTextField
              v-model="paymentDetails.account_holder_name"
              label="Account Holder Name"
              variant="outlined"
              density="comfortable"
            />
          </VCol>

          <VCol
            cols="12"
            md="6"
          >
            <VTextField
              v-model="paymentDetails.account_number"
              label="Account Number"
              variant="outlined"
              density="comfortable"
            />
          </VCol>

          <VCol
            cols="12"
            md="6"
          >
            <VTextField
              v-model="paymentDetails.iban"
              label="IBAN"
              variant="outlined"
              density="comfortable"
            />
          </VCol>

          <VCol
            cols="12"
            md="6"
          >
            <VTextField
              v-model="paymentDetails.bic"
              label="BIC/SWIFT"
              variant="outlined"
              density="comfortable"
            />
          </VCol>
        </VRow>

        <!-- Currency Settings -->
        <VDivider class="my-4" />
        <h4 class="text-h6 mb-4">
          Currency Settings
        </h4>

        <VRow>
          <VCol
            cols="12"
            md="6"
          >
            <VSelect
              v-model="paymentDetails.default_currency"
              :items="availableCurrencies"
              item-title="title"
              item-value="value"
              label="Default Currency"
              variant="outlined"
              density="comfortable"
            />
          </VCol>

          <VCol
            cols="12"
            md="6"
          >
            <VSelect
              v-model="paymentDetails.supported_currencies"
              :items="availableCurrencies"
              item-title="title"
              item-value="value"
              label="Supported Currencies"
              variant="outlined"
              density="comfortable"
              multiple
              chips
            />
          </VCol>
        </VRow>

        <!-- API Configuration (Only for Business Owners) -->
        <template v-if="showApiConfig">
          <VDivider class="my-4" />
          <h4 class="text-h6 mb-4">
            Wise API Configuration
            <span class="text-caption text-medium-emphasis d-block mt-1">
              Available only for business accounts
            </span>
          </h4>

          <VRow>
            <VCol cols="12">
              <VTextField
                v-model="paymentDetails.wise_api_key"
                label="Wise API Key"
                variant="outlined"
                density="comfortable"
                type="password"
                persistent-hint
                hint="You need to apply for API access in your Wise Business account"
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <VTextField
                v-model="paymentDetails.wise_profile_id"
                label="Wise Profile ID"
                variant="outlined"
                density="comfortable"
              />
            </VCol>

            <VCol
              cols="12"
              md="6"
            >
              <VSelect
                v-model="paymentDetails.wise_environment"
                :items="[
                  { title: 'Sandbox', value: 'sandbox' },
                  { title: 'Production', value: 'production' }
                ]"
                item-title="title"
                item-value="value"
                label="Environment"
                variant="outlined"
                density="comfortable"
              />
            </VCol>
          </VRow>
        </template>

        <div class="d-flex justify-end mt-4">
          <VBtn
            color="primary"
            type="submit"
            :loading="loading"
          >
            Save Payment Details
          </VBtn>
        </div>
      </VForm>
    </VCardText>
  </VCard>
</template> 