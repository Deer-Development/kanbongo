<template>
  <VDialog
    persistent
    max-width="80%"
    :model-value="props.isDialogVisible"
    class="github-dialog"
  >
    <DialogCloseBtn
      class="close-btn"
      @click="onReset"
    />
    <VCard class="p-4 github-card">
      <VCardTitle class="text-center text-dark fs-6 fw-bold">
        Board Payment Details
      </VCardTitle>

      <VCardText>
        <div class="mb-4">
          <label
            for="date-picker"
            class="form-label"
          >
            Select Date Range:
          </label>
          <AppDateTimePicker
            v-model="selectedDateRange"
            :config="{ mode: 'range' }"
            placeholder="Select date range"
            class="input-github"
          />
        </div>

        <div
          v-if="members.length"
          class="members-container"
        >
          <div
            v-for="member in members"
            :key="member.member_id"
            class="entry-card-github"
            :class="{ 'entry-card-deleted': member.total_hours_worked === 0 }"
          >
            <!-- Performance Badge -->
            <VChip
              class="performance-badge"
              :color="member.total_hours_worked > 40 ? 'success' : member.total_hours_worked > 5 ? 'warning' : 'error'"
              size="small"
              label
              outlined
            >
              {{
                member.total_hours_worked > 40
                  ? 'High Performer'
                  : member.total_hours_worked > 5
                    ? 'Moderate'
                    : 'Needs Improvement'
              }}
            </VChip>

            <div class="d-flex align-items-center gap-3 mb-3">
              <VAvatar
                size="50"
                class="avatar"
                :color="$vuetify.theme.current.dark ? '#373B50' : '#F5F7FA'"
              >
                <template v-if="member.user.avatar">
                  <img
                    :src="member.user.avatar"
                    alt="Avatar"
                  >
                </template>
                <template v-else>
                  <span>{{ member.user.avatar_or_initials }}</span>
                </template>
              </VAvatar>
              <div class="member-info">
                <h3 class="fs-6 text-dark fw-bold">
                  {{ member.member_name }}
                </h3>
                <p class="text-muted">
                  {{ member.user.email }}
                </p>
              </div>
            </div>

            <div class="payment-details">
              <div class="detail">
                <span class="label">Total Hours Worked:</span>
                <span
                  :class="{
                    'text-danger': member.total_hours_worked === 0,
                  }"
                >
                  {{ member.total_hours_worked.toFixed(2) }} hrs
                </span>
              </div>
              <div class="detail">
                <span class="label">Billable Rate:</span>
                <span class="value text-primary">
                  ${{ member.billable_rate }}/hr
                </span>
              </div>
              <div class="detail">
                <span class="label">Total Payment:</span>
                <span class="value text-primary">
                  ${{ member.total_payment.toFixed(2) }}
                </span>
              </div>
            </div>

            <div class="card-actions">
              <VBtn
                v-if="props.isSuperAdmin || props.isOwner"
                color="primary"
                variant="tonal"
                class="btn-github"
                @click="handlePayment(member)"
              >
                Payment
              </VBtn>
              <VBtn
                color="secondary"
                variant="tonal"
                class="btn-github"
                @click="handleDetails(member)"
              >
                Details
              </VBtn>
            </div>
          </div>
        </div>

        <div
          v-else
          class="no-data"
        >
          No payment details available.
        </div>

        <div class="global-stats mt-4">
          <h4 class="fs-6 fw-bold">
            Summary
          </h4>
          <div class="d-flex justify-content-between">
            <span class="text-muted">Total Hours Worked:</span>
            <span class="text-primary fw-bold">{{ totalHours }} hrs</span>
          </div>
          <div class="d-flex justify-content-between">
            <span class="text-muted">Total Payments:</span>
            <span class="text-primary fw-bold">${{ totalPayments.toFixed(2) }}</span>
          </div>
        </div>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<script setup>
import { defineProps, ref, watch, computed } from "vue"

const props = defineProps({
  boardId: {
    type: Number,
    required: true,
    default: 0,
  },
  isOwner: {
    type: Boolean,
    required: false,
    default: false,
  },
  isSuperAdmin: {
    type: Boolean,
    required: false,
    default: false,
  },
  isDialogVisible: {
    type: Boolean,
    required: true,
    default: false,
  },
})

