<?php
$path = realpath(dirname(__FILE__));
require_once("{$path}/find_job.php");

new Find_job("{$path}/config.json");
