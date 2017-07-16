<?php

namespace TheNewHEROBRINE\Murder;

use pocketmine\scheduler\Task;

class MurderTimer extends Task {

    private $owner;

    public function __construct(MurderMain $owner) {
        $this->owner = $owner;
    }

    public function onRun(int $tick) {
        foreach ($this->getOwner()->getArenas() as $arena)
            $arena->tick();
    }

    /**
     * @return MurderMain
     */
    public function getOwner(): MurderMain {
        return $this->owner;
    }
}
