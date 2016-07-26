<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4: */ 
/**+----------------------------------------------------------------------+
 * | PHP version 5                                                        |
 * +----------------------------------------------------------------------+
 * | Copyright (c) 1997-2008 The PHP Group                                |
 * +----------------------------------------------------------------------+
 * | All rights reserved.                                                 |
 * |                                                                      |
 * | Redistribution and use in source and binary forms, with or without   |
 * | modification, are permitted provided that the following conditions   |
 * | are met:                                                             |
 * |                                                                      |
 * | - Redistributions of source code must retain the above copyright     |
 * | notice, this list of conditions and the following disclaimer.        |
 * | - Redistributions in binary form must reproduce the above copyright  |
 * | notice, this list of conditions and the following disclaimer in the  |
 * | documentation and/or other materials provided with the distribution. |
 * | - Neither the name of the The PEAR Group nor the names of its        |
 * | contributors may be used to endorse or promote products derived from |
 * | this software without specific prior written permission.             |
 * |                                                                      |
 * | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS  |
 * | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT    |
 * | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS    |
 * | FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE       |
 * | COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,  |
 * | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, |
 * | BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;     |
 * | LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER     |
 * | CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT   |
 * | LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN    |
 * | ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE      |
 * | POSSIBILITY OF SUCH DAMAGE.                                          |
 * +----------------------------------------------------------------------+
 *
 * @category Services
 * @package  File_Formats
 * @author   Marshall Roch <mroch@php.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version  CVS: $Id: Vcalendar.php 283050 2009-06-29 18:34:52Z till $
 * @link     http://pear.php.net/package/File_IMC
 */

/**
*
* Parser for vCalendars.
*
* This class parses vCalendar sources from file or text into a
* structured array.
*
* Usage:
*
* <code>
*     // include this class file
*     require_once 'File/IMC.php';
*
*     // instantiate a parser object
*     $parse = new File_IMC::parse('vCalendar');
*
*     // parse a vCalendar file and store the data
*     // in $calinfo
*     $calinfo = $parse->fromFile('sample.vcs');
*
*     // view the card info array
*     echo '<pre>';
*     print_r($calinfo);
*     echo '</pre>';
* </code>
*
*
* @author Paul M. Jones <pjones@ciaweb.net>
* @package File_IMC
*/

/**
 * The common IMC parser is needed
 */
require_once 'File/IMC/Parse.php';

class File_IMC_Parse_Vcalendar extends File_IMC_Parse
{
    // nothing was needed parsing so far.
}