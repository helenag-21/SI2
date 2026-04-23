<?php
// new-entry.php — presmeruje na entry.php (nový zápis pre konkrétny denník)
// Tento súbor slúži ako alias, logika je v entry.php

$dennikId = $_GET['dennik'] ?? null;

if (!$dennikId) {
    header('Location: diaries.php');
    exit;
}

header("Location: entry.php?dennik=$dennikId");
exit;
