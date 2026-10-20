(function () {
  // Crisis hotlines pop-up. Used on the login page (opened by the link) and for students
  // (opened automatically when a post or chat message contains crisis language).
  var dlg = document.getElementById('crisis-dialog');
  if (!dlg) return;
  var openBtn = document.getElementById('crisis-open'), closeBtn = document.getElementById('crisis-close');

  if (typeof dlg.showModal !== 'function') { if (openBtn) openBtn.hidden = true; return; }

  function open() { if (!dlg.open) dlg.showModal(); }
  window.gabayCrisis = { open: open };

  if (openBtn) openBtn.addEventListener('click', open);
  if (closeBtn) closeBtn.addEventListener('click', function () { dlg.close(); });
  dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });   // click outside closes it

  if (document.body.hasAttribute('data-crisis-open')) open();
})();
