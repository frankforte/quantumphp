<?php
/**
 * Copyright 2017 Frank Forte
 *
 * This is a derivative work of ccampbell/chromephp by Craig Campbell
 * Copyright 2010-2013 Craig Campbell
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
/**
 * Server Side Firefox 57+ (Quantum) PHP debugger class
 *
 * @package QuantumPHP
 * @author Frank Forte <frank.forte@gmail.com>
 * @author Craig Campbell <iamcraigcampbell@gmail.com>
 */
class QuantumPHP
{
    /**
     * @var string
     */
    const VERSION = '1.0.11';

    /**
     * @var string
     */
    const HEADER_NAME = 'X-ChromeLogger-Data';

    /**
     * @var int
	 * The total size of all response headers including this serialized server log
	 * should be less than the Apache LimitRequestFieldSize Directive
     */
    public static $HEADER_LIMIT = 5000;

	/**
	 * @var string
	 * whether to send the log as a cookie or a HTTP header
	 * valid values are 1 for all, 2 for cookie only, 3 for header only
	 */
	public static $MODE = 2;


	/**
	 * @var string
	 * most critical level that occured
	 */
	public static $LEVEL = 'info';

    /**
     * @var string
     */
    const BACKTRACE_LEVEL = 'backtrace_level';

    /**
     * @var string
     */
    const LOG = 'log';

    /**
     * @var string
     */
    const WARN = 'warn';

    /**
     * @var string
     */
    const ERROR = 'error';

    /**
     * @var string
     */
    const GROUP = 'group';

    /**
     * @var string
     */
    const INFO = 'info';

	/**
	 * @var array
	 */
	 private static $statuses = ['status','critical','failure','error','warning','success','notice','info'];

    /**
     * @var string
     */
    const GROUP_END = 'groupEnd';

    /**
     * @var string
     */
    const GROUP_COLLAPSED = 'groupCollapsed';

    /**
     * @var string
     */
    const TABLE = 'table';

    /**
     * @var string
     */
    protected $_php_version;

    /**
     * @var int
     */
    protected $_timestamp;

    /**
     * @var int
     */
    protected $_start_time;

    /**
     * @var int

     */
    protected $_debug_list;


    /**
     * @var array
     */
    public $_json = [
        'version' => self::VERSION,
        'columns' => ['log', 'backtrace', 'type'],
        'rows' => []
    ];

    /**
     * @var array
     */
    protected $_backtraces = [];

    /**
     * @var bool
     */
    protected $_error_triggered = false;

    /**
     * @var array
     */
    protected $_settings = [
        self::BACKTRACE_LEVEL => 1
    ];

    /**
     * @var QuantumPHP
     */
    protected static $_instance;

    /**
     * Prevent recursion when working with objects referring to each other
     *
     * @var array
     */
    protected $_processed = [];

    /**
     * constructor
     */
    private function __construct()
    {
		$this->_start_time = microtime(true);
        $this->_php_version = phpversion();
        $this->_timestamp = $this->_php_version >= 5.1 ? $_SERVER['REQUEST_TIME'] : time();
        $this->_json['request_uri'] = $_SERVER['REQUEST_URI'];
    }

    /**
     * gets instance of this class
     *
     * @return QuantumPHP
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * logs a variable to the console
     *
     * @param mixed $data,... unlimited OPTIONAL number of additional logs [...]
     * @return void
     */
    public static function log()
    {
        $args = func_get_args();
        return self::_log('', $args);
    }

    /**
     * logs a warning to the console
     *
     * @param mixed $data,... unlimited OPTIONAL number of additional logs [...]
     * @return void
     */
    public static function warn()
    {
        $args = func_get_args();
        return self::_log(self::WARN, $args);
    }

    /**
     * logs an error to the console
     *
     * @param mixed $data,... unlimited OPTIONAL number of additional logs [...]
     * @return void
     */
    public static function error()
    {
        $args = func_get_args();
        return self::_log(self::ERROR, $args);
    }

    /**
     * sends a group log
     *
     * @param string value
     */
    public static function group()
    {
        $args = func_get_args();
        return self::_log(self::GROUP, $args);
    }

    /**
     * sends an info log
     *
     * @param mixed $data,... unlimited OPTIONAL number of additional logs [...]
     * @return void
     */
    public static function info()
    {
        $args = func_get_args();
        return self::_log(self::INFO, $args);
    }

