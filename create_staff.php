<?php
require 'vendor/autoload.php';
require 'config/bootstrap.php';

$kernel = new App\Kernel('dev', false);
$kernel->boot();
$container = $kernel->getContainer();

$em = $container->get('doctrine.orm.entity_manager');
$passwordHasher = $container->get('security.password_hasher');

$user = new App\Entity\User();
$user->setUsername('staff@test.com');
$user->setEmail('staff@test.com');
$user->setFirstName('Staff');
$user->setLastName('Member');
$user->setRoles(['ROLE_STAFF']);
$user->setIsVerified(true);
$hashedPassword = $passwordHasher->hashPassword($user, 'password123');
$user->setPassword($hashedPassword);

$em->persist($user);
$em->flush();

echo 'Staff user created with ID: ' . $user->getId();
