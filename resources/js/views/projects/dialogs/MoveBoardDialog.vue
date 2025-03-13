<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useToast } from 'vue-toastification'

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  boardDetails: {
    type: null,
    required: false,
  },
})

const emit = defineEmits(['update:isDialogVisible', 'confirm'])

const toast = useToast()
const isLoading = ref(false)
const projects = ref([])
const selectedProjectId = ref(null)
const showNewProjectId = ref(false)
const showNewProjectForm = ref(false)
const newProjectName = ref('')
const newProjectDescription = ref('')

const isConfirmDisabled = computed(() => {
  if (showNewProjectForm.value) {
    return !newProjectName.value.trim()
  }
  return !selectedProjectId.value
})

const boardName = computed(() => props.boardDetails?.name || 'this container')

const fetchProjects = async () => {
  isLoading.value = true
  try {
    const response = await $api('/project/owned', {
      method: 'GET',
    })
    
    if (response && response.data.items) {
      projects.value = response.data.items.filter(project => 
        project.id !== props.boardDetails?.project_id
      ).map(project => ({
        id: project.id,
        name: project.name,
        icon: 'tabler-folder'
      }))
    }
  } catch (error) {
    console.error('Error fetching projects:', error)
    toast.error('Failed to load projects')
  } finally {
    isLoading.value = false
  }
}

const resetDialog = () => {
  selectedProjectId.value = null
  showNewProjectForm.value = false
  newProjectName.value = ''
  newProjectDescription.value = ''
}

const confirmMove = async () => {
  isLoading.value = true

  try {
    let targetProjectId = selectedProjectId.value
    
    if (showNewProjectForm.value) {
      const newProjectResponse = await $api('/project', {
        method: 'POST',
        body: {
          name: newProjectName.value,
          is_active: true
        }
      })
      
      if (newProjectResponse && newProjectResponse.data) {
        targetProjectId = newProjectResponse.data.id
      } else {
        throw new Error('Failed to create new project')
      }
    }
    console.log('dsadasasdsa')
  console.log(targetProjectId)
    console.log(props.boardDetails.id)
    
    emit('confirm', {
      confirmed: true,
      boardId: props.boardDetails.id,
      targetProjectId: targetProjectId
    })
    
    emit('update:isDialogVisible', false)
    
    resetDialog()
    
    toast.success(`Board moved successfully${showNewProjectForm.value ? ' to new project' : ''}`)
  } catch (error) {
    console.error('Error moving Board:', error)
    toast.error('Failed to move board')
  } finally {
    isLoading.value = false
  }
}

const cancelMove = () => {
  emit('update:isDialogVisible', false)
  resetDialog()
}

const toggleNewProjectForm = () => {
  showNewProjectForm.value = !showNewProjectForm.value
  if (showNewProjectForm.value) {
    selectedProjectId.value = null
  }
}

watch(() => props.isDialogVisible, (newValue) => {
  if (newValue) {
    fetchProjects()
  } else {
    resetDialog()
  }
})

onMounted(() => {
  if (props.isDialogVisible) {
    fetchProjects()
  }
})

const hexToRgb = (hex) => {
  hex = hex.replace('#', '')
  
  if (hex.length === 3) {
    hex = hex.split('').map(char => char + char).join('')
  }
  
  const r = parseInt(hex.substring(0, 2), 16)
  const g = parseInt(hex.substring(2, 4), 16)
  const b = parseInt(hex.substring(4, 6), 16)
  
  return `${r}, ${g}, ${b}`
}

const selectProject = (projectId) => {
  selectedProjectId.value = projectId
}
</script>

