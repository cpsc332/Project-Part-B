<?php function theatre_header() { 
     require_once __DIR__ . '/../includes/init.php';
     $user = current_user();

     $authLinks = '';
     if ($user) {
          $authLinks = '<a href="logout.php">Logout</a>';
     } else {
          $authLinks = '<a href="login.php">Login</a>
               <a href="register.php">Register</a>';
     }

	return '
   <header id = "header-style">
     <h1>Theatre Booking</h1>
       <nav>
            <a href="index.php"><strong>Homepage</strong></a>
            <a href="movies.php"><strong>Movies</strong></a>
            <a href="my_tickets.php"><strong>Tickets</strong></a>
            <a href="reports.php"><strong>Reports</strong></a>
            <strong> '.  $authLinks  . '</strong>
       </nav>
  </header>
	';
} ?>

