<?php
require __DIR__ . '/../includes/bootstrap.php';
require_role('student');
redirect('student/dashboard.php#toolkit');   // the toolkit now lives on the dashboard
