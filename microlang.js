//
// FreeBSD 2-clause license
//
// microlang.js
//
// A javascript microlang interpreter
//
// microlang version 1.1
// js intepreter version 1.1.0
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
        i,
        n,
        j,
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
        value,
        string_closed,
        pp,
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
        label,
        err,
        l,
        i1,
        i2;



    var is_string = function( x )
    {
        return ( typeof( x ) === 'string' );
    };



    var is_int = function( x )
    {
        return ( typeof( x ) === 'number' && Number.isFloat( x ) === false );
    };



    var is_float = function( x )
    {
        return ( typeof( x ) === 'number' && Number.isFloat( x ) === true );
    };



    var is_number = function( x )
    {
        return ( typeof( x ) === 'number' );
    };



    var gettype = function( x )
    {
        if( is_string( x ) ) return 'string';
        if( is_float( x )  ) return 'float';
        if( is_int( x )    ) return 'int';
    };



    var microlang_label_is_valid = function( lab )
    {
        var i,n,c;

        n = lab.length;
        if( n === 0 )
        {
            return false;
        }
        for( i = 0; i < n; i++ )
        {
            c = lab[i];

            if( i === 0 && "abcdefghjkilmnopqrtsuvwxyzABCDEFGHJKILMNOPQRSTUVWXYZ_".indexOf( c ) !== -1 ) continue;
            if( i >   0 && "abcdefghjkilmnopqrtsuvwxyzABCDEFGHJKILMNOPQRSTUVWXYZ_0123456789".indexOf( c ) !== false ) continue;

            return false;
        }
        return true;
    };



    var microlang_vsn = function( t1, t2, t3, t4, t5, t6 )
    {
        if(
            ( t1 ) === 'undefined' ) t1 = null;
        if( typeof( t2 ) === 'undefined' ) t2 = null;
        if( typeof( t3 ) === 'undefined' ) t3 = null;
        if( typeof( t4 ) === 'undefined' ) t4 = null;
        if( typeof( t5 ) === 'undefined' ) t5 = null;
        if( typeof( t6 ) === 'undefined' ) t6 = null;

        if( ( t1 === null || t1 === 'variable' || t1 === 'string' || t1 === 'int' || t1 === 'float' ) &&
            ( t2 === null || t2 === 'variable' || t2 === 'string' || t2 === 'int' || t2 === 'float' ) &&
            ( t3 === null || t3 === 'variable' || t3 === 'string' || t3 === 'int' || t3 === 'float' ) &&
            ( t4 === null || t4 === 'variable' || t4 === 'string' || t4 === 'int' || t4 === 'float' ) &&
            ( t5 === null || t5 === 'variable' || t5 === 'string' || t5 === 'int' || t5 === 'float' ) &&
            ( t6 === null || t6 === 'variable' || t6 === 'string' || t6 === 'int' || t6 === 'float' ) ) return true;
        return false;
    };



    var microlang_chk = function( types, line, s1, v1,
                                           s2, v2,
                                           s3, v3,
                                           s4, v4,
                                           s5, v5,
                                           s6, v6 )
    {
        var t,v,s,n;

        if( typeof( s1 ) === 'undefined' ) s1 = null;
        if( typeof( s2 ) === 'undefined' ) s2 = null;
        if( typeof( s3 ) === 'undefined' ) s3 = null;
        if( typeof( s4 ) === 'undefined' ) s4 = null;
        if( typeof( s5 ) === 'undefined' ) s5 = null;
        if( typeof( s6 ) === 'undefined' ) s6 = null;
        if( typeof( v1 ) === 'undefined' ) v1 = null;
        if( typeof( v2 ) === 'undefined' ) v2 = null;
        if( typeof( v3 ) === 'undefined' ) v3 = null;
        if( typeof( v4 ) === 'undefined' ) v4 = null;
        if( typeof( v5 ) === 'undefined' ) v5 = null;
        if( typeof( v6 ) === 'undefined' ) v6 = null;

        types = " " + types;

        if( types.length > 1 )
        {
            t = types[1];
            s = s1;
            v = v1;
            n = 1;

            if( v === null ) return "undefined variable " + s + ": " + line;
            if( t === 'S' && ! is_string( v ) ) return "parameter " + n + " must be string: " + line;
            if( t === 'I' && ! is_int( v ) )    return "parameter " + n + " must be integer: " + line;
            if( t === 'F' && ! is_float( v ) )  return "parameter " + n + " must be float: " + line;
            if( t === 'N' && ! is_number( v ) ) return "parameter " + n + " must be float or integer: " + line;
        }

        if( types.length > 2 )
        {
            t = types[2];
            s = s2;
            v = v2;
            n = 2;

            if( v === null ) return "undefined variable " + s + ": " + line;
            if( t === 'S' && ! is_string( v ) ) return "parameter " + n + " must be string: " + line;
            if( t === 'I' && ! is_int( v ) )    return "parameter " + n + " must be integer: " + line;
            if( t === 'F' && ! is_float( v ) )  return "parameter " + n + " must be float: " + line;
            if( t === 'N' && ! is_number( v ) ) return "parameter " + n + " must be float or integer: " + line;
        }

        if( types.length > 3 )
        {
            t = types[3];
            s = s3;
            v = v3;
            n = 3;

            if( v === null ) return "undefined variable " + s + ": " + line;
            if( t === 'S' && ! is_string( v ) ) return "parameter " + n + " must be string: " + line;
            if( t === 'I' && ! is_int( v ) )    return "parameter " + n + " must be integer: " + line;
            if( t === 'F' && ! is_float( v ) )  return "parameter " + n + " must be float: " + line;
            if( t === 'N' && ! is_number( v ) ) return "parameter " + n + " must be float or integer: " + line;
        }

        if( types.length > 4 )
        {
            t = types[4];
            s = s4;
            v = v4;
            n = 4;

            if( v === null ) return "undefined variable " + s + ": " + line;
            if( t === 'S' && ! is_string( v ) ) return "parameter " + n + " must be string: " + line;
            if( t === 'I' && ! is_int( v ) )    return "parameter " + n + " must be integer: " + line;
            if( t === 'F' && ! is_float( v ) )  return "parameter " + n + " must be float: " + line;
            if( t === 'N' && ! is_number( v ) ) return "parameter " + n + " must be float or integer: " + line;
        }

        if( types.length > 5 )
        {
            t = types[5];
            s = s5;
            v = v5;
            n = 5;

            if( v === null ) return "undefined variable " + s + ": " + line;
            if( t === 'S' && ! is_string( v ) ) return "parameter " + n + " must be string: " + line;
            if( t === 'I' && ! is_int( v ) )    return "parameter " + n + " must be integer: " + line;
            if( t === 'F' && ! is_float( v ) )  return "parameter " + n + " must be float: " + line;
            if( t === 'N' && ! is_number( v ) ) return "parameter " + n + " must be float or integer: " + line;
        }

        if( types.length > 6 )
        {
            t = types[6];
            s = s6;
            v = v6;
            n = 6;

            if( v === null ) return "undefined variable " + s + ": " + line;
            if( t === 'S' && ! is_string( v ) ) return "parameter " + n + " must be string: " + line;
            if( t === 'I' && ! is_int( v ) )    return "parameter " + n + " must be integer: " + line;
            if( t === 'F' && ! is_float( v ) )  return "parameter " + n + " must be float: " + line;
            if( t === 'N' && ! is_number( v ) ) return "parameter " + n + " must be float or integer: " + line;
        }

        return "";
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
        'string',
        'typeof',
        '+',
        '-',
        '*',
        '/',
        '%',
        'if', 'then', 'else', '==', '!=', '>', '<', '>=', '<='
    ];



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

        parts = l.split( " " );
        tokens = [];
        n = parts.length;
        for( i = 0; i < n; i++ )
        {
            p = parts[i];


            // Empty space

            if( p === '' ) continue;


            // Keywords

            if( keywords.indexOf( p ) !== -1 )
            {
                tokens.push( { 'type': 'keyword', 'symbol': p, 'value': null } );

                for( j = i + 1; j < n; j++ )
                {
                    if( parts[j] === '' ) continue;
                    if( parts[j] === '=' ) return "keywords cannot be used for variable names";
                    break;
                }

                continue;
            }


            // Labels

            if( p.slice( -1 ) === ":" && i === 0 )
            {
                p = p.substring( 0, p.length - 1 );
                if( keywords.indexOf( p ) !== -1 ) return "keywords cannot be used for label names: " + y1b;
                if( ! microlang_label_is_valid( p ) ) return "Invalid label: " + y1b;
                tokens.push( { 'type': 'label', 'symbol': p, 'value': y } );
                if( typeof( labels[p] ) !== 'undefined' ) return "Label " + p + " duplicate: " + y1b;
                labels[p] = y;
                continue;
            }

            if( p.slice( -1 ) === ":" && i > 0 )
            {
                return "Unexpected label: " + y1b;
            }


            // Strings

            if( p === '""' )
            {
                tokens.push( { 'type': 'string', 'symbol': null, 'value': "" } );
                continue;
            }

            if( p.substring( 0, 1 ) === '"' )
            {
                if( p.slice( -1 ) === '"' && p.length > 1 )
                {
                    tokens.push( { 'type': 'string', 'symbol': null, 'value': p.substring( 1, p.length - 1 ) } );
                    continue;
                }

                value = p.substring( 1, p.length );
                string_closed = false;
                for( j = i + 1; j < n; j++ )
                {
                    pp = parts[j];

                    if( pp.slice( -1 ) === '"' )
                    {
                        value += ' ' + pp.substring( 0, pp.length - 1 );
                        string_closed = true;
                        break;
                    }
                    value += ' ' + pp;
                }
                if( ! string_closed ) return "string not closed: " + y1b;

                tokens.push( { 'type': 'string', 'symbol': null, 'value': value } );
                i = j;
                continue;
            }


            // Integers

            if( /^-?\d+$/.test( p ) )
            {
                if( parseFloat( p ) > 9223372036854775807 || parseFloat( p ) < -9223372036854775808 ) return "overflow: " + y1b;

                tokens.push( { 'type': 'int', 'symbol': null, 'value': parseInt(p) } );
                continue;
            }


            // Floats

            if( /^-?\d+\.\d+$/.test( p ) )
            {
                tokens.push( { 'type': 'float', 'symbol': null, 'value': parseFloat(p) } );
                continue;
            }


            // Variable names

            if( ! microlang_label_is_valid( p ) ) return "Invalid variable name: " + y1b + " " + p;
            tokens.push( { 'type': 'variable', 'symbol': p, 'value': null } );

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

            if( t['type'] === 'label' || t['type'] === 'string' || t['type'] === 'int' || t['type'] === 'float' || t['type'] === 'keyword' )
            {
                tokens.push( t );
            }
            else if( t['type'] === 'variable' )
            {
                if( typeof( vars[t['symbol'] ] ) !== 'undefined' )
                {
                    t['value'] = vars[t['symbol']];
                    tokens.push( t );
                }
                else if( typeof( labels[t['symbol']]) !== 'undefined' )
                {
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

        if( tn > 0 ) { t1t = tokens[0]['type']; t1s = tokens[0]['symbol']; t1v = tokens[0]['value']; }
        if( tn > 1 ) { t2t = tokens[1]['type']; t2s = tokens[1]['symbol']; t2v = tokens[1]['value']; }
        if( tn > 2 ) { t3t = tokens[2]['type']; t3s = tokens[2]['symbol']; t3v = tokens[2]['value']; }
        if( tn > 3 ) { t4t = tokens[3]['type']; t4s = tokens[3]['symbol']; t4v = tokens[3]['value']; }
        if( tn > 4 ) { t5t = tokens[4]['type']; t5s = tokens[4]['symbol']; t5v = tokens[4]['value']; }
        if( tn > 5 ) { t6t = tokens[5]['type']; t6s = tokens[5]['symbol']; t6v = tokens[5]['value']; }
        if( tn > 6 ) { t7t = tokens[6]['type']; t7s = tokens[6]['symbol']; t7v = tokens[6]['value']; }
        if( tn > 7 ) { t8t = tokens[7]['type']; t8s = tokens[7]['symbol']; t8v = tokens[7]['value']; }



        // Goto

        if( ! done && tn === 2 && t1t === 'keyword' && t1s === 'goto' && t2t === 'variable' )
        {
            done = true;

            label = t2s;

            if( typeof( labels[label] ) !== 'undefined' )
            {
                iter++;
                y = labels[label];
                continue;
            }
            else return "Unknown label label: " + y1b;
        }


        // Gosub

        if( ! done && tn === 2 && t1t === 'keyword' && t1s === 'gosub' && t2t === 'variable' )
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
            else return "Unknown label label: " + y1b;
        }


        // Return

        if( ! done && tn === 1 && t1t === 'keyword' && t1s === 'return' )
        {
            done = true;

            if( stack.length === 0 ) return "return without gosub: y1b\n";

            y = stack.pop();
        }


        // Exit

        if( ! done && tn === 1 && t1t === 'keyword' && t1s === 'exit' )
        {
            done = true;

            break;
        }


        // Exit with error message

        if( ! done && tn === 2 && t1t === 'keyword' && t1s === 'exit' && microlang_vsn( t2t ) )
        {
            return t2v + '';
        }


        // = Assignment

        if( ! done && tn === 3 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && microlang_vsn( t3t ) )
        {
            if( t1t === 'keyword' ) return "keywords cannot be used for variable names: " + y1b;

            if( t3v === null ) return "undefined variable: " + y1b;

            if( typeof( vars[t1s] ) !== 'undefined' && gettype( vars[t1s] ) !== gettype( t3v ) ) return "variable cannot change type: " + y1b;

            vars[t1s] = t3v;

            done = true;
        }


        // Substring

        if( ! done && tn === 6 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && t3t === 'keyword' && t3s === 'substring' && microlang_vsn( t4t, t5t, t6t ) )
        {
            err = microlang_chk( "SII", y1b, t4s, t4v, t5s, t5v, t6s, t6v ); if( err !== '' ) return err;

            if( t5v < 0 || t6v < 0 ) return "substring accepts only positive index and length";

            vars[t1s] = t4v.substring( t5v, t5v + t6v );

            done = true;
        }


        // Position

        if( ! done && tn === 5 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && t3t === 'keyword' && t3s === 'position' && microlang_vsn( t4t, t5t ) )
        {
            err = microlang_chk( "SS", y1b, t4s, t4v, t5s, t5v ); if( err !== '' ) return err;

            vars[t1s] = t4v.indexOf( t5v );

            done = true;
        }


        // Replace

        if( ! done && tn === 6 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && t3t === 'keyword' && t3s === 'replace' && microlang_vsn( t4t, t5t ) )
        {
            err = microlang_chk( "SSS", y1b, t4s, t4v, t5s, t5v, t6s, t6v ); if( err !== '' ) return err;

            vars[t1s] = t4v.split( t5v ).join( t6v );

            if( vars[t1s].length > max_str_len ) return "string too long: " . y1b;

            done = true;
        }


        // Between

        if( ! done && tn === 6 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && t3t === 'keyword' && t3s === 'between' && microlang_vsn( t4t, t5t, t6t ) )
        {
            err = microlang_chk( "SSS", y1b, t4s, t4v, t5s, t5v, t6s, t6v ); if( err !== '' ) return err;

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

        if( ! done && tn === 4 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && t3t === 'keyword' && t3s === 'trim' && microlang_vsn( t4t ) )
        {
            err = microlang_chk( "S", y1b, t4s, t4v ); if( err !== '' ) return err;

            vars[t1s] = t4v.trim();

            done = true;
        }


        // Len

        if( ! done && tn === 4 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && t3t === 'keyword' && t3s === 'len' && microlang_vsn( t4t ) )
        {
            err = microlang_chk( "S", y1b, t4s, t4v ); if( err !== '' ) return err;

            vars[t1s] = t4v.length;

            done = true;
        }


        // Typeof

        if( ! done && tn === 4 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && t3t === 'keyword' && t3s === 'typeof' && microlang_vsn( t4t ) )
        {
            err = microlang_chk( "X", y1b, t4s, t4v ); if( err !== '' ) return err;

            if( is_string( t4v ) ) vars[t1s] = 'string';
            if( is_int( t4v ) )    vars[t1s] = 'int';
            if( is_float( t4v ) )  vars[t1s] = 'float';

            done = true;
        }


        // Int

        if( ! done && tn === 4 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && t3t === 'keyword' && t3s === 'int' && microlang_vsn( t4t ) )
        {
            if( is_string( t4v ) && /^-?\d+$/.test( t4v ) === false )
            {
                vars[t1s] = parseInt(0);
                vars['cast_failed'] = 1;
            }
            else
            {
                if( parseFloat( vars[t4v] ) > 9223372036854775807 || parseFloat( vars[t4v] ) < -9223372036854775808 )
                {
                    vars[t1s] = parseInt(0);
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

        if( ! done && tn === 4 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && t3t === 'keyword' && t3s === 'float' && microlang_vsn( t4t ) )
        {
            if( is_string( t4v ) && /^-?\d+$/.test( t4v ) === false && /^-?\d+\.\d+$/.test( t4v ) === false)
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

        if( ! done && tn === 4 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && t3t === 'keyword' && t3s === 'string' && microlang_vsn( t4t ) )
        {
            vars[t1s] = t4v + '';

            done = true;
        }


        // + Sum

        if( ! done && tn === 5 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && t4t === 'keyword' && t4s === '+' && microlang_vsn( t3t, t5t ) )
        {
            if( typeof( t3v ) === 'string' && typeof( t5v ) === 'string' )
            {
                vars[t1s] = t3v + t5v;

                if( vars[t1s].length > max_str_len ) return "string too long: " + y1b;
            }
            else if( gettype( t3v ) === 'int' && gettype( t5v ) === 'int' )
            {
                vars[t1s] = t3v + t5v;

                if( vars[t1s] > 9223372036854775807 || vars[t1s] < -9223372036854775808 ) return "overflow: " + y1b;
            }
            else if( gettype( t3v ) === 'float' && gettype( t5v ) === 'float' )
            {
                vars[t1s] = t3v + t5v;
            }
            else return "values must be of the same type: " + y1b;

            done = true;
        }


        // - Diff

        if( ! done && tn === 5 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && t4t === 'keyword' && t4s === '-' && microlang_vsn( t3t, t5t ) )
        {
            err = microlang_chk( "NN", y1b, t3s, t3v, t5s, t5v ); if( err !== '' ) return err;

            if( gettype( t3v ) === 'int' && gettype( t5v ) === 'int' )
            {
                vars[t1s] = t3v - t5v;

                if( vars[t1s] > 9223372036854775807 || vars[t1s] < -9223372036854775808 ) return "overflow: " + y1b;
            }
            else if( gettype( t3v ) === 'float' && gettype( t5v ) === 'float' )
            {
                vars[t1s] = t3v - t5v;
            }
            else return "values must be of the same type: " + y1b;

            done = true;
        }


        // * Mult

        if( ! done && tn === 5 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && t4t === 'keyword' && t4s === '*' && microlang_vsn( t3t, t5t ) )
        {
            err = microlang_chk( "NN", y1b, t3s, t3v, t5s, t5v ); if( err !== '' ) return err;

            if( gettype( t3v ) === 'int' && gettype( t5v ) === 'int' )
            {
                vars[t1s] = t3v * t5v;

                if( vars[t1s] > 9223372036854775807 || vars[t1s] < -9223372036854775808 ) return "overflow: " + y1b;
            }
            else if( gettype( t3v ) === 'float' && gettype( t5v ) === 'float' )
            {
                vars[t1s] = t3v * t5v;
            }
            else return "values must be of the same type: " + y1b;

            done = true;
        }


        // / Div

        if( ! done && tn === 5 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && t4t === 'keyword' && t4s === '/' && microlang_vsn( t3t, t5t ) )
        {
            err = microlang_chk( "II", y1b, t3s, t3v, t5s, t5v ); if( err !== '' ) return err;

            if( t5v === 0 ) return "division by zero: " + y1b;

            if( gettype( t3v ) === 'int' && gettype( t5v ) === 'int' )
            {
                vars[t1s] = Math.floor( t3v / t5v );

                if( vars[t1s] > 9223372036854775807 || vars[t1s] < -9223372036854775808 ) return "overflow: " + y1b;

                vars[t1s] = parseInt( vars[t1s] );
            }
            else if( gettype( t3v ) === 'float' && gettype( t5v ) === 'float' )
            {
                vars[t1s] = t3v / t5v;
            }
            else return "values must be of the same type: " + y1b;

            done = true;
        }


        // % Modulo

        if( ! done && tn === 5 && t1t === 'variable' && t2t === 'keyword' && t2s === '=' && t4t === 'keyword' && t4s === '%' && microlang_vsn( t3t, t5t ) )
        {
            err = microlang_chk( "II", y1b, t3s, t3v, t5s, t5v ); if( err !== '' ) return err;

            if( t5v === 0 ) return "division by zero: " + y1b;

            vars[t1s] = t3v % t5v;

            done = true;
        }


        // If Then [Else]

        if( ! done && ( tn === 6 || tn === 8 ) && t1t === 'keyword' && t1s === 'if' && microlang_vsn( t2t, t4t ) &&
           t3t === 'keyword' && ( t3s === '==' || t3s === '!=' || t3s === '>' || t3s === '<' || t3s === '>=' || t3s === '<=' )  &&
               t5t === 'keyword' && t5s === 'then' && t6t === 'variable' )
        {
            if( tn === 8 )
            {
                if( t7t === 'keyword' && t7s === 'else' && t8t === 'variable' )
                {
                    // ok
                } else return "Syntax error: " + y1b;
            }

            err = microlang_chk( "II", y1b, t2s, t2v, t4s, t4v ); if( err !== '' ) return err;

            if( gettype( t2v ) !== gettype( t4v ) ) return "values must be of the same type: " + y1b;

            if( t6v === null ) return "undefined label t6s: " + y1b;
            if( tn === 8 && t8v === null ) return "undefined label t8s: " + y1b;

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

    return "";
}
