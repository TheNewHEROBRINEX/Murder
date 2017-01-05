<?php

namespace CastleGames\Murder;

use pocketmine\scheduler\PluginTask;

class MurderTimer extends PluginTask {

    public function __construct(Main $owner) {
        parent::__construct($owner);
    }

    public function onRun($tick) {
        foreach ($this->getOwner()->getArenas() as $arena)
            $arena->tick();
    }
}
