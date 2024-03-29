# microlang - a minimal language
### provided with JavaScript and Php interpreters



`language version 1.3`

`interpreters version 1.3.5`

## &nbsp;
&nbsp;

**microlang** is a minimal language intended primarly for small algorithms for basic string manipulation and math; it is designed to be executed via interpreter by the browser or server side (php).

&nbsp;

### Incorporating in your project

**JavaScript:**

let the script `microlang.js` be loaded by the browser. The function `microlang` will be available in the global scope.

Alternatively grab the function `microlang()` and place it where is more convenient for you.

Usage:

```js
var code,    // microlang code to be executed
    error,   // error eventually occurred during parsing or execution
    io,      // input/output variables
    result;  // the final result
    
io =
{
    'a': 10,
    'b': 20
};

code = "int c\n";
code+= "c = a + b";

var error = microlang( code, io ); // execute

if( error !== '' ) // provide a mean to handle errors
{
    throw '[MICROLANG]: ' + error;
}

result = io['c']; // catch the result
```

To inject microlang code in a web page to let it be executed by the JavaScript interpreter you may proceed as follow (Php example):

let `$code` is the variable where the code is stored...

```<?php
$code = str_replace( "\r\n", "\n" );
$code = str_replace( "\n", '\n', $code );
$code = str_replace( '"' , '\"', $code );
echo "<script type='text/javascript'>\nvar code=\"$code\";\n</script>\n";
```

**Php:**

Just `require_once "path_to/microlang.php";` in the file(s) where you need the interpreter.

```<?php
$io =
[
    'a' => 10,
    'b' => 20
];

$code = "int c\n";
$code = "c = a + b";

$error = microlang( code, io );

$result = $io['c'];
```

&nbsp;

### Interpreter usage

The interpreter is invoked by calling the function `microlang()`

**Php:**

```<?php
$error = microlang( $code, &$io, $options );
```

where `$code` is the variable where the microlang program is stored

`$io` is a key-value pair array that hold the input variables passed to the program and the output variables at the end of execution.

The array can be empty, allowed types for the values passed are `integer`, `float` or `string`.

`$error` is an empty string if no errors occurrs or a description of the error occurred elsewhere.

`$options` parameter is optional and allows defining the interpreter behaviour.

When passed to the function `$options` must be a key-value pair array. If a key is not specified its value fallbacks to default:

```
$options =
[
    'max_str_len'       =>  1048576,
    'max_iterations'    =>     1000,
    'action'            => 'execute'
];
```

Similarly...

&nbsp;

**JavaScript:**   

```js
error = microlang( code, io, options );
```

`io` and `options` (if passed) are objects.

```
options =
{
    'max_str_len':        1048576,
    'max_iterations':        1000,
    'action':            'execute'
};
```

&nbsp;

**Interpreter options**

`max_str_len` is the maximum length allowed for a string.

`max_iterations` is the maximum number of times control statements alter the execution of the program. Every time `if`, `goto` and `gosub` statements are executed a counter is incremented. If the counter exceeds the maximum number of iterations allowed the program is halted with an error. Setting this limit avoids infinite loops.

The value can be set to zero `0` to allow for an indefinite number of iterations.

&nbsp;

**Static analysis to early detection of errors**

`action` is a string whose value can be `"execute"` (default) or `"analyze"`.

Passing `execute` the code is immediately executed.

Passing `analyze` the interpreter does a static analysis of the code detecting syntax errors, use of undefined variables, value-type errors.

The code is not executed, the returned `error` variable is inspected for a early detect of errors in the code.

