function initAppInteractions() {
  document.querySelectorAll('[data-confirm]').forEach((element) => {
    if (element.dataset.confirmBound === 'true') return;
    element.dataset.confirmBound = 'true';

    element.addEventListener('click', (event) => {
      const message = element.getAttribute('data-confirm') || '¿Confirmar acción?';
      if (!confirm(message)) {
        event.preventDefault();
      }
    });
  });

  const addRowButton = document.querySelector('[data-add-compra-row]');
  const rowsBody = document.querySelector('[data-compra-rows]');
  if (addRowButton && rowsBody && rowsBody.dataset.purchaseRowsBound !== 'true') {
    rowsBody.dataset.purchaseRowsBound = 'true';

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

function setActiveSidebarLink(url) {
  const target = new URL(url, window.location.href);
  const targetRoute = target.searchParams.get('r') || 'dashboard';

  document.querySelectorAll('.sidebar .nav-link').forEach((link) => {
    const linkRoute = new URL(link.href, window.location.href).searchParams.get('r');
    link.classList.toggle('active', linkRoute === targetRoute);
  });
}

async function loadMainContent(url, pushState = true) {
  const response = await fetch(url, {
    headers: { 'X-Requested-With': 'partial-navigation' },
  });

  if (!response.ok) {
    window.location.href = url;
    return;
  }

  const html = await response.text();
  const nextDocument = new DOMParser().parseFromString(html, 'text/html');
  const nextMain = nextDocument.querySelector('#app-main');
  const currentMain = document.querySelector('#app-main');

  if (!nextMain || !currentMain) {
    window.location.href = url;
    return;
  }

  currentMain.replaceWith(nextMain);
  document.title = nextDocument.title;
  setActiveSidebarLink(url);
  initAppInteractions();

  if (pushState) {
    history.pushState({ partialNavigation: true }, '', url);
  }
}

function initPartialNavigation() {
  if (document.body.dataset.partialNavigationBound === 'true') return;
  document.body.dataset.partialNavigationBound = 'true';

  document.addEventListener('click', (event) => {
    const link = event.target.closest('.sidebar .nav-link');
    if (!link) return;

    const url = new URL(link.href, window.location.href);
    if (url.origin !== window.location.origin) return;

    event.preventDefault();
    loadMainContent(url.href).catch(() => {
      window.location.href = url.href;
    });
  });

  window.addEventListener('popstate', () => {
    loadMainContent(window.location.href, false).catch(() => {
      window.location.reload();
    });
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    initAppInteractions();
    initPartialNavigation();
  });
} else {
  initAppInteractions();
  initPartialNavigation();
}
