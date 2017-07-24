<?php

namespace TheNewHEROBRINE\Murder\entities;

use pocketmine\entity\Human;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;

class Corpse extends Human {

    /**
     * @param Level $level
     * @param CompoundTag $nbt
     * @param Player $player
     */
    public function __construct(Level $level, CompoundTag $nbt, Player $player) {
        $nbt = new CompoundTag("", [
            "Pos" => new ListTag("Pos", [
                new DoubleTag("", $player->x),
                new DoubleTag("", $player->y),
                new DoubleTag("", $player->z)
            ]),
            "Motion" => new ListTag("Motion", [
                new DoubleTag("", 0),
                new DoubleTag("", 0),
                new DoubleTag("", 0)
            ]),
            "Rotation" => new ListTag("Rotation", [
                new FloatTag("", $player->yaw),
                new FloatTag("", $player->pitch)
            ]),
        ]);
        $player->saveNBT();
        $nbt->Inventory = clone $player->namedtag->Inventory;
        $nbt->Skin = new CompoundTag("Skin", ["Data" => new StringTag("Data", $player->getSkinData()), "Name" => new StringTag("Name", $player->getSkinId())]);
        parent::__construct($level, $nbt);
        $this->setDataProperty(Human::DATA_PLAYER_BED_POSITION, Human::DATA_TYPE_POS, [(int)$player->x, (int)$player->y, (int)$player->z]);
        $this->setDataFlag(Human::DATA_PLAYER_FLAGS, Human::DATA_PLAYER_FLAG_SLEEP, true, Human::DATA_TYPE_BYTE);
    }
}