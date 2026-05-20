function initAppInteractions() {
  document.querySelectorAll('[data-confirm]').forEach((element) => {
    element.addEventListener('click', (event) => {
      const message = element.getAttribute('data-confirm') || '¿Confirmar acción?';
      if (!confirm(message)) {
        event.preventDefault();
      }
    });
  });

  const addRowButton = document.querySelector('[data-add-compra-row]');
  const rowsBody = document.querySelector('[data-compra-rows]');
  if (addRowButton && rowsBody) {
    const updateRemoveButtons = () => {
      const rows = rowsBody.querySelectorAll('[data-compra-row]');
      rows.forEach((row) => {
        const removeButton = row.querySelector('[data-remove-compra-row]');
        if (removeButton) {
          removeButton.hidden = rows.length <= 1;
          removeButton.disabled = rows.length <= 1;
        }
      });
    };

    addRowButton.addEventListener('click', () => {
      const template = document.querySelector('[data-compra-template]');
      const clone = template.content.cloneNode(true);
      rowsBody.appendChild(clone);
      updateRemoveButtons();
    });

    rowsBody.addEventListener('click', (event) => {
      const removeButton = event.target.closest('[data-remove-compra-row]');
      if (!removeButton) return;

      const rows = rowsBody.querySelectorAll('[data-compra-row]');
      if (rows.length <= 1) return;

      removeButton.closest('[data-compra-row]').remove();
      updateRemoveButtons();
    });

    updateRemoveButtons();
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initAppInteractions);
} else {
  initAppInteractions();
}