The `io` array(**php**) **/** object(**js**) must be passed. The values of the variable passed is not relevant but the type must be the same as when the code will be executed.


&nbsp;

### Language specifications


**Variable declaration:**

Before a variable is used it must be declared and set to a value; variable types are `int`, `float`, `string`.

```
int a,b,c

a = 10
b = 20
c = a + b
```

An initial value can be set upon declaration of single variable:

```
int txt = "foo bar"
```

Variables names are case sensitive.

Allowed characters are `azAZ09_$` variable name must not start with a number.

Variable type cannot be changed later; only exception to this rule are `int`s that can be coverted to `float` by assigning them a float number.

```
int a = 1
a = float( a )
``` 

&nbsp;

**Variable value assignment:**

```
a = "text" // string
b = 123 // integer
c = 12.3 // float
```


String can be up to 1048576 characters long.

Integers must fit a 64 bit signed integer.

Float must fit a 64 bit double.

To let a literal number be considered float a decimal separator must be included, ex: `4` is `int` while `4.0` is `float`

If any, the input variables passed as `io` parameters when invoking the script are considered both assigned in type and value; those variables must conform to the rules that apply to variables declared inside the **microlang** program.

&nbsp;

**Escape characters in string literals**

```
lf = "\n"
cr = "\r"
tab ="\t"
backslash = "\\"
double_quotes = "\""
```

&nbsp;


**Casting:**

```
a = "123"
b = int( a ) // b = 123
```

```
a = 123
b = string( a ) // b = "123"
```

```
a = "12.3"
b = float( a ) // b = 12.3
```

Casting success or failure can be detected by inspecting the variable `cast_failed` whose value can be `0` (failed) or `1` (success).

```
a = "1b"
b = int( a ) 
// b = 0
// cast_failed = 1
```

&nbsp;

**Math:**

```
a = b + c // sum
a = b - c // subtraction
a = b * c // multiplication
a = b / c // division, (always rounded down on integers)
a = b % c // modulo (remainder of division) - integers only
```

Both operands must be of the same type

&nbsp;

**String manipulation:**

```
a = b + c // concatenation (where `b` and `c` are strings)

                            // substr php style
a = substring( b, 3, 5 )    // 5 characters from position 3 (index 0 based)
a = substring( b, 3 )       // the characters from position 3 to the end of the string
a = substring( b, -3, 2 )   // 2 characters from 3 characters before the end of the string
a = substring( b, 3, -2 )   // the characters from position 3 to 2 charcaters before the end of the string

a = between( b, "(", ")" )  // the text between the markers `(` and `)`
a = between( b, "", "x" )   // the text between the beginning and "x"
a = between( b, "x", "" )   // the text between "x" and the end of the string

a = between( "  ()  ", "[", "]", i )
    // `i` is set to `0` if no matches are found...
a = between( "  ()  ", "(", ")", i )
    // ...`1` otherwise; `i` must be `int`     

a = trim( b )               // removes leading and trailing spaces, LFs, CRs, Tabs

idx = position( a, b )      // the starting position of `b` in `a`, -1 if not found
idx = position( a, '' )     // -1 if searching for an empty string

l = len( a )                // the length of the string
```

&nbsp;

**Type detection:**

```
a = typeof( b ) // "string", "int" or "float"
```

&nbsp;

**Flow control:**

```
loop: // `loop` is a label and marks a position in the code
goto loop // executes the code after the specified label
gosub subr // like goto, until `return` is reached
return // execution continues after the last `gosub`
if a == b then c // go to `c` if condition is met
if a == b then c else d // if condition is not met go to d
exit // terminates execution
exit "something wrong" // the specified string is returned as error
```

&nbsp;

**Conditional operators**

`==` equality

`!=` not equal

`>` `<` `>=` `<=`

&nbsp;

**Formulas and expressions**

Formulas / expression are not allowed.

Examples:

```
a = b + c + d // not allowed

x = substring( y, position( y, "text" ), 5 ) // not allowed
```

This is to be considered **not a language specification** but a limitation of the actual interpreters instead.

&nbsp;

### Example

```
// Create a string with the numbers from `a` to `b`
// separated by space

string result = ""
string t
int i,j

i = int( a )
j = int( b )

loop:
    if i > j then finish
    t = string( i )
    result = result + t
    result = result + " "
    i = i + 1
goto loop

finish:
    result = trim( result )
    exit
```

&nbsp;

**JavaScript and float input values**

When a number is passed as input value...

```
io = { 'x': y };
```

...there is no way to force `x` to be of type float despite the value of `y`.
This is due to the JavaScript language.
To solve this issue cast `x` to `float` at the beginning of the microlang program.

```
x = float( x )
``` 

&nbsp;

### Safety

In order to avoid microlang code to run indefinitely (ex. due to an infinite loop) every time `goto`, `gosub` or `if` are encountered a counter is incremented.

When the counter reach `1000` the execution is halted.

A different limit can be specified by passing a third parameter to the `microlang()` function.

Passing `0` allows unlimited number of iterations.

&nbsp;

### Testing

A test suite is provided.

By running `$ php test/php/runme.php` a set of tests is executed to ensure the php interpreter behaves correctly.

Also, by running the test the corresponding JavaScript test code is automatically generated and saved into `test/js/autogenerated/tests.js`.

To check the JavaScript interpreter first execute the Php test then open `test/js/openme.html` with your browser.

On **macOS** the php test attemp to open the js tests with **Safari** via **AppleScript** when all the test pass.

&nbsp;

### Motivation

For a website I manage I needed to give privileged users the ability to write small string manipulation algorithms.

The algorithms had to be run in a safe, sandboxed environment and shall run both on the server and on the browser.

&nbsp;
         

-

&nbsp;

  
    
### Contact

```
mailbox = "paolo.bertani"
at = "@kalei.it"
email = mailbox + at
```


&nbsp;

### FreeBSD 2-clause license


**Copyright (c) 2022, Paolo Bertani - Kalei S.r.l.**

**All rights reserved.**

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

1. Redistributions of source code must retain the above copyright notice, this
   list of conditions and the following disclaimer.
2. Redistributions in binary form must reproduce the above copyright notice,
   this list of conditions and the following disclaimer in the documentation
   and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.














