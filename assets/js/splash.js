(function () {
  var root = document.documentElement, splash = document.getElementById('splash');
  if (!splash || !root.classList.contains('splash-on')) return;

  var done = false, t1, t2;

  function finish() {
    if (done) return; done = true;
    clearTimeout(t1); clearTimeout(t2);
    splash.classList.add('leaving');
    setTimeout(function () { root.classList.remove('splash-on'); splash.remove(); }, 650);
  }
  t1 = setTimeout(finish, 3600);          // total time on screen
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') finish(); });
})();
