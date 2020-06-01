<?php
namespace FrankForte\QuantumPHP;

/**
 * FrankForte\QuantumPHP\Cookie
 * Class to set cookies with SameSite attribute, even in PHP < 7.3
 * Copyright 2020 Frank Forte
 *
 * Adapted from Symfony/Component/HttpFoundation/Cookie.php
 * See the LICENCE file distributed with this file.
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

		if(!isset($c['domain']) && isset($_SERVER['HTTP_HOST'])) {
			$c['domain'] = $_SERVER['HTTP_HOST'];
		}

		if(!isset($c['secure'])) {
			$c['secure'] = static::is_ssl();
		}

		if(!isset($c['httponly'])) {
			$c['httponly'] = true;
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

			$reservedCharsFrom = ['=', ',', ';', ' ', "\t", "\r", "\n", "\v", "\f"];
			$reservedCharsTo = ['%3D', '%2C', '%3B', '%20', '%09', '%0D', '%0A', '%0B', '%0C'];


            $str = str_replace($reservedCharsFrom, $reservedCharsTo, $c['name']);
			$str .= '=';

			if ('' === (string) $c['value']) {
				$str .= 'deleted; expires='.gmdate('D, d-M-Y H:i:s T', time() - 31536001).'; Max-Age=0';
			} else {
				$str .= rawurlencode($c['value']);

				$maxAge = $c['expires'] - time();
				if($maxAge < 0) {
					$maxAge = 0;
				}

				if (0 !== $c['expires']) {

					$str .= '; expires='.gmdate('D, d-M-Y H:i:s T', $c['expires']).'; Max-Age='.$maxAge;
				}
			}

            $str .= '; path='.(empty($c['path']) ? '/' : $c['path']);

			if($c['domain'])
			{
				$str .= '; domain='.$c['domain'];
			}

			if($c['secure'])
			{
				 $str .= '; secure';
			}

			if($c['httponly'])
			{
				 $str .= '; httponly';
			}

			$str .= '; samesite='.$c['samesite'];

			return $str;

		} else {

			$set = setcookie($c['name'], $c['value'], [
				'expires' => $c['expires'],
				'path' => '/',
				'domain' => $c['domain'],
				'samesite' => $c['samesite'],
				'secure' => $c['secure'],
				'httponly' => $c['httponly']
			]);

			if(false === $set )
			{
				// unable to send cookie
			}
		}
	}
}