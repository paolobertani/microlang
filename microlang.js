//
// FreeBSD 2-clause license
//
// microlang.js
//
// A javascript microlang interpreter
//
// microlang version 1.1
// js intepreter version 1.1.1
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

function microlang( code, vars, max_iterations )
{
    var keywords,
        max_str_len,
        labels,
        stack,
        lines,
        typs,
        key,
        value,
        i,
        n,
        idx,
        newlines,
        t,
        y,
        y1b,
        m,
        k,
        parts,
        tokens,
        p,
        lines_count,
        iter,
        done,
        tok,
        tn,
        t1t,
        t2t,
        t3t,
        t4t,
        t5t,
        t6t,
        t7t,
        t8t,
        t1s,
        t2s,
        t3s,
        t4s,
        t5s,
        t6s,
        t7s,
        t8s,
        t1v,
        t2v,
        t3v,
        t4v,
        t5v,
        t6v,
        t7v,
        t8v,
        t1x,
        t2x,
        t3x,
        t4x,
        t5x,
        t6x,
        t7x,
        t8x,
        label,
        err,
        error,
        l,
        i1,
        i2,
        rt;



    var is_string = function( x )
    {
        return ( typeof( x ) === 'string' );
    };



    var is_int = function( x )
    {
        return ( typeof( x ) === 'number' && Number.isInteger( x ) === true );
    };



    var is_float = function( x )
    {
        return ( typeof( x ) === 'number' && Number.isInteger( x ) === false );
    };



    var microlang_vv = function( t1, t2, t3, t4, t5, t6 )
    {
        if( typeof( t1 ) === 'undefined' ) t1 = null;
        if( typeof( t2 ) === 'undefined' ) t2 = null;
        if( typeof( t3 ) === 'undefined' ) t3 = null;
        if( typeof( t4 ) === 'undefined' ) t4 = null;
        if( typeof( t5 ) === 'undefined' ) t5 = null;
        if( typeof( t6 ) === 'undefined' ) t6 = null;

        if( ( t1 === null || t1 === 'variable' || t1 === 'value' ) &&
            ( t2 === null || t2 === 'variable' || t2 === 'value' ) &&
            ( t3 === null || t3 === 'variable' || t3 === 'value' ) &&
            ( t4 === null || t4 === 'variable' || t4 === 'value' ) &&
            ( t5 === null || t5 === 'variable' || t5 === 'value' ) &&
            ( t6 === null || t6 === 'variable' || t6 === 'value' ) ) return true;
        return false;
    };



    var microlang_chk = function( tps, line, s1, x1,
                                             s2, x2,
                                             s3, x3,
                                             s4, x4,
                                             s5, x5,
                                             s6, x6 )
    {
        var t,x,s,n;

        if( typeof( s1 ) === 'undefined' ) s1 = null;
        if( typeof( s2 ) === 'undefined' ) s2 = null;
        if( typeof( s3 ) === 'undefined' ) s3 = null;
        if( typeof( s4 ) === 'undefined' ) s4 = null;
        if( typeof( s5 ) === 'undefined' ) s5 = null;
        if( typeof( s6 ) === 'undefined' ) s6 = null;
        if( typeof( x1 ) === 'undefined' ) x1 = null;
        if( typeof( x2 ) === 'undefined' ) x2 = null;
        if( typeof( x3 ) === 'undefined' ) x3 = null;
        if( typeof( x4 ) === 'undefined' ) x4 = null;
        if( typeof( x5 ) === 'undefined' ) x5 = null;
        if( typeof( x6 ) === 'undefined' ) x6 = null;

        tps = " " + tps;

        if( tps.length > 1 )
        {
            t = tps[1];
            s = s1;
            x = x1;
            n = 1;

            if( x === null )                                return "undefined variable " + s + ": " + line;
            if( t === 'S' && x !== 'string' )               return "parameter " + n + " must be string: " + line;
            if( t === 'I' && x !== 'int'    )               return "parameter " + n + " must be integer: " + line;
            if( t === 'F' && x !== 'float'  )               return "parameter " + n + " must be float: " + line;
            if( t === 'N' && x !== 'float' && x !== 'int' ) return "parameter " + n + " must be float or integer: " + line;
        }

        if( tps.length > 2 )
        {
            t = tps[2];
            s = s2;
            x = x2;
            n = 2;

            if( x === null )                                return "undefined variable " + s + ": " + line;
            if( t === 'S' && x !== 'string' )               return "parameter " + n + " must be string: " + line;
            if( t === 'I' && x !== 'int'    )               return "parameter " + n + " must be integer: " + line;
            if( t === 'F' && x !== 'float'  )               return "parameter " + n + " must be float: " + line;
            if( t === 'N' && x !== 'float' && x !== 'int' ) return "parameter " + n + " must be float or integer: " + line;
        }

        if( tps.length > 3 )
        {
            t = tps[3];
            s = s3;
            x = x3;
            n = 3;

            if( x === null )                                return "undefined variable " + s + ": " + line;
            if( t === 'S' && x !== 'string' )               return "parameter " + n + " must be string: " + line;
            if( t === 'I' && x !== 'int'    )               return "parameter " + n + " must be integer: " + line;
            if( t === 'F' && x !== 'float'  )               return "parameter " + n + " must be float: " + line;
            if( t === 'N' && x !== 'float' && x !== 'int' ) return "parameter " + n + " must be float or integer: " + line;
        }

        if( tps.length > 4 )
        {
            t = tps[4];
            s = s4;
            x = x4;
            n = 4;

            if( x === null )                                return "undefined variable " + s + ": " + line;
            if( t === 'S' && x !== 'string' )               return "parameter " + n + " must be string: " + line;
            if( t === 'I' && x !== 'int'    )               return "parameter " + n + " must be integer: " + line;
            if( t === 'F' && x !== 'float'  )               return "parameter " + n + " must be float: " + line;
            if( t === 'N' && x !== 'float' && x !== 'int' ) return "parameter " + n + " must be float or integer: " + line;
        }

        if( tps.length > 5 )
        {
            t = tps[5];
            s = s5;
            x = x5;
            n = 5;

            if( x === null )                                return "undefined variable " + s + ": " + line;
            if( t === 'S' && x !== 'string' )               return "parameter " + n + " must be string: " + line;
            if( t === 'I' && x !== 'int'    )               return "parameter " + n + " must be integer: " + line;
            if( t === 'F' && x !== 'float'  )               return "parameter " + n + " must be float: " + line;
            if( t === 'N' && x !== 'float' && x !== 'int' ) return "parameter " + n + " must be float or integer: " + line;
        }

        if( tps.length > 6 )
        {
            t = tps[6];
            s = s6;
            x = x6;
            n = 6;

            if( x === null )                                return "undefined variable " + s + ": " + line;
            if( t === 'S' && x !== 'string' )               return "parameter " + n + " must be string: " + line;
            if( t === 'I' && x !== 'int'    )               return "parameter " + n + " must be integer: " + line;
            if( t === 'F' && x !== 'float'  )               return "parameter " + n + " must be float: " + line;
            if( t === 'N' && x !== 'float' && x !== 'int' ) return "parameter " + n + " must be float or integer: " + line;
        }

        return "";
    };



    var microlang_tokenize = function( line, error )
    {
        var tokens,
            i,
            n,
            s,
            token,
            p,
            c,
            c2,
            cn,
            cp;

        error['msg'] = '';

        tokens = [];

        n = line.length;
        token = "";
        s = ' '; // currently parsing: ' ' nothing, 'o' operator, 's' string, 'n' number, 'y' symbol (keyword, variable, label)
        p = ' '; // number; currently parsing: ' ' not a number, 'i' integer part, 'd' decimal part, 'e' exponent

        for( i = 0; i < n; i++ )
        {
            c  = line.substring( i, i + 1 );
            c2 = line.substring( i, i + 2 );
            cn = line.substring( i + 1, i + 2 );
            cp = line.substring( i - 1, i );

            if( c === ' ' )
            {
                if( s === 's' )
                {
                    token += c;
                    continue;
                }

                if( token !== '' )
                {
                    tokens.push( token );
                    token = '';
                }

                p = ' ';
                s = ' ';
                continue;
            }

            if( c === "\\" )
            {
                if( s !== 's' )
                {
                    error['msg'] = "unexpected escape character `" + c + "`: ";
                    return tokens;
                }

                if( c2 === "\\\\" || c2 === "\\n" || c2 === "\\r" || c2 === "\\t" || c2 === "\\\"" )
                {
                    token += c2;
                    i++;
                    continue;
                }
                else
                {
                    error['msg'] = "unrecognized escape sequence `" + c2 + "`: ";
                }
                return tokens;
            }

            if( c === '"' )
            {
                if( s === 's' )
                {
                    token += c;
                    tokens.push( token );
                    token = '';
                    s = ' ';
                    p = ' ';
                    continue;
                }

                if( token !== '' )
                {
                    tokens.push( token );
                    token = "";
                }

                token += c;
                s = 's';
                p = ' ';
                continue;
            }

            if( "0123456789".indexOf( c ) !== -1 )
            {
                if( s === 's' || s === 'y' || s === 'n' )
                {
                    token += c;
                    continue;
                }

                if( token !== '' )
                {
                    tokens.push( token );
                    token = "";
                }

                token += c;
                s = 'n';
                p = 'i';
                continue;
            }

            if( c === '.' )
            {
                if( s === 's' )
                {
                    token += c;
                    continue;
                }

                if( s === 'n' )
                {
                    if( p === 'i' )
                    {
                        token += c;
                        p = 'd';
                        continue;
                    }
                    else
                    {
                        error['msg'] = "unexpected character `" + c + "`: ";
                        return tokens;
                    }
                }

                if( s === 'o' || s === 'y' )
                {
                    if( token !== '' )
                    {
                        tokens.push( token );
                        token = "";
                    }
                }

                if( "0123456789".indexOf( cn ) !== -1 )
                {
                    token += "0" + c;
                    s = 'n';
                    p = 'd';
                    continue;
                }

                error['msg'] = "unexpected character `" + c + "`: ";
                return tokens;
            }

            if( c === '-' )
            {
                if( s === 's' )
                {
                    token += c;
                    continue;
                }

                if( s === ' ' || s === 'y' || s === 'o' )
                {
                    if( token !== '' )
                    {
                        tokens.push( token );
                        token = "";
                    }

                    token += c;

                    if( "0123456789".indexOf( cn ) !== -1 )
                    {
                        s = 'n';
                        p = 'i';
                        continue;
                    }

                    if( cn === '.' )
                    {
                        s = 'n';
                        p = 'i';
                        continue;
                    }

                    tokens.push( token );
                    token = "";
                    s = ' ';
                    p = ' ';
                    continue;
                }

                if( s === 'n' && p === 'e' && ( cp === 'e' || cp === 'E' ) )
                {
                    token += c;
                    continue;
                }
            }

            if( c === 'e' || c === 'E' )
            {
                if( s === 's' )
                {
                    token += c;
                    continue;
                }

                if( s === 'n' && ( p === 'i' || p === 'd' ) )
                {
                    token += c;
                    p = 'e';
                    continue;
                }

                if( s === 'y' )
                {
                    token += c;
                    p = 'e';
                    continue;
                }

                if( token !== '' )
                {
                    tokens.push( token );
                    token = "";
                }

                token += c;
                s = 'y';
                p = ' ';
                continue;
            }

            if( "=<>!+-*/%".indexOf( c ) !== -1 )
            {
                if( s === 's' )
                {
                    token += c;
                    continue;
                }

                if( s === 'o' )
                {
                    token += c;
                    continue;
                }

                if( token !== '' )
                {
                    tokens.push( token );
                    token = "";
                }

                token += c;
                s = 'o';
                p = ' ';
                continue;
            }

            if( c === ':' )
            {
                if( s === 's' )
                {
                    token += c;
                    continue;
                }

                if( s === 'y' && i === ( n - 1 ) )
                {
                    token += c;
                    continue;
                }

                error['msg'] = "unexpected character `" + c + "`: ";
                return tokens;
            }

            if( "_$abcdefghijkilmnopqrstuvwxyzABCDEFGHIJKILMNOPQRSTUVXYZ".indexOf( c ) !== -1 )
            {
                if( s === 's' )
                {
                    token += c;
                    continue;
                }

                if( s === 'y' )
                {
                    token += c;
                    continue;
                }

                if( token !== '' )
                {
                    tokens.push( token );
                    token = "";
                }

                token += c;
                s = 'y';
                p = ' ';
                continue;
            }

            if( s === 's' )
            {
                token += c;
                continue;
            }

            error['msg'] = "unexpected character `" + c + "`: ";
            return tokens;
        }

        if( token !== '' )
        {
            tokens.push( token );
        }

        return tokens;
    };


    if( typeof( max_iterations ) === 'undefined' )
    {
        max_iterations = 1000;
    }



    keywords = [
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
        'float',
        'string',
        'typeof',
        '+',
        '-',
        '*',
        '/',
        '%',
        'if', 'then', 'else', '==', '!=', '>', '<', '>=', '<='
    ];



    error = { 'msg': '' };

    max_str_len = 1024*1024;

    labels = {};
    stack = [];

    if( typeof( vars ) === 'undefined' )
    {
        return "i/o vars object is undefined";
    }

    if( typeof( vars ) !== 'object' )
    {
        return "input data provided is not object";
    }


    // check and store input types

    typs = {};

    for( key in vars )
    {
        if( vars.hasOwnProperty( key ) )
        {
            value = vars[ key ];

            if( is_int( value ) ) typs[ key ] = 'int';
            else if( is_string( value ) ) typs[ key ] = 'string';
            else if( is_float( value ) ) typs[ key ] = 'float';
            else return "unsupported variable type: " + key;
        }
    }


    // convert windows linefeeds to unix linefeeds if any

    code = code.split( "\r\n" ).join( "\n" );


    // explode code

    lines = code.split( "\n" );


    // remove comments

    n = lines.length;
    for( i = 0; i < n; i++ )
    {
        l = lines[i];
        idx = l.indexOf( '//' );
        if( idx !== -1 )
        {
            l = l.substring( 0, idx );
        }
        l = l.trim();
        lines[i] = l;
    }


    // tokenize

    newlines = lines.slice();
    lines = [];
    y = -1;
    y1b = y + 1;
    m = newlines.length;
    for( k = 0; k < m; k++)
    {
        l = newlines[ k ];

        y++;
        y1b = y + 1;

        if( y1b === 1 ) debugger;
        parts = microlang_tokenize( l, error );
        if( error['msg'] !== '' ) return error['msg'] + y1b;

        tokens = [];
        n = parts.length;
        for( i = 0; i < n; i++ )
        {
            p = parts[i];


            // Keywords

            if( keywords.indexOf( p ) !== -1 )
            {
                tokens.push( { 'type': 'keyword', 'symbol': p, 'value': null, 'vtype': null } );

                if( i < n - 1 && parts[ i + 1 ] === '=' ) return "keywords cannot be used for variable names";

                continue;
            }


            // Labels

            if( p.slice( -1 ) === ":" && i === 0 )
            {
                p = p.substring( 0, p.length - 1 );
                if( keywords.indexOf( p ) !== -1 ) return "keywords cannot be used for label names: " + y1b;
                tokens.push( { 'type': 'label', 'symbol': p, 'value': y, 'vtype': null } );
                if( typeof( labels[p] ) !== 'undefined' ) return "Label " + p + " duplicate: " + y1b;
                labels[p] = y;
                continue;
            }

            if( p.slice( -1 ) === ":" && i > 0 )
            {
                return "unexpected label: " + y1b;
            }


            // Strings

            if( p === '""' )
            {
                tokens.push( { 'type': 'value', 'symbol': null, 'value': "", 'vtype': 'string' } );
                continue;
            }

            if( p.substring( 0, 1 ) === '"' )
            {
                if( p.slice( -1 ) === '"' && p.length > 1 )
                {
                    tokens.push( { 'type': 'value', 'symbol': null, 'value': p.substring( 1, p.length - 1 ), 'vtype': 'string' } );
                    continue;
                }

                value = p.substring( 1, p.length - 1 );

                if( p.substring( p.length - 1, p.length ) !== '"' ) return "string not closed: " + y1b;

                tokens.push( { 'type': 'value', 'symbol': null, 'value': value, 'vtype': 'string' } );
                continue;
            }


            // Integers

            if( /^-?\d+(?:e\d+|E\d+|e-\d+|E-\d+)?$/.test( p ) )
            {
                if( parseFloat( p ) > 9223372036854775807 || parseFloat( p ) < -9223372036854775808 ) return "overflow: " + y1b;

                tokens.push( { 'type': 'value', 'symbol': null, 'value': parseInt( parseFloat(p) ), 'vtype': 'int' } );
                continue;
            }


            // Floats

            if( /^-?\d*\.\d+(?:e\d+|E\d+|e-\d+|E-\d+)?$/.test( p ) )
            {
                tokens.push( { 'type': 'value', 'symbol': null, 'value': parseFloat(p), 'vtype': 'float' } );
                continue;
            }


            // Variable names

            tokens.push( { 'type': 'variable', 'symbol': p, 'value': null, 'vtype': null } );

        }
        lines.push( tokens );
    }



    // execute

    y = 0;
    lines_count = lines.length;
    iter = 0;
    vars['cast_failed'] = 0;

    while( true )
    {
        done = false;

        y1b = y + 1;

        if( iter > max_iterations && max_iterations !== 0 ) return "Max iterations exceeded: " + y1b;

        tok = lines[ y ];
        tn = tok.length;

        if( tn === 0 ) done = true;

        if( tn === 1 && tok[0]['type'] === 'label' ) done = true;

        tokens = [];

        for( i = 0; i < tn; i++ )
        {
            t = tok[ i ];

            if( t['type'] === 'label' || t['type'] === 'value' || t['type'] === 'keyword' )
            {
                tokens.push( t );
            }
            else if( t['type'] === 'variable' )
            {
                if( typeof( vars[t['symbol'] ] ) !== 'undefined' )
                {
                    t['value'] = vars[t['symbol']];
                    t['vtype'] = typs[t['symbol']];
                    tokens.push( t );
                }
                else if( typeof( labels[t['symbol']]) !== 'undefined' )
                {
                    t['type'] = 'label';
                    t['value'] = labels[t['symbol']];
                    tokens.push( t );
                }
                else
                {
                    t['value'] = null;
                    tokens.push( t );
                }
            }
        }

        t1t = null; t2t = null; t3t = null; t4t = null; t5t = null; t6t = null; t7t = null; t8t = null;
        t1s = null; t2s = null; t3s = null; t4s = null; t5s = null; t6s = null; t7s = null; t8s = null;
        t1v = null; t2v = null; t3v = null; t4v = null; t5v = null; t6v = null; t7v = null; t8v = null;
        t1x = null; t2x = null; t3x = null; t4x = null; t5x = null; t6x = null; t7x = null; t8x = null;

        if( tn > 0 ) { t1t = tokens[0]['type']; t1s = tokens[0]['symbol']; t1v = tokens[0]['value']; t1x = tokens[0]['vtype']; }
        if( tn > 1 ) { t2t = tokens[1]['type']; t2s = tokens[1]['symbol']; t2v = tokens[1]['value']; t2x = tokens[1]['vtype']; }
        if( tn > 2 ) { t3t = tokens[2]['type']; t3s = tokens[2]['symbol']; t3v = tokens[2]['value']; t3x = tokens[2]['vtype']; }
        if( tn > 3 ) { t4t = tokens[3]['type']; t4s = tokens[3]['symbol']; t4v = tokens[3]['value']; t4x = tokens[3]['vtype']; }
        if( tn > 4 ) { t5t = tokens[4]['type']; t5s = tokens[4]['symbol']; t5v = tokens[4]['value']; t5x = tokens[4]['vtype']; }
        if( tn > 5 ) { t6t = tokens[5]['type']; t6s = tokens[5]['symbol']; t6v = tokens[5]['value']; t6x = tokens[5]['vtype']; }
        if( tn > 6 ) { t7t = tokens[6]['type']; t7s = tokens[6]['symbol']; t7v = tokens[6]['value']; t7x = tokens[6]['vtype']; }
        if( tn > 7 ) { t8t = tokens[7]['type']; t8s = tokens[7]['symbol']; t8v = tokens[7]['value']; t8x = tokens[7]['vtype']; }



        // Goto

        if( ! done && tn === 2 && t1t === 'keyword' && t1s === 'goto' && t2t === 'label' )
        {
            done = true;

            label = t2s;

            if( typeof( labels[label] ) !== 'undefined' )
            {
                iter++;
                y = labels[label];
                continue;
            }
            else return "unknown label " + label + ": " + y1b;
        }


        // Gosub

        if( ! done && tn === 2 && t1t === 'keyword' && t1s === 'gosub' && t2t === 'label' )
        {
            done = true;

            label = t2s;

            if( typeof( labels[label] ) !== 'undefined' )
            {
                stack.push( y );
                iter++;
                y = labels[label];
                continue;
            }
            else return "unknown label " + label + ": " + y1b;
        }


        // Return

        if( ! done && tn === 1 && t1t === 'keyword' && t1s === 'return' )
        {
            done = true;

            if( stack.length === 0 ) return "return without gosub: " + y1b;

            y = stack.pop();
        }


        // Exit

        if( ! done && tn === 1 && t1t === 'keyword' && t1s === 'exit' )
        {
            done = true;

            break;
        }


        // Exit with error message

        if( ! done && tn === 2 && t1t === 'keyword' && t1s === 'exit' && microlang_vv( t2t ) )
        {
            return t2v + '';
        }


        // = Assignment

        if( ! done && tn === 3 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && microlang_vv( t3t ) )
        {
            if( t1t === 'keyword' ) return "keywords cannot be used for variable names: " + y1b;

            if( t1s === 'cast_failed' ) return "`cast_failed` is a reserved variable name: " + y1b;

            if( t3v === null ) return "undefined variable: " + y1b;

            if( typeof( vars[t1s] ) !== 'undefined' )
            {
                if( typs[t1s] === t3x )
                {
                    vars[t1s] = t3v;
                }
                else if( typs[t1s] === 'float' && t3x === 'int' )
                {
                    vars[t1s] = parseFloat( t3v );
                }
                else
                {
                    return "variable cannot change type: " + y1b;
                }
            }
            else
            {
                vars[t1s] = t3v;
                typs[t1s] = t3x;
            }

            done = true;
        }


        // Substring

        if( ! done && tn === 6 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && t3t === 'keyword' && t3s === 'substring' && microlang_vv( t4t, t5t, t6t ) )
        {
            err = microlang_chk( "SII", y1b, t4s, t4x, t5s, t5x, t6s, t6x ); if( err !== '' ) return err;

            if( t5v < 0 || t6v < 0 ) return "substring accepts only positive index and length";

            rt = 'string';
            if( typeof( typs[t1s] ) !== 'undefined' && typs[t1s] !== rt ) return "variable " + t1s + " must be "+ rt + ": " + y1b;
            typs[t1s] = rt;

            vars[t1s] = t4v.substring( t5v, t5v + t6v );

            done = true;
        }


        // Position

        if( ! done && tn === 5 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && t3t === 'keyword' && t3s === 'position' && microlang_vv( t4t, t5t ) )
        {
            err = microlang_chk( "SS", y1b, t4s, t4x, t5s, t5x ); if( err !== '' ) return err;

            rt = 'int';
            if( typeof( typs[t1s] ) !== 'undefined' && typs[t1s] !== rt ) return "variable " + t1s + " must be "+ rt + ": " + y1b;
            typs[t1s] = rt;

            if( typeof( typs[t1s] ) !== 'undefined' && typs[t1s] !== 'int' ) return "variable " + t1s + " must be int: " + y1b;

            vars[t1s] = t4v.indexOf( t5v );
            typs[t1s] = 'int';

            done = true;
        }


        // Replace

        if( ! done && tn === 6 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && t3t === 'keyword' && t3s === 'replace' && microlang_vv( t4t, t5t ) )
        {
            err = microlang_chk( "SSS", y1b, t4s, t4x, t5s, t5x, t6s, t6x ); if( err !== '' ) return err;

            rt = 'string';
            if( typeof( typs[t1s] ) !== 'undefined' && typs[t1s] !== rt ) return "variable " + t1s + " must be "+ rt + ": " + y1b;
            typs[t1s] = rt;

            vars[t1s] = t4v.split( t5v ).join( t6v );

            if( vars[t1s].length > max_str_len ) return "string too long: " . y1b;

            done = true;
        }


        // Between

        if( ! done && tn === 6 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && t3t === 'keyword' && t3s === 'between' && microlang_vv( t4t, t5t, t6t ) )
        {
            err = microlang_chk( "SSS", y1b, t4s, t4x, t5s, t5x, t6s, t6x ); if( err !== '' ) return err;

            rt = 'string';
            if( typeof( typs[t1s] ) !== 'undefined' && typs[t1s] !== rt ) return "variable " + t1s + " must be "+ rt + ": " + y1b;
            typs[t1s] = rt;

            if( t5v === '' ) { i1 = 0; }          else { i1 = t4v.indexOf( t5v ); }

            if( t6v === '' ) { i2 = t5v.length; } else { i2 = t4v.indexOf( t6v ); }

            if( i1 === -1 || i2 === -1 || i2 < i1 )
            {
                vars[t1s] = "";
            }
            else
            {
                vars[t1s] = t4v.substring( i1 + t5v.length, i2 );
            }

            done = true;
        }


        // Trim

        if( ! done && tn === 4 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && t3t === 'keyword' && t3s === 'trim' && microlang_vv( t4t ) )
        {
            err = microlang_chk( "S", y1b, t4s, t4x ); if( err !== '' ) return err;

            rt = 'string';
            if( typeof( typs[t1s] ) !== 'undefined' && typs[t1s] !== rt ) return "variable " + t1s + " must be "+ rt + ": " + y1b;
            typs[t1s] = rt;

            vars[t1s] = t4v.trim();

            done = true;
        }


        // Len

        if( ! done && tn === 4 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && t3t === 'keyword' && t3s === 'len' && microlang_vv( t4t ) )
        {
            err = microlang_chk( "S", y1b, t4s, t4x ); if( err !== '' ) return err;

            rt = 'int';
            if( typeof( typs[t1s] ) !== 'undefined' && typs[t1s] !== rt ) return "variable " + t1s + " must be "+ rt + ": " + y1b;
            typs[t1s] = rt;

            vars[t1s] = t4v.length;

            done = true;
        }


        // Typeof

        if( ! done && tn === 4 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && t3t === 'keyword' && t3s === 'typeof' && microlang_vv( t4t ) )
        {
            err = microlang_chk( "X", y1b, t4s, t4x ); if( err !== '' ) return err;

            rt = 'string';
            if( typeof( typs[t1s] ) !== 'undefined' && typs[t1s] !== rt ) return "variable " + t1s + " must be "+ rt + ": " + y1b;
            typs[t1s] = rt;

            vars[t1s] = t4x;

            done = true;
        }


        // Int

        if( ! done && tn === 4 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && t3t === 'keyword' && t3s === 'int' && microlang_vv( t4t ) )
        {
            rt = 'int';
            if( typeof( typs[t1s] ) !== 'undefined' && typs[t1s] !== rt ) return "variable " + t1s + " must be "+ rt + ": " + y1b;
            typs[t1s] = rt;

            if( t4x === 'string' && /^-?\d+$/.test( t4v ) === false )
            {
                vars[t1s] = 0;
                vars['cast_failed'] = 1;
            }
            else
            {
                if( parseFloat( vars[t4v] ) > 9223372036854775807 || parseFloat( vars[t4v] ) < -9223372036854775808 )
                {
                    vars[t1s] = 0;
                    vars['cast_failed'] = 1;
                }
                else
                {
                    vars[t1s] = parseInt( t4v );
                    vars['cast_failed'] = 0;
                }
            }

            done = true;
        }


        // Float

        if( ! done && tn === 4 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && t3t === 'keyword' && t3s === 'float' && microlang_vv( t4t ) )
        {
            if( typeof( typs[t1s] ) !== 'undefined' && typs[t1s] !== 'int' && typs[t1s] !== 'float' ) return "variable " + t1s + " must be int or float: " + y1b;
            typs[t1s] = 'float';

            if( t4x === 'string' && /^-?\d+$/.test( t4v ) === false && /^-?\d+\.\d+$/.test( t4v ) === false)
            {
                vars[t1s] = parseFloat(0);
                vars['cast_failed'] = 1;
            }
            else
            {
                vars[t1s] = parseFloat( t4v );
                vars['cast_failed'] = 0;
            }

            done = true;
        }


        // String

        if( ! done && tn === 4 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && t3t === 'keyword' && t3s === 'string' && microlang_vv( t4t ) )
        {
            rt = 'string';
            if( typeof( typs[t1s] ) !== 'undefined' && typs[t1s] !== rt ) return "variable " + t1s + " must be "+ rt + ": " + y1b;
            typs[t1s] = rt;

            vars[t1s] = t4v + '';

            done = true;
        }


        // + Sum

        if( ! done && tn === 5 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && t4t === 'keyword' && t4s === '+' && microlang_vv( t3t, t5t ) )
        {
            if( t3x === 'string' && t5x === 'string' )
            {
                rt = 'string';
                if( typeof( typs[t1s] ) !== 'undefined' && typs[t1s] !== rt ) return "variable " + t1s + " must be "+ rt + ": " + y1b;
                typs[t1s] = rt;

                vars[t1s] = t3v + t5v;

                if( vars[t1s].length > max_str_len ) return "string too long: " + y1b;
            }
            else if( t3x === 'int' && t5x === 'int' )
            {
                rt = 'int';
                if( typeof( typs[t1s] ) !== 'undefined' && typs[t1s] !== rt ) return "variable " + t1s + " must be "+ rt + ": " + y1b;
                typs[t1s] = rt;

                vars[t1s] = t3v + t5v;

                if( vars[t1s] > 9223372036854775807 || vars[t1s] < -9223372036854775808 ) return "overflow: " + y1b;
            }
            else if( t3x === 'float' && t5x === 'float' )
            {
                rt = 'float';
                if( typeof( typs[t1s] ) !== 'undefined' && typs[t1s] !== rt ) return "variable " + t1s + " must be "+ rt + ": " + y1b;
                typs[t1s] = rt;

                vars[t1s] = t3v + t5v;
            }
            else return "operands must be of the same type: " + y1b;

            done = true;
        }


        // - Diff

        if( ! done && tn === 5 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && t4t === 'keyword' && t4s === '-' && microlang_vv( t3t, t5t ) )
        {
            err = microlang_chk( "NN", y1b, t3s, t3x, t5s, t5x ); if( err !== '' ) return err;

            if( t3x === 'int' && t5x === 'int' )
            {
                rt = 'int';
                if( typeof( typs[t1s] ) !== 'undefined' && typs[t1s] !== rt ) return "variable " + t1s + " must be "+ rt + ": " + y1b;
                typs[t1s] = rt;

                vars[t1s] = t3v - t5v;

                if( vars[t1s] > 9223372036854775807 || vars[t1s] < -9223372036854775808 ) return "overflow: " + y1b;
            }
            else if( t3x === 'float' && t5x === 'float' )
            {
                rt = 'float';
                if( typeof( typs[t1s] ) !== 'undefined' && typs[t1s] !== rt ) return "variable " + t1s + " must be "+ rt + ": " + y1b;
                typs[t1s] = rt;

                vars[t1s] = t3v - t5v;
            }
            else return "operands must be of the same type: " + y1b;

            done = true;
        }


        // * Mult

        if( ! done && tn === 5 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && t4t === 'keyword' && t4s === '*' && microlang_vv( t3t, t5t ) )
        {
            err = microlang_chk( "NN", y1b, t3s, t3x, t5s, t5x ); if( err !== '' ) return err;

            if( t3x === 'int' && t5x === 'int' )
            {
                rt = 'int';
                if( typeof( typs[t1s] ) !== 'undefined' && typs[t1s] !== rt ) return "variable " + t1s + " must be "+ rt + ": " + y1b;
                typs[t1s] = rt;

                vars[t1s] = t3v * t5v;

                if( vars[t1s] > 9223372036854775807 || vars[t1s] < -9223372036854775808 ) return "overflow: " + y1b;
            }
            else if( t3x === 'float' && t5x === 'float' )
            {
                rt = 'float';
                if( typeof( typs[t1s] ) !== 'undefined' && typs[t1s] !== rt ) return "variable " + t1s + " must be "+ rt + ": " + y1b;
                typs[t1s] = rt;

                vars[t1s] = t3v * t5v;
            }
            else return "operands must be of the same type: " + y1b;

            done = true;
        }


        // / Div

        if( ! done && tn === 5 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && t4t === 'keyword' && t4s === '/' && microlang_vv( t3t, t5t ) )
        {
            err = microlang_chk( "NN", y1b, t3s, t3x, t5s, t5x ); if( err !== '' ) return err;

            if( t5v === 0 ) return "division by zero: " + y1b;

            if( t3x === 'int' && t5x === 'int' )
            {
                rt = 'int';
                if( typeof( typs[t1s] ) !== 'undefined' && typs[t1s] !== rt ) return "variable " + t1s + " must be "+ rt + ": " + y1b;
                typs[t1s] = rt;

                vars[t1s] = Math.floor( t3v / t5v );

                if( vars[t1s] > 9223372036854775807 || vars[t1s] < -9223372036854775808 ) return "overflow: " + y1b;

                vars[t1s] = parseInt( vars[t1s] );
            }
            else if( t3x === 'float' && t5x === 'float' )
            {
                rt = 'float';
                if( typeof( typs[t1s] ) !== 'undefined' && typs[t1s] !== rt ) return "variable " + t1s + " must be "+ rt + ": " + y1b;
                typs[t1s] = rt;

                vars[t1s] = t3v / t5v;
            }
            else return "operands must be of the same type: " + y1b;

            done = true;
        }


        // % Modulo

        if( ! done && tn === 5 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && t4t === 'keyword' && t4s === '%' && microlang_vv( t3t, t5t ) )
        {
            err = microlang_chk( "II", y1b, t3s, t3x, t5s, t5x ); if( err !== '' ) return err;

            if( t5v === 0 ) return "division by zero: " + y1b;

            rt = 'int';
            if( typeof( typs[t1s] ) !== 'undefined' && typs[t1s] !== rt ) return "variable " + t1s + " must be "+ rt + ": " + y1b;
            typs[t1s] = rt;

            vars[t1s] = t3v % t5v;

            done = true;
        }


        // If Then [Else]

        if( ! done && ( tn === 6 || tn === 8 ) && t1t === 'keyword' && t1s === 'if' && microlang_vv( t2t, t4t ) &&
           t3t === 'keyword' && ( t3s === '==' || t3s === '!=' || t3s === '>' || t3s === '<' || t3s === '>=' || t3s === '<=' )  &&
               t5t === 'keyword' && t5s === 'then' && t6t === 'label' )
        {
            if( tn === 8 )
            {
                if( t7t === 'keyword' && t7s === 'else' && t8t === 'label' )
                {
                    // ok
                } else return "syntax error: " + y1b;
            }

            if( t2x !== t4x ) return "operands must be of the same type: " + y1b;

            if( t6v === null ) return "undefined label " + t6s + ": " + y1b;
            if( tn === 8 && t8v === null ) return "undefined label " + t8s + ": " + y1b;

            if( t3s === '==' )
            {
                if( t2v == t4v )
                {
                    y = t6v;
                    iter++;
                    continue;
                }
                else
                {
                    if( t8v !== null )
                    {
                        y = t8v;
                        iter++;
                        continue;
                    }
                    else
                    {
                        done = true;
                    }
                }
            }

            if( t3s === '!=' )
            {
                if( t2v != t4v )
                {
                    y = t6v;
                    iter++;
                    continue;
                }
                else
                {
                    if( t8v !== null )
                    {
                        y = t8v;
                        iter++;
                        continue;
                    }
                    else
                    {
                        done = true;
                    }
                }
            }

            if( t3s === '>' )
            {
                if( t2v > t4v )
                {
                    y = t6v;
                    iter++;
                    continue;
                }
                else
                {
                    if( t8v !== null )
                    {
                        y = t8v;
                        iter++;
                        continue;
                    }
                    else
                    {
                        done = true;
                    }
                }
            }

            if( t3s === '<' )
            {
                if( t2v < t4v )
                {
                    y = t6v;
                    iter++;
                    continue;
                }
                else
                {
                    if( t8v !== null )
                    {
                        y = t8v;
                        iter++;
                        continue;
                    }
                    else
                    {
                        done = true;
                    }
                }
            }

            if( t3s === '<=' )
            {
                if( t2v <= t4v )
                {
                    y = t6v;
                    iter++;
                    continue;
                }
                else
                {
                    if( t8v !== null )
                    {
                        y = t8v;
                        iter++;
                        continue;
                    }
                    else
                    {
                        done = true;
                    }
                }
            }

            if( t3s === '>=' )
            {
                if( t2v >= t4v )
                {
                    y = t6v;
                    iter++;
                    continue;
                }
                else
                {
                    if( t8v !== null )
                    {
                        y = t8v;
                        iter++;
                        continue;
                    }
                    else
                    {
                        done = true;
                    }
                }
            }
        }

        if( ! done )
        {
            return "error: " + y1b;
        }

        y++;

        if( y >= lines_count )
        {
            break;
        }
    }

    delete vars[ 'cast_failed' ];

    return "";
}
