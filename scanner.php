<?php

$db = new SQLite3( 'scanner.db' );

function __http_get_json( $url )
{
    $result = @file_get_contents
    (
        $url,
        false,
        stream_context_create
        (
            array
            (
                'http' => array( 'timeout' => 2 ),
                'ssl'  => array( 'verify_peer' => false,
                    'verify_peer_name' => false )
            )
        )
    );

    if ( false === $result )
        return false;

    $json = @json_decode( $result );

    if ( ! is_object( $json ) )
        return false;

    return $json;
}

function __get_well_known( $domain )
{
    $result = __http_get_json(
        sprintf( 'https://%s/.well-known/matrix/server', $domain ) );

    if ( false === $result )
        return false;

    if ( ! isset( $result->{ 'm.server' } ) )
        return false;

    return $result->{ 'm.server' };
}

function __get_domain_srv_record( $d )
{
    $d = sprintf( '_matrix._tcp.%s', $d );

    if ( false === ( $r = @dns_get_record( $d, DNS_SRV ) ) )
        return false;

    if ( ! isset( $r[ 0 ] ) or ! is_array( $r[ 0 ] ) )
        return false;

    return sprintf( '%s:%s', $r[ 0 ][ 'target' ],
        $r[ 0 ][ 'port' ] );
}

function __get_domain_target( $d )
{
    $t = $d;
    $p = '8448';

    $r = __get_well_known( $d );
    if ( $r )
    {
        #echo "well-known for $d is $r" . PHP_EOL;
        return $r;
    }
    else
    {
        #echo "well-known failed for $d" . PHP_EOL;
        $r = __get_domain_srv_record( $d );
        if ( $r )
        {
            #echo "srv for $d is $r" . PHP_EOL;
            return $r;
        }
        else
        {
            #echo "srv failed for $d, using fallback" . PHP_EOL;
            return sprintf( '%s:%s', $d, $p );
        }
    }
}

function __get_server_version( $d )
{
    $u = sprintf( 'https://%s/_matrix/federation/v1/version', $d );

    $c = array
    (
        'http' => array( 'timeout' => 2 ),
        'ssl'  => array( 'verify_peer' => false, 'verify_peer_name' => false ),
    );

    $r = @file_get_contents( $u, false, stream_context_create( $c ) );

    if ( $r === false )
        return false;

    $j = json_decode( $r, true );

    if ( ! isset( $j[ 'server' ] ) )
        return false;

    return $j[ 'server' ];
}

if ( ! isset( $argv[ 1 ] ) )
    exit;

$domains = file( $argv[ 1 ] );
$total   = count( $domains );
$count   = 0;

foreach ( $domains as $d )
{
    $count++;

    $d = trim( $d );

    if ( empty( $d ) )
        continue;

    $log_prefix = sprintf( '[%s/%s] <%s>', $count, $total, $d );
    
    while ( true )
    {
        $c = @$db->querySingle( sprintf( 'select * from versions where domain = \'%s\'
            order by id desc limit 1', $db->escapeString( $d ) ), true );

        if ( $c !== false )
            break;
        else
            sleep( 1 );
    }

    #echo $log_prefix . ' scanning...' . PHP_EOL;

    $t = __get_domain_target( $d );

    if ( false === $t )
    {
        #echo $log_prefix . ' target = FAIL' . PHP_EOL;
        continue;
    }

    #else printf( '%s target = %s' . PHP_EOL, $log_prefix, $t );

    $v = __get_server_version( $t );

    if ( false === $v )
    {
        #echo $log_prefix . ' version = FAIL' . PHP_EOL;
        continue;
    }

    #printf( '%s version = %s/%s' . PHP_EOL, $log_prefix, $v[ 'name' ], $v[ 'version' ] );

    if ( ! is_array( $c ) )
        continue;

    if ( empty( $c ) or $c[ 'software' ] != $v[ 'name' ] or $c[ 'version' ] != $v[ 'version' ] )
    {
        printf( '%s upgraded to %s/%s' . PHP_EOL, $d, $v[ 'name' ], $v[ 'version' ] );

        while ( true )
        {
            if ( false !== @$db->query( sprintf( 'insert into versions
                ( first_time, last_time, domain, software, version )
                values ( datetime(), datetime(), \'%s\', \'%s\', \'%s\' )', 
                    $db->escapeString( $d ),
                    $db->escapeString( $v[ 'name' ] ),
                    $db->escapeString( $v[ 'version' ] ) ) ) )
                break;
            else
                sleep( 1 );
        }
    }

    else
    {
        while ( true )
        {
            if ( false !== @$db->query( sprintf( 'update versions set last_time = datetime()
                where id = \'%s\'', $c[ 'id' ] ) ) )
                break;
            else
                sleep( 1 );
        }
    }
}

echo 'DONE!' . PHP_EOL;
