<?php

namespace TheNewHEROBRINE\Murder\projectile;

use pocketmine\level\format\Chunk;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\protocol\AddItemEntityPacket;
use pocketmine\Player;

class MurderKnifeProjectile extends MurderProjectile  {

    protected $knife;

    public function getName() {
        return "MurderKnifeProjectile";
    }

    public function __construct(Chunk $chunk, CompoundTag $nbt, Player $murderer = null) {
        if ($murderer !== null)
            $this->knife = $murderer->getInventory()->getItemInHand();
        parent::__construct($chunk, $nbt, $murderer);
    }

    public function onUpdate($currentTick) {
        if ($this->closed) {
            return false;
        }

        $hasUpdate = parent::onUpdate($currentTick);

        if ($this->age > 30 * 20 or !isset($this->shootingEntity)) {
            $this->kill();
            $hasUpdate = true;
        }

        return $hasUpdate;
    }

    public function spawnTo(Player $player) {
        if ($this->knife !== null) {
            $pk = new AddItemEntityPacket();
            $pk->eid = $this->getId();
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