    /**
     * sends a collapsed group log
     *
     * @param string value
     */
    public static function groupCollapsed()
    {
        $args = func_get_args();
        return self::_log(self::GROUP_COLLAPSED, $args);
    }

    /**
     * ends a group log
     *
     * @param string value
     */
    public static function groupEnd()
    {
        $args = func_get_args();
        return self::_log(self::GROUP_END, $args);
    }

    /**
     * sends a table log
     *
     * @param string value
     */
    public static function table()
    {
        $args = func_get_args();
        return self::_log(self::TABLE, $args);
    }

    /**
     * internal logging call
     *
     * @param string $type
     * @return void
     */
    protected static function _log($type, array $args, $prepend = false)
    {
        // nothing passed in, don't do anything
        if (count($args) == 0 && $type != self::GROUP_END) {
            return;
        }

        $logger = self::getInstance();

        $logger->_processed = [];

        $logs = [];
        foreach ($args as $arg) {
            $logs[] = $logger->_convert($arg);
        }

        $backtrace = debug_backtrace(false);
        $level = $logger->getSetting(self::BACKTRACE_LEVEL);

        $backtrace_message = 'unknown';
        if (isset($backtrace[$level]['file']) && isset($backtrace[$level]['line'])) {
            $backtrace_message = $backtrace[$level]['file'] . ' : ' . $backtrace[$level]['line'];
        }

        $logger->_addRow($logs, $backtrace_message, $type, $prepend);
    }

    /**
     * converts an object to a better format for logging
     *
     * @param Object
     * @return array
     */
    protected function _convert($object)
    {
        // if this isn't an object then just return it
        if (!is_object($object)) {
            return $object;
        }

        //Mark this object as processed so we don't convert it twice and it
        //Also avoid recursion when objects refer to each other
        $this->_processed[] = $object;

        $object_as_array = [];

        // first add the class name
        $object_as_array['___class_name'] = get_class($object);

        // loop through object vars
        $object_vars = get_object_vars($object);
        foreach ($object_vars as $key => $value) {

            // same instance as parent object
            if ($value === $object || in_array($value, $this->_processed, true)) {
                $value = 'recursion - parent object [' . get_class($value) . ']';
            }
            $object_as_array[$key] = $this->_convert($value);
        }

        $reflection = new ReflectionClass($object);

        // loop through the properties and add those
        foreach ($reflection->getProperties() as $property) {

            // if one of these properties was already added above then ignore it
            if (array_key_exists($property->getName(), $object_vars)) {
                continue;
            }
            $type = $this->_getPropertyKey($property);

            if ($this->_php_version >= 5.3) {
                $property->setAccessible(true);
            }

            try {
                $value = $property->getValue($object);
            } catch (ReflectionException $e) {
                $value = 'only PHP 5.3 can access private/protected properties';
            }

            // same instance as parent object
            if ($value === $object || in_array($value, $this->_processed, true)) {
                $value = 'recursion - parent object [' . get_class($value) . ']';
            }

            $object_as_array[$type] = $this->_convert($value);
        }
        return $object_as_array;
    }

    /**
     * takes a reflection property and returns a nicely formatted key of the property name
     *
     * @param ReflectionProperty
     * @return string
     */
    protected function _getPropertyKey(ReflectionProperty $property)
    {
        $static = $property->isStatic() ? ' static' : '';
        if ($property->isPublic()) {
            return 'public' . $static . ' ' . $property->getName();
        }

        if ($property->isProtected()) {
            return 'protected' . $static . ' ' . $property->getName();
        }

        if ($property->isPrivate()) {
            return 'private' . $static . ' ' . $property->getName();
        }
    }

    /**
     * adds a value to the data array
     *
     * @var mixed
     * @return void
     */
    protected function _addRow(array $logs, $backtrace, $type, $prepend = false)
    {
        // if this is logged on the same line for example in a loop, set it to null to save space
        if (in_array($backtrace, $this->_backtraces)) {
            $backtrace = null;
        }

        // for group, groupEnd, and groupCollapsed
        // take out the backtrace since it is not useful
        if ($type == self::GROUP || $type == self::GROUP_END || $type == self::GROUP_COLLAPSED) {
            $backtrace = null;
        }

        if ($backtrace !== null) {
            $this->_backtraces[] = $backtrace;
        }

        $row = [$logs, $backtrace, $type];

		if( !$prepend ){
			$this->_json['rows'][] = $row;
		} else {
			array_unshift($this->_json['rows'], $row);
		}
    }

