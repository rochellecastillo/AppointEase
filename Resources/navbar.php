<nav class="navbar navbar-expand-sm bg-primary navbar-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="#"><img src="Resources/Images/logo.png" height="30px" alt="AppointEase"></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#collapsibleNavbar">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="collapsibleNavbar">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" href="main.php">Home</a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">User</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="adminadduser.php">Add</a></li>
            <li><a class="dropdown-item" href="adminshowusers.php">Show</a></li>
          </ul>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="appointmentlist.php">Appointment</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="about.php">About Us</a>
        </li>
      </ul>
    </div>
    <div class="collapse navbar-collapse" id="collapsibleNavbar">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link" href="Resources/logout.php">Logout</a>
        </li>
      </ul>
    </div>
  </div>
</nav>