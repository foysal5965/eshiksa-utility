<?php
// This will create a new, secure hash for the password 'password123'
$new_hash = password_hash('password123', PASSWORD_DEFAULT);

echo "Here is your new hash for 'password123':<br><br>";
echo "<strong>" . $new_hash . "</strong>";
?>