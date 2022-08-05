<?php
define( 'ROOT_PATH', __DIR__ );
define( 'SCRP_PATH', __FILE__ );

require_once ROOT_PATH . "/include/test.php";



//
// Empty code
//



// Test 1
$input = [];
$code = "";
require $execute;
do_assert( @$output === [] );
require $clean;



// Test 2
$input = [];
$code = "   \n\n  \n ";
require $execute;
do_assert( @$output === [] );
require $clean;



// Test 3
$input = [ 'a' => 1 ];
$code = "   \n\n  \n ";
require $execute;
do_assert( @$a === 1 );
require $clean;



//
// Type detection of input data
//



// Test 4
$input = [ 'a' => 'text', 'b' => 10, 'c' => 12.3 ];
$code = "ta = typeof( a )\n";
$code.= "tb = typeof( b )\n";
$code.= "tc = typeof( c )\n";
require $execute;
do_assert( @$ta === 'string' && @$tb === 'int' && @$tc === 'float' );
require $clean;



// Test 5
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



// Test 6
$input = [ 'a' => 10 ];
$code = "a = float( a )\n"; // allowed
require $execute;
do_assert( @$a === 10.0 );
require $clean;



// Test 7
$input = [ 'a' => 12.3 ];
$code = "a = int( a )\n"; // not allowed
require $execute;
do_assert();
require $clean;



// Test 8
$input = [ 'a' => 1 ];
$code = "b = \"foo\"\n";
$code.= "a = b\n"; // type change not allowed
require $execute;
do_assert();
require $clean;



// Test 9
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
// Numeric type detection
//

// Test 10
$input = [];
$code = "i = -10e2\n";
$code.= "ti= typeof( i )\n";
$code.= "f = .0\n";
$code.= "tf= typeof( f )\n";
require $execute;
do_assert( @$i === -1000 && @$ti === 'int' && @$f === 0.0 && @$tf === 'float' );
require $clean;









//
//
//

echo chr(27)."[2K\rAll test passed!\n";


