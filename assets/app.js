const toggle = document.querySelector('.nav-toggle');
const nav = document.querySelector('.nav-links');
if (toggle && nav) {
  toggle.addEventListener('click', () => {
    const open = nav.classList.toggle('open');
    toggle.setAttribute('aria-expanded', String(open));
  });
}

document.querySelectorAll('[data-confirm]').forEach((button) => {
  button.addEventListener('click', (event) => {
    if (button.closest('form[data-ajax]')) return;
    if (!window.confirm(button.dataset.confirm)) event.preventDefault();
  });
});

document.querySelectorAll('[data-filter-scope]').forEach((scope) => {
  const search = scope.querySelector('[data-live-search]');
  const chips = [...scope.querySelectorAll('[data-filter]')];
  const grid = scope.parentElement.querySelector('[data-filter-grid]');
  const items = [...grid.querySelectorAll('[data-filter-item]')];
  const count = scope.querySelector('[data-result-count]');
  const empty = scope.parentElement.querySelector('.filter-empty');
  let category = 'all';

  const update = () => {
    const query = (search?.value || '').trim().toLowerCase();
    let visible = 0;
    items.forEach((item) => {
      const matchesCategory = category === 'all' || item.dataset.category === category;
      const matchesSearch = !query || item.dataset.search.includes(query);
      const show = matchesCategory && matchesSearch;
      item.hidden = !show;
      if (show) visible += 1;
    });
    count.textContent = visible;
    empty.hidden = visible !== 0;
  };

  search?.addEventListener('input', update);
  chips.forEach((chip) => chip.addEventListener('click', () => {
    chips.forEach((item) => item.classList.remove('active'));
    chip.classList.add('active');
    category = chip.dataset.filter;
    update();
  }));
});

const counterObserver = new IntersectionObserver((entries, observer) => {
  entries.forEach((entry) => {
    if (!entry.isIntersecting) return;
    const element = entry.target;
    const target = Number(element.dataset.count || 0);
    const start = performance.now();
    const tick = (now) => {
      const progress = Math.min(1, (now - start) / 650);
      element.textContent = Math.round(target * (1 - Math.pow(1 - progress, 3)));
      if (progress < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
    observer.unobserve(element);
  });
});
document.querySelectorAll('[data-count]').forEach((counter) => counterObserver.observe(counter));

const revealObserver = new IntersectionObserver((entries) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting) entry.target.classList.add('revealed');
  });
}, { threshold: 0.08 });
document.querySelectorAll('.reveal').forEach((element) => revealObserver.observe(element));

document.querySelectorAll('.flash').forEach((message) => {
  setTimeout(() => message.classList.add('flash-out'), 3600);
});

const toast = (message, ok = true) => {
  document.querySelector('.inline-toast')?.remove();
  const element = document.createElement('div');
  element.className = 'inline-toast';
  element.textContent = `${ok ? '✓' : '×'} ${message}`;
  document.body.appendChild(element);
  setTimeout(() => element.remove(), 3500);
};

document.querySelectorAll('form[data-ajax]').forEach((form) => {
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const submitter = event.submitter;
    if (submitter?.dataset.confirm && !window.confirm(submitter.dataset.confirm)) return;
    const data = new FormData(form);
    if (submitter?.name) data.set(submitter.name, submitter.value);
    const original = submitter?.textContent;
    if (submitter) { submitter.disabled = true; submitter.textContent = 'Working…'; }
    try {
      const response = await fetch(form.dataset.ajax, { method: 'POST', body: data, headers: { 'X-Requested-With': 'fetch' } });
      const result = await response.json();
      if (!result.ok) throw new Error(result.message);
      toast(result.message);

      if (form.dataset.ajax.includes('event-registration')) {
        const card = form.closest('.event-card');
        const count = card.querySelector('[data-registration-count]');
        if (count) count.textContent = result.data.registration_count;
        const action = form.querySelector('[name="action"]');
        if (action) action.value = result.data.state === 'registered' ? 'cancel_registration' : 'register';
        submitter.name = 'action';
        submitter.value = action?.value || '';
        submitter.className = result.data.state === 'registered' ? 'button button-quiet' : 'button button-primary';
        submitter.textContent = result.data.state === 'registered' ? 'Registered ✓ · Cancel' : 'Claim my place';
        if (result.data.state === 'registered') submitter.dataset.confirm = 'Cancel your registration?';
        else delete submitter.dataset.confirm;
      } else if (form.querySelector('[name="action"]')?.value === 'request_join') {
        submitter.textContent = 'Request pending'; submitter.disabled = true;
        const state = form.closest('.club-card')?.querySelector('[data-membership-state]');
        if (state) state.textContent = 'Pending';
      } else {
        const row = form.closest('[data-membership-row]');
        const action = submitter?.value || form.querySelector('[name="action"]')?.value;
        const state = row?.querySelector('[data-approval-state]');
        if (state && action === 'approve') { state.textContent = 'Approved'; state.classList.remove('badge-warn'); }
        if (state && action === 'reject') { state.textContent = 'Rejected'; state.classList.remove('badge-warn'); }
        if (action === 'remove') { row?.classList.add('flash-out'); setTimeout(() => row?.remove(), 300); }
        if (submitter) submitter.textContent = original;
      }
    } catch (error) {
      toast(error.message || 'That action could not be completed.', false);
      if (submitter) submitter.textContent = original;
    } finally {
      if (submitter && submitter.textContent !== 'Request pending') submitter.disabled = false;
    }
  });
});

const eventGrid = document.querySelector('[data-event-view]');
const viewButtons = [...document.querySelectorAll('[data-view]')];
if (eventGrid && viewButtons.length) {
  const setView = (view) => {
    eventGrid.classList.toggle('event-view-list', view === 'list');
    viewButtons.forEach((button) => button.classList.toggle('active', button.dataset.view === view));
    localStorage.setItem('campushub-event-view', view);
  };
  setView(localStorage.getItem('campushub-event-view') || 'grid');
  viewButtons.forEach((button) => button.addEventListener('click', () => setView(button.dataset.view)));
}

document.querySelectorAll('img').forEach((image) => {
  image.addEventListener('error', () => {
    image.hidden = true;
    image.parentElement?.classList.add('image-fallback');
  });
});
