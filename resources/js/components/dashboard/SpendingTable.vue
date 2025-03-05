<script setup>
import { formatCurrency } from '@/utils/formatters'
import { ref } from 'vue'

const props = defineProps({
  containers: {
    type: Array,
    required: true
  },
  totals: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['navigate'])

const expandedRows = ref([])

const toggleRow = (containerId) => {
  const index = expandedRows.value.indexOf(containerId)
  if (index === -1) {
    expandedRows.value.push(containerId)
  } else {
    expandedRows.value.splice(index, 1)
  }
}

const isRowExpanded = (containerId) => {
  return expandedRows.value.includes(containerId)
}
</script>

<template>
  <div class="spending-section">
    <div class="section-header">
      <h2 class="section-title">
        <VIcon icon="tabler-cash-off" size="20" class="mr-2" />
        Spending Breakdown
      </h2>
    </div>
    
    <div v-if="containers.length" class="spending-table">
      <VCard class="table-card" elevation="0" border>
        <VTable class="data-table">
          <thead>
            <tr>
              <th class="board-column">Board</th>
              <th class="project-column">Project</th>
              <th class="amount-column">Paid</th>
              <th class="amount-column">Pending</th>
              <th class="amount-column">Total</th>
              <th class="action-column"></th>
            </tr>
          </thead>
          
          <tbody>
            <template v-for="container in containers" :key="container.id">
              <!-- Main Row -->
              <tr 
                :class="{ 
                  'main-row': true,
                  'expanded': isRowExpanded(container.id),
                  'hoverable': container.members?.length > 0
                }"
                @click="container.members?.length && toggleRow(container.id)"
              >
                <td class="board-column">
                  <div class="board-info">
                    <VIcon 
                      v-if="container.members?.length"
                      :size="14" 
                      :icon="isRowExpanded(container.id) ? 'tabler-chevron-down' : 'tabler-chevron-right'"
                      class="expand-icon"
                    />
                    {{ container.name }}
                  </div>
                </td>
                <td class="project-column">
                  <span class="project-name">{{ container.project_name }}</span>
                </td>
                <td class="amount-column">
                  <span class="amount paid">{{ formatCurrency(container.paid_amount) }}</span>
                </td>
                <td class="amount-column">
                  <span class="amount pending">{{ formatCurrency(container.unpaid_amount) }}</span>
                </td>
                <td class="amount-column">
                  <span class="amount total">{{ formatCurrency(container.total_amount) }}</span>
                </td>
                <td class="action-column">
                  <VBtn
                    icon
                    variant="text"
                    size="small"
                    class="action-btn"
                    @click.stop="$router.push({ 
                      name: 'container-view',
                      params: { 
                        id: container.project_id,
                        containerId: container.id
                      }
                    })"
                  >
                    <VIcon size="16">tabler-external-link</VIcon>
                  </VBtn>
                </td>
              </tr>
              
              <!-- Members Detail Row -->
              <tr 
                v-if="isRowExpanded(container.id) && container.members?.length"
                class="detail-row"
              >
                <td colspan="6" class="detail-cell">
                  <div class="members-table-wrapper">
                    <VTable density="compact" class="members-table">
                      <thead>
                        <tr>
                          <th>Member</th>
                          <th>Rate</th>
                          <th>Paid</th>
                          <th>Pending</th>
                          <th>Total</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr 
                          v-for="member in container.members" 
                          :key="member.user_id"
                          class="member-row"
                        >
                          <td>
                            <div class="member-info">
                              <VAvatar size="24" class="member-avatar">
                                {{ member.initials }}
                              </VAvatar>
                              <span class="member-name">{{ member.name }}</span>
                            </div>
                          </td>
                          <td>{{ formatCurrency(member.hourly_rate) }}/hr</td>
                          <td>{{ formatCurrency(member.paid_amount) }}</td>
                          <td>{{ formatCurrency(member.unpaid_amount) }}</td>
                          <td>{{ formatCurrency(member.paid_amount + member.unpaid_amount) }}</td>
                        </tr>
                      </tbody>
                    </VTable>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
          
          <!-- Footer -->
          <tfoot>
            <tr class="total-row">
              <td colspan="2" class="total-label">Total</td>
              <td class="amount-column">
                <span class="amount">{{ formatCurrency(totals.total_spending) }}</span>
              </td>
              <td class="amount-column">
                <span class="amount pending">{{ formatCurrency(totals.pending_spending) }}</span>
              </td>
              <td class="amount-column">
                <span class="amount total">{{ formatCurrency(totals.grand_total) }}</span>
              </td>
              <td></td>
            </tr>
          </tfoot>
        </VTable>
      </VCard>
    </div>
    
    <div v-else class="empty-state">
      <div class="empty-state-content">
        <VIcon size="48" color="secondary" class="empty-icon">tabler-cash-off</VIcon>
        <h3 class="empty-title">No Spending Data</h3>
        <p class="empty-description">You don't have any spending data for the selected period.</p>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.spending-section {
  margin-top: 1.5rem;

  .section-header {
    margin-bottom: 1rem;

    .section-title {
      font-size: 1rem;
      font-weight: 600;
      color: #24292f;
      margin: 0;
      display: flex;
      align-items: center;
    }
  }

  .table-card {
    border: 1px solid #d0d7de !important;
    transition: all 0.2s ease;

    &:hover {
      border-color: #0969da !important;
    }
  }

  .data-table {
    width: 100%;

    th {
      font-size: 0.75rem;
      color: #57606a;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      padding: 0.75rem 1rem;
      background: #f6f8fa;
      border-bottom: 1px solid #d0d7de;
      white-space: nowrap;
    }

    .main-row {
      &.hoverable {
        cursor: pointer;
      }

      &:hover {
        background: #f6f8fa;

        .action-btn {
          opacity: 1;
        }
      }

      &.expanded {
        background: #f6f8fa;
        
        .expand-icon {
          transform: rotate(90deg);
        }
      }

      td {
        padding: 0.875rem 1rem;
        border-bottom: 1px solid #d0d7de;

        .board-info {
          display: flex;
          align-items: center;
          gap: 0.5rem;
          font-weight: 500;
          color: #24292f;

          .expand-icon {
            color: #57606a;
            transition: transform 0.2s ease;
          }
        }

        .project-name {
          color: #57606a;
        }

        .amount {
          font-weight: 500;

          &.paid { color: #1a7f37; }
          &.pending { color: #0969da; }
          &.total { color: #24292f; }
        }

        .action-btn {
          color: #57606a;
          opacity: 0;
          transition: all 0.2s ease;

          &:hover {
            color: #0969da;
            background: #f6f8fa;
          }
        }
      }
    }

    .detail-row {
      background: #f6f8fa;

      .detail-cell {
        padding: 0;
      }

      .members-table-wrapper {
        padding: 0.5rem 2rem 1rem;

        .members-table {
          background: white;
          border: 1px solid #d0d7de;
          border-radius: 6px;

          th {
            font-size: 0.75rem;
            background: #f6f8fa;
            text-transform: none;
            letter-spacing: 0;
          }

          td {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            border-bottom: 1px solid #d0d7de;

            .member-info {
              display: flex;
              align-items: center;
              gap: 0.75rem;

              .member-avatar {
                font-size: 0.75rem;
                font-weight: 500;
                background: #f6f8fa;
                color: #57606a;
                border: 1px solid #d0d7de;
              }

              .member-name {
                color: #24292f;
                font-weight: 500;
              }
            }
          }

          tr:last-child td {
            border-bottom: none;
          }
        }
      }
    }

    .total-row {
      td {
        padding: 1rem;
        border-top: 2px solid #d0d7de;
        font-weight: 600;

        &.total-label {
          color: #24292f;
        }
      }
    }
  }

  .empty-state {
    padding: 3rem 1rem;
    text-align: center;
    background: #f6f8fa;
    border: 1px solid #d0d7de;
    border-radius: 6px;

    .empty-state-content {
      max-width: 300px;
      margin: 0 auto;

      .empty-icon {
        margin-bottom: 1rem;
        opacity: 0.5;
      }

      .empty-title {
        font-size: 1rem;
        font-weight: 600;
        color: #24292f;
        margin: 0 0 0.5rem;
      }

      .empty-description {
        font-size: 0.875rem;
        color: #57606a;
        margin: 0;
      }
    }
  }
}

// Responsive adjustments
@media (max-width: 768px) {
  .spending-section {
    .data-table {
      .action-btn {
        opacity: 1 !important;
      }
    }
  }
}
</style> 