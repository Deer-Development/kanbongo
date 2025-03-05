<script setup>
import { formatCurrency, formatHours } from '@/utils/formatters'
import { ref } from 'vue'

const props = defineProps({
  containers: {
    type: Array,
    default: () => []
  },
  totals: {
    type: Object,
    default: () => ({
      total_income: 0,
      total_hours: 0,
      pending_income: 0,
      pending_hours: 0,
      grand_total: 0
    })
  },
  isLoading: {
    type: Boolean,
    default: false
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
  <div class="income-section">
    <div class="section-header">
      <h2 class="section-title">
        <VIcon icon="tabler-cash" size="20" class="mr-2" />
        Income Breakdown
      </h2>
    </div>
    
    <VCard class="income-table" :loading="isLoading" elevation="0" border>
      <div v-if="!isLoading && (!containers || !containers.length)" class="empty-state">
        <VIcon size="40" icon="tabler-cash-off" class="empty-icon" />
        <h3 class="empty-title">No Income Data</h3>
        <p class="empty-description">There is no income data for the selected period.</p>
      </div>
      
      <div v-else class="table-wrapper">
        <div class="income-table">
          <VTable class="data-table">
            <thead>
              <tr>
                <th class="board-column">Board</th>
                <th class="project-column">Project</th>
                <th class="rate-column">Rate</th>
                <th class="hours-column">Hours</th>
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
                    'hoverable': container.tasks?.length > 0
                  }"
                  @click="container.tasks?.length && toggleRow(container.id)"
                >
                  <td class="board-column">
                    <div class="board-info">
                      <VIcon 
                        v-if="container.tasks?.length"
                        :size="14" 
                        :icon="isRowExpanded(container.id) ? 'tabler-chevron-down' : 'tabler-chevron-right'"
                        class="expand-icon"
                      />
                      {{ container.name }}
                    </div>
                  </td>
                  <td class="project-column">
                    <div class="project-info">
                      <span class="project-name">{{ container.project_name }}</span>
                      <VChip
                        v-if="container.is_active"
                        size="x-small"
                        color="success"
                        variant="flat"
                        class="status-chip"
                      >
                        Active
                      </VChip>
                    </div>
                  </td>
                  <td class="rate-column">
                    <span class="rate">{{ formatCurrency(container.hourly_rate) }}/hr</span>
                  </td>
                  <td class="hours-column">
                    <div class="hours-info">
                      <span class="hours">{{ formatHours(container.total_hours) }}</span>
                      <span v-if="container.pending_hours" class="pending-hours">
                        (+{{ formatHours(container.pending_hours) }})
                      </span>
                    </div>
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
                    <div class="action-buttons">
                      <VBtn
                        v-if="container.can_invoice"
                        icon
                        variant="text"
                        size="small"
                        class="action-btn invoice-btn"
                        @click.stop
                      >
                        <VIcon size="16">tabler-file-invoice</VIcon>
                        
                        <VTooltip activator="parent" location="top">
                          Generate Invoice
                        </VTooltip>
                      </VBtn>
                      
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
                        
                        <VTooltip activator="parent" location="top">
                          View Details
                        </VTooltip>
                      </VBtn>
                    </div>
                  </td>
                </tr>
                
                <!-- Tasks Detail Row -->
                <tr 
                  v-if="isRowExpanded(container.id) && container.tasks?.length"
                  class="detail-row"
                >
                  <td colspan="8" class="detail-cell">
                    <div class="tasks-table-wrapper">
                      <VTable density="compact" class="tasks-table">
                        <thead>
                          <tr>
                            <th>Task</th>
                            <th>Status</th>
                            <th>Hours</th>
                            <th>Amount</th>
                            <th>Last Activity</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr 
                            v-for="task in container.tasks" 
                            :key="task.id"
                            class="task-row"
                          >
                            <td>
                              <div class="task-info">
                                <span class="task-name">{{ task.name }}</span>
                                <VChip
                                  v-if="task.is_paid"
                                  size="x-small"
                                  color="success"
                                  variant="flat"
                                  class="paid-chip"
                                >
                                  Paid
                                </VChip>
                              </div>
                            </td>
                            <td>
                              <VChip
                                size="x-small"
                                :color="task.status_color"
                                variant="flat"
                                class="status-chip"
                              >
                                {{ task.status }}
                              </VChip>
                            </td>
                            <td>{{ formatHours(task.hours) }}</td>
                            <td>{{ formatCurrency(task.amount) }}</td>
                            <td class="last-activity">{{ task.last_activity }}</td>
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
                <td colspan="4" class="total-label">Total</td>
                <td class="amount-column">
                  <span class="amount">{{ formatCurrency(totals.total_income) }}</span>
                </td>
                <td class="amount-column">
                  <span class="amount pending">{{ formatCurrency(totals.pending_income) }}</span>
                </td>
                <td class="amount-column">
                  <span class="amount total">{{ formatCurrency(totals.grand_total) }}</span>
                </td>
                <td></td>
              </tr>
            </tfoot>
          </VTable>
        </div>
      </div>
    </VCard>
  </div>
</template>

<style lang="scss" scoped>
.income-section {
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

  .income-table {
    border: 1px solid #d0d7de !important;
    transition: all 0.2s ease;

    &:hover {
      border-color: #0969da !important;
    }

    .empty-state {
      padding: 3rem 1rem;
      text-align: center;

      .empty-icon {
        color: #8c959f;
        margin-bottom: 1rem;
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

    .table-wrapper {
      .section-header {
        padding: 0.75rem 1rem;
        display: flex;
        justify-content: flex-end;
        border-bottom: 1px solid #d0d7de;

        .export-btn {
          color: #0969da;
          font-size: 0.875rem;
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

            .action-buttons {
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
            transition: background 0.2s ease;

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

            .project-info {
              display: flex;
              align-items: center;
              gap: 0.5rem;

              .project-name {
                color: #57606a;
              }

              .status-chip {
                font-size: 0.625rem;
                height: 18px;
              }
            }

            .hours-info {
              display: flex;
              align-items: center;
              gap: 0.25rem;

              .hours {
                color: #24292f;
                font-weight: 500;
              }

              .pending-hours {
                color: #0969da;
                font-size: 0.75rem;
              }
            }

            .amount {
              font-weight: 500;
              color: #24292f;

              &.paid { color: #1a7f37; }
              &.pending { color: #0969da; }
              &.total { color: #24292f; }
            }

            .action-buttons {
              display: flex;
              gap: 0.25rem;
              opacity: 0;
              transition: opacity 0.2s ease;

              .action-btn {
                color: #57606a;

                &:hover {
                  color: #0969da;
                  background: #f6f8fa;
                }

                &.invoice-btn:hover {
                  color: #1a7f37;
                }
              }
            }
          }
        }

        .detail-row {
          background: #f6f8fa;

          .detail-cell {
            padding: 0;
          }

          .tasks-table-wrapper {
            padding: 0.5rem 2rem 1rem;

            .tasks-table {
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

                .task-info {
                  display: flex;
                  align-items: center;
                  gap: 0.5rem;

                  .task-name {
                    color: #0969da;
                    font-weight: 500;
                  }
                }

                &.last-activity {
                  color: #57606a;
                  font-size: 0.75rem;
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
    }
  }
}

// Responsive adjustments
@media (max-width: 768px) {
  .income-table {
    .data-table {
      .action-buttons {
        opacity: 1 !important;
      }
    }
  }
}
</style> 