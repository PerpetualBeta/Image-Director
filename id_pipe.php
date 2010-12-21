<?php

define("VERSION", "1.02");

/*
	VERSION HISTORY

	1.00: 12th July, 2010
		Initial release

	1.01: 12th July, 2010
		Minor bug fix.

	1.02: 12th July, 2010
		Minor bug fix.

*/

/*

	Program:
		id_pipe: An Image Director ancillary function for recursive operations, by Jonathan M. Hollin (darkblue@sdf.lonestar.org).

	Copyright:
		Copyright 2010 Jonathan M. Hollin

	License:
		This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

		This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

		You should have received a copy of the GNU General Public License along with this program. If not, see: http://www.gnu.org/licenses/

	Usage:
		include				'./id_pipe.php';
		$url					= 'http://example.com/image_director.php?src=example.png&fo=1';
		$image_path_and_filename	= id_pipe($url);

*/

	function id_pipe($url) {
		// Decode the URL...
		$arguments = '';
		$parts = explode('?', $url);
		$url = $parts[0];
		$args = explode('&', $parts[1]);
		foreach ($args as &$parameter) {
			$arguments .= ' -d ' . $parameter;
		}

		// Set up the pipe...
		$descriptorspec = array(
			0 => array('pipe','r'),
			1 => array('pipe','w'),
			2 => array('file','/dev/null','a')
		);

		// Define current working directory...
		$cwd = './';

		// Open process pipe and pass it the relevant arguments...
		$process = proc_open('curl ' . $url . ' -m 60 -G' . $arguments, $descriptorspec, $pipes, $cwd);

		if (is_resource($process)) {
			// Get pipe output...
			$the_stream = '';
			$the_stream = stream_get_contents($pipes[1]);
		}

		// Close pipe...
		fclose($pipes[1]);

		// Close process...
		proc_close($process);

		// Pass back...
		return $the_stream;
	}

?>