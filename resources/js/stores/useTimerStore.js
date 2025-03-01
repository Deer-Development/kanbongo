import { defineStore } from 'pinia'
import { ref, reactive, computed } from 'vue'
import { differenceInSeconds, parseISO } from 'date-fns'
import { useToast } from 'vue-toastification'

export const useTimerStore = defineStore('timer', () => {
  const timers = reactive({})
  const intervals = reactive({})
  const userToggleStatus = reactive({})
  const weeklyProgress = reactive({})
  const pendingRequests = reactive({})
  const toast = useToast()

  // Verifică dacă există un request în curs pentru un utilizator și task
  const isRequestPending = (userId, taskId) => {
    const key = `${userId}-${taskId}`
    return pendingRequests[key] === true
  }

  // Marchează un request ca fiind în curs
  const markRequestPending = (userId, taskId) => {
    const key = `${userId}-${taskId}`
    pendingRequests[key] = true
    
    // Curățăm starea după 5 secunde pentru a preveni blocarea permanentă
    setTimeout(() => {
      if (pendingRequests[key]) {
        delete pendingRequests[key]
      }
    }, 5000)
  }

  // Marchează un request ca fiind finalizat
  const markRequestCompleted = (userId, taskId) => {
    const key = `${userId}-${taskId}`
    delete pendingRequests[key]
  }

  // Start a timer for a user
  const startTimer = (userId, timeEntry, isKanban = false) => {
    if (!timeEntry?.start) return
    
    // Clear any existing timer for this user
    clearTimer(userId, isKanban)
    
    const startDate = parseISO(timeEntry.start)
    const now = new Date()
    const elapsedSeconds = differenceInSeconds(now, startDate)
    
    // Set initial timer value
    const key = getTimerKey(userId, isKanban)
    timers[key] = formatTime(elapsedSeconds)
    
    // Start interval to update timer
    intervals[key] = setInterval(() => {
      const currentDate = new Date()
      const currentElapsedSeconds = differenceInSeconds(currentDate, startDate)
      timers[key] = formatTime(currentElapsedSeconds)
      
      // Update weekly progress if available
      if (weeklyProgress[userId]) {
        updateWeeklyProgress(userId, currentElapsedSeconds)
      }
    }, 1000)
    
    return timers[key]
  }
  
  // Initialize weekly progress tracking for a user
  const initWeeklyProgress = (userId, entry) => {
    // Verificăm dacă utilizatorul are weekly limit
    if (!entry.has_weekly_limit) return
    
    // Verificăm dacă avem toate datele necesare
    if (!entry.weekly_limit_seconds || !entry.weekly_tracked) {
      console.warn('Missing weekly limit data for user', userId)
      return
    }
    
    // Calculăm valorile inițiale
    const initialSeconds = entry.weekly_tracked.total_seconds || 0
    const limitSeconds = entry.weekly_limit_seconds || 0
    const limitHours = entry.weekly_limit_hours || 0
    
    weeklyProgress[userId] = {
      initialSeconds: initialSeconds,
      limitSeconds: limitSeconds,
      limitHours: limitHours, // Stocăm și valoarea în ore pentru afișare
      startTime: entry.time_entry?.start ? parseISO(entry.time_entry.start) : null,
      currentValue: initialSeconds,
      percentValue: Math.min(100, (initialSeconds / limitSeconds) * 100),
      displayValue: formatWeeklyProgressDisplay(initialSeconds, limitHours)
    }
  }
  
  // Adăugăm o funcție helper pentru formatarea afișării progresului
  const formatWeeklyProgressDisplay = (totalSeconds, limitHours) => {
    const hours = Math.floor(totalSeconds / 3600)
    const minutes = Math.floor((totalSeconds % 3600) / 60)
    
    // Formatăm afișarea cu o precizie de 1 zecimală pentru limitele mici
    const limitDisplay = limitHours < 1 ? 
      `${(limitHours * 60).toFixed(0)}m` : 
      `${limitHours}h`
    
    return `${hours}h ${minutes}m / ${limitDisplay}`
  }
  
  // Update weekly progress for a user
  const updateWeeklyProgress = (userId, additionalSeconds = 0) => {
    if (!weeklyProgress[userId]) return
    
    const wp = weeklyProgress[userId]
    const totalSeconds = wp.initialSeconds + (additionalSeconds || 0)
    const percentValue = Math.min(100, (totalSeconds / wp.limitSeconds) * 100)
    
    // Update the reactive object
    wp.currentValue = totalSeconds
    wp.percentValue = percentValue
    
    // Folosim funcția helper pentru formatare
    wp.displayValue = formatWeeklyProgressDisplay(totalSeconds, wp.limitHours)
  }
  
  // Get weekly progress for a user
  const getWeeklyProgress = (userId) => {
    return weeklyProgress[userId] || null
  }
  
  // Check weekly limit and stop timer if needed
  const checkWeeklyLimit = (entry, toggleTimerFn) => {
    if (!entry.has_weekly_limit || !entry.time_entry?.start) return false
    
    // Verificăm dacă există deja un request în curs pentru acest utilizator și task
    // Dar permitem verificarea limitei chiar dacă există un request în curs
    const isRequestAlreadyPending = isRequestPending(entry.user.id, entry.time_entry.task_id)
    
    // Initialize weekly progress tracking if not already done
    if (!weeklyProgress[entry.user.id]) {
      initWeeklyProgress(entry.user.id, entry)
    } else {
      // Forțăm o actualizare a progress bar-ului
      const startDate = parseISO(entry.time_entry.start)
      const now = new Date()
      const elapsedSeconds = differenceInSeconds(now, startDate)
      updateWeeklyProgress(entry.user.id, elapsedSeconds)
    }
    
    const startDate = parseISO(entry.time_entry.start)
    const now = new Date()
    const elapsedSeconds = differenceInSeconds(now, startDate)
    
    const totalTracked = entry.weekly_tracked.total_seconds + elapsedSeconds
    const remainingSeconds = entry.weekly_limit_seconds - totalTracked
    
    // Adăugăm o toleranță de 3 secunde pentru a evita oprirea prematură
    const toleranceSeconds = 3
    
    // If already at limit, stop timer immediately
    if (remainingSeconds <= -toleranceSeconds && !userToggleStatus[entry.user.id] && !isRequestAlreadyPending) {
      userToggleStatus[entry.user.id] = true
      
      // Marcăm requestul ca fiind în curs
      markRequestPending(entry.user.id, entry.time_entry.task_id)
      
      // Folosim un wrapper pentru toggleTimerFn pentru a gestiona erorile
      const stopTimer = async () => {
        try {
          await toggleTimerFn({
            user_id: entry.user.id,
            task_id: entry.time_entry.task_id,
            stopped_by_system: true,
          }, entry.time_entry.task_id)
          
          toast.error('Weekly limit reached. Timer stopped.')
        } catch (error) {
          console.error('Error stopping timer at weekly limit:', error)
        } finally {
          // Marcăm requestul ca fiind finalizat
          markRequestCompleted(entry.user.id, entry.time_entry.task_id)
        }
      }
      
      // Executăm funcția
      stopTimer()
      
      return true
    } 
    // If approaching limit, schedule timer stop
    else if (remainingSeconds > -toleranceSeconds) {
      // Adăugăm toleranța și la programarea opririi
      const timeoutSeconds = remainingSeconds + toleranceSeconds
      
      if (timeoutSeconds > 0) {
        setTimeout(() => {
          // Verificăm din nou dacă există un request în curs în momentul executării
          if (!userToggleStatus[entry.user.id] && !isRequestPending(entry.user.id, entry.time_entry.task_id)) {
            userToggleStatus[entry.user.id] = true
            
            // Marcăm requestul ca fiind în curs
            markRequestPending(entry.user.id, entry.time_entry.task_id)
            
            // Folosim un wrapper pentru toggleTimerFn pentru a gestiona erorile
            const stopTimer = async () => {
              try {
                await toggleTimerFn({
                  user_id: entry.user.id,
                  task_id: entry.time_entry.task_id,
                  stopped_by_system: true,
                }, entry.time_entry.task_id)
                
                toast.error('Weekly limit reached. Timer stopped.')
              } catch (error) {
                console.error('Error stopping timer at weekly limit:', error)
              } finally {
                // Marcăm requestul ca fiind finalizat
                markRequestCompleted(entry.user.id, entry.time_entry.task_id)
              }
            }
            
            // Executăm funcția
            stopTimer()
          }
        }, timeoutSeconds * 1000)
      }
      return false
    }
    
    return false
  }
  
  // Clear a specific timer
  const clearTimer = (userId, isKanban = false) => {
    const key = getTimerKey(userId, isKanban)
    if (intervals[key]) {
      clearInterval(intervals[key])
      delete intervals[key]
    }
  }
  
  // Clear all timers
  const clearAllTimers = (isKanban = false) => {
    Object.keys(intervals).forEach(key => {
      if (key.endsWith(isKanban ? '-kanban' : '-default')) {
        clearInterval(intervals[key])
        delete intervals[key]
      }
    })
    
    // Clear user toggle status
    Object.keys(userToggleStatus).forEach(userId => {
      delete userToggleStatus[userId]
    })
    
    // Clear weekly progress
    Object.keys(weeklyProgress).forEach(userId => {
      delete weeklyProgress[userId]
    })
  }
  
  // Get timer value
  const getTimer = (userId, isKanban = false) => {
    const key = getTimerKey(userId, isKanban)
    return timers[key] || '00:00:00'
  }
  
  // Helper to get timer key
  const getTimerKey = (userId, isKanban) => {
    return `${userId}-${isKanban ? 'kanban' : 'default'}`
  }
  
  // Format seconds to HH:MM:SS
  const formatTime = (seconds) => {
    const hours = Math.floor(seconds / 3600)
    const minutes = Math.floor((seconds % 3600) / 60)
    const secs = seconds % 60
    
    return [
      hours.toString().padStart(2, '0'),
      minutes.toString().padStart(2, '0'),
      secs.toString().padStart(2, '0')
    ].join(':')
  }
  
  // Reset user toggle status
  const resetUserToggleStatus = (userId) => {
    if (userToggleStatus[userId]) {
      delete userToggleStatus[userId]
    }
  }
  
  // Adăugăm o funcție pentru resetarea stării unui utilizator
  const resetUserState = (userId) => {
    // Resetăm timer-ul
    clearTimer(userId)
    
    // Resetăm starea de toggle
    resetUserToggleStatus(userId)
    
    // Resetăm progresul săptămânal
    if (weeklyProgress[userId]) {
      delete weeklyProgress[userId]
    }
  }
  
  return {
    timers,
    startTimer,
    clearTimer,
    clearAllTimers,
    getTimer,
    checkWeeklyLimit,
    resetUserToggleStatus,
    initWeeklyProgress,
    getWeeklyProgress,
    updateWeeklyProgress,
    resetUserState,
    isRequestPending,
    markRequestPending,
    markRequestCompleted
  }
}) 