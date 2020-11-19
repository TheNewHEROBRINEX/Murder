<?php
declare(strict_types=1);

namespace TheNewHEROBRINE\Murder\entity;

use pocketmine\item\Item;
use pocketmine\Player;

class MurderPlayer extends Player{
	public function getMurderName() : string{
		return $this->getName() !== $this->getDisplayName() ? $this->getDisplayName() . " (" . $this->getName() . ")" : $this->getName();
	}

	public function getItemCount(Item $item = null) : int{
		$item = $item ?? Item::get(Item::EMERALD);
		$inv = $this->getInventory();
		if(!$inv->contains($item)){
			return 0;
		}
		return $inv->getItem($inv->first($item))->getCount();
	}
}