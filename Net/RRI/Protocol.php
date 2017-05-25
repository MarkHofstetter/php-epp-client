<?php
/* Copyright (C) 2012 Bernhard Reutner-Fischer  <rep.dot.nop@gmail.com>
 *
 * Licensed under GPLv2 or later, see file LICENSE in this source tree.
 */

require_once('PEAR.php');

class Net_RRI_Protocol {
	/* Read an RRI frame from the given socket.
	 */
	static function getFrame($socket) {
		if (@feof($socket))
			return new PEAR_Error('connection closed (socket is EOF)');
		$hdr_len = 4;
		$hdr = '';
		while ($hdr_len > 0 && !feof($socket)) {
			$read = fread($socket, 4);
			$hdr_len -= strlen($read);
			$hdr .= $read;
		}
		if (empty($hdr) && feof($socket)) {
			return new PEAR_Error('connection closed (no header received and socket is EOF)');
		} elseif (false === $hdr) {
			return new PEAR_Error('Error reading from peer: '.$php_errormsg);
		} else {
			$unpacked = unpack('N', $hdr);
			$length = $unpacked[1];
			if (!isset($length) || $length < 1) {
				return new PEAR_Error(sprintf('Got a bad frame header length of %d bytes from peer', $length));
			} else {
				$frame = '';
				while (strlen($frame) < $length) $frame .= fread($socket, ($length));
				if (strlen($frame) > $length) {
					return new PEAR_Error(sprintf("Frame length (%d bytes) doesn't match header (%d bytes)", strlen($frame), ($length)));
				} else {
					return $frame;
				}
			}
		}
	}
	/* Write an xml frame to a given socket */
	static function sendFrame($socket, $xml) {
		$stuff = pack('N', strlen($xml)) . $xml;
		$len = strlen($stuff);
		while (!feof($socket) && $len > 0) {
			$wrote = fwrite($socket, $stuff, $len);
			if ($wrote < 1 || $wrote === FALSE || feof($socket))
				break;
			$len -= $wrote;
			$stuff = substr($stuff, $wrote);
		}
	}
}
?>
