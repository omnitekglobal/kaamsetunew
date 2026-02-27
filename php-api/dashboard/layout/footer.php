</main>
<script>
(function() {
  var body = document.body;
  var toggle = document.getElementById('sidebar-toggle');
  var overlay = document.getElementById('sidebar-overlay');
  var closeBtn = document.getElementById('sidebar-close');
  if (toggle) {
    toggle.addEventListener('click', function() { body.classList.add('nav-open'); });
  }
  if (overlay) {
    overlay.addEventListener('click', function() { body.classList.remove('nav-open'); });
  }
  if (closeBtn) {
    closeBtn.addEventListener('click', function() { body.classList.remove('nav-open'); });
  }
  document.querySelectorAll('.sidebar-nav .nav-item').forEach(function(link) {
    link.addEventListener('click', function() { body.classList.remove('nav-open'); });
  });
  // Submenu toggle: open/close Users group
  var groupBtn = document.getElementById('nav-group-users-btn');
  var navGroup = document.getElementById('nav-group-users');
  if (groupBtn && navGroup) {
    groupBtn.addEventListener('click', function() {
      var open = navGroup.classList.toggle('open');
      groupBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }
})();
</script>
</body>
</html>
