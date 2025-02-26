import { defineStore } from 'pinia'

export const useNotificationStore = defineStore('notifications', {
  state: () => ({
    notifications: [],
    loading: false,
    currentPage: 1,
    totalPages: 1
  }),

  getters: {
    unreadCount: state => state.notifications.filter(n => !n.isSeen).length
  },

  actions: {
    mapNotification(notification) {
      return {
        id: notification.id,
        title: notification.title,
        subtitle: notification.content,
        time: notification.created_at,
        color: notification.type === 'mention' ? 'info' : 'primary',
        isSeen: notification.is_seen,
        icon: notification.type === 'mention' ? 'tabler-at' : 'tabler-message',
        data: notification.data,
        type: notification.type
      }
    },

    async fetchNotifications(page = 1) {
      try {
        this.loading = true
        const response = await $api(`/notifications?page=${page}`, {
          method: 'GET'
        })
        
        if (response.meta) {
          this.notifications = response.data.map(this.mapNotification)
          this.currentPage = response.meta.current_page
          this.totalPages = response.meta.last_page
        } else {
          this.notifications = (response.data || []).map(this.mapNotification)
          this.currentPage = 1
          this.totalPages = 1
        }
      } catch (error) {
        console.error('Error fetching notifications:', error)
        this.notifications = []
      } finally {
        this.loading = false
      }
    },

    async toggleReadStatus(notification) {
      try {
        const endpoint = notification.isSeen 
          ? `/notifications/${notification.id}/mark-as-unread`
          : `/notifications/${notification.id}/mark-as-read`
        
        await $api(endpoint, { method: 'PATCH' })
        
        const notif = this.notifications.find(n => n.id === notification.id)
        if (notif) {
          notif.isSeen = !notif.isSeen
        }
      } catch (error) {
        console.error('Error toggling notification status:', error)
      }
    },

    async markAllAsRead() {
      try {
        await $api('/notifications/mark-all-as-read', { method: 'PATCH' })
        this.notifications.forEach(notification => {
          notification.isSeen = true
        })
      } catch (error) {
        console.error('Error marking all as read:', error)
      }
    },

    async removeNotification(notificationId) {
      try {
        await $api(`/notifications/${notificationId}`, { method: 'DELETE' })
        this.notifications = this.notifications.filter(n => n.id !== notificationId)
      } catch (error) {
        console.error('Error removing notification:', error)
      }
    },

    addNotification(notification) {
      const mappedNotification = this.mapNotification(notification)
      this.notifications = [mappedNotification, ...this.notifications]
    }
  }
}) 