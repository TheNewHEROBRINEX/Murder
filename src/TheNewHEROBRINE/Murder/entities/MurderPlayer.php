<?php

namespace TheNewHEROBRINE\Murder\entities;

use pocketmine\item\Item;
use pocketmine\Player;

class MurderPlayer extends Player {

    /**
     * @param string $text
     */
    public function setButtonText(string $text){
        $this->setDataProperty(self::DATA_INTERACTIVE_TAG, self::DATA_TYPE_STRING, $text);
    }

    /**
     * @return string
     */
    public function getMurderName() : string{
        return $this->getName() !== $this->getDisplayName() ? $this->getDisplayName() . " (" . $this->getName() . ")"  : $this->getName();
    }

    /**
     * @param Item $item
     * @return int
     */
    public function getItemCount(Item $item = null) : int {
        $item = $item ?? Item::get(Item::EMERALD);
        $inv = $this->getInventory();
        if (!$inv->contains($item)){
            return 0;
        }
        return $inv->getItem($inv->first($item))->getCount();
    }
}