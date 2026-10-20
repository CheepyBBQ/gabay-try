<?php
require __DIR__ . '/includes/bootstrap.php';
if (is_post()) log_out();
redirect('index.php');
