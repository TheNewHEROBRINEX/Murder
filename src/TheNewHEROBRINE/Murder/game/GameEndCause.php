<?php
declare(strict_types=1);

namespace TheNewHEROBRINE\Murder\game;

interface GameEndCause{
	public function getMessage() : string;
}