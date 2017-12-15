<?php

namespace TheNewHEROBRINE\Murder\entity\projectile;

use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use TheNewHEROBRINE\Murder\entity\Corpse;

abstract class MurderProjectile extends Projectile {

    /** @var float $width */
    public $width = 0.25;

    /** @var float $height */
    public $height = 0.25;

    /** @var float $gravity */
    protected $gravity = 0;

    /** @var float $drag */
    protected $drag = 0;

    /**
     * @param Entity $entity
     * @return bool
     */
    public function canCollideWith(Entity $entity): bool {
        return parent::canCollideWith($entity) ? !$entity instanceof Corpse : false;
    }
}