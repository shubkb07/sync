<?php

$function_files = array(
    'compat',
    'helper',
    'hook',
    'error-protection',
    'default-constants',
    'option',
    'formatting',
    'functions',
    'meta',
    'load',
);
foreach ($function_files as $file) {
    if ($file != '.' && $file != '..') {
        include_once INC_DIR . 'functions/' . $file . '.php';
    }
}
