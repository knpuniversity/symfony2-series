<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;

umask(0000);

$loader = require_once __DIR__.'/app/bootstrap.php.cache';
Debug::enable();

require_once __DIR__.'/app/AppKernel.php';

$kernel = new AppKernel('dev', true);
$kernel->loadClassCache();
$request = Request::createFromGlobals();
$kernel->boot();

$container = $kernel->getContainer();
$container->enterScope('request');
$container->set('request', $request);

// all our setup is done!!!!!!

$em = $container->get('doctrine')
    ->getManager()
;

$user = $em
    ->getRepository('UserBundle:User')
    ->findOneBy(array('username' => 'wayne'))
;

$user->setPlainPassword('newpass');
$em->persist($user);
$em->flush();
