// Nuzex Maritime — main JS
document.addEventListener('DOMContentLoaded', () => {

  /* ============ MOBILE MENU ============ */
  const burger = document.getElementById('hamburger');
  const nav    = document.getElementById('mainNav');
  if (burger && nav) {
    burger.addEventListener('click', () => nav.classList.toggle('open'));
  }

  /* ============ USER DROPDOWN ============ */
  const userMenu   = document.getElementById('userMenu');
  const userToggle = document.getElementById('userToggle');
  if (userMenu && userToggle) {
    userToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      userMenu.classList.toggle('open');
      userToggle.setAttribute('aria-expanded', userMenu.classList.contains('open'));
    });
    document.addEventListener('click', (e) => {
      if (!userMenu.contains(e.target)) userMenu.classList.remove('open');
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') userMenu.classList.remove('open');
    });
  }

  /* ============ TOAST NOTIFICATIONS ============ */
  function toast(msg, type = 'info') {
    const t = document.createElement('div');
    t.className = 'nuzex-toast nuzex-toast-' + type;
    t.textContent = msg;
    document.body.appendChild(t);
    requestAnimationFrame(() => t.classList.add('show'));
    setTimeout(() => {
      t.classList.remove('show');
      setTimeout(() => t.remove(), 300);
    }, 2400);
  }

  /* ============ LIKE BUTTONS ============ */
  document.querySelectorAll('[data-like]').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      const id = btn.dataset.like;
      btn.disabled = true;

      try {
        const res = await fetch('/api/like.php', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ job_id: id })
        });
        const data = await res.json();

        if (data.needs_login && data.redirect) {
          toast('Please log in to like jobs', 'info');
          setTimeout(() => window.location = data.redirect, 700);
          return;
        }
        if (!data.ok) {
          toast(data.error || 'Something went wrong', 'error');
          return;
        }

        const icon  = btn.querySelector('.like-icon');
        const count = btn.querySelector('.count');

        btn.classList.toggle('liked', data.liked);
        if (icon)  icon.textContent = data.liked ? '♥' : '♡';
        if (count) count.textContent = data.count;

        toast(data.liked ? 'Liked' : 'Like removed', 'success');
      } catch (err) {
        console.error(err);
        toast('Network error', 'error');
      } finally {
        btn.disabled = false;
      }
    });
  });

  /* ============ SHARE BUTTONS ============ */
  document.querySelectorAll('[data-share]').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      const platform = btn.dataset.share;
      const url   = btn.dataset.url || window.location.href;
      const title = btn.dataset.title || document.title;
      const id    = btn.dataset.id;

      const urls = {
        fb:    `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`,
        li:    `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(url)}`,
        wa:    `https://wa.me/?text=${encodeURIComponent(title + ' ' + url)}`,
        x:     `https://twitter.com/intent/tweet?text=${encodeURIComponent(title)}&url=${encodeURIComponent(url)}`,
        tg:    `https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`,
        email: `mailto:?subject=${encodeURIComponent(title)}&body=${encodeURIComponent(url)}`
      };

      if (platform === 'copy') {
        try {
          await navigator.clipboard.writeText(url);
          toast('Link copied to clipboard', 'success');
        } catch {
          window.prompt('Copy this link:', url);
        }
      } else if (platform === 'native') {
        if (navigator.share) {
          try { await navigator.share({ title, url }); }
          catch (err) { /* user cancelled */ }
        } else {
          try {
            await navigator.clipboard.writeText(url);
            toast('Link copied — paste it anywhere to share', 'success');
          } catch {
            window.prompt('Copy this link:', url);
          }
        }
      } else if (urls[platform]) {
        window.open(urls[platform], '_blank', 'width=620,height=560,noopener');
      }

      if (id) {
        fetch('/api/share.php', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ job_id: id, platform })
        }).catch(() => {});
      }
    });
  });

  /* ============ AUTO-HIDE FLASH ALERTS ============ */
  document.querySelectorAll('.alert').forEach(a => {
    setTimeout(() => {
      a.style.transition = 'opacity .5s';
      a.style.opacity = '0';
    }, 4000);
    setTimeout(() => a.remove(), 4600);
  });

});