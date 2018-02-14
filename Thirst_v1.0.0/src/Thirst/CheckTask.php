<?php

namespace Thirst;


use pocketmine\scheduler\PluginTask;
use pocketmine\utils\TextFormat as C;
use pocketmine\entity\Effect;

class CheckTask extends PluginTask
{
	
    private $plugin;
	private $player;
	
    public function __construct($plugin,$player)
    {
        $this->plugin = $plugin;
		$this->player = $player;
        parent::__construct($plugin);
    }

    private function PlayerStateCheckThirst($name)
    {
        $ezz = $this->plugin->getThirst($name);
        if($ezz <= 25 AND $ezz > 15)
        {
            $this->player->sendPopup(C::RED."你的水含量低于25%,身体很虚弱,急需补充水份！！");
            $this->player->sendPopup(C::RED."");
            $this->player->addEffect(Effect::getEffect(18)->setDuration(20*30)->setAmplifier(0)->setVisible(true));
            $this->player->addEffect(Effect::getEffect(2)->setDuration(20*30)->setAmplifier(0)->setVisible(true));
        }
        if($ezz <= 15 AND $ezz > 7)
        {
            $this->player->sendPopup(C::RED."你现在非常渴,急需补充水份！！");
            $this->player->sendPopup(C::RED."");
            $this->player->addEffect(Effect::getEffect(4)->setDuration(20*30)->setAmplifier(0)->setVisible(true));
            $this->player->addEffect(Effect::getEffect(18)->setDuration(20*30)->setAmplifier(0)->setVisible(true));
            $this->player->addEffect(Effect::getEffect(2)->setDuration(20*30)->setAmplifier(0)->setVisible(true));
        }
        if($ezz <= 7 AND $ezz > 0)
        {
            $this->player->sendPopup(C::RED."你的水含量严重不足,必须要补充水份！！");
            $this->player->sendPopup(C::RED."");
            $this->player->addEffect(Effect::getEffect(9)->setDuration(20*30)->setAmplifier(1)->setVisible(true));
            $this->player->addEffect(Effect::getEffect(15)->setDuration(20*30)->setAmplifier(1)->setVisible(true));
        }
        if($ezz <= 0)
        {
            $this->player->sendPopup(C::RED."你的水含量已达0,再不补充水份即将死亡！！！！！");
            $this->player->setHealth($this->player->getHealth() - 9);
        }
    }

    public function onRun($CK)
	{
        if(in_array($this->player->getLevel()->getFolderName(),$this->plugin->world->get("worlds")))
        {
            $name=$this->player->getName();
            $this->PlayerStateCheckThirst($name);
        }
    }
}