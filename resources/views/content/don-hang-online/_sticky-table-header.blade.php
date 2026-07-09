@once
  @section('page-style')
    @parent
    <style>
      .js-fixed-table-header {
        position: fixed;
        inset-inline-start: 0;
        top: 0;
        z-index: 1040;
        display: none;
        overflow: hidden;
        pointer-events: none;
        box-shadow: 0 .25rem .75rem rgba(67, 89, 113, .12);
      }
      .js-fixed-table-header.is-visible {
        display: block;
      }
      .js-fixed-table-header table {
        margin-bottom: 0;
      }
    </style>
  @endsection

  @section('page-script')
    @parent
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const setups = Array.from(document.querySelectorAll('.js-sticky-table-wrap')).map(wrapper => {
          const table = wrapper.querySelector('.js-sticky-table');
          const thead = table?.querySelector('thead');
          if (!table || !thead) return null;

          const fixed = document.createElement('div');
          fixed.className = 'js-fixed-table-header';
          const cloneTable = table.cloneNode(false);
          cloneTable.className = table.className;
          cloneTable.appendChild(thead.cloneNode(true));
          fixed.appendChild(cloneTable);
          document.body.appendChild(fixed);

          const sync = () => {
            const wrapperRect = wrapper.getBoundingClientRect();
            const tableRect = table.getBoundingClientRect();
            const theadRect = thead.getBoundingClientRect();
            const visible = theadRect.bottom <= 0 && tableRect.bottom > 0 && wrapperRect.bottom > 0;

            fixed.classList.toggle('is-visible', visible);
            if (!visible) return;

            fixed.style.left = `${wrapperRect.left}px`;
            fixed.style.width = `${wrapperRect.width}px`;
            cloneTable.style.width = `${table.offsetWidth}px`;
            cloneTable.style.transform = `translateX(${-wrapper.scrollLeft}px)`;

            const sourceCells = thead.querySelectorAll('th');
            const cloneCells = fixed.querySelectorAll('th');
            sourceCells.forEach((cell, index) => {
              if (cloneCells[index]) cloneCells[index].style.width = `${cell.getBoundingClientRect().width}px`;
            });
          };

          wrapper.addEventListener('scroll', sync, { passive: true });
          return sync;
        }).filter(Boolean);

        const syncAll = () => setups.forEach(sync => sync());
        window.addEventListener('scroll', syncAll, { passive: true });
        window.addEventListener('resize', syncAll);
        syncAll();
      });
    </script>
  @endsection
@endonce
