<?php

//
// FreeBSD 2-clause license
//
// microlang.php
//
// A php microlang interpreter
//
// microlang version 1.1
// php interpreter version 1.1.0
//
// Copyright (c) 2022, Paolo Bertani - Kalei S.r.l.
//
// All rights reserved.
//
// Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
//
// Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
// Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer
// in the documentation and/or other materials provided with the distribution.
// THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING,
// BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
// IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
// OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS;
// OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
// (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
//

function microlang( $code, &$vars, $max_iterations = 1000 )
{
    $keywords = [
        'goto',
        'gosub',
        'return',
        'exit',
        '=',
        'substring',
        'position',
        'between',
        'trim',
        'len',
        'int',
        'string',
        'typeof',
        '+',
        '-',
        '*',
        '/',
        '%',
        'if', 'then', 'else', '==', '!=', '>', '<', '>=', '<='
    ];

    $max_str_len = 1024*1024;

    $labels = [];
    $stack = [];

    if( ! isset( $vars ) )
    {
        $vars = [];
    }

    if( ! is_array( $vars ) )
    {
        return "input data provided is not array";
    }


    // convert windows linefeeds to unix linefeeds if any

    $code = str_replace( "\r\n", "\n", $code );


    // explode code

    $lines = explode( "\n", $code );



    // remove comments

    $n = count( $lines );
    for( $i = 0; $i < $n; $i++)
    {
        $l = $lines[$i];
        $idx = mb_strpos( $l, '//' );
        if( $idx !== false )
        {
            $l = mb_substr( $l, 0, $idx );
        }
        $l = trim( $l );
        $lines[$i] = $l;
    }


    // tokenize

    $newlines = $lines;
    $lines = [];
    $y = -1;
    $y1b = $y + 1;
    $m = count( $newlines );
    for( $k = 0; $k < $m; $k++)
    {
        $l = $newlines[ $k ];

        $y++;
        $y1b = $y + 1;

        $parts = explode( " ", $l );
        $tokens = [];
        $n = count($parts);
        for( $i = 0; $i < $n; $i++ )
        {
            $p = $parts[$i];


            // Empty space

            if( $p === '' ) continue;


            // Keywords

            if( in_array( $p, $keywords ) )
            {
                $tokens[] = ['type' => 'keyword', 'symbol' => $p, 'value' => null ];

                for( $j = $i + 1; $j < $n; $j++ )
                {
                    if( $parts[$j] === '' ) continue;
                    if( $parts[$j] === '=' ) return "keywords cannot be used for variable names: $y1b";
                    break;
                }

                continue;
            }


            // Labels

            if( substr( $p, -1, 1 ) === ":" && $i === 0 )
            {
                $p = mb_substr( $p, 0, -1 );
                if( in_array( $p, $keywords ) ) return "keywords cannot be used for label names: $y1b";
                if( ! microlang_label_is_valid( $p ) ) return "Invalid label: $y1b";
                $tokens[] = ['type' => 'label', 'symbol' => $p, 'value' => $y ];
                if( isset( $labels[$p] ) ) return "Label $p duplicate: $y1b";
                $labels[$p] = $y;
                continue;
            }

            if( substr( $p, -1, 1 ) === ":" && $i > 0 )
            {
                return "Unexpected label: $y1b";
            }


            // Strings

            if( $p === '""' )
            {
                $tokens[] = ['type' => 'string', 'symbol' => null, 'value' => "" ];
                continue;
            }

            if( substr( $p, 0, 1 ) === '"' )
            {
                if( substr( $p, -1, 1 ) === '"' && mb_strlen( $p ) > 1 )
                {
                    $tokens[] = ['type' => 'string', 'symbol' => null, 'value' => substr( $p, 1, -1 ) ];
                    continue;
                }

                $value = substr( $p, 1 );
                $closed = false;
                for( $j = $i + 1; $j < $n; $j++ )
                {
                    $pp = $parts[$j];

                    if( substr( $pp, -1, 1) == '"' )
                    {
                        $value .= ' ' . substr( $pp, 0, -1 );
                        $closed = true;
                        break;
                    }
                    $value .= ' ' . $pp;
                }
                if( ! $closed ) return "string not closed: $y1b";

                $tokens[] = ['type' => 'string', 'symbol' => null, 'value' => $value ];
                $i = $j;
                continue;
            }


            // Integers

            if( preg_match( '/^-?\d+$/', $p ) === 1 )
            {
                if( floatval( $p ) > PHP_INT_MAX || floatval( $p ) < PHP_INT_MIN ) return "overflow: $y1b";

                $tokens[] = ['type' => 'int', 'symbol' => null, 'value' => intval($p) ];
                continue;
            }


            // Floats

            if( preg_match( '/^-?\d+\.\d+$/', $p ) === 1 )
            {
                $tokens[] = ['type' => 'float', 'symbol' => null, 'value' => floatval($p) ];
                continue;
            }


            // Variable names

            if( ! microlang_label_is_valid( $p ) ) return "Invalid variable name: $y1b $p";
            $tokens[] = ['type' => 'variable', 'symbol' => $p, 'value' => null ];

        }
        $lines[] = $tokens;
    }



    // execute

    $y = 0;
    $lines_count = count( $lines );
    $iter = 0;
    $vars['cast_failed'] = 0;

    while( true )
    {
        $done = false;

        $y1b = $y + 1;

        if( $iter > $max_iterations && $max_iterations !== 0 ) return "Max iterations exceeded: $y1b";

        $tok = $lines[ $y ];
        $tn = count( $tok );

        if( $tn === 0 ) $done = true;

        if( $tn === 1 && $tok[0]['type'] === 'label' ) $done = true;

        $tokens = [];

        for( $i = 0; $i < $tn; $i++ )
        {
            $t = $tok[ $i ];

            if( $t['type'] === 'label' || $t['type'] === 'string' || $t['type'] === 'int' || $t['type'] === 'float' || $t['type'] === 'keyword' )
            {
                $tokens[] = $t;
            }
            elseif( $t['type'] === 'variable' )
            {
                if( isset( $vars[$t['symbol'] ] ) )
                {
                    $t['value'] = $vars[$t['symbol']];
                    $tokens[] = $t;
                }
                elseif( isset( $labels[$t['symbol']]) )
                {
                    $t['value'] = $labels[$t['symbol']];
                    $tokens[] = $t;
                }
                else
                {
                    $t['value'] = null;
                    $tokens[] = $t;
                }
            }
        }

        $t1t = null; $t2t = null; $t3t = null; $t4t = null; $t5t = null; $t6t = null; $t7t = null; $t8t = null;
        $t1s = null; $t2s = null; $t3s = null; $t4s = null; $t5s = null; $t6s = null; $t7s = null; $t8s = null;
        $t1v = null; $t2v = null; $t3v = null; $t4v = null; $t5v = null; $t6v = null; $t7v = null; $t8v = null;

        if( $tn > 0 ) { $t1t = $tokens[0]['type']; $t1s = $tokens[0]['symbol']; $t1v = $tokens[0]['value']; }
        if( $tn > 1 ) { $t2t = $tokens[1]['type']; $t2s = $tokens[1]['symbol']; $t2v = $tokens[1]['value']; }
        if( $tn > 2 ) { $t3t = $tokens[2]['type']; $t3s = $tokens[2]['symbol']; $t3v = $tokens[2]['value']; }
        if( $tn > 3 ) { $t4t = $tokens[3]['type']; $t4s = $tokens[3]['symbol']; $t4v = $tokens[3]['value']; }
        if( $tn > 4 ) { $t5t = $tokens[4]['type']; $t5s = $tokens[4]['symbol']; $t5v = $tokens[4]['value']; }
        if( $tn > 5 ) { $t6t = $tokens[5]['type']; $t6s = $tokens[5]['symbol']; $t6v = $tokens[5]['value']; }
        if( $tn > 6 ) { $t7t = $tokens[6]['type']; $t7s = $tokens[6]['symbol']; $t7v = $tokens[6]['value']; }
        if( $tn > 7 ) { $t8t = $tokens[7]['type']; $t8s = $tokens[7]['symbol']; $t8v = $tokens[7]['value']; }



        // Goto

        if( ! $done && $tn === 2 && $t1t === 'keyword' && $t1s === 'goto' && $t2t === 'variable' )
        {
            $done = true;

            $label = $t2s;

            if( isset( $labels[$label] ) )
            {
                $iter++;
                $y = $labels[$label];
                continue;
            }
            else return "Unknown label $label: $y1b";
        }


        // Gosub

        if( ! $done && $tn === 2 && $t1t === 'keyword' && $t1s === 'gosub' && $t2t === 'variable' )
        {
            $done = true;

            $label = $t2s;

            if( isset( $labels[$label] ) )
            {
                $stack[] = $y;
                $iter++;
                $y = $labels[$label];
                continue;
            }
            else return "Unknown label $label: $y1b";
        }


        // Return

        if( ! $done && $tn === 1 && $t1t === 'keyword' && $t1s === 'return' )
        {
            $done = true;

            if( count( $stack ) === 0 ) return "return without gosub: $y1b\n";

            $y = array_pop( $stack );
        }


        // Exit

        if( ! $done && $tn === 1 && $t1t === 'keyword' && $t1s === 'exit' )
        {
            $done = true;

            break;
        }


        // Exit with error message

        if( ! $done && $tn === 2 && $t1t === 'keyword' && $t1s === 'exit' && microlang_vsn( $t2t ) )
        {
            return $t3v . '';
        }


        // = Assignment

        if( ! $done && $tn === 3 && $t1t === 'variable' && $t2t === 'keyword' && $t2s === '=' && microlang_vsn( $t3t ) )
        {
            if( $t1t === 'keyword' ) return "keywords cannot be used for variable names";

            if( $t3v === null ) return "undefined variable: $y1b";

            if( isset( $vars[$t1s] ) && gettype( $vars[$t1s] ) !== gettype( $t3v ) ) return "variable cannot change type: $y1b";

            $vars[$t1s] = $t3v;

            $done = true;
        }


        // Substring

        if( ! $done && $tn === 6 && $t1t === 'variable' && $t2t === 'keyword' && $t2s === '=' && $t3t === 'keyword' && $t3s === 'substring' && microlang_vsn( $t4t, $t5t, $t6t ) )
        {
            $err = microlang_chk( "SII", $y1b, $t4s, $t4v, $t5s, $t5v, $t6s, $t6v ); if( $err !== '' ) return $err;

            if( $t5v < 0 || $t6v < 0 ) return "substring accepts only positive index and length";

            $vars[$t1s] = mb_substr( $t4v, $t5v, $t6v );

            $done = true;
        }


        // Position

        if( ! $done && $tn === 5 && $t1t === 'variable' && $t2t === 'keyword' && $t2s === '=' && $t3t === 'keyword' && $t3s === 'position' && microlang_vsn( $t4t, $t5t ) )
        {
            $err = microlang_chk( "SS", $y1b, $t4s, $t4v, $t5s, $t5v ); if( $err !== '' ) return $err;

            $vars[$t1s] = mb_strpos( $t4v, $t5v );

            if( $vars[$t1s] === false ) $vars[$t1s] = -1;

            $done = true;
        }


        // Replace

        if( ! $done && $tn === 6 && $t1t === 'variable' && $t2t === 'keyword' && $t2s === '=' && $t3t === 'keyword' && $t3s === 'replace' && microlang_vsn( $t4t, $t5t, $t6t ) )
        {
            $err = microlang_chk( "SSS", $y1b, $t4s, $t4v, $t5s, $t5v, $t6s, $t6v ); if( $err !== '' ) return $err;

            $vars[$t1s] = str_replace( $t5v, $t6v, $t4v );

            if( mb_strlen( $vars[$t1s] ) > $max_str_len ) return "string too long: $y1b";

            $done = true;
        }


        // Between

        if( ! $done && $tn === 6 && $t1t === 'variable' && $t2t === 'keyword' && $t2s === '=' && $t3t === 'keyword' && $t3s === 'between' && microlang_vsn( $t4t, $t5t, $t6t ) )
        {
            $err = microlang_chk( "SSS", $y1b, $t4s, $t4v, $t5s, $t5v, $t6s, $t6v ); if( $err !== '' ) return $err;

            if( $t5v === '' ) { $i1 = 0; }                 else { $i1 = mb_strpos( $t4v, $t5v ); }

            if( $t6v === '' ) { $i2 = mb_strlen( $t5v ); } else { $i2 = mb_strpos( $t4v, $t6v ); }

            $i1 = mb_strpos( $t4v, $t5v );
            $i2 = mb_strpos( $t4v, $t6v );

            if( $i1 === false || $i2 === false || $i2 < $i1 )
            {
                $vars[$t1s] = "";
            }
            else
            {
                $vars[$t1s] = mb_substr( $t4v, $i1 + mb_strlen( $t5v ), $i2 - $i1 - mb_strlen( $t5v ) );
            }

            $done = true;
        }


        // Trim

        if( ! $done && $tn === 4 && $t1t === 'variable' && $t2t === 'keyword' && $t2s === '=' && $t3t === 'keyword' && $t3s === 'trim' && microlang_vsn( $t4t ) )
        {
            $err = microlang_chk( "SS", $y1b, $t4s, $t4v ); if( $err !== '' ) return $err;

            $vars[$t1s] = trim( $t4v, " " );

            $done = true;
        }



        // Len

        if( ! $done && $tn === 4 && $t1t === 'variable' && $t2t === 'keyword' && $t2s === '=' && $t3t === 'keyword' && $t3s === 'len' && microlang_vsn( $t4t ) )
        {
            $err = microlang_chk( "S", $y1b, $t4s, $t4v ); if( $err !== '' ) return $err;

            $vars[$t1s] = mb_strlen( $t4v );

            $done = true;
        }


        // Typeof

        if( ! $done && $tn === 4 && $t1t === 'variable' && $t2t === 'keyword' && $t2s === '=' && $t3t === 'keyword' && $t3s === 'typeof' && microlang_vsn( $t4t ) )
        {
            $err = microlang_chk( "X", $y1b, $t4s, $t4v ); if( $err !== '' ) return $err;

            if( is_string( $t4v ) ) $vars[$t1s] = 'string';
            if( is_int( $t4v ) )    $vars[$t1s] = 'int';
            if( is_float( $t4v ) )  $vars[$t1s] = 'float';

            $done = true;
        }


        // Int

        if( ! $done && $tn === 4 && $t1t === 'variable' && $t2t === 'keyword' && $t2s === '=' && $t3t === 'keyword' && $t3s === 'int' && microlang_vsn( $t4t ) )
        {
            if( is_string( $t4v ) && preg_match( '/^-?\d+$/', $t4v ) === 0 )
            {
                $vars[$t1s] = (int)0;
                $vars['cast_failed'] = 1;
            }
            else
            {
                if( floatval( $vars[$t4v] ) > PHP_INT_MAX || floatval( $vars[$t4v] ) < PHP_INT_MIN )
                {
                    $vars[$t1s] = (int)0;
                    $vars['cast_failed'] = 1;
                }
                else
                {
                    $vars[$t1s] = intval( $t4v );
                    $vars['cast_failed'] = 0;
                }
            }

            $done = true;
        }


        // Float

        if( ! $done && $tn === 4 && $t1t === 'variable' && $t2t === 'keyword' && $t2s === '=' && $t3t === 'keyword' && $t3s === 'float' && microlang_vsn( $t4t ) )
        {
            if( is_string( $t4v ) && preg_match( '/^-?\d+$/', $t4v ) === 0 && preg_match( '/^-?\d+\.\d+$/', $t4v ) === 0 )
            {
                $vars[$t1s] = (float)0;
                $vars['cast_failed'] = 1;
            }
            else
            {
                $vars[$t1s] = floatval( $t4v );
                $vars['cast_failed'] = 0;
            }

            $done = true;
        }


        // String

        if( ! $done && $tn === 4 && $t1t === 'variable' && $t2t === 'keyword' && $t2s === '=' && $t3t === 'keyword' && $t3s === 'string' && microlang_vsn( $t4t ) )
        {
            $vars[$t1s] = (string)$t4v;

            $done = true;
        }


        // + Sum

        if( ! $done && $tn === 5 && $t1t === 'variable' && $t2t === 'keyword' && $t2s === '=' && $t4t === 'keyword' && $t4s === '+' && microlang_vsn( $t3t, $t5t ) )
        {
            if( is_string( $t3v ) && is_string( $t5v ) )
            {
                $vars[$t1s] = $t3v . $t5v;

                if( mb_strlen( $vars[$t1s] ) > $max_str_len ) return "string too long: $y1b";
            }
            elseif( is_int( $t3v ) && is_int( $t5v ) )
            {
                $vars[$t1s] = $t3v + $t5v;

                if( $vars[$t1s] > PHP_INT_MAX || $vars[$t1s] < PHP_INT_MIN ) return "overflow: $y1b";
            }
            elseif( is_float( $t3v ) && is_float( $t5v ) )
            {
                $vars[$t1s] = $t3v + $t5v;
            }
            else return "values must be of the same type: $y1b";

            $done = true;
        }


        // - Diff

        if( ! $done && $tn === 5 && $t1t === 'variable' && $t2t === 'keyword' && $t2s === '=' && $t4t === 'keyword' && $t4s === '-' && microlang_vsn( $t3t, $t5t ) )
        {
            $err = microlang_chk( "NN", $y1b, $t3s, $t3v, $t5s, $t5v ); if( $err !== '' ) return $err;

            if( is_int( $t3v ) && is_int( $t5v ) )
            {
                $vars[$t1s] = $t3v - $t5v;

                if( $vars[$t1s] > PHP_INT_MAX || $vars[$t1s] < PHP_INT_MIN ) return "overflow: $y1b";
            }
            elseif( is_float( $t3v ) && is_float( $t5v ) )
            {
                $vars[$t1s] = $t3v - $t5v;
            }
            else return "values must be of the same type: $y1b";

            $done = true;
        }


        // * Mult

        if( ! $done && $tn === 5 && $t1t === 'variable' && $t2t === 'keyword' && $t2s === '=' && $t4t === 'keyword' && $t4s === '*' && microlang_vsn( $t3t, $t5t ) )
        {
            $err = microlang_chk( "NN", $y1b, $t3s, $t3v, $t5s, $t5v ); if( $err !== '' ) return $err;

            if( is_int( $t3v ) && is_int( $t5v ) )
            {
                $vars[$t1s] = $t3v * $t5v;

                if( $vars[$t1s] > PHP_INT_MAX || $vars[$t1s] < PHP_INT_MIN ) return "overflow: $y1b";
            }
            elseif( is_float( $t3v ) && is_float( $t5v ) )
            {
                $vars[$t1s] = $t3v * $t5v;
            }
            else return "values must be of the same type: $y1b";

            $done = true;
        }


        // / Div

        if( ! $done && $tn === 5 && $t1t === 'variable' && $t2t === 'keyword' && $t2s === '=' && $t4t === 'keyword' && $t4s === '/' && microlang_vsn( $t3t, $t5t ) )
        {
            $err = microlang_chk( "NN", $y1b, $t3s, $t3v, $t5s, $t5v ); if( $err !== '' ) return $err;

            if( $t5v === 0 ) return "division by zero: $y1b";

            if( is_int( $t3v ) && is_int( $t5v ) )
            {
                $vars[$t1s] = $t3v / $t5v;

                if( $vars[$t1s] > PHP_INT_MAX || $vars[$t1s] < PHP_INT_MIN ) return "overflow: $y1b";

                $vars[$t1s] = intdiv( $t3v, $t5v );
            }
            elseif( is_float( $t3v ) && is_float( $t5v ) )
            {
                $vars[$t1s] = $t3v / $t5v;
            }
            else return "values must be of the same type: $y1b";

            $done = true;
        }


        // % Modulo

        if( ! $done && $tn === 5 && $t1t === 'variable' && $t2t === 'keyword' && $t2s === '=' && $t4t === 'keyword' && $t4s === '%' && microlang_vsn( $t3t, $t5t ) )
        {
            $err = microlang_chk( "II", $y1b, $t3s, $t3v, $t5s, $t5v ); if( $err !== '' ) return $err;

            if( $t5v === 0 ) return "division by zero: $y1b";

            $vars[$t1s] = $t3v % $t5v;

            $done = true;
        }


        // If Then [Else]

        if( ! $done && ( $tn === 6 || $tn === 8 ) && $t1t === 'keyword' && $t1s === 'if' && microlang_vsn( $t2t, $t4t ) &&
           $t3t === 'keyword' && ( $t3s === '==' || $t3s === '!=' || $t3s === '>' || $t3s === '<' || $t3s === '>=' || $t3s === '<=' )  &&
               $t5t === 'keyword' && $t5s === 'then' && $t6t === 'variable' )
        {
            if( $tn === 8 )
            {
                if( $t7t === 'keyword' && $t7s === 'else' && $t8t === 'variable' )
                {
                    // ok
                } else return "syntax error: $y1b";
            }

            $err = microlang_chk( "XX", $y1b, $t2s, $t2v, $t4s, $t4v ); if( $err !== '' ) return $err;

            if( gettype( $t2v ) !== gettype( $t4v) ) return "values must be of the same type: $y1b";

            if( $t6v === null ) return "undefined label $t6s: $y1b";
            if( $tn === 8 && $t8v === null ) return "undefined label $t8s: $y1b";

            if( $t3s === '==' )
            {
                if( $t2v == $t4v )
                {
                    $y = $t6v;
                    $iter++;
                    continue;
                }
                else
                {
                    if( $t8v !== null )
                    {
                        $y = $t8v;
                        $iter++;
                        continue;
                    }
                    else
                    {
                        $done = true;
                    }
                }
            }

            if( $t3s === '!=' )
            {
                if( $t2v != $t4v )
                {
                    $y = $t6v;
                    $iter++;
                    continue;
                }
                else
                {
                    if( $t8v !== null )
                    {
                        $y = $t8v;
                        $iter++;
                        continue;
                    }
                    else
                    {
                        $done = true;
                    }
                }
            }

            if( $t3s === '>' )
            {
                if( $t2v > $t4v )
                {
                    $y = $t6v;
                    $iter++;
                    continue;
                }
                else
                {
                    if( $t8v !== null )
                    {
                        $y = $t8v;
                        $iter++;
                        continue;
                    }
                    else
                    {
                        $done = true;
                    }
                }
            }

            if( $t3s === '<' )
            {
                if( $t2v < $t4v )
                {
                    $y = $t6v;
                    $iter++;
                    continue;
                }
                else
                {
                    if( $t8v !== null )
                    {
                        $y = $t8v;
                        $iter++;
                        continue;
                    }
                    else
                    {
                        $done = true;
                    }
                }
            }

            if( $t3s === '<=' )
            {
                if( $t2v <= $t4v )
                {
                    $y = $t6v;
                    $iter++;
                    continue;
                }
                else
                {
                    if( $t8v !== null )
                    {
                        $y = $t8v;
                        $iter++;
                        continue;
                    }
                    else
                    {
                        $done = true;
                    }
                }
            }

            if( $t3s === '>=' )
            {
                if( $t2v >= $t4v )
                {
                    $y = $t6v;
                    $iter++;
                    continue;
                }
                else
                {
                    if( $t8v !== null )
                    {
                        $y = $t8v;
                        $iter++;
                        continue;
                    }
                    else
                    {
                        $done = true;
                    }
                }
            }
        }

        if( ! $done )
        {
            return "error: $y1b";
        }

        $y++;

        if( $y >= $lines_count )
        {
            break;
        }
    }

    return "";
}



