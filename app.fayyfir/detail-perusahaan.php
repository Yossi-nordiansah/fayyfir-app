<?php
session_start();
$queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header("Location: abdul-hadi/detail-perusahaan" . $queryString);
exit();
