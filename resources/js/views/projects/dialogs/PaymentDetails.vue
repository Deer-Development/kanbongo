<template>
  <VDialog
    :width="$vuetify.display.smAndDown ? 'auto' : 900"
    :model-value="props.isDialogVisible"
    @update:model-value="onReset"
  >
    <DialogCloseBtn @click="onReset" />
    <VCard class="pa-4 dialog-card">
      <VCardText>
        <h2 class="dialog-title text-center">
          Board Payment Details
        </h2>
        <div class="date-picker-container">
          <label
            for="date-picker"
            class="picker-label"
          >Select Date Range:</label>
          <AppDateTimePicker
            v-model="selectedDateRange"
            :config="{ mode: 'range' }"
            class="mt-3"
            placeholder="Select date"
          />
        </div>
        <div
          v-if="members.length"
          class="members-container"
        >
          <div
            v-for="member in members"
            :key="member.member_id"
            class="payment-member-card"
            :class="{'highlight-error': member.total_hours_worked === 0}"
          >
            <div class="payment-member-header">
              <VAvatar
                v-tooltip="member.user.full_name"
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
              <div class="member-info mx-2">
                <h3>{{ member.member_name }}</h3>
                <p class="email">
                  {{ member.user.email }}
                </p>
              </div>
            </div>
            <div class="payment-details">
              <div class="detail">
                <span>Total Hours Worked:</span>
                <span
                  :class="{
                    'text-error': member.total_hours_worked === 0,
                  }"
                >
                  <span
                    v-if="member.total_hours_worked === 0"
                    class="icon-error"
                  >
                    <VIcon
                      color="red"
                      icon="tabler-alert-circle"
                    />
                  </span>
                  {{ member.total_hours_worked.toFixed(2) }} hrs
                </span>
              </div>
              <!--              <div class="progress-container"> -->
              <!--                <span>Total Hours Progress</span> -->
              <!--                <VProgressLinear -->
              <!--                  :value="(member.total_hours_worked / 40) * 100" -->
              <!--                  color="blue" -->
              <!--                  height="10" -->
              <!--                /> -->
              <!--              </div> -->
              <div class="detail">
                <span>Billable Rate:</span>
                <span class="text-highlight">
                  ${{ member.billable_rate }}/hr
                </span>
              </div>
              <div class="detail">
                <span>Total Payment:</span>
                <span class="text-highlight">
                  ${{ member.total_payment.toFixed(2) }}
                </span>
              </div>
            </div>
            <div
              class="performance-badge"
              :class="{
                'badge-green': member.total_hours_worked > 40,
                'badge-yellow': member.total_hours_worked > 5 && member.total_hours_worked <= 40,
                'badge-red': member.total_hours_worked <= 5
              }"
            >
              <span>{{ member.total_hours_worked > 40 ? 'High Performer' : member.total_hours_worked > 5 ? 'Moderate' : 'Needs Improvement' }}</span>
            </div>
          </div>
        </div>
        <div
          v-else
          class="no-data"
        >
          No payment details available.
        </div>
        <div class="global-stats">
          <h4>Summary</h4>
          <div>
            <span>Total Hours Worked:</span>
            <span class="highlight">{{ totalHours }} hrs</span>
          </div>
          <div>
            <span>Total Payments:</span>
            <span class="highlight">${{ totalPayments.toFixed(2) }}</span>
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
  isDialogVisible: {
    type: Boolean,
    required: true,
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
</script>

<style lang="scss">
.dialog-card {
  background: linear-gradient(145deg, #f3f6fa, #ffffff);
  border-radius: 16px;
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

.dialog-title {
  font-size: 1.8rem;
  font-weight: bold;
  margin-bottom: 1.5rem;
  color: #333;
}

.date-picker-container {
  margin-bottom: 1.5rem;

  .picker-label {
    font-size: 1rem;
    font-weight: 500;
    color: #555;
  }
}

.members-container {
  display: grid;
  gap: 1.5rem;
  grid-template-columns: 1fr;

  @media (min-width: 768px) {
    grid-template-columns: 1fr 1fr;
  }
}

.payment-member-card {
  border: 2px solid #f0f0f0;
  border-radius: 12px;
  padding: 1.5rem;
  background-color: #ffffff;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s, box-shadow 0.3s;

  &:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
  }

  &.highlight-error {
    border-color: #ff6666;
    background-color: #fff5f5;
  }

  .payment-member-header {
    display: flex;
    align-items: center;
    margin-bottom: 1.5rem;

    .avatar {
      width: 50px;
      height: 50px;
      font-size: 1.2rem;
      background: #007bff;
      color: white;
      font-weight: bold;
    }

    .member-info {
      h3 {
        font-size: 1.2rem;
        margin: 0;
      }

      .email {
        font-size: 0.9rem;
        color: #555;
      }
    }
  }

  .payment-details {
    .detail {
      display: flex;
      justify-content: space-between;
      margin-bottom: 0.5rem;

      span:last-child {
        font-weight: bold;
      }

      .text-error {
        color: #ff4d4d;
      }

      .text-highlight {
        color: #007bff;
      }
    }
  }
}

.progress-container {
  margin: 0.5rem 0;
}

.performance-badge {
  display: inline-block;
  padding: 0.2rem 0.5rem;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: bold;

  &.badge-green {
    background-color: #d4edda;
    color: #155724;
  }

  &.badge-yellow {
    background-color: #fff3cd;
    color: #856404;
  }

  &.badge-red {
    background-color: #f8d7da;
    color: #721c24;
  }
}

.no-data {
  text-align: center;
  font-size: 1rem;
  color: #999;
}

.global-stats {
  margin-top: 2rem;
  text-align: center;

  h4 {
    font-size: 1.2rem;
    font-weight: bold;
    margin-bottom: 1rem;
  }

  div {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;

    .highlight {
      font-weight: bold;
      color: #007bff;
    }
  }
}
</style>
