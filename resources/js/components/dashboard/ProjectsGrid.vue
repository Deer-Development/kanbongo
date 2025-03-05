<script setup>
import { formatHours, timeAgo, getAvatarColor } from '@/utils/formatters'
import { computed } from 'vue'

const props = defineProps({
  projects: {
    type: Array,
    default: () => []
  },
  isLoading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['navigate'])

const sortedProjects = computed(() => {
  if (!props.projects) return []
  
  return [...props.projects].sort((a, b) => {
    // Sortare: Active first, then by last activity
    const aActiveUsers = a.active_users?.length || 0
    const bActiveUsers = b.active_users?.length || 0
    
    if (aActiveUsers && !bActiveUsers) return -1
    if (!aActiveUsers && bActiveUsers) return 1
    
    const aLastActivity = a.last_activity ? new Date(a.last_activity) : new Date(0)
    const bLastActivity = b.last_activity ? new Date(b.last_activity) : new Date(0)
    
    return bLastActivity - aLastActivity
  })
})

const getMaxDisplayedBoards = (container) => {
  return window.innerWidth < 768 ? 2 : 3
}
</script>

<template>
  <div class="projects-section">
    <div class="section-header">
      <h2 class="section-title">
        <VIcon icon="tabler-layout-grid" size="20" class="mr-2" />
        Active Projects
      </h2>
      
      <div class="header-actions">
        <VBtn
          variant="text"
          size="small"
          class="view-all-btn"
          :to="{ name: 'projects' }"
          prepend-icon="tabler-layout-grid"
        >
          View All Projects
        </VBtn>
      </div>
    </div>
    
    <VRow v-if="!isLoading && (!projects || !projects.length)" class="empty-state">
      <VCol cols="12" class="text-center">
        <div class="empty-content">
          <VIcon size="48" icon="tabler-layout-grid-off" class="empty-icon" />
          <h3 class="empty-title">No Active Projects</h3>
          <p class="empty-description">There are no active projects at the moment.</p>
        </div>
      </VCol>
    </VRow>
    
    <div v-else class="projects-grid">
      <VCard
        v-for="project in sortedProjects"
        :key="project.id"
        class="project-card"
        :class="{ 'active': project.active_users?.length > 0 }"
        elevation="0"
        border
        @click="$router.push({ 
          name: 'container-view',
          params: { 
            id: project.id,
            containerId: project.containers?.[0]?.id
          }
        })"
      >
        <div class="card-header">
          <div class="header-content">
            <div class="status-indicator">
              <div 
                class="status-dot"
                :class="{ 'pulse': project.active_users?.length > 0 }"
              />
            </div>
            
            <h3 class="project-name">
              {{ project.name }}
              <VTooltip 
                v-if="project.description"
                location="top"
                :text="project.description"
              >
                <template #activator="{ props }">
                  <VIcon
                    v-bind="props"
                    size="14"
                    icon="tabler-info-circle"
                    class="info-icon"
                  />
                </template>
              </VTooltip>
            </h3>
          </div>
          
          <VChip
            size="x-small"
            :color="project.active_users?.length ? 'success' : 'secondary'"
            variant="flat"
            class="status-chip"
          >
            {{ project.active_users?.length ? 'Currently Active' : 'No Active Users' }}
          </VChip>
        </div>
        
        <VDivider />
        
        <div class="card-content">
          <div class="stats-grid">
            <div class="stat-item">
              <VIcon size="16" icon="tabler-clock" />
              <span>{{ formatHours(project.total_hours_this_month || 0) }} this month</span>
            </div>
            
            <div class="stat-item">
              <VIcon size="16" icon="tabler-layout-board" />
              <span>{{ project.containers?.length || 0 }} boards</span>
            </div>
          </div>
          
          <template v-if="project.active_users?.length">
            <div class="section-divider">
              <span class="divider-label">Active Users</span>
            </div>
            
            <div class="active-users-list">
              <div 
                v-for="user in project.active_users"
                :key="user.id"
                class="active-user-item"
              >
                <div class="user-info">
                  <VAvatar
                    :size="24"
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
                  <div class="user-details">
                    <div class="info-row">
                      <span class="user-name">{{ user.first_name }} {{ user.last_name }}</span>
                      <div class="activity-meta">
                        <span class="container-name">on {{ user.container.name }}</span>
                        <span class="activity-time">Started {{ timeAgo(user.started_at) }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </template>
          
          <template v-if="project.last_active_user">
            <div class="section-divider">
              <span class="divider-label">Last Activity</span>
            </div>
            
            <div class="last-activity">
              <div class="activity-user">
                <VAvatar
                  :size="24"
                  :color="getAvatarColor(project.last_active_user?.id)"
                >
                  <VImg
                    v-if="project.last_active_user.avatar"
                    :src="project.last_active_user.avatar"
                    :alt="`${project.last_active_user.first_name} ${project.last_active_user.last_name}`"
                  />
                  <span v-else>{{ project.last_active_user.avatar_or_initials }}</span>
                </VAvatar>
                <span class="user-name">{{ project.last_active_user.first_name }} {{ project.last_active_user.last_name }}</span>
                <span class="container-name">on {{ project.last_active_user.container.name }}</span>
              </div>
              
              <span class="activity-time">{{ timeAgo(project.last_activity) }}</span>
            </div>
          </template>
        </div>
        
        <VDivider />
        
        <div class="card-footer">
          <div class="boards-list">
            <VChip
              v-for="(container, index) in project.containers?.slice(0, getMaxDisplayedBoards())"
              :key="container.id"
              size="small"
              variant="flat"
              class="board-chip"
              @click.stop="$router.push({ 
                name: 'container-view',
                params: { 
                  id: project.id,
                  containerId: container.id
                }
              })"
            >
              {{ container.name }}
            </VChip>
            
            <VChip
              v-if="project.containers?.length > getMaxDisplayedBoards()"
              size="small"
              variant="flat"
              class="more-chip"
            >
              +{{ project.containers.length - getMaxDisplayedBoards() }} more
            </VChip>
          </div>
        </div>
      </VCard>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.projects-section {
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
      color: #0969da;
      font-size: 0.875rem;
      
      &:hover {
        background: #f6f8fa;
      }
    }
  }

  .projects-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1rem;
  }

  .project-card {
    border: 1px solid #d0d7de !important;
    transition: all 0.2s ease;
    cursor: pointer;
    background: #ffffff;
    display: flex;
    flex-direction: column;
    height: 100%;
    
    &:hover {
      transform: translateY(-2px);
      border-color: #0969da !important;
      box-shadow: 0 4px 12px rgba(27, 31, 35, 0.15) !important;
    }
    
    &.active {
      border-color: #2da44e !important;
      
      .status-dot {
        background: #2da44e;
        
        &.pulse {
          animation: pulse 2s infinite;
          
          @keyframes pulse {
            0% {
              box-shadow: 0 0 0 0 rgba(45, 164, 78, 0.4);
            }
            70% {
              box-shadow: 0 0 0 6px rgba(45, 164, 78, 0);
            }
            100% {
              box-shadow: 0 0 0 0 rgba(45, 164, 78, 0);
            }
          }
        }
      }
    }
    
    .card-header {
      padding: 1rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      
      .header-content {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-width: 0;
        
        .status-indicator {
          flex: none;
          
          .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #cf222e;
          }
        }
        
        .project-name {
          font-size: 0.875rem;
          font-weight: 600;
          color: #24292f;
          margin: 0;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
          display: flex;
          align-items: center;
          gap: 0.5rem;
          
          .info-icon {
            color: #6e7781;
            opacity: 0.8;
            cursor: help;
            
            &:hover {
              opacity: 1;
            }
          }
        }
      }
      
      .status-chip {
        height: 20px;
        font-size: 0.75rem;
        font-weight: 500;
      }
    }
    
    .card-content {
      padding: 1rem;
      flex: 1;
      display: flex;
      flex-direction: column;
      
      .stats-grid {
        display: flex;
        gap: 1.5rem;
        margin-bottom: auto;
        
        .stat-item {
          display: flex;
          align-items: center;
          gap: 0.5rem;
          font-size: 0.75rem;
          color: #57606a;
          
          .v-icon {
            color: #57606a;
            opacity: 0.8;
          }
          
          span {
            font-weight: 500;
          }
        }
      }
      
      .section-divider {
        margin: 1rem 0 0.75rem;
        
        .divider-label {
          font-size: 0.75rem;
          font-weight: 600;
          color: #57606a;
          text-transform: uppercase;
          letter-spacing: 0.5px;
        }
      }
      
      .active-users-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        margin-bottom: 1rem;
        
        .active-user-item {
          display: flex;
          align-items: center;
          padding: 0.25rem 0;
          
          &:hover {
            .user-details {
              .user-name {
                color: #0969da;
              }
            }
          }
          
          .user-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            
            .user-avatar {
              color: #57606a;
              background: #f6f8fa;
              border: 1px solid #d0d7de;
            }
            
            .user-details {
              display: flex;
              flex: 1;
              
              .info-row {
                display: flex;
                align-items: center;
                gap: 1rem;
                width: 100%;
                
                .user-name {
                  font-weight: 500;
                  color: #24292f;
                  font-size: 0.75rem;
                  transition: color 0.2s ease;
                  width: 50%;
                  white-space: nowrap;
                  overflow: hidden;
                  text-overflow: ellipsis;
                  padding-top: 2px;
                }
                
                .activity-meta {
                  display: flex;
                  flex-direction: column;
                  gap: 0.125rem;
                  color: #57606a;
                  font-size: 0.75rem;
                  flex: 1;
                  
                  .container-name,
                  .activity-time {
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                  }
                }
              }
            }
          }
        }
      }
      
      .last-activity {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 0.75rem;
        
        .activity-user {
          display: flex;
          align-items: center;
          gap: 0.5rem;
          
          .user-name {
            font-weight: 500;
            color: #24292f;
          }
          
          .container-name {
            color: #57606a;
            
            &::before {
              content: "•";
              margin: 0 0.25rem;
            }
          }
        }
        
        .activity-time {
          color: #57606a;
        }
      }
    }
    
    .card-footer {
      padding: 0.75rem 1rem;
      background: #f6f8fa;
      margin-top: auto;
      
      .boards-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        
        .board-chip {
          font-size: 0.75rem;
          font-weight: 500;
          background: #ffffff;
          border: 1px solid #d0d7de;
          transition: all 0.2s ease;
          
          &:hover {
            background: #0969da;
            color: #ffffff;
            border-color: #0969da;
          }
        }
        
        .more-chip {
          font-size: 0.75rem;
          color: #57606a;
          background: #f6f8fa;
          border: 1px solid #d0d7de;
        }
      }
    }
  }

  .empty-state {
    padding: 3rem 1rem;
    
    .empty-content {
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
  }
}

// Responsive adjustments
@media (max-width: 768px) {
  .projects-section {
    .projects-grid {
      grid-template-columns: 1fr;
    }
    
    .project-card {
      .card-content {
        .stats-grid {
          flex-direction: column;
          gap: 0.75rem;
        }
      }
    }
  }
}

// Print styles
@media print {
  .projects-section {
    .project-card {
      break-inside: avoid;
      border: 1px solid #d0d7de !important;
      box-shadow: none !important;
      
      .status-dot.pulse {
        animation: none !important;
      }
    }
    
    .view-all-btn {
      display: none !important;
    }
  }
}
</style> 