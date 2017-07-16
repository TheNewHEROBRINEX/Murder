<?php

namespace TheNewHEROBRINE\Murder\projectile;

use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddItemEntityPacket;
use pocketmine\Player;

class MurderKnifeProjectile extends MurderProjectile {

    protected $knife;

    public function __construct(Level $level, CompoundTag $nbt, Player $murderer = null) {
        if ($murderer !== null)
            $this->knife = $murderer->getInventory()->getItemInHand();
        parent::__construct($level, $nbt, $murderer);
    }

    public function getName() {
        return "MurderKnifeProjectile";
    }

    public function onUpdate($currentTick) {
        if ($this->closed) {
            return false;
        }

        $hasUpdate = parent::onUpdate($currentTick);

        if ($this->age > 30 * 20 or $this->getOwningEntity() == null) {
            $this->kill();
            $hasUpdate = true;
        }

        return $hasUpdate;
    }

    public function spawnTo(Player $player) {
        if ($this->knife !== null) {
            $pk = new AddItemEntityPacket();
            $pk->entityRuntimeId = $this->getId();
            $pk->x = $this->x;
            $pk->y = $this->y;
            $pk->z = $this->z;
            $pk->speedX = $this->motionX;
            $pk->speedY = $this->motionY;
            $pk->speedZ = $this->motionZ;
            $pk->item = $this->knife;
            $player->dataPacket($pk);

            $this->sendData($player);

            parent::spawnTo($player);
        }
    }
}