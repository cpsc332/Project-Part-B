<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['lang'])) {
    $lang = ($_GET['lang'] === 'es') ? 'es' : 'en';
    $_SESSION['lang'] = $lang;
}

$lang = $_SESSION['lang'] ?? 'en';

$translations = [
    'en' => [
        'site_title'        => 'Theatre Booking',
        'nav_home'          => 'Home',
        'nav_movies'        => 'Browse Movies',
        'nav_my_tickets'    => 'My Tickets',
        'nav_reports'       => 'Manager Dashboard',
        'nav_login'         => 'Log in',
        'nav_logout'        => 'Log out',
        'nav_register'      => 'Register',
        'lang_label'        => 'Language',
        'home_heading'      => 'Theatre Booking | Part C',
        'db_status'         => 'Database Status',
        'movies_heading'    => 'Browse Movies',
        'search_title'      => 'Search by Title:',
        'showtimes_heading' => 'Showtimes',
        'select_seats'      => 'Select Seats',
        'login_heading'     => 'Log in',
        'register_heading'  => 'Create an Account',
        'reports_heading'   => 'Theatre Manager Dashboard',
        'top_movies_chart'  => 'Top Movies (Last 30 Days)',
        'util_chart'        => 'Theatre Utilization (Next 7 Days)',
    ],
    'es' => [
        'site_title'        => 'Sistema de Boletos',
        'nav_home'          => 'Inicio',
        'nav_movies'        => 'Películas',
        'nav_my_tickets'    => 'Mis boletos',
        'nav_reports'       => 'Panel del gerente',
        'nav_login'         => 'Iniciar sesión',
        'nav_logout'        => 'Cerrar sesión',
        'nav_register'      => 'Registrarse',
        'lang_label'        => 'Idioma',
        'home_heading'      => 'Sistema de reservación de cines',
        'db_status'         => 'Estado de la base de datos',
        'movies_heading'    => 'Películas',
        'search_title'      => 'Buscar por título:',
        'showtimes_heading' => 'Funciones',
        'select_seats'      => 'Seleccionar asientos',
        'login_heading'     => 'Iniciar sesión',
        'register_heading'  => 'Crear cuenta',
        'reports_heading'   => 'Panel del gerente del teatro',
        'top_movies_chart'  => 'Películas más populares (30 días)',
        'util_chart'        => 'Ocupación del teatro (7 días)',
    ],
];

function t(string $key): string
{
    global $translations, $lang;
    return $translations[$lang][$key] ?? $key;
}
