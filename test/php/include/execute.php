<?php
echo chr(27)."[2K\rRunning test $microlang_test_number:$microlang_test_count";
$microlang_reserved = [ 'input', 'output', 'execute', 'clean', 'error' ];
checkvars( $microlang_reserved, $input, 'input' );
$microlang_io = $input;
$error = microlang( $code, $microlang_io );
$output = $microlang_io;
checkvars( $microlang_reserved, $output, 'code' );
foreach( $output as $microlang_var => $microlang_value )
{
    $$microlang_var = $output[$microlang_var];
}