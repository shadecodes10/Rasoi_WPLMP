document.addEventListener('DOMContentLoaded', function () {

  var params = new URLSearchParams(window.location.search);
  if (params.get('user')) {
    localStorage.setItem('rasoi_user', params.get('user'));
    history.replaceState(null, '', window.location.pathname);
  }

  var name = localStorage.getItem('rasoi_user');
  var authEl = document.querySelector('.nav-auth');
  if (!authEl) return;

  if (name) {
    authEl.outerHTML =
      '<div class="nav-auth-user">' +
        '<span class="nav-welcome">Welcome, ' + name + '</span>' +
        '<button class="nav-signout" onclick="rasoiSignOut()">Sign Out</button>' +
      '</div>';
  }
});

function rasoiSignOut() {
  localStorage.removeItem('rasoi_user');
  window.location.href = 'signin.php';
}
