<?php

namespace TheNewHEROBRINE\Murder;

use pocketmine\scheduler\PluginTask;

class MurderTimer extends PluginTask {
    /**
     * @param int $tick
     */
    public function onRun(int $tick) {
        foreach ($this->getOwner()->getArenas() as $arena) {
            $arena->tick();
        }
    }
}
