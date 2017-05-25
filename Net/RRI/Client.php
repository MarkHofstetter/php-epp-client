<?php
/* Copyright (C) 2012 Bernhard Reutner-Fischer  <rep.dot.nop@gmail.com>
 *
 * Licensed under GPLv2 or later, see file LICENSE in this source tree.
 */

require_once('PEAR.php');
require_once('Net/RRI/Protocol.php');

class Net_RRI_Client {
	var $socket;
	function connect($host, $port=700, $timeout=1, $ssl=true, $context=NULL) {
		$target = sprintf('%s://%s:%d', ($ssl === true ? 'tls' : 'tcp'), $host, $port);
		if (is_resource($context)) {
			$result = stream_socket_client($target, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
		} else {
			$result = stream_socket_client($target, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT);
		}
		if (!$result) {
			return new PEAR_Error("Error connecting to $target: $errstr (code $errno)");
		} else {
			$this->socket = $result;
			return $this->getFrame();
		}
	}
	function getFrame() {
		return Net_RRI_Protocol::getFrame($this->socket);
	}
	function sendFrame($xml) {
		return Net_RRI_Protocol::sendFrame($this->socket, $xml);
	}
	function request($xml) {
		$this->sendFrame($xml);
		return $this->getFrame();
	}
	function disconnect() {
		return @fclose($this->socket);
	}
	function ping() {
		return (!is_resource($this->socket) || feof($this->socket) ? false : true);
	}
}
?>
