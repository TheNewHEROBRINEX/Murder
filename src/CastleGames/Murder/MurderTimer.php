<?php

namespace CastleGames\Murder;

class MurderTimer {

    private $owner;

    public function __construct(MurderMain $owner) {
        $this->owner = $owner;
    }

    public function onRun($tick) {
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
