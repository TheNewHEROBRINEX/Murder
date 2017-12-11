<?php

namespace TheNewHEROBRINE\Murder\entity\projectile;

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
     * @param int $currentTick
     * @return bool
     */
    public function onUpdate(int $currentTick): bool{
        if ($this->closed){
            return false;
        }

        $hasUpdate = parent::onUpdate($currentTick);

        if (!$this->isClosed() and $this->isAlive()){
            if ($this->hadCollision){
                $this->getLevel()->dropItem($this, $this->knife, new Vector3(0, 0, 0));
                $this->flagForDespawn();
                return true;
            }

            if ($this->age > 30 * 20 or $this->getOwningEntity() == null){
                $this->flagForDespawn();
                return true;
            }

        }

        return $hasUpdate;
    }

    /**
     * @param Player $player
     */
    protected function sendSpawnPacket(Player $player): void {
        if ($this->knife !== null) {
            $pk = new AddItemEntityPacket();
            $pk->entityRuntimeId = $this->getId();
            $pk->position = $this->asVector3();
            $pk->motion = $this->getMotion();
            $pk->item = $this->knife;
            $pk->metadata = $this->dataProperties;
            $player->dataPacket($pk);

            $this->sendData($player);
        }
    }
}