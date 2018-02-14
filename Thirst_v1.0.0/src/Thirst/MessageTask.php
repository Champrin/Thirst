<?php

namespace Thirst;


use pocketmine\scheduler\PluginTask;
use pocketmine\utils\TextFormat as C;

class MessageTask extends PluginTask
{
	
    private $plugin;
	private $player;
	
    public function __construct($plugin,$player)
    {
        $this->plugin = $plugin;
		$this->player=$player;
        parent::__construct($plugin);
    }

    public function onRun($CK)
	{
        if(in_array($this->player->getLevel()->getFolderName(),$this->plugin->world->get("worlds")))
        {
            $name=$this->player->getName();
            $shl=$this->plugin->getThirst($name);
            $strshl=C::AQUA.     "\n   水含量:   ".C::WHITE."$shl"."                                                ";
            $str=$strshl."\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n";
            $this->player->sendTip($str);
            return true;
        }
    }
}