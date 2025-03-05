<script setup>
import { formatDuration, timeAgo, getAvatarColor, formatCurrency } from '@/utils/formatters'
import { computed } from 'vue'

const props = defineProps({
  activities: {
    type: Array,
    required: true
  },
  limit: {
    type: Number,
    default: null
  }
})

const displayedActivities = computed(() => {
  if (props.limit) {
    return props.activities.slice(0, props.limit)
  }
  return props.activities
})
</script>

<template>
  <div class="activity-section">
    <div class="section-header">
      <h2 class="section-title">
        <VIcon icon="tabler-history" size="20" class="mr-2" />
        Recent Activity
      </h2>
    </div>
    
    <div v-if="activities.length" class="activity-timeline">
      <VCard class="timeline-card" elevation="0" border>
        <VList class="timeline-list" lines="three">
          <VListItem
            v-for="activity in displayedActivities"
            :key="activity.id"
            class="timeline-item"
          >
            <template #prepend>
              <div class="timeline-avatar">
                <VAvatar
                  size="36"
                  :color="getAvatarColor(activity.user.id)"
                  class="user-avatar"
                >
                  {{ activity.user.avatar_or_initials }}
                </VAvatar>
                <div class="timeline-line"></div>
              </div>
            </template>
            
            <VListItemTitle class="timeline-title">
              <span class="user-name">{{ activity.user.name }}</span>
              worked on
              <span class="task-name">{{ activity.task.name }}</span>
            </VListItemTitle>
            
            <VListItemSubtitle class="timeline-subtitle">
              <div class="activity-path">
                <span class="path-item project">{{ activity.project.name }}</span>
                <VIcon size="14" icon="tabler-chevron-right" class="path-separator" />
                <span class="path-item container">{{ activity.container.name }}</span>
                <VIcon size="14" icon="tabler-chevron-right" class="path-separator" />
                <span class="path-item board">{{ activity.board.name }}</span>
              </div>
              
              <div class="activity-meta">
                <div class="meta-item duration">
                  <VIcon size="14" icon="tabler-clock" class="meta-icon" />
                  {{ formatDuration(activity.duration) }}
                </div>
                
                <div class="meta-separator"></div>
                
                <div class="meta-item time-ago" :title="activity.end">
                  {{ timeAgo(activity.end) }}
                </div>
                
                <template v-if="activity.is_paid">
                  <div class="meta-separator"></div>
                  <div class="meta-item payment">
                    <VIcon size="14" icon="tabler-check-circle" class="paid-icon" />
                    Paid {{ formatCurrency(activity.amount) }}
                  </div>
                </template>
              </div>
            </VListItemSubtitle>
          </VListItem>
        </VList>
      </VCard>
    </div>
    
    <div v-else class="empty-state">
      <div class="empty-state-content">
        <VIcon size="48" color="secondary" class="empty-icon">tabler-history</VIcon>
        <h3 class="empty-title">No Recent Activity</h3>
        <p class="empty-description">There hasn't been any activity in the last 30 days.</p>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.activity-section {
  margin-top: 1.5rem;
  .section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;

    .section-title {
      font-size: 1rem;
      font-weight: 600;
      color: #24292f;
      margin: 0;
      display: flex;
      align-items: center;
    }

    .view-all-btn {
      font-size: 0.875rem;
      color: #0969da;
      
      &:hover {
        background: #f6f8fa;
      }
    }
  }

  .timeline-card {
    border: 1px solid #d0d7de !important;
    transition: all 0.2s ease;

    &:hover {
      border-color: #0969da !important;
    }

    .timeline-list {
      background: transparent;

      .timeline-item {
        position: relative;
        padding: 1rem;
        
        &:hover {
          background: #f6f8fa;
        }

        .timeline-avatar {
          position: relative;
          margin-right: 1rem;

          .user-avatar {
            border: 2px solid #fff;
            font-size: 0.875rem;
            font-weight: 500;
          }

          .timeline-line {
            position: absolute;
            top: 100%;
            left: 50%;
            width: 2px;
            height: calc(100% + 1rem);
            background: #d0d7de;
            transform: translateX(-50%);
          }
        }

        &:last-child {
          .timeline-line {
            display: none;
          }
        }

        .timeline-title {
          font-size: 0.875rem !important;
          color: #24292f;
          margin-bottom: 0.25rem;

          .user-name {
            font-weight: 600;
            color: #24292f;
          }

          .task-name {
            color: #0969da;
            font-weight: 500;
          }
        }

        .timeline-subtitle {
          .activity-path {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            margin-bottom: 0.25rem;

            .path-item {
              font-size: 0.75rem;
              color: #57606a;
              font-weight: 500;

              &.project { color: #1a7f37; }
              &.container { color: #9a6700; }
              &.board { color: #cf222e; }
            }

            .path-separator {
              color: #8c959f;
              opacity: 0.5;
            }
          }

          .activity-meta {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.75rem;
            color: #57606a;

            .meta-item {
              display: flex;
              align-items: center;
              gap: 0.25rem;

              &.duration {
                color: #1a7f37;
                .meta-icon { color: #1a7f37; }
              }

              &.payment {
                color: #0969da;
                .paid-icon { color: #1a7f37; }
              }
            }

            .meta-separator {
              width: 4px;
              height: 4px;
              border-radius: 50%;
              background: #d0d7de;
            }
          }
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
@media (max-width: 600px) {
  .activity-section {
    .timeline-card {
      .timeline-item {
        padding: 0.75rem;

        .timeline-avatar {
          .user-avatar {
            width: 32px;
            height: 32px;
            font-size: 0.75rem;
          }
        }

        .activity-path {
          flex-wrap: wrap;
        }
      }
    }
  }
}
</style> 