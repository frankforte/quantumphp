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

/* prevent errors in browsers without console */
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
 var ffQuantumPhp = {};
/**
 * Get a cookie value. If the value is valid json,
 * the return value will be the result of JSON.parse(value)
 *
 * @param string
 * @return mixed value
 */
ffQuantumPhp.getcookie = function(c_name){
	var c_value = document.cookie;
	var c_start = c_value.indexOf(" " + c_name + "=");
	if (c_start == -1){ c_start = c_value.indexOf(c_name + "=");}
	if (c_start == -1){
		c_value = null;
	} else {
		c_start = c_value.indexOf("=", c_start) + 1;
		var c_end = c_value.indexOf(";", c_start);
		if (c_end == -1){ c_end = c_value.length; }
		c_value = c_value.replace(/\+/g," ");
		c_value = unescape(c_value.substring(c_start,c_end));
	}
	return c_value;
};

/**
 * Get log from cookie(s), then clears each cookie
 * @return string base64 encoded log
 */
ffQuantumPhp.cookie_log = function(){

	/* get logs from gookie, one bite at a time */
	var log = "";
	var i = 0;
	var samesite = window.location.protocol == "https:" ? "None" : "Lax";
	do{
		var bite = ffQuantumPhp.getcookie('fortephplog'+i);
		if(bite != null){
			log = log+bite;

			/* clear cookie to prevent repeat logs */
			document.cookie = "fortephplog"+i+"=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/; SameSite="+samesite;
			/* Must remove cookie with exact same parameters used to add cookie */
			document.cookie = "fortephplog"+i+"=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/; domain="+window.location.host+"; SameSite="+samesite;
			document.cookie = "fortephplog"+i+"=; expires=Thu, 01 Jan 1970 23:00:00 UTC; path=/; domain=."+window.location.host+"; SameSite="+samesite;
		}
		i++;
	} while (bite);

	return log;
}

/**
 * Get log from HTML comment
 * @return string base64 encoded log
 */
ffQuantumPhp.comment_log = function(){

	var log = "";
	for(var i in document.childNodes){

		if(document.childNodes[i].nodeType == 8){
			var match = document.childNodes[i].nodeValue.match(/ fortephplog ([^> ]+) /);
			if(match){
				log = match[1];
				break;
			}
		}
	}

	return log;
}

/**
 * Log exception to the developer console
 */
ffQuantumPhp.log_exception = function(e){
	var s = "";
	if(e.fileName){ s += e.fileName;}
	if(e.lineNumber){ s += " line "+e.lineNumber;}
	if(e.columnNumber){ s += " col "+e.columnNumber;}
	if(e.message){ s += " "+e.message;}
	console.warn(s);
}

/**
 * Retrieves and parses the server log, and adds it to the developer console
 */
ffQuantumPhp.show_console = function(log){
	try{
		if(log){

			if(typeof(log) == "string"){ log = JSON.parse(atob(log)); }

			for(var i in log["rows"]){

				if(log["rows"][i][2] == "table"){
					console.table(log["rows"][i][0][0]);
				} else {
					for(var j in log["rows"][i][0]){
						if(typeof console[ log["rows"][i][2] ] != "undefined" ){
							console[log["rows"][i][2]](log["rows"][i][0][j] + " [" +log["rows"][i][1]+"]");
						} else {
							console.log(log["rows"][i][0]+" ["+log["rows"][i][1]+"]");
						}
					}

				}
			}
		}
	} catch (e) {
		ffQuantumPhp.log_exception(e);
	}
}

ffQuantumPhp.lastComment = '';
ffQuantumPhp.lastCookie = '';
var cookieChanged = false;
ffQuantumPhp.logUpdate = function(){

	try{
		var log = ffQuantumPhp.cookie_log();
		if(ffQuantumPhp.lastCookie != log){
			ffQuantumPhp.lastCookie = log;
			ffQuantumPhp.show_console(log);
		}

		var log = ffQuantumPhp.comment_log();
		if(ffQuantumPhp.lastComment != log){
			ffQuantumPhp.lastComment = log;
			ffQuantumPhp.show_console(log);
		}

		/* Use timeout if included in HTML, or when cookies.onChanged does not work in web extension */
		cookieChanged = setTimeout(ffQuantumPhp.logUpdate, 2500);

	} catch (e) {
		ffQuantumPhp.log_exception(e);
	}
}

/* start update loop */
ffQuantumPhp.logUpdate();
