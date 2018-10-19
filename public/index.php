<?php

use Symfony\Component\HttpFoundation\Request;
use App\Kernel;

require __DIR__.'/../src/Kernel.php';

$kernel = new Kernel('dev', true);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
