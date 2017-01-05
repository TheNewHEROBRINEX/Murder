<?php

namespace CastleGames\Murder;


use pocketmine\scheduler\PluginTask;


class MurderTimer extends PluginTask {

    public function onRun($tick) {
    
        foreach ($this->getOwner()->getArenas() as $Murdername => $Murderarena)
            $Murderarena->tick();

    }
}
