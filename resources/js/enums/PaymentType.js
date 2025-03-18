export const PaymentType = {
  HOURLY: 1,
  SALARY: 2,
  NO_PAYMENT: 3,
    
  getName(value) {
    const names = {
      1: 'Hourly',
      2: 'Salary',
      3: 'No Payment',
    }

    return names[value]
  },
} 
