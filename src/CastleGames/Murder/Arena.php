<?php

namespace CastleGames\Murder;

public class Arena {
    public function __construct(Main $plugin, $murderName = 'murder', $slot = 0, $world = 'world', $countdown = 60, $maxtime = 1200, $void = 0) {
        $this->pg = $plugin;
        $this->murderName = $Murdername;
        $this->slot = ($slot + 0);
        $this->world = $world;
        $this->countdown = ($countdown + 0);
        $this->maxtime = ($maxtime + 0);
        $this->void = $void;
    }
}
