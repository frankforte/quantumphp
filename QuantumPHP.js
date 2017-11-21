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

 // prevent errors in browsers without console
if(!console){
	console = {};
	var calls = ["log", "debug", "info", "warn", "error", "assert", "dir", "dirxml",
    "group", "groupEnd", "time", "timeEnd", "count", "trace", "profile", "profileEnd","table"];
	for(var i = 0; i < calls.length; i++){
		console[calls[i]] = function(){};
	}
	window.console = console;
}

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
		// get logs from gookie
		var log = JSON.parse(atob(ffQunatumPhp.getcookie('fortephplog')));
		if(log){

			for(var i in log["rows"]){

				if(log["rows"][i][2] == "table"){
					console.table(log["rows"][i][0][0]);
				} else {

					for(var j in log["rows"][i][0]){
						console[log["rows"][i][2]](log["rows"][i][0][j] + " in " +log["rows"][i][1]);
					}

				}
			}
		}
	} catch (e) {}
	// clear cookie to prevent repeated logs
	document.cookie = "fortephplog=; path=/; expires=Thu, 01 Jan 1970 00:00:01 GMT;";
})();