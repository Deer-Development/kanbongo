import { formatDistanceToNow } from 'date-fns'

export const formatCurrency = (amount) => {
  if (!amount) return '$0.00'
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2
  }).format(amount)
}

export const formatHours = (hours) => {
  if (!hours) return '0h'
  const h = Math.floor(hours)
  const m = Math.round((hours - h) * 60)
  if (m === 0) return `${h}h`
  return `${h}h ${m}m`
}

export const formatDuration = (seconds) => {
  if (!seconds) return '0m'
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  
  if (hours > 0) {
    return `${hours}h ${minutes}m`
  }
  return `${minutes}m`
}

export const timeAgo = (date) => {
  if (!date) return ''
  return formatDistanceToNow(new Date(date), { addSuffix: true })
}

export const getAvatarColor = (id) => {
  const colors = [
    '#2196F3', '#4CAF50', '#FFC107', '#E91E63',
    '#9C27B0', '#FF5722', '#795548', '#607D8B'
  ]
  return colors[id % colors.length]
}

export const formatPercent = (value) => {
  if (!value && value !== 0) return '0%'
  
  const number = Number(value)
  const formatted = new Intl.NumberFormat('en-US', {
    style: 'percent',
    signDisplay: 'exceptZero',
    minimumFractionDigits: 1,
    maximumFractionDigits: 1
  }).format(Math.abs(number) / 100)
  
  return formatted
} 