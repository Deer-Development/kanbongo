<script setup>
import { timeAgo, getAvatarColor } from '@/utils/formatters'

const props = defineProps({
  users: {
    type: Array,
    required: true
  }
})
</script>

<template>
  <div class="activity-section">
    <div class="section-header">
      <h2 class="section-title">
        <VIcon icon="tabler-users" size="20" class="mr-2" />
        Active Users
      </h2>
    </div>
    
    <div v-if="users.length" class="activity-timeline">
      <VCard class="timeline-card" elevation="0" border>
        <VList class="timeline-list" lines="two">
          <VListItem
            v-for="user in users"
            :key="user.id"
            class="timeline-item"
          >
            <template #prepend>
              <VAvatar
                size="36"
                :color="getAvatarColor(user.id)"
                class="user-avatar"
              >
                <VImg
                  v-if="user.avatar"
                  :src="user.avatar"
                  :alt="`${user.first_name} ${user.last_name}`"
                />
                <span v-else>{{ user.avatar_or_initials }}</span>
              </VAvatar>
            </template>
            
            <VListItemTitle class="timeline-title">
              <span class="user-name">{{ user.first_name }} {{ user.last_name }}</span>
              working on
              <span class="task-name">{{ user.task.name }}</span>
            </VListItemTitle>
            
            <VListItemSubtitle class="timeline-subtitle">
              <div class="activity-path">
                <span class="path-item project">{{ user.project.name }}</span>
                <VIcon size="14" icon="tabler-chevron-right" class="path-separator" />
                <span class="path-item container">{{ user.container.name }}</span>
                <VIcon size="14" icon="tabler-chevron-right" class="path-separator" />
                <span class="path-item board">{{ user.board.name }}</span>
              </div>
              
              <div class="activity-meta">
                <div class="meta-item time-ago">
                  Started {{ timeAgo(user.started_at) }}
                </div>
              </div>
            </VListItemSubtitle>
          </VListItem>
        </VList>
      </VCard>
    </div>
    
    <div v-else class="empty-state">
      <div class="empty-state-content">
        <VIcon size="48" color="secondary" class="empty-icon">tabler-users-off</VIcon>
        <h3 class="empty-title">No Active Users</h3>
        <p class="empty-description">There are no users currently working.</p>
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
        padding: 1rem;
        
        &:hover {
          background: #f6f8fa;
        }

        .user-avatar {
          border: 2px solid #fff;
          font-size: 0.875rem;
          font-weight: 500;
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
</style> 