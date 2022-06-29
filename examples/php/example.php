<?php

//
// Create a string with the numbers between `start` and `end` separated by a single space
//

define( 'ROOT', __DIR__ );
require_once ROOT . '/../../microlang.php';
$code = file_get_contents( ROOT . '/code.mlang' );

$start = 10;
$end   = 20;

$io =
[
    'start' => $start,
    'end'   => $end
];

$error = microlang( $code, $io );
if( $error !== '' )
{
    echo "$error\n";
}
else
{
    echo $io['result'] . "\n";
}
