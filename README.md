## Overview
QuantumPHP is a PHP and JavaScript library that can log server side variables directly to the developer tools console in various browsers like Firefox Quantum, with or  without the use of a browser extension or add-on.


## Requirements
- PHP 7.3 or later


## Installation

### 1. Add QuantumPHP to your project

a) `composer require frankforte/quantumphp`

or

b) `git clone https://github.com/frankforte/quantumphp.git vendor/frankforte/quantumphp`


### 2. Get QuamtumPHP in your browser:

#### a) For Firefox, add the following light weight add-on:

https://addons.mozilla.org/en-US/firefox/addon/quantumphp/

or

#### b) For Google Chrome, install the Chrome extension:

https://chrome.google.com/extensions/detail/noaneddfkdjfnfdakjjmocngnfkfehhd

More information can be found here:
http://www.chromelogger.com

NOTE:  Do not forget to turn on Chrome Logger for each website that you debug. There is a little icon that looks like a console window near the top right, just beside the address bar.

or

#### c) copy the JavaScript file into your public directory and include it in your HTML.

for example:

    cp vendor/frankforte/quantumphp/src/QuantumPHP.js public_html/js/QuantumPHP.js


Then add the file to the HTML template

    <script src="/js/QuantumPHP.js"></script>


### 3. Use it in your project:

#### Laravel Monolog

Make the class available to your namespace:

    use Monolog\Logger;
    use FrankForte\QuantumPHP\QuantumPHPHandler;

Use the logger

    $logger = new Logger('my_logger');
    $logger->pushHandler(new QuantumPHPHandler());

    $test_data = ['foo'=>'bar'];
    $logger->addInfo('Log Message', $test_data);
    $logger->warning('Some warning');
    $logger->critical('Someething Bad');
    $logger->table([
		['one'=>'a', 'two'=>'b', 'three'=>'c'],
		['one'=>'aa','two'=>'bb','three'=>'cc']
	]);


#### Without Monolog or in other PHP projects or frameworks

Add this to your PHP file. The 'add' method will add rich information to the logs in a table format.
Note: objects and arrays should be logged with the "add" method: `QuantumPHP::add($object);`

```php
<?php

// Optional if you do not have an autoloader
include 'QuantumPHP.php';

// use statement is required as of version 1.2
// unless you make calls to the fully qualified class name, e.g.
// \FrankForte\QuantumPHP\QuantumPHP::$MODE = 2;
use FrankForte\QuantumPHP\QuantumPHP;

// alternatively, using a class autoloader:
// use FrankForte\QuantumPHP\QuantumPHP;

/**
 * Optional debugging mode
 * mode = 1 for Chrome and Firefox
 * mode 2 for just Firefox
 * mode 3 for just Chrome
 * mode 0 when you have a HUGE-MONGOUS log, and
 *    HTTP headers break the server or browser...
 *    WARNING: mode 0 will echo the log in an HTML comment, so
 *    no more http headers can be sent once you call QuantumPHP::send()
 *    (unless you use output buffering)
 * defaults to mode 2
 */
QuantumPHP::$MODE = 2;

// Optional debug size. Defaults to 5kB
QuantumPHP::$HEADER_LIMIT = 16000;

// Logging strings
QuantumPHP::log('Regular log');
QuantumPHP::warn('Regular warn');
QuantumPHP::error('Regular error');

// Logging strings, objects, or arrays
QuantumPHP::add('Hello console table!');
QuantumPHP::add('Something Bad','error');
QuantumPHP::add('Something Really Bad','critical');
// QuantumPHP::add($_SERVER); // you will need mode 0 for this!
try
{
	throw new Exception('Something Bad!!');
}
catch(Exception $e)
{
	\QuantumPHP::add('test','warning',$e);
}

// Logging data in a table
// objects can be expanded in Firefox console table, but not Chrome:
$obj = new stdClass();
$obj->name = 'test class';
$obj->items = [1,2,3];
$lines = [];

$lines[] = [
	 'Time' =>round(microtime(true),8)
	,'Level' => 'status'
	,'Comment' => $obj // Chrome just shows {...}
	,'Function' => debug_backtrace()[0]['function']
	,'File' => __LINE__.' - '.__FILE__
];
$lines[] = [
	 'Time' =>round(microtime(true),8)
	,'Level' => 'status'
	,'Comment' => 'Strings are ok in Chrome'
	,'Function' => debug_backtrace()[0]['function']
	,'File' => __LINE__.' - '.__FILE__
];

QuantumPHP::table($lines);

QuantumPHP::send();
```

Finally, hit F12 in your browser to open developer tools, and view the output under the "console" tab. If you are using Chrome, don't forget to turn on Chrome Logger by clicking the icon beside the address bar that looks like a command line window.

## Security Tip

You should never add sensitive data to the logs, or at the very least, you should check that you are in development before sending debug information to the browser, for example:

```php
<?php
if($_SERVER['HTTP_HOST'] == 'localhost') {
	QuantumPHP::send();
}
```

## Known bug

If you have multiple tabs open in Firefox with the developer tools console open, the log will sometimes appear on one of the other tabs. Closing those tabs or the console on those tabs appears to prevent the issue.

If you have any issues, please double check that you did not miss anything above.  You can submit the details of your issue here: https://github.com/frankforte/quantumphp/issues
