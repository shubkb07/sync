<?php

define ('CLASSES', INC_DIR . 'classes/');
define ('FUNCTIONS', INC_DIR . 'functions/');
define ('LIB', INC_DIR . 'lib/');

$function_files = array(
    'compat',
    'object-cache',
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
