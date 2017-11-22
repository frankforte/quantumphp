## Overview
QuantumPHP is a PHP and JavaScript library that can log server side variables directly to the developer tools console in various browsers like Firefox Quantum, with or  without the use of a browser extension or add-on.


## Requirements
- PHP 5.6 or later

## Installation

### 1. Add QuantumPHP to your project

a) composer require frankforte/quantumphp

or

b) git clone https://github.com/frankforte/quantumphp.git vendor/frankforte/quantumphp


### 2. Get QuamtumPHP in your browser:

#### a) For Firefox, add the following light weight add-on:

https://addons.mozilla.org/en-US/firefox/addon/quantumphp/

or

#### b) For Google Chrome, install the Chrome extension:

https://chrome.google.com/extensions/detail/noaneddfkdjfnfdakjjmocngnfkfehhd

More information can be found here:
http://www.chromelogger.com

or

#### c) copy the JavaScript file into your public directory and include it in your HTML.

for example:

    cp vendor/frankforte/quantumphp/QuantumPHP.js public_html/js/QuantumPHP.js


Then add the file to the HTML template

    <script src="/js/QuantumPHP.js"></script>


### 3. Use it in your project:

Add this to your PHP file. The 'add' method will add rich information to the logs in a table format.

```php
<?php

// Optional if you do not have an autoloader
include 'QuantumPHP.php';

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

QuantumPHP::log('Regular log');
QuantumPHP::warn('Regular warn');
QuantumPHP::error('Regular error');
QuantumPHP::add('Hello console table!');
QuantumPHP::add('Something Bad','error');
QuantumPHP::add('Something Really Bad','critical');
// QuantumPHP::log($_SERVER); // you will need mode 0 for this!
QuantumPHP::send();
```

Finally, hit F12 in your browser to open developer tools, and view the output under the "console" tab.


Please submit any issues you have: https://github.com/frankforte/quantumphp/issues