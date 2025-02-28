.actions-section {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  width: 100%;
  justify-content: flex-start;

  @media (min-width: 768px) {
    width: auto;
    justify-content: flex-end;
    gap: 1rem;
  }

  .github-btn {
    flex: 0 0 28%;
    
    @media (min-width: 768px) {
      flex: 0 0 auto;
      padding: 0 12px;
    }
    
    @media (max-width: 767px) {
      padding: 0 8px;
      min-width: 0;
      white-space: nowrap;
      
      .v-btn__prepend {
        margin-inline-end: 4px;
      }
      
      span {
        font-size: 0.75rem;
      }
    }
  }
} 