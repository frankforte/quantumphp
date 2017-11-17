/**
 * Copyright 2017 Frank Forte
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
 * Client Side Firefox 57+ (Quantum) PHP debugger class
 *
 * @package QuantumPHP
 * @author Frank Forte <frank.forte@gmail.com>
 */
 
 var ffQunatumPhp = {};
/**
 * Get a cookie value. If the value is valid json, 
 * the return value will be the result of JSON.parse(value)
 *
 * @param string
 * @return mixed value
 */
ffQunatumPhp.getcookie = function(c_name){
	
	var c_value = document.cookie;
	var c_start = c_value.indexOf(" " + c_name + "=");
	
	if (c_start == -1){ c_start = c_value.indexOf(c_name + "=");}
	if (c_start == -1){ c_value = null;}
	else{
		c_start = c_value.indexOf("=", c_start) + 1;
		var c_end = c_value.indexOf(";", c_start);
		if (c_end == -1){ c_end = c_value.length; }
		c_value = unescape(c_value.substring(c_start,c_end));
		if(c_value) {
			try{ var v = JSON.parse(c_value); return v;} catch (e) {}
		}
	}
	return c_value;
};
/**
 * Retrieves and parses the server log, and adds it to the developer console
 */
(ffQunatumPhp.show_console = function(){
	try{
		var log = JSON.parse(atob(ffQunatumPhp.getcookie('fortephplog')));
		if(!log){ log =["no log"]; }
		console.table(log);
		document.cookie = "fortephplog=; expires=Thu, 01 Jan 1970 00:00:01 GMT;";
	} catch (e) {}
})();