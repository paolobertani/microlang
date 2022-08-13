<?php
echo chr(27)."[2K\rRunning test $microlang_test_number:$microlang_test_count";
$microlang_reserved = [ 'input', 'output', 'execute', 'clean', 'error', 'stage' ];
check_vars( $microlang_reserved, $input, 'input' );
$microlang_io = $input;
$stage = 'analyzing';
$error = microlang( $code, $microlang_io, [ 'action' => 'analyze' ] );
if( $error === '' )
{
    $microlang_io = $input;
    $stage = 'executing';
    $error = microlang( $code, $microlang_io );
    $output = $microlang_io;
    check_vars( $microlang_reserved, $output, 'code' );
    foreach( $output as $microlang_var => $microlang_value )
    {
        $$microlang_var = $output[$microlang_var];
    }
}
