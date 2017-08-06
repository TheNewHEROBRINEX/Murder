<?php

namespace TheNewHEROBRINE\Murder\entities;

use pocketmine\Player;

class MurderPlayer extends Player {
    /**
     * @param string $text
     */
    public function setButtonText(string $text){
        $this->setDataProperty(self::DATA_INTERACTIVE_TAG, self::DATA_TYPE_STRING, $text);
    }

    public function getMurderName(){
        return $this->getName() !== $this->getDisplayName() ? $this->getDisplayName() . " (" . $this->getName() . ")"  : $this->getName();
    }
}