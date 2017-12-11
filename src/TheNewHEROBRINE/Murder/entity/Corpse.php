<?php

namespace TheNewHEROBRINE\Murder\entity;

use pocketmine\entity\Human;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;


use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;

class Corpse extends Human {
    /**
     * @param Level $level
     * @param CompoundTag $nbt
     * @param Player $player
     */
    public function __construct(Level $level, CompoundTag $nbt, Player $player = null) {
        if ($player === null){
            $this->flagForDespawn();
        }
        else{
            $nbt = self::createBaseNBT($player, null, $player->yaw, $player->pitch);
            $player->saveNBT();
            $nbt->Inventory = clone $player->namedtag->Inventory;
            $nbt->Skin = new CompoundTag("Skin", ["Data" => new StringTag("Data", $player->getSkin()->getSkinData()), "Name" => new StringTag("Name", $player->getSkin()->getSkinId())]);
            parent::__construct($level, $nbt);
            $this->setDataProperty(Human::DATA_PLAYER_BED_POSITION, Human::DATA_TYPE_POS, [(int)$player->x, (int)$player->y, (int)$player->z]);
            $this->setDataFlag(Human::DATA_PLAYER_FLAGS, Human::DATA_PLAYER_FLAG_SLEEP, true, Human::DATA_TYPE_BYTE);
        }
    }
}