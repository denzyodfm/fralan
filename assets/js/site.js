const menu = document.querySelector('.menu-button');
const nav = document.querySelector('.site-header nav');

function closeMenu() {
  if (!menu || !nav) return;
  nav.classList.remove('open');
  menu.setAttribute('aria-expanded', 'false');
  menu.setAttribute('aria-label', 'Open menu');
}

menu?.addEventListener('click', () => {
  const open = !nav.classList.contains('open');
  nav.classList.toggle('open', open);
  menu.setAttribute('aria-expanded', String(open));
  menu.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
});
nav?.addEventListener('click', event => {
  if (event.target.closest('a')) closeMenu();
});
document.addEventListener('keydown', event => {
  if (event.key === 'Escape' && nav?.classList.contains('open')) {
    closeMenu();
    menu.focus();
  }
});
document.addEventListener('click', event => {
  if (nav?.classList.contains('open') && !event.target.closest('.site-header')) closeMenu();
});

const filterGroup = document.querySelector('.filters');
if (filterGroup && Array.isArray(window.archiveContentTypes)) {
  const definitions = [{ key: 'all', label: 'All' }, ...window.archiveContentTypes];
  filterGroup.replaceChildren(...definitions.map((type, index) => {
    const button = document.createElement('button');
    button.type = 'button';
    button.dataset.filter = type.key;
    button.textContent = type.label;
    button.classList.toggle('active', index === 0);
    button.setAttribute('aria-pressed', String(index === 0));
    return button;
  }));
}
const filterButtons = document.querySelectorAll('.filters button');
const searchInput = document.getElementById('archive-search');
const archiveCards = document.querySelectorAll('.archive-card');
const archiveEmpty = document.getElementById('archive-empty');
const archiveStatus = document.getElementById('archive-status');
let activeFilter = 'all';

function applyArchiveFilters() {
  const query = (searchInput?.value || '').trim().toLowerCase();
  let visibleCount = 0;
  archiveCards.forEach(card => {
    const matchesFilter = activeFilter === 'all' || (card.dataset.type || '').toLowerCase() === activeFilter;
    const matchesQuery = !query || (card.textContent || '').toLowerCase().includes(query);
    const visible = matchesFilter && matchesQuery;
    card.hidden = !visible;
    if (visible) visibleCount++;
  });
  if (archiveEmpty) archiveEmpty.hidden = visibleCount !== 0;
  if (archiveStatus) archiveStatus.textContent = query || activeFilter !== 'all' ? `${visibleCount} ${visibleCount === 1 ? 'entry' : 'entries'} found` : '';
}

filterButtons.forEach(button => button.addEventListener('click', () => {
  filterButtons.forEach(candidate => {
    const active = candidate === button;
    candidate.classList.toggle('active', active);
    candidate.setAttribute('aria-pressed', String(active));
  });
  activeFilter = button.dataset.filter || 'all';
  applyArchiveFilters();
}));
searchInput?.addEventListener('input', applyArchiveFilters);

const animatedItems = document.querySelectorAll('.archive-card,.journal-list>a,.intro>div');
if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
  const observer = new IntersectionObserver(entries => entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
      observer.unobserve(entry.target);
    }
  }), { threshold: .08 });
  animatedItems.forEach(element => observer.observe(element));
} else animatedItems.forEach(element => element.classList.add('visible'));
