FLUENTA — LOGIN SETUP (PHP + MySQL on Hostinger)
=================================================

The app works without this (you'll just see the app directly). To turn ON
the login gate with real accounts stored in YOUR Hostinger database:

1) hPanel  ->  Databases  ->  MySQL Databases
     - Create a new database (note the full name, e.g. u123_fluenta)
     - Create a database user + password
     - Add that user to the database (grant all privileges)

2) Edit  api/config.php  and fill in:
     'name' => 'your_database_name',
     'user' => 'your_database_user',
     'pass' => 'your_database_password',
   (leave 'host' as 'localhost', 'driver' as 'mysql')

3) Upload. That's it — the "users" table is created automatically on the
   first sign-up. Visit the site: you'll now get the Fluenta login screen.

No MySQL? Set 'driver' => 'sqlite' in config.php and nothing else is needed
(accounts are saved in api/fluenta.sqlite). MySQL is recommended for a real app.

Passwords are stored hashed (bcrypt). Never edit fluenta.sqlite by hand.
