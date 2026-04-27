<?php
// Hapus cookie dengan memundurkan waktu expired
setcookie('users', '', time() - 3600, "/");
setcookie('role', '', time() - 3600, "/");

header("Location: login.php?pesan=logout");
exit();
?>