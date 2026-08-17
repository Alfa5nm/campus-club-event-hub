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
