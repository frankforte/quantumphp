## Overview
QuantumPHP is a PHP and JavaScript library that can log server side variables directly to the JavaScript console in various browsers like Firefox Quantum, without the requirement of a browser extension or add-on.


## Requirements
- PHP 5.6 or later

## Installation

### 1. Add QuantumPHP to your composer.json file

    "repositories": [
        {"type": "vcs", "url": "https://github.com/frankforte/quantumphp"}
    ],
    "require": {
        "frankforte/quantumphp": "^1.0"
    }

### 2. install the package

   composer install

### 3. Copy  the QuantumPHP.js file into your public directory

    cp vendor/frankforte/quantumphp/QuantumPHP.js public_html/js/QuantumPHP.js

You can also copy it manually in your file system, just make sure the target path is correct, you might use something other than `public_html`

### 4. Include the code.

Add this to your PHP file. The 'add' method will add rich information to the logs in a table format.

```php
<?php
include 'QuantumPHP.php';

// optional, also send the X-ChromeLogger-Data header
// QuantumPHP::$MODE = 1;

QuantumPHP::log('Regular log');
QuantumPHP::warn('Regular warn');
QuantumPHP::error('Regular error');
QuantumPHP::add('Hello console table!');
QuantumPHP::add('Something Bad','error');
QuantumPHP::add('Something Really Bad','critical');
QuantumPHP::log($_SERVER);
QuantumPHP::send();
```

Add this to the HTML template

    <script src="/js/QuantumPHP.js"></script>


### 5. (optional) Install the Chrome extension:

https://chrome.google.com/extensions/detail/noaneddfkdjfnfdakjjmocngnfkfehhd

More information can be found here:
http://www.chromelogger.com
