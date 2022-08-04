<?php
define( 'ROOT_PATH', __DIR__ );
define( 'SCRP_PATH', __FILE__ );

require_once ROOT_PATH . "/include/test.php";



//
// Empty code
//



$input = [];
$code = "";
require $execute;
do_assert( @$output === [] );
require $clean;



$input = [];
$code = "   \n\n  \n ";
require $execute;
do_assert( @$output === [] );
require $clean;



$input = [ 'a' => 1 ];
$code = "   \n\n  \n ";
require $execute;
do_assert( @$a === 1 );
require $clean;



//
// Type detection of input data
//



$input = [ 'a' => 'text', 'b' => 10, 'c' => 12.3 ];
$code = "ta = typeof( a )\n";
$code.= "tb = typeof( b )\n";
$code.= "tc = typeof( c )\n";
require $execute;
do_assert( @$ta === 'string' && @$tb === 'int' && @$tc === 'float' );
require $clean;



$input = [ 'a' => true, 'b' => 1, 'c' => 1 ]; // boolean not allowed
$code = "ta = typeof( a )\n";
$code.= "tb = typeof( b )\n";
$code.= "tc = typeof( c )\n";
require $execute;
do_assert();
require $clean;



//
// Type casting
//



$input = [ 'a' => 10 ];
$code = "a = float( a )\n"; // allowed
require $execute;
do_assert( @$a === 10.0 );
require $clean;



$input = [ 'a' => 12.3 ];
$code = "a = int( a )\n"; // not allowed
require $execute;
do_assert();
require $clean;



$input = [ 'a' => 1 ];
$code = "b = \"foo\"\n";
$code.= "a = b\n"; // type change not allowed
require $execute;
do_assert();
require $clean;



$input = [];
$code = "s = \"10.8\"\n";
$code.= "stoi = int(    s )\n";
$code.= "stof = float(  s )\n";
$code.= "stos = string( s )\n";
$code.= "i = 10E2\n";
$code.= "itoi = int(    i )\n";
$code.= "itof = float(  i )\n";
$code.= "itos = string( i )\n";
$code.= "f = 10.0e-2\n";
$code.= "ftoi = int(    f )\n";
$code.= "ftof = float(  f )\n";
$code.= "ftos = string( f )\n";
require $execute;
do_assert( @$stoi === 10 && @$stof === 10.8 && @$stos === "10.8" && @$itoi === 1000 && @$itof === 1000.0 && @$itos === "1000" && @$ftoi === 0 && @$ftof === 0.1 && @$ftos === "0.1" );
require $clean;





//
//
//

echo chr(27)."[2K\rAll test passed!\n";


