<?php

final class Arena
{

    public function __construct(Main $plugin, $Murdername = 'murder', $slot = 0, $world = 'world', $countdown = 60, $maxtime = 300, $void = 0)
    {
        $this->pg = $plugin;
        $this->Murdername = $Murdername;
        $this->slot = ($slot + 0);
        $this->world = $world;
        $this->countdown = ($countdown + 0);
        $this->maxtime = ($maxtime + 0);
        $this->void = $void;
        if (!$this->reload()) {
            $this->pg->getLogger()->info(TextFormat::RED . 'An error occured while reloading the arena: ' . TextFormat::WHITE . $this->Murdername);
            $this->pg->getServer()->getPluginManager()->disablePlugin($this->pg);
        }
    }
    }
