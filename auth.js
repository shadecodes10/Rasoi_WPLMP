document.addEventListener('DOMContentLoaded', function () {

  var name = localStorage.getItem('rasoi_user');
  var authEl = document.querySelector('.nav-auth');
  if (!authEl) return;

  if (name) {
    authEl.outerHTML =
      '<div class="nav-auth-user">' +
        '<span class="nav-welcome">Welcome, ' + name + '</span>' +
        '<a href="logout.php" class="nav-signout">Sign Out</a>' +
      '</div>';
  }
});
