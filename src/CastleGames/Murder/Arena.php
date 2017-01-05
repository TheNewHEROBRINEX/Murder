<?php

namespace CastleGames\Murder;

class Arena {

    const GAME_IDLE = 0;
    const GAME_STARTING = 1;
    const GAME_RUNNING = 2;

    private $pg, $slot, $name, $countdown, $maxtime, $void, $status = self::GAME_IDLE;

    public function __construct(Main $plugin, $slot = 0, $name = 'world', $countdown = 60, $maxtime = 1200, $void = 0) {
        $this->pg = $plugin;
        $this->slot = $slot;
        $this->name = $name;
        $this->countdown = $countdown;
        $this->maxtime = $maxtime;
        $this->void = $void;
    }

    public function isIdle() {
        return $this->status == 0;
    }

    public function isStarting() {
        return $this->status == 1;
    }

    public function isRunning() {
        return $this->status == 2;
    }
}