const emit = defineEmits(["update:isDialogVisible", "update:boardDetails", "formSubmitted"])

const boardIdLocal = ref(null)
const members = ref([])

const selectedDateRange = ref('')

const fetchMemberDetails = async () => {
  const res = await $api(`/board/payment-details/${boardIdLocal.value}`, {
    method: "GET",
    params: {
      date_range: selectedDateRange.value,
      is_super_admin: props.isSuperAdmin,
      is_owner: props.isOwner,
    },
  })

  members.value = res.data
}

watch(
  () => props.boardId,
  value => {
    boardIdLocal.value = value

    if (boardIdLocal.value !== 0) {
      fetchMemberDetails()
    }
  },
)

watch(
  () => props.isDialogVisible,
  value => {
    boardIdLocal.value = props.boardId
    if (value) {
      fetchMemberDetails()
    }
  },
)

watch(selectedDateRange, () => {
  if (boardIdLocal.value !== 0) {
    fetchMemberDetails()
  }
})

const onReset = () => {
  emit("update:isDialogVisible", false)
  members.value = []
  boardIdLocal.value = null
  selectedDateRange.value = ''
}

const totalHours = computed(() =>
  members.value.reduce((sum, member) => sum + member.total_hours_worked, 0),
)

const totalPayments = computed(() =>
  members.value.reduce((sum, member) => sum + member.total_payment, 0),
)

const handlePayment = () => {

}

const handleDetails = () => {

}
</script>

<style lang="scss" scoped>
.github-dialog {
  .v-card {
    background-color: #f6f8fa;
    border: 1px solid #d0d7de;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  }
}

.github-card {
  padding: 1.5rem;
}

.members-container {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); // Two cards per row
}

.entry-card-github {
  position: relative;
  background-color: #ffffff;
  border: 1px solid #d0d7de;
  border-radius: 6px;
  padding: 1rem;
  transition: all 0.2s;

  &:hover {
    background-color: #f6f8fa;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
  }

  &.entry-card-deleted {
    background-color: #ffe6e6;
    border-color: #e63946;
    opacity: 0.8;

    &:hover {
      opacity: 1;
    }
  }

  .performance-badge {
    position: absolute;
    top: -0.5rem;
    right: -0.5rem;
    z-index: 1;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
  }

  .payment-details {
    margin-bottom: 1rem;

    .detail {
      display: flex;
      justify-content: space-between;
      margin-bottom: 0.5rem;

      .label {
        font-size: 0.875rem;
        color: #6c757d;
      }

      .value {
        font-weight: bold;
        font-size: 0.875rem;
      }
    }
  }

  .card-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;

    .btn-github {
      font-weight: 600;
      padding: 0.5rem 1rem;
      border-radius: 6px;
      transition: background-color 0.2s;
    }
  }
}

.input-github {
  .v-input {
    border-radius: 6px;
    padding: 0.5rem;
    transition: border-color 0.2s;

    &:focus {
      border-color: #0969da;
    }
  }
}

.members-container {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
}

.entry-card-github {
  position: relative;
  background-color: #ffffff;
  border: 1px solid #d0d7de;
  border-radius: 6px;
  padding: 1rem;
  margin-bottom: 1rem;
  transition: all 0.2s;

  &:hover {
    background-color: #f6f8fa;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
  }

  &.entry-card-deleted {
    background-color: #ffe6e6;
    border-color: #e63946;
    opacity: 0.8;

    &:hover {
      opacity: 1;
    }
  }

  .performance-badge {
    position: absolute;
    top: -0.5rem;
    right: -0.5rem;
    z-index: 1;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
  }
}

.global-stats {
  h4 {
    font-size: 1.2rem;
    font-weight: bold;
    margin-bottom: 1rem;
  }

  div {
    margin-bottom: 0.5rem;

    span:last-child {
      font-weight: bold;
      color: #0969da;
    }
  }
}

.no-data {
  text-align: center;
  font-size: 1rem;
  color: #6c757d;
  margin-top: 2rem;
}

.text-danger {
  color: #cf222e;
}

.text-primary {
  color: #0969da;
}
</style>