<template>
  <VDialog
    :model-value="isDialogVisible"
    @update:model-value="$emit('update:isDialogVisible', $event)"
    max-width="550"
    persistent
    class="move-board-dialog"
  >
    <VCard class="move-board-card">
      <VCardItem class="dialog-header">
        <template #prepend>
          <div class="info-icon">
            <VIcon
              icon="tabler-arrows-transfer-up"
              color="primary"
              size="28"
            />
          </div>
        </template>
        <VCardTitle class="dialog-title">
          Move "{{ boardName }}" to another project
        </VCardTitle>
      </VCardItem>

      <VDivider />

      <VCardText class="pt-6">
        <p class="text-body-1 mb-4">
          Select a project to move <strong>"{{ boardName }}"</strong> to, or create a new project.
          This will move the Board and all its data to the selected project.
        </p>

        <VProgressLinear
          v-if="isLoading"
          indeterminate
          color="primary"
          class="mb-4"
        />

        <div v-if="!showNewProjectForm" class="project-selection-container">
          <p class="text-subtitle-2 mb-3">
            Select a destination project:
          </p>

          <div v-if="projects.length === 0 && !isLoading" class="no-projects">
            <VAlert
              type="info"
              variant="tonal"
              border="start"
              class="mb-4"
            >
              <template #prepend>
                <VIcon
                  icon="tabler-info-circle"
                  start
                />
              </template>
              <p class="text-body-1 mb-1">No Other Projects Available</p>
              <p class="text-body-2 mb-0">
                You don't have any other projects. Please create a new project to move this Board.
              </p>
            </VAlert>
          </div>

          <div v-else class="projects-list">
            <VRadioGroup
              v-model="selectedProjectId"
              class="project-radio-group"
            >
              <div
                v-for="project in projects"
                :key="project.id"
                class="project-item"
                :class="{ 'selected': selectedProjectId === project.id }"
                @click="selectProject(project.id)"
              >
                <VRadio
                  :value="project.id"
                  color="primary"
                  class="project-radio"
                  hide-details
                >
                  <template #label>
                    <div class="project-label">
                      <VIcon
                        icon="tabler-folder"
                        size="20"
                        color="primary"
                        class="mr-2"
                      />
                      <div class="project-info">
                        <span class="project-name">{{ project.name }}</span>
                      </div>
                    </div>
                  </template>
                </VRadio>
              </div>
            </VRadioGroup>
          </div>
          
          <VBtn
            variant="text"
            color="primary"
            class="mt-4"
            prepend-icon="tabler-plus"
            @click="toggleNewProjectForm"
          >
            Create a new project
          </VBtn>
        </div>
        
        <div v-else class="new-project-form">
          <h3 class="section-title">Create a new project</h3>
          
          <VTextField
            v-model="newProjectName"
            label="Project Name"
            placeholder="Enter project name"
            variant="outlined"
            class="mb-4"
            :rules="[(v) => !!v || 'Project name is required']"
            required
          />
          
          <VBtn
            variant="text"
            color="secondary"
            class="mt-2"
            prepend-icon="tabler-arrow-left"
            @click="toggleNewProjectForm"
          >
            Back to project selection
          </VBtn>
        </div>
      </VCardText>
      
      <VDivider />
      
      <VCardActions class="dialog-actions">
        <VSpacer />
        
        <VBtn
          color="secondary"
          variant="outlined"
          @click="cancelMove"
        >
          Cancel
        </VBtn>
        
        <VBtn
          color="primary"
          :disabled="isConfirmDisabled"
          :loading="isLoading"
          @click="confirmMove"
        >
          Move Board
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style lang="scss" scoped>
.move-board-dialog {
  .move-board-card {
    border-radius: 6px;
    
    .dialog-header {
      padding: 16px 20px;
      
      .info-icon {
        background: rgba(var(--v-theme-primary), 0.1);
        padding: 8px;
        border-radius: 6px;
      }
      
      .dialog-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: rgb(var(--v-theme-on-surface));
      }
    }

    .project-selection-container {
      background: rgb(var(--v-theme-surface));
      border-radius: 6px;
      border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
      padding: 16px;
      margin-top: 16px;

      .projects-list {
        max-height: 240px;
        overflow-y: auto;
        border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
        border-radius: 6px;
        background: rgb(var(--v-theme-background));

        .project-radio-group {
          padding: 4px;
        }

        .project-item {
          border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);
          transition: all 0.2s ease;
          cursor: pointer;
          
          &:last-child {
            border-bottom: none;
          }
          
          &:hover {
            background-color: rgba(var(--v-theme-primary), 0.05);
          }
          
          &.selected {
            background-color: rgba(var(--v-theme-primary), 0.08);
            
            .project-name {
              color: rgb(var(--v-theme-primary));
              font-weight: 600;
            }
          }

          .project-radio {
            margin: 0;
            padding: 12px 16px;
            width: 100%;
          }

          .project-label {
            display: flex;
            align-items: center;
            width: 100%;
          }

          .project-info {
            display: flex;
            flex-direction: column;
          }

          .project-name {
            font-weight: 500;
            transition: color 0.2s ease;
          }
        }
      }
    }

    .new-project-form {
      background: rgb(var(--v-theme-surface));
      border-radius: 6px;
      border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
      padding: 16px;
      margin-top: 16px;
      
      .section-title {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 1rem;
        color: rgb(var(--v-theme-on-surface));
      }
    }

    .dialog-actions {
      background: rgb(var(--v-theme-surface));
      padding: 16px 20px;
    }

    :deep(.v-alert) {
      .v-alert-title {
        margin-bottom: 8px;
      }

      .v-icon {
        opacity: 1;
      }
    }
  }
}

// Custom scrollbar for the projects list
.projects-list {
  scrollbar-width: thin;
  scrollbar-color: rgba(var(--v-theme-on-surface), 0.2) transparent;

  &::-webkit-scrollbar {
    width: 6px;
  }

  &::-webkit-scrollbar-track {
    background: transparent;
  }

  &::-webkit-scrollbar-thumb {
    background-color: rgba(var(--v-theme-on-surface), 0.2);
    border-radius: 3px;

    &:hover {
      background-color: rgba(var(--v-theme-on-surface), 0.3);
    }
  }
}
</style> 