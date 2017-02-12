<?php

namespace TheNewHEROBRINE\Murder\projectile;

use pocketmine\Player;
use TheNewHEROBRINE\Murder\particle\MobSpellParticle;

class MurderGunProjectile extends MurderProjectile {

    public function getName(){
        return "MurderGunProjectile";
    }

    public function onUpdate($currentTick) {
        if ($this->closed) {
            return false;
        }

        $this->timings->startTiming();

        $hasUpdate = parent::onUpdate($currentTick);

        if ($this->age > 30 * 20 or !isset($this->shootingEntity) or $this->hadCollision) {
            $this->kill();
            $hasUpdate = true;
        } else {
            for ($i = 0; $i < 20; $i++) {
                $this->level->addParticle(new MobSpellParticle($this->add(
                    $this->width / 2 + mt_rand(-100, 100) / 500,
                    $this->height / 2 + mt_rand(-100, 100) / 500,
                    $this->width / 2 + mt_rand(-100, 100) / 500),
                    mt_rand(0, 255),
                    mt_rand(0, 255),
                    mt_rand(0, 255)));
            }
        }

        $this->timings->startTiming();

        return $hasUpdate;
    }

    public function spawnTo(Player $player) {
        //invisible, only particles
    }
}