export const SalaryPaymentType = {
  MONTHLY: 1,
  WEEKLY: 2,
  BI_WEEKLY: 3,

  getName(value) {
    const names = {
      1: 'Monthly',
      2: 'Weekly',
      3: 'Bi-Weekly',
    }

    return names[value]
  },
}
