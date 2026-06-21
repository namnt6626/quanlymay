<script>
  document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('don-hang-form');
    const groupContainer = document.getElementById('don-hang-detail-groups');
    const addGroupButton = document.querySelector('[data-add-group]');
    const groupTemplate = document.getElementById('don-hang-group-template');
    const itemTemplate = document.getElementById('don-hang-item-template');

    function normalizeNumber(value) {
      let text = String(value || '').trim();

      if (!text) {
        return '';
      }

      text = text.replace(/\s+/g, '');

      const commaCount = (text.match(/,/g) || []).length;
      const dotCount = (text.match(/\./g) || []).length;

      if (commaCount > 0 && dotCount > 0) {
        const decimalSeparator = text.lastIndexOf(',') > text.lastIndexOf('.') ? ',' : '.';
        const thousandSeparator = decimalSeparator === ',' ? '.' : ',';
        text = text.split(thousandSeparator).join('');
        text = text.replace(decimalSeparator, '.');
      } else if (commaCount > 0) {
        const parts = text.split(',');
        if (commaCount === 1 && parts[parts.length - 1].length !== 3) {
          text = text.replace(',', '.');
        } else {
          text = text.split(',').join('');
        }
      } else if (dotCount > 0) {
        const parts = text.split('.');
        if (!(dotCount === 1 && parts[parts.length - 1].length !== 3)) {
          text = text.split('.').join('');
        }
      }

      text = text.replace(/[^\d.\-]/g, '');

      const firstDotIndex = text.indexOf('.');
      if (firstDotIndex !== -1) {
        text = text.slice(0, firstDotIndex + 1) + text.slice(firstDotIndex + 1).replace(/\./g, '');
      }

      return text;
    }

    function formatDisplayNumber(value) {
      const normalized = normalizeNumber(value);

      if (normalized === '') {
        return '';
      }

      const number = Number(normalized);

      if (Number.isNaN(number)) {
        return '';
      }

      return new Intl.NumberFormat('de-DE', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 4,
      }).format(number);
    }

    function formatEditableNumber(value) {
      const normalized = normalizeNumber(value);

      if (normalized === '') {
        return '';
      }

      return normalized.replace('.', ',');
    }

    function updateQuantityInput(input) {
      input.value = input.value.replace(/[^\d.,]/g, '');
    }

    function wireQuantityInput(input) {
      input.addEventListener('input', function() {
        updateQuantityInput(input);
      });

      input.addEventListener('focus', function() {
        input.value = formatEditableNumber(input.value);
      });

      input.addEventListener('blur', function() {
        input.value = formatDisplayNumber(input.value);
      });

      input.value = formatDisplayNumber(input.value);
    }

    function clearFields(container) {
      container.querySelectorAll('select').forEach(function(select) {
        select.selectedIndex = 0;
      });

      container.querySelectorAll('input').forEach(function(input) {
        input.value = '';
      });

      container.querySelectorAll('textarea').forEach(function(textarea) {
        textarea.value = '';
      });
    }

    function wireItem(item) {
      const quantityInput = item.querySelector('.js-quantity-input');
      const removeButton = item.querySelector('[data-remove-item]');

      if (quantityInput && !quantityInput.dataset.quantityWired) {
        quantityInput.dataset.quantityWired = '1';
        wireQuantityInput(quantityInput);
      }

      if (removeButton && !removeButton.dataset.removeWired) {
        removeButton.dataset.removeWired = '1';
        removeButton.addEventListener('click', function() {
          const group = item.closest('[data-don-hang-group]');
          const itemBody = group ? group.querySelector('[data-item-body]') : null;
          const items = itemBody ? itemBody.querySelectorAll('[data-don-hang-item]') : [];

          if (items.length <= 1) {
            clearFields(item);
            return;
          }

          item.remove();
        });
      }
    }

    function getNextGroupIndex() {
      const groups = Array.from(groupContainer.querySelectorAll('[data-don-hang-group]'));

      if (!groups.length) {
        return 0;
      }

      return groups.reduce(function(maxIndex, group) {
        const groupIndex = Number.parseInt(group.dataset.groupIndex || '0', 10);
        return Number.isNaN(groupIndex) ? maxIndex : Math.max(maxIndex, groupIndex);
      }, 0) + 1;
    }

    function getNextItemIndex(group) {
      const items = Array.from(group.querySelectorAll('[data-don-hang-item]'));

      if (!items.length) {
        return 0;
      }

      return items.reduce(function(maxIndex, item) {
        const itemIndex = Number.parseInt(item.dataset.itemIndex || '0', 10);
        return Number.isNaN(itemIndex) ? maxIndex : Math.max(maxIndex, itemIndex);
      }, 0) + 1;
    }

    function addItem(group) {
      if (!itemTemplate || !group) {
        return;
      }

      const itemBody = group.querySelector('[data-item-body]');

      if (!itemBody) {
        return;
      }

      const groupIndex = group.dataset.groupIndex || '0';
      const nextItemIndex = getNextItemIndex(group);
      const html = itemTemplate.innerHTML
        .split('__GROUP__').join(String(groupIndex))
        .split('__ITEM__').join(String(nextItemIndex));
      const wrapper = document.createElement('tbody');
      wrapper.innerHTML = html.trim();
      const item = wrapper.querySelector('[data-don-hang-item]');

      if (!item) {
        return;
      }

      itemBody.appendChild(item);
      wireItem(item);
    }

    function wireGroup(group) {
      const addItemButton = group.querySelector('[data-add-item]');
      const removeGroupButton = group.querySelector('[data-remove-group]');

      group.querySelectorAll('[data-don-hang-item]').forEach(wireItem);

      if (addItemButton && !addItemButton.dataset.addWired) {
        addItemButton.dataset.addWired = '1';
        addItemButton.addEventListener('click', function() {
          addItem(group);
        });
      }

      if (removeGroupButton && !removeGroupButton.dataset.removeWired) {
        removeGroupButton.dataset.removeWired = '1';
        removeGroupButton.addEventListener('click', function() {
          const groups = groupContainer.querySelectorAll('[data-don-hang-group]');

          if (groups.length <= 1) {
            clearFields(group);
            return;
          }

          group.remove();
        });
      }
    }

    function addGroup() {
      if (!groupTemplate || !groupContainer) {
        return;
      }

      const nextGroupIndex = getNextGroupIndex();
      const html = groupTemplate.innerHTML.split('__GROUP__').join(String(nextGroupIndex));
      const wrapper = document.createElement('div');
      wrapper.innerHTML = html.trim();
      const group = wrapper.querySelector('[data-don-hang-group]');

      if (!group) {
        return;
      }

      groupContainer.appendChild(group);
      wireGroup(group);
    }

    if (groupContainer) {
      groupContainer.querySelectorAll('[data-don-hang-group]').forEach(wireGroup);
    }

    if (addGroupButton) {
      addGroupButton.addEventListener('click', addGroup);
    }

    if (form) {
      form.addEventListener('submit', function() {
        form.querySelectorAll('.js-quantity-input').forEach(function(input) {
          input.value = normalizeNumber(input.value);
        });
      });
    }
  });
</script>