	/**
     * Sends debug logs to the browser
     *
     * @var mixed
     * @return void
     */
	protected function _writeLogs($data)
	{
		// in case cookie mode was used, prevent previous log from persisting
		$i = 0;
		while(isset($_COOKIE['fortephplog'.$i]))
		{
			setcookie('fortephplog'.$i,'',time()-28400,'/');
			$i++;
		}

		if(self::$MODE == 0)
		{
			echo '<!-- fortephplog '.$this->_encode($data).' -->';
		}
		if(self::$MODE == 1 || self::$MODE == 2)
		{
			$encdata = $this->_shrinkLog($data);

			// cookies larger than 4kB can break
			$bits = str_split($encdata, 2000);
			$i = 0;
			foreach($bits as $bite)
			{
				setcookie('fortephplog'.$i,$bite,time()+3600,'/');
				$i++;
			}
		}
		if(self::$MODE == 1 || self::$MODE == 3)
		{
			// Not sure if Chrome Logger allows chunking of header
			// If so, we can update this to match chunked cookies above
			header(self::HEADER_NAME . ': ' . $encdata);
		}
	}

	/**
	 * checks if headers will be too large. If so, it
	 * will remove notices first, then other types until
	 * the header will be small enough.
	 * @author Frank Forte <frank.forte@gmail.com>
	 * @var array
	 * @return string
	 */
	protected function _shrinkLog($data)
    {
		$encdata = $this->_encode($data);

		if(strlen($encdata) > self::$HEADER_LIMIT)
		{
			array_unshift($data['rows'],[['!! WARNING !!! Headers too large, log truncated to prevent Apache 500 Server Error.'], 'QuantumPHP: '.__LINE__, self::ERROR]);
		}

		/*
		If we want to check the entire header size, use this for good estimate:
		$cur = headers_list();
		$cur = json_encode($cur);
		$cursize = strlen($cur);
		*/
		while(strlen($encdata) > self::$HEADER_LIMIT)
		{
			$shrinking = false;

			// first remove regular status messages from tables from start to finish
			// try to leave behind warnings, errors
			foreach($data['rows'] as $j => $row)
			{
				if(isset($row[2]) && $row[2] === 'table')
				{
					foreach($row[0] as $k => $ro)
					{
						$len = sizeof($ro) -1;

						// $ro is array of messages in the table
						// loop through them from end to start
						for($i = $len; $i >=0; $i --)
						{

							if(isset($data['rows'][$j][0][$k][$i]['Level'])
								&& $data['rows'][$j][0][$k][$i]['Level'] == 'status')
							{
								// remove this message
								unset($data['rows'][$j][0][$k][$i]);
								$shrinking = true;
								break 3;
							}

							// old array structure
							elseif(isset($data['rows'][$j][0][$k][$i][3])
								&& $data['rows'][$j][0][$k][$i][3] == 'status')
							{
								// remove this message
								unset($data['rows'][$j][0][$k][$i]);
								$shrinking = true;
								break 3;
							}
						}
						// still not shrinking?  remove everything but errors
						for($i = $len; $i >=0; $i --)
						{

							if(isset($data['rows'][$j][0][$k][$i]['Level'])
								&& $data['rows'][$j][0][$k][$i]['Level'] != 'error')
							{
								// remove this message
								unset($data['rows'][$j][0][$k][$i]);
								$shrinking = true;
								break 3;
							}

							// old array structure
							elseif(isset($data['rows'][$j][0][$k][$i][3])
								&& $data['rows'][$j][0][$k][$i][3] != 'error')
							{
								// remove this message
								unset($data['rows'][$j][0][$k][$i]);
								$shrinking = true;
								break 3;
							}
						}
					}
				}
			}

			// only errors left in the tables, or no tables to shrink
			// lets remove regular logs
			if(!$shrinking)
			{
				foreach($data['rows'] as $j => $row)
				{
					if(isset($row[$j][2]) && in_array($row[$j][2],self::$statuses))
					{
						if($row[$j][2] !== 'error')
						{
							unset($data['rows'][$j]);
							$shrinking = true;
							break;
						}
					}
				}

			}

			// ok now remove some errors in the table
			foreach($data['rows'] as $j => $row)
			{
				if(isset($row[2]) && $row[2] === 'table')
				{
					foreach($row[0] as $k => $ro)
					{
						array_pop($data['rows'][$j][0][$k]);
						$shrinking = true;
						break 2;
					}
				}
			}

			// last resort - remove each debug entry until we are small enough or empty
			if(!$shrinking)
			{
				array_pop($data['rows']);
			}

			$encdata = $this->_encode($data);
		}

		return $encdata;
	}

