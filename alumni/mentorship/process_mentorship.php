<?php
/**
 * PROCESS MENTORSHIP (REFACTORED)
 * Redirect to dashboard as approval logic has moved to Career Service Officer.
 */
session_start();
header("Location: ../dashboard.php");
exit();
?>