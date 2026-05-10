<?php
require_once __DIR__ . '/../../utils/session.php';
require_once __DIR__ . '/../../utils/helpers.php';

logoutUser();
flashSuccess('You have been logged out successfully.');
redirect('/pages/auth/login.php');