<?php
declare(strict_types=1);

namespace TheNewHEROBRINE\Murder\entity\projectile;

use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddItemEntityPacket;
use pocketmine\Player;
use TheNewHEROBRINE\Murder\entity\Corpse;

class MurderKnifeProjectile extends Projectile {

    /** @var float $width */
    public $width = 0.25;

    /** @var float $height */
    public $height = 0.25;

    /** @var float $gravity */
    protected $gravity = 0;

    /** @var float $drag */
    protected $drag = 0;

    /** @var Item $knife */
    protected $knife;

    /**
     * @param Entity $entity
     * @return bool
     */
    public function canCollideWith(Entity $entity): bool {
        return parent::canCollideWith($entity) ? !$entity instanceof Corpse : false;
    }

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
     * @param int $tickDiff
     * @return bool
     */
    public function entityBaseTick(int $tickDiff = 1) : bool {
        if ($this->closed){
            return false;
        }

        $hasUpdate = parent::entityBaseTick($tickDiff);

        if ($this->getOwningEntity() == null){
            $this->flagForDespawn();
            return true;
        }

        if (!$this->isClosed() and $this->isAlive()){
            if ($this->hadCollision){
                $this->getLevel()->dropItem($this, $this->knife, new Vector3(0, 0, 0));
                $this->flagForDespawn();
                return true;
            }
        }


        return $hasUpdate;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
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
        }
    }
}