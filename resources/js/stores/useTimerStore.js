import { defineStore } from 'pinia'
import { differenceInSeconds, parseISO } from 'date-fns'

export const useTimerStore = defineStore('timer', {
  state: () => ({
    timers: {},
    intervals: {},
    menuTimers: {},
    menuIntervals: {}
  }),

  actions: {
    calculateTrackedTime(start) {
      try {
        const startDate = parseISO(start)
        const now = new Date()
        const seconds = differenceInSeconds(now, startDate)

        if (seconds < 0) return 'Invalid Time'

        const hours = Math.floor(seconds / 3600)
        const minutes = Math.floor((seconds % 3600) / 60)
        const remainingSeconds = seconds % 60

        return `${hours}h ${minutes}m ${remainingSeconds}s`
      } catch (error) {
        return 'Error calculating time'
      }
    },

    startTimer(userId, timeEntry, isMenu = false) {
      const timerState = isMenu ? this.menuTimers : this.timers
      const intervalState = isMenu ? this.menuIntervals : this.intervals

      this.clearTimer(userId, isMenu)

      timerState[userId] = this.calculateTrackedTime(timeEntry.start)

      intervalState[userId] = setInterval(() => {
        timerState[userId] = this.calculateTrackedTime(timeEntry.start)
      }, 1000)
    },

    clearTimer(userId, isMenu = false) {
      const intervalState = isMenu ? this.menuIntervals : this.intervals
      const timerState = isMenu ? this.menuTimers : this.timers

      if (intervalState[userId]) {
        clearInterval(intervalState[userId])
        delete intervalState[userId]
      }
      delete timerState[userId]
    },

    clearAllTimers(isMenu = false) {
      const intervalState = isMenu ? this.menuIntervals : this.intervals
      Object.keys(intervalState).forEach(userId => {
        this.clearTimer(userId, isMenu)
      })
    }
  },

  getters: {
    getTimer: (state) => (userId, isMenu = false) => {
      return isMenu ? state.menuTimers[userId] : state.timers[userId]
    }
  }
}) 