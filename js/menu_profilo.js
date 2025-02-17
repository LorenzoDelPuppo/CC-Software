document.addEventListener('DOMContentLoaded', function () {
    const userIcon = document.querySelector('.user-icon');
    const dropdownMenu = document.querySelector('.dropdown-menu');
  
    if (userIcon && dropdownMenu) {
      // Toggle del menu al click sull'icona utente
      userIcon.addEventListener('click', function (e) {
        e.stopPropagation(); // Impedisce la propagazione del click al document
        dropdownMenu.classList.toggle('show');
      });
  
      // Chiude il menu se si clicca fuori
      document.addEventListener('click', function (e) {
        if (!dropdownMenu.contains(e.target)) {
          dropdownMenu.classList.remove('show');
        }
      });
    }
  });
  