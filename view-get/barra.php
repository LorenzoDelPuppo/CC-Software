<link rel="stylesheet" href=".././style/barra_alta.css">
<div class="top-bar">
<script src="../CC-Software/js/menu_profilo.js"></script>
  <div class="left-section">
    <a href=".././view-get/menu.php">
      <img src=".././style/rullino/tasto_home.svg" alt="Home" class="home-button">
    </a>
  </div>
  <div class="center-section">
    <a href=".././view-get/menu.php">
      <img src=".././style/rullino/logo.png" alt="Logo" class="logo" />
    </a>
  </div>

  <div class="right-section">
    <div class="user-menu">
      <!-- Icona utente (o un'immagine) -->
      <img src=".././style/rullino/fotodefault.png" alt="User Icon" class="user-icon">

      <!-- Dropdown -->
      <div class="dropdown-menu">
        <a href=".././view-get/profilo.php" class="dropdown-item">
          <img src=".././style/rullino/profilo.svg" alt="Profilo Icon" class="logout-icon">
          Profilo
        </a>
        <a href=".././add-edit/impostazioni.php" class="dropdown-item">
          <img src=".././style/rullino/imp.svg" alt="Impostazioni Icon" class="logout-icon">
          Impostazioni
        </a>
        <br>
        <hr class="dropdown-separator">
        
        <!-- Logout con icona e testo sulla stessa riga -->
        <a href=".././add-edit/logout.php" class="dropdown-item logout-item">
          <img src=".././style/rullino/logoutr.svg" alt="Logout Icon" class="logout-icon">
          Logout
        </a>
      </div>
    </div>
  </div>
</div>
