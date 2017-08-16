<?php

namespace TheNewHEROBRINE\Murder\entities\projectiles;

use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddItemEntityPacket;
use pocketmine\Player;

class MurderKnifeProjectile extends MurderProjectile {

    /** @var Item $knife */
    protected $knife;

    /**
     * @param Level $level
     * @param CompoundTag $nbt
     * @param Player|null $murderer
     */
    public function __construct(Level $level, CompoundTag $nbt, Player $murderer = null) {
        if ($murderer !== null){
            $this->knife = $murderer->getInventory()->getItemInHand();
        }
        parent::__construct($level, $nbt, $murderer);
    }

    /**
     * @return string
     */
    public function getName(): string{
        return "MurderKnifeProjectile";
    }

    /**
     * @param $currentTick
     * @return bool
     */
    public function onUpdate($currentTick): bool{
        if ($this->closed){
            return false;
        }

        $hasUpdate = parent::onUpdate($currentTick);

        if ($this->isAlive()){
            if ($this->hadCollision){
                $this->getLevel()->dropItem($this, $this->knife, new Vector3(0, 0, 0));
                $this->kill();
                return true;
            }

            if ($this->age > 30 * 20 or $this->getOwningEntity() == null){
                $this->kill();
                return true;
            }

        }

        return $hasUpdate;
    }

    /**
     * @param Player $player
     */
    public function spawnTo(Player $player) {
        if ($this->knife !== null){
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