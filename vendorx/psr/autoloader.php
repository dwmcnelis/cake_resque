<?php

//$path = APP . 'Vendor' . DS . 'psr';
$path = dirname(__FILE__);

$mapping = array(
  'Psr\Log\AbstractLogger' => $path . '/log/Psr/Log/AbstractLogger.php',
  'Psr\Log\InvalidArgumentException' => $path . '/log/Psr/Log/InvalidArgumentException.php',
  'Psr\Log\LoggerAwareInterface' => $path . '/log/Psr/Log/LoggerAwareInterface.php',
  'Psr\Log\LoggerAwareTrait' => $path . '/log/Psr/Log/LoggerAwareTrait.php',
  'Psr\Log\LoggerInterface' => $path . '/log/Psr/Log/LoggerInterface.php',
  'Psr\Log\LoggerTrait' => $path . '/log/Psr/Log/LoggerTrait.php',
  'Psr\Log\LogLevel' => $path . '/log/Psr/Log/LogLevel.php',
  'Psr\Log\NullLogger' => $path . '/log/Psr/Log/NullLogger.php'
);

spl_autoload_register(function ($class) use ($mapping) {
  if (isset($mapping[$class])) {
    require $mapping[$class];
  }
}, true);
