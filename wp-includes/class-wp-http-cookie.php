<?php
 #[AllowDynamicProperties]
 class WP_Http_Cookie { public $name; public $value; public $expires; public $path; public $domain; public $port; public $host_only; public function __construct( $data, $requested_url = '' ) { if ( $requested_url ) { $parsed_url = parse_url( $requested_url ); } if ( isset( $parsed_url['host'] ) ) { $this->domain = $parsed_url['host']; } $this->path = isset( $parsed_url['path'] ) ? $parsed_url['path'] : '/'; if ( ! str_ends_with( $this->path, '/' ) ) { $this->path = dirname( $this->path ) . '/'; } if ( is_string( $data ) ) { $pairs = explode( ';', $data ); $name = trim( substr( $pairs[0], 0, strpos( $pairs[0], '=' ) ) ); $value = substr( $pairs[0], strpos( $pairs[0], '=' ) + 1 ); $this->name = $name; $this->value = urldecode( $value ); array_shift( $pairs ); foreach ( $pairs as $pair ) { $pair = rtrim( $pair ); if ( empty( $pair ) ) { continue; } list( $key, $val ) = strpos( $pair, '=' ) ? explode( '=', $pair ) : array( $pair, '' ); $key = strtolower( trim( $key ) ); if ( 'expires' === $key ) { $val = strtotime( $val ); } $this->$key = $val; } } else { if ( ! isset( $data['name'] ) ) { return; } foreach ( array( 'name', 'value', 'path', 'domain', 'port', 'host_only' ) as $field ) { if ( isset( $data[ $field ] ) ) { $this->$field = $data[ $field ]; } } if ( isset( $data['expires'] ) ) { $this->expires = is_int( $data['expires'] ) ? $data['expires'] : strtotime( $data['expires'] ); } else { $this->expires = null; } } } public function test( $url ) { if ( is_null( $this->name ) ) { return false; } if ( isset( $this->expires ) && time() > $this->expires ) { return false; } $url = parse_url( $url ); $url['port'] = isset( $url['port'] ) ? $url['port'] : ( 'https' === $url['scheme'] ? 443 : 80 ); $url['path'] = isset( $url['path'] ) ? $url['path'] : '/'; $path = isset( $this->path ) ? $this->path : '/'; $port = isset( $this->port ) ? $this->port : null; $domain = isset( $this->domain ) ? strtolower( $this->domain ) : strtolower( $url['host'] ); if ( false === stripos( $domain, '.' ) ) { $domain .= '.local'; } $domain = ( str_starts_with( $domain, '.' ) ) ? substr( $domain, 1 ) : $domain; if ( ! str_ends_with( $url['host'], $domain ) ) { return false; } if ( ! empty( $port ) && ! in_array( $url['port'], array_map( 'intval', explode( ',', $port ) ), true ) ) { return false; } if ( ! str_starts_with( $url['path'], $path ) ) { return false; } return true; } public function getHeaderValue() { if ( ! isset( $this->name ) || ! isset( $this->value ) ) { return ''; } return $this->name . '=' . apply_filters( 'wp_http_cookie_value', $this->value, $this->name ); } public function getFullHeader() { return 'Cookie: ' . $this->getHeaderValue(); } public function get_attributes() { return array( 'expires' => $this->expires, 'path' => $this->path, 'domain' => $this->domain, ); } } 