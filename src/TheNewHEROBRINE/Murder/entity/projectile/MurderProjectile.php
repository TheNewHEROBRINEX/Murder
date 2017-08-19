<?php

namespace TheNewHEROBRINE\Murder\entity\projectile;

use pocketmine\entity\Entity;
use pocketmine\entity\Projectile;
use TheNewHEROBRINE\Murder\entity\Corpse;

abstract class MurderProjectile extends Projectile {
    public $width = 0.5;
    public $length = 0.5;
    public $height = 0.5;

    protected $gravity = 0;
    protected $drag = 0;

    /**
     * @param Entity $entity
     * @return bool
     */
    public function canCollideWith(Entity $entity): bool {
        return parent::canCollideWith($entity) ? !$entity instanceof Corpse : false;
    }
}