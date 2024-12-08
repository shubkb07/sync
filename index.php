<?php
$_URI=explode('/', trim($_GET['path'], '/'));
echo json_encode($_URI);