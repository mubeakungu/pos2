<?php
/* A4 invoice version of the receipt. bill.php uses the size chosen in Settings. */
$_GET["format"] = "a4";
require __DIR__."/bill.php";
