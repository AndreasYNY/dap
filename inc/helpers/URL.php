<?php

class URL {
	public static function Avatar() {
		global $URL;

		return isset($URL['avatar']) ? $URL['avatar'] : 'https://a.datenshi.xyz';
	}

	public static function Server() {
		global $URL;

		return isset($URL['server']) ? $URL['server'] : 'https://datenshi.xyz';
	}
	
	public static function Bancho() {
		global $URL;

		return isset($URL['bancho']) ? $URL['bancho'] : 'http://c.datenshi.xyz';
	}
}
