<?php
// Debug information
echo 'meow';
echo "Access type: " . $_GET['access'] . "\n";
echo "Requested file: " . $_GET['file'] ?? 'N/A';
