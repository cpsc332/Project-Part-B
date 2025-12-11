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
       <nav>
            <a href="index.php">Homepage</a>
            <a href="movies.php">Movies</a>
            <a href="my_tickets.php">Tickets</a>
            <a href="reports.php">Reports</a>
            ' . $authLinks . '
       </nav>
  </header>
	';
} ?>

