<?php

namespace CastleGames\Murder;

use pocketmine\metadata\MetadataValue;

class MurderMetadata extends MetadataValue {

    public function __construct(MurderMain $owningPlugin) {
        parent::__construct($owningPlugin);
    }

    public function value() {
        // TODO: Implement value() method.
    }

    public function invalidate() {
        // TODO: Implement invalidate() method.
    }
}