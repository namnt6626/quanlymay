<style>
  .production-filter-form {
    --bs-gutter-x: .875rem;
    --bs-gutter-y: .875rem;
  }

  .production-filter-form > [class*='col-'] {
    display: flex;
    flex-direction: column;
    min-width: 0;
  }

  .production-filter-form .form-label {
    align-items: center;
    color: var(--bs-heading-color);
    display: flex;
    font-size: .78rem;
    font-weight: 600;
    line-height: 1.2;
    margin-bottom: .4rem;
    min-height: 1.1rem;
  }

  .production-filter-form .form-control,
  .production-filter-form .form-select {
    min-height: 42px;
  }

  .production-filter-form .filter-actions {
    justify-content: flex-end;
  }

  .production-filter-form .filter-actions .btn {
    min-height: 42px;
    white-space: nowrap;
  }

  @media (max-width: 767.98px) {
    .production-filter-form {
      --bs-gutter-x: .65rem;
      --bs-gutter-y: .75rem;
    }

    .production-filter-form .form-label {
      font-size: .75rem;
    }

    .production-filter-form .filter-actions {
      display: grid !important;
      gap: .65rem !important;
      grid-template-columns: 1fr 1fr;
    }

    .production-filter-form .filter-actions .btn {
      width: 100%;
    }
  }

  @media (max-width: 420px) {
    .production-filter-form .filter-actions {
      grid-template-columns: 1fr;
    }
  }

  @media (min-width: 992px) {
    .production-filter-form.production-filter-grid {
      display: grid;
      gap: .875rem;
      grid-template-columns: repeat(12, minmax(0, 1fr));
      margin-left: 0;
      margin-right: 0;
    }

    .production-filter-form.production-filter-grid > [class*='col-'] {
      padding-left: 0;
      padding-right: 0;
      width: 100%;
    }

    .production-filter-grid .filter-span-1 {
      grid-column: span 1;
    }

    .production-filter-grid .filter-span-2 {
      grid-column: span 2;
    }

    .production-filter-grid .filter-span-3 {
      grid-column: span 3;
    }

    .production-filter-grid .filter-span-4 {
      grid-column: span 4;
    }

    .production-filter-grid .filter-span-6 {
      grid-column: span 6;
    }

    .production-filter-grid .filter-actions {
      display: grid !important;
      gap: .5rem !important;
      grid-template-columns: 1fr 1fr;
    }

    .production-filter-grid .filter-actions .btn {
      min-width: 0;
      padding-left: .65rem;
      padding-right: .65rem;
      width: 100%;
    }
  }
</style>