    /**
     * encodes the data to be sent along with the request
     *
     * @param array $data
     * @return string
     */
    protected function _encode($data)
    {
        return base64_encode(utf8_encode(json_encode((object)$data)));
    }

    /**
     * adds a setting
     *
     * @param string key
     * @param mixed value
     * @return void
     */
    public function addSetting($key, $value)
    {
        $this->_settings[$key] = $value;
    }

    /**
     * add ability to set multiple settings in one call
     *
     * @param array $settings
     * @return void
     */
    public function addSettings(array $settings)
    {
        foreach ($settings as $key => $value) {
            $this->addSetting($key, $value);
        }
    }

    /**
     * gets a setting
     *
     * @param string key
     * @return mixed
     */
    public function getSetting($key)
    {
        if (!isset($this->_settings[$key])) {
            return null;
        }
        return $this->_settings[$key];
    }

	public static function add($comment, $level = 'status', $exceptionObj = false, $time = false, $file = false, $line = false, $function = false)
	{

		if(!in_array($level,self::$statuses))
		{
			throw new Exception('Debug status is not valid: '.print_r($level,true));
		}

		$backtrace = debug_backtrace();
		$file = $file !== false ? $file : $backtrace[0]['file'];
		$line = $line !== false ? $line : $backtrace[0]['line'];

		if($function === false)
		{
			if(isset($backtrace[1]['function']))
			{
				$function = $backtrace[1]['function'];

				if(isset($backtrace[1]['object']))
				{
					if(method_exists($backtrace[1]['object'],'__tostring'))
					{
						$function = $backtrace[1]['object'].$backtrace[1]['type'].$function;
					}
					else
					{
						$function = get_class($backtrace[1]['object']).$backtrace[1]['type'].$function;
					}
				}
				elseif(isset($backtrace[1]['class']))
				{
					$function = $backtrace[1]['class'].$backtrace[1]['type'].$function;
				}

				$function .= '()';
			}
			else
			{
				$function = '';
			}
		}

		$logger = self::getInstance();

		$entry['Time'] = $time ? $time : microtime(true) - $logger->_start_time;
		$entry['Level'] = $level;
		$entry['Comment'] = $comment;
		$entry['Function'] = $function;
		$entry['File'] = $file;
		$entry['Line'] = $line;
		$entry['Exception'] = !$exceptionObj ? null : [
			 'message' => $exceptionObj->getMessage()
			,'file' => $exceptionObj->getFile()
			,'line' => $exceptionObj->getLine()
			,'trace' => $exceptionObj->getTrace()
		];

		$logger->_debug_list[] = $entry;
	}

	/**
	* output http headers for FirePHP add-on
	*/
	public static function send()
	{
		if( headers_sent())
		{
			return;
		}

		$level_count = [];
		foreach(self::$statuses as $s){
			$level_count[$s] = 0;
		}

		$logger = self::getInstance();
		if(!empty($logger->_debug_list))
		{
			foreach($logger->_debug_list as $entry)
			{
				if($entry['Level'] != 'status')
				{
					$level_count[$entry['Level']]++;
				}
			}
		}

		$table_header = "QuantumPHP ".self::VERSION.': '.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].' ';
		foreach($level_count as $level=>$num)
		{
			if($num > 0)
			{
				if($num == 1) { $s = ''; } else { $s = 's'; }
				$table_header .= ' ('.$num.' '.$level.' message'.$s.')';
			}
		}

		$logger::_log('info', [$table_header.' Peak Memory Usage '.round(memory_get_peak_usage() / (1024 * 1024),2).'MB '], true);

		if(!empty($logger->_debug_list))
		{
			self::table($logger->_debug_list);
		}

		// send server logs to browser
		$logger->_writeLogs($logger->_json);

		// prevent re-sending logs
		$logger->_debug_list = [];

	}

}
