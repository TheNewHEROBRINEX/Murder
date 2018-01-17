<?php
declare(strict_types=1);

namespace TheNewHEROBRINE\Murder\particle;

use pocketmine\level\particle\GenericParticle;
use pocketmine\level\particle\Particle;
use pocketmine\math\Vector3;

class MobSpellParticle extends GenericParticle {

    /**
     * @param Vector3 $pos
     * @param int $r
     * @param int $g
     * @param int $b
     * @param int $a
     */
    public function __construct(Vector3 $pos, $r = 255, $g = 255, $b = 255, $a = 255) {
        parent::__construct($pos, Particle::TYPE_MOB_SPELL_INSTANTANEOUS, (($a & 0xff) << 24) | (($r & 0xff) << 16) | (($g & 0xff) << 8) | ($b & 0xff));
    }
}