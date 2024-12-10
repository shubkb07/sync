<?php

define ('CLASSES', INC . 'classes/');

$function_files = array(
    'compat',
    'user',
    'object-cache',
    'helper',
    'hook',
    'error-protection',
    'default-constants',
    'load',
);
foreach ($function_files as $file) {
    if ($file != '.' && $file != '..') {
        include_once INC . 'functions/' . $file . '.php';
    }
}