function microlang_label_is_valid( $lab )
{
    $n = mb_strlen( $lab );
    if( $n === 0 )
    {
        return false;
    }
    for( $i = 0; $i < $n; $i++ )
    {
        $c = mb_substr( $lab, $i, 1 );

        if( $i === 0 && mb_strpos( "abcdefghjkilmnopqrtsuvwxyzABCDEFGHJKILMNOPQRSTUVWXYZ_", $c ) !== false ) continue;
        if( $i >   0 && mb_strpos( "abcdefghjkilmnopqrtsuvwxyzABCDEFGHJKILMNOPQRSTUVWXYZ_0123456789", $c ) !== false ) continue;

        return false;
    }
    return true;
}



function microlang_vsn( $t1 = null, $t2 = null, $t3 = null, $t4 = null, $t5 = null, $t6 = null )
{
    if( ( $t1 === null || $t1 === 'variable' || $t1 === 'string' || $t1 === 'int' || $t1 === 'float' ) &&
        ( $t2 === null || $t2 === 'variable' || $t2 === 'string' || $t2 === 'int' || $t2 === 'float' ) &&
        ( $t3 === null || $t3 === 'variable' || $t3 === 'string' || $t3 === 'int' || $t3 === 'float' ) &&
        ( $t4 === null || $t4 === 'variable' || $t4 === 'string' || $t4 === 'int' || $t4 === 'float' ) &&
        ( $t5 === null || $t5 === 'variable' || $t5 === 'string' || $t5 === 'int' || $t5 === 'float' ) &&
        ( $t6 === null || $t6 === 'variable' || $t6 === 'string' || $t6 === 'int' || $t6 === 'float' ) ) return true;
    return false;
}



