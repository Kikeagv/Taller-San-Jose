document.addEventListener('DOMContentLoaded', () => {
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
    addRowButton.addEventListener('click', () => {
      const template = document.querySelector('[data-compra-template]');
      const clone = template.content.cloneNode(true);
      rowsBody.appendChild(clone);
    });
  }
});
