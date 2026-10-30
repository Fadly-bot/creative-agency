<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
$checks=[
  [e('<script>')==='&lt;script&gt;','HTML escaping'],
  [status_label('in_progress')==='Dikerjakan','status label'],
  [money(1500000)==='Rp 1.500.000','currency formatting'],
  [password_verify('demo-password',password_hash('demo-password',PASSWORD_DEFAULT)),'password hash/verify'],
];
$failed=false;
foreach($checks as [$passed,$label]){echo ($passed?'PASS':'FAIL').' '.$label.PHP_EOL;$failed=$failed||!$passed;}
exit($failed?1:0);
