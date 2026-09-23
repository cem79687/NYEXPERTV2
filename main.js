// main.js — Logique commune du site Nurset YAZGOREN Expert Bâtiment
// Menu mobile (identique sur toutes les pages)

document.addEventListener('DOMContentLoaded', () => {
  const menuToggle = document.getElementById('menuToggle');
  const navLinks = document.getElementById('navLinks');

  if (menuToggle && navLinks) {
    const menuSpans = menuToggle.querySelectorAll('span');

    const closeMenu = () => {
      navLinks.classList.remove('open');
      menuToggle.setAttribute('aria-expanded', 'false');
      menuSpans.forEach((span) => { span.style.cssText = ''; });
    };

    menuToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      const isOpen = navLinks.classList.toggle('open');
      menuToggle.setAttribute('aria-expanded', String(isOpen));
      if (isOpen) {
        menuSpans[0].style.cssText = 'transform:translateY(7px) rotate(45deg)';
        menuSpans[1].style.cssText = 'opacity:0; transform:scaleX(0)';
        menuSpans[2].style.cssText = 'transform:translateY(-7px) rotate(-45deg)';
      } else {
        closeMenu();
      }
    });

    navLinks.querySelectorAll('a').forEach(a => {
      a.addEventListener('click', closeMenu);
    });

    document.addEventListener('click', (e) => {
      if (!menuToggle.contains(e.target) && !navLinks.contains(e.target)) {
        closeMenu();
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && navLinks.classList.contains('open')) {
        closeMenu();
        menuToggle.focus();
      }
    });
  }
});
