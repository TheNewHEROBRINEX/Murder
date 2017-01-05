<?php

namespace CastleGames\Murder;

class Arena {

	private $pg, $slot, $name, $countdown, $maxtime, $void;

	public function __construct(Main $plugin, $slot = 0, $name = 'world', $countdown = 60, $maxtime = 1200, $void = 0) {
		$this->pg = $plugin;
		$this->slot = $slot;
		$this->name = $name;
		$this->countdown = $countdown;
		$this->maxtime = $maxtime;
		$this->void = $void;
	}
}
