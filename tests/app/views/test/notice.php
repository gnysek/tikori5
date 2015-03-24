<?php
$username = 'joe';        // in real life this would be from $_SESSION

// and then much further down in the code...

if ($usernmae) {            // typo, $usernmae expands to null
    echo "Logged in";
}
else {
    echo "Please log in...";
}
