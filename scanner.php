<?php

$db = new SQLite3( 'scanner.db' );

function __get_domain_srv_record( $d )
{
    $d = sprintf( '_matrix._tcp.%s', $d );

    if ( false === ( $r = @dns_get_record( $d, DNS_SRV ) ) )
        return false;
    else
        if ( isset( $r[ 0 ] ) and is_array( $r[ 0 ] ) )
            return $r[ 0 ];
        else
            return false;
}

function __get_domain_target( $d )
{
    $t = $d;
    $p = '8448';
    $r = __get_domain_srv_record( $d );

    if ( isset( $r[ 'target' ] ) )
        $t = $r[ 'target' ];

    if ( isset( $r[ 'port' ] ) )
        $p = $r[ 'port' ];

    if ( false !== strpos( $t, ':' ) )
        list( $t, $p ) = explode( ':', $t );

    return sprintf( '%s:%s', $t, $p );
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

    #if ( is_array( $c ) and ! empty( $c ) and strtotime( $c[ 'last_time' ] ) > time() - 6 * 3600 )
    #{
    #    echo $log_prefix . ' recently scanned' . PHP_EOL;
    #    continue;
    #}

    #else echo $log_prefix . ' scanning...' . PHP_EOL;

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
