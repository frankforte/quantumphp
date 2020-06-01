<?php
namespace FrankForte\QuantumPHP;

/**
 * FrankForte\QuantumPHP\Cookie
 * Class to set cookies with SameSite attribute, even in PHP < 7.3
 * Copyright 2020 Frank Forte
 *
 * @package QuantumPHP
 * @author Frank Forte <frank.forte@gmail.com>
 */
class Cookie
{
	/**
	 * Determines whether the client connected over SSL or HTTPS
	 * Also considers environments where HTTPS terminates at a load balancer,
	 * where the HTTP_X_FORWARDED_PROTO header should be included in the forwarded
	 * request.
	 * @return bool true if the client connected over HTTPS
	 */
	public static function is_ssl()
	{
		return (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off')
		|| (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https')
		|| $_SERVER['SERVER_PORT'] == 443;
	}

	/**
	 * Send cookie with SameSite attribute
	 * If attributes are sent into the first argument as an array, all other
	 * arguments are ignored.
	 *
	 * @param mixed $c array of cookie attributes or name of the cookie.
	 * @param $value (optional) The value of the cookie.
	 * @param $expires (optional) The Unix Timestamp when the cookie expires.
	 * @param $path (optional) the path where the cookie will be available.
	 * @param $domain (optional) The (sub)domain where the cookie will be
	 * available.
	 * @param $secure (optional) If true, only send cookie over secure HTTPS
	 * connections from the client (It is always sent from the server).
	 * @param $httponly (optional) If true, it will not be availble to JavaScript.
	 * @param $samesite (optional) Whether cookie should be sent when user is
	 * referred from another webiste.
	 */
	public static function send_cookie($c, $value = '', $expires = 0, $path = '/', $domain = null, $secure = null, $httponly = null, $samesite = null)
	{
		if(!is_array($c)){
			$c = [
					 'name' => $c
					,'value' =>  $value
					,'expires' => $expires
					,'path' => $path
					,'domain' => $domain
					,'secure' => $secure
					,'httponly' => $httponly // default true
					,'samesite' => $samesite
			];
		}

		if (empty($c['name'])){
			throw new \InvalidArgumentException('The cookie name cannot be empty.');
		}

		if(!isset($c['expires'])) {
			if(isset($c['expiration'])) {
				$c['expires'] = $c['expiration'];
			} else {
				$c['expires'] = 0; // when browser closes
			}
		}

		if($c['expires'] < 0) {
			$c['expires'] = time() - 31536001; // one year ago
		}

		if(!isset($c['path']) || empty($c['path'])) {
			$c['path'] = '/';
		}

		if(!isset($c['domain'])) {
			$c['domain'] = '';
		}

		// default to secure if available, in case developers send back something sensitive.
		if(!isset($c['secure'])) {
			$c['secure'] = static::is_ssl();
		}

		if(!isset($c['httponly'])) {
			$c['httponly'] = false;
		}

		// Make sure SameSite is set
		// CORS cookies requires secure and samesite=none
		if(!isset($c['samesite']) || empty($c['samesite'])) {
			$c['samesite'] = $c['secure'] ? 'none' : 'lax';
		}

		if($c['samesite'] == 'none' && !$c['secure']) {
			// These cookies will be rejected by some browsers in the future
		}

		// hack for PHP before 7.3 to get SameSite working
		if (PHP_VERSION_ID < 70300) {

			// hack that will stop working in PHP 7.3
			$set = setcookie($c['name'], $c['value'], $c['expires'], $c['path'].'; samesite='.$c['samesite'], $c['domain'], $c['secure'], $c['httponly']);

		} else {

			// hack that will stop working in PHP 7.3
			$set = setcookie($c['name'], $c['value'], [
				'expires' => $c['expires'],
				'path' => '/',
				'domain' => $c['domain'],
				'samesite' => $c['samesite'],
				'secure' => $c['secure'],
				'httponly' => $c['httponly']
			]);
		}

		if(false === $set )
		{
			// unable to send cookie
		}
	}
}