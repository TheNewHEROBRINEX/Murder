<?php

namespace TheNewHEROBRINE\Murder\entities;

use pocketmine\Player;

class MurderPlayer extends Player {
    /**
     * @param string $text
     */
    public function setButton(string $text){
        $this->setDataProperty(self::DATA_INTERACTIVE_TAG, self::DATA_TYPE_STRING, $text);
    }
}