function microlang_chk( $types, $line, $s1 = null, $v1 = null,
                                       $s2 = null, $v2 = null,
                                       $s3 = null, $v3 = null,
                                       $s4 = null, $v4 = null,
                                       $s5 = null, $v5 = null,
                                       $s6 = null, $v6 = null )
{
    $types = " $types";

    if( strlen( $types ) > 1 )
    {
        $t = $types[1];
        $v = $v1;
        $s = $s1;
        $n = 1;

        if( $v === null ) return "undefined variable $s: $line";
        if( $t === 'S' && ! is_string( $v ) ) return "parameter $n must be string: $line";
        if( $t === 'I' && ! is_int( $v )    ) return "parameter $n must be integer: $line";
        if( $t === 'F' && ! is_float( $v )  ) return "parameter $n must be float: $line";
        if( $t === 'N' && ! is_float( $v ) && ! is_int( $v ) ) return "parameter $n must be integer or float: $line";
    }

    if( strlen( $types ) > 2 )
    {
        $t = $types[2];
        $v = $v2;
        $s = $s2;
        $n = 2;

        if( $v === null ) return "undefined variable $s: $line";
        if( $t === 'S' && ! is_string( $v ) ) return "parameter $n must be string: $line";
        if( $t === 'I' && ! is_int( $v )    ) return "parameter $n must be integer: $line";
        if( $t === 'F' && ! is_float( $v )  ) return "parameter $n must be float: $line";
        if( $t === 'N' && ! is_float( $v ) && ! is_int( $v ) ) return "parameter $n must be integer or float: $line";
    }

    if( strlen( $types ) > 3 )
    {
        $t = $types[3];
        $v = $v3;
        $s = $s3;
        $n = 3;

        if( $v === null ) return "undefined variable $s: $line";
        if( $t === 'S' && ! is_string( $v ) ) return "parameter $n must be string: $line";
        if( $t === 'I' && ! is_int( $v )    ) return "parameter $n must be integer: $line";
        if( $t === 'F' && ! is_float( $v )  ) return "parameter $n must be float: $line";
        if( $t === 'N' && ! is_float( $v ) && ! is_int( $v ) ) return "parameter $n must be integer or float: $line";
    }

    if( strlen( $types ) > 4 )
    {
        $t = $types[4];
        $v = $v4;
        $s = $s4;
        $n = 4;

        if( $v === null ) return "undefined variable $s: $line";
        if( $t === 'S' && ! is_string( $v ) ) return "parameter $n must be string: $line";
        if( $t === 'I' && ! is_int( $v )    ) return "parameter $n must be integer: $line";
        if( $t === 'F' && ! is_float( $v )  ) return "parameter $n must be float: $line";
        if( $t === 'N' && ! is_float( $v ) && ! is_int( $v ) ) return "parameter $n must be integer or float: $line";
    }

    if( strlen( $types ) > 5 )
    {
        $t = $types[5];
        $v = $v5;
        $s = $s5;
        $n = 5;

        if( $v === null ) return "undefined variable $s: $line";
        if( $t === 'S' && ! is_string( $v ) ) return "parameter $n must be string: $line";
        if( $t === 'I' && ! is_int( $v )    ) return "parameter $n must be integer: $line";
        if( $t === 'F' && ! is_float( $v )  ) return "parameter $n must be float: $line";
        if( $t === 'N' && ! is_float( $v ) && ! is_int( $v ) ) return "parameter $n must be integer or float: $line";
    }

    if( strlen( $types ) > 6 )
    {
        $t = $types[6];
        $v = $v6;
        $s = $s6;
        $n = 6;

        if( $v === null ) return "undefined variable $s: $line";
        if( $t === 'S' && ! is_string( $v ) ) return "parameter $n must be string: $line";
        if( $t === 'I' && ! is_int( $v )    ) return "parameter $n must be integer: $line";
        if( $t === 'F' && ! is_float( $v )  ) return "parameter $n must be float: $line";
        if( $t === 'N' && ! is_float( $v ) && ! is_int( $v ) ) return "parameter $n must be integer or float: $line";
    }

    return "";
}