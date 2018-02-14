<?php

namespace Thirst;

use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerBedLeaveEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat as C;
use pocketmine\scheduler\Task;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\Player;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\level\particle\ExplodeParticle;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\math\Vector3;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\block\Block;
use pocketmine\entity\Effect;
use pocketmine\level\particle\FloatingTextParticle;
use Thirst\CheckTask;
use Thirst\MessageTask;

class Main extends PluginBase implements Listener
{
    public $tip,$world;
    public $Thirst=100,$ThirstCount=0;
    public $ThirstCountP=500;

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        @mkdir($this->getDataFolder());
        $this->tip = new Config($this->getDataFolder() . "PlayerIn.yml", Config::YAML, array());
        $this->world = new Config($this->getDataFolder()."Worlds.yml", Config::YAML, array("worlds"=>array()));
        $this->getLogger()->info("Water 加载完成!");
    }
    public function onDeath(PlayerDeathEvent $event)
    {
        if(in_array($event->getPlayer()->getLevel()->getFolderName(),$this->world->get("worlds")))
        {
            $player = $event->getPlayer();
            $name = $player->getName();
            $this->tip->set($name,[
                "Thirst"=>$this->Thirst,
                "ThirstCount"=>$this->ThirstCount,
            ]);
            $this->tip->save();
        }
    }
    public function oTeleport(EntityTeleportEvent $event)
    {
        $player = $event->getEntity();
        if($player instanceof Player)
        {
            $name = $player->getName();
            $level = $event->getTo()->getLevel()->getFolderName();
            if(in_array($level,$this->world->get("worlds")))
            {
                if($this->tip->get($name)===null)
                {
                    $this->tip->set($name,[
                        "Thirst"=>$this->Thirst,
                        "ThirstCount"=>$this->ThirstCount,
                    ]);
                    $this->tip->save();
                }
                else
                {
                    $this->PlayerConfigCheck($name);
                }
                $this->getServer()->getScheduler()->scheduleRepeatingTask(new CheckTask($this,$player), 100);
                $this->getServer()->getScheduler()->scheduleRepeatingTask(new MessageTask($this,$player), 20);
            }
        }
    }
    public function PlayerConfigCheck($name)
    {
        if($this->tip->get($name)["Thirst"]===null)
        {
            $b = $this->tip->get($name)["ThirstCount"];
            $this->tip->set($name,[
                "Thirst"=>$this->Thirst,
                "ThirstCount"=>$b,
            ]);
            $this->tip->save();
        }
        if($this->tip->get($name)["ThirstCount"]===null)
        {
            $a = $this->tip->get($name)["Thirst"];
            $this->tip->set($name,[
                "Thirst"=>$a,
                "ThirstCount"=>$this->ThirstCount,
            ]);
            $this->tip->save();
        }
    }
    public function onDropItem(PlayerDropItemEvent $event)
    {
        if(in_array($event->getPlayer()->getLevel()->getFolderName(),$this->world->get("worlds")))
        {
            $this->PlayerThirst($event->getPlayer()->getName());
        }
    }
    public function onMove(PlayerMoveEvent $event)
    {
        if(in_array($event->getPlayer()->getLevel()->getFolderName(),$this->world->get("worlds")))
        {
            $this->PlayerThirst($event->getPlayer()->getName());
        }
    }
    public function onChat(PlayerChatEvent $event)
    {
        if(in_array($event->getPlayer()->getLevel()->getFolderName(),$this->world->get("worlds")))
        {
            $this->PlayerThirst($event->getPlayer()->getName());
        }
    }
    public function onBreak(BlockBreakEvent $event)
    {
        if(in_array($event->getPlayer()->getLevel()->getFolderName(),$this->world->get("worlds")))
        {
            $this->PlayerThirst($event->getPlayer()->getName());
        }
    }
    public function onPlace(BlockPlaceEvent $event)
    {
        if(in_array($event->getPlayer()->getLevel()->getFolderName(),$this->world->get("worlds")))
        {
            $this->PlayerThirst($event->getPlayer()->getName());
        }
    }
    public function onDrink(PlayerInteractEvent $event)
    {
        if(in_array($event->getPlayer()->getLevel()->getFolderName(),$this->world->get("worlds")))
        {
            $player = $event->getPlayer();
            $inventory = $player->getInventory();
            $level = $player->getLevel();

            $item = $event->getItem()->getId();
            $damage = $event->getItem()->getDamage();
            $name = $player->getName();

            switch ($item)
            {
                case 325:
                    if ($damage == 8)//牛奶
                    {
                        if ($this->getThirst($name) >= $this->Thirst)
                        {
                            $player->sendMessage("你现在不渴！不需要补充水份！");
                            $event->setCancelled(true);
                        }
                        else
                        {
                            $inventory->removeItem(new Item(325, 8, 1));
                            $inventory->addItem(new Item(325, 0, 1));
                            $this->setThirst($name, $this->getThirst($name) + 15);
                        }
                    }
                    if ($damage == 10)//水桶
                    {
                        if ($this->getThirst($name) >= $this->Thirst)
                        {
                            $player->sendMessage("你现在不渴！不需要补充水份！");
                            $event->setCancelled(true);
                        }
                        else
                        {
                            $inventory->removeItem(new Item(325, 10, 1));
                            $inventory->addItem(new Item(325, 0, 1));
                            $this->setThirst($name, $this->getThirst($name) + 25);
                        }
                    }
                    break;
            }
        }
    }

    public function getThirst($name)
    {
        if($this->tip->get($name)===null)
        {
            return false;
        }
        else
        {
            return $this->tip->get($name)["Thirst"];
        }
    }
    public function setThirst($name,$thirst)
    {
        $b = $this->tip->get($name)["ThirstCount"];
        $this->tip->set($name,[
            "Thirst"=>$thirst,
            "ThirstCount"=>$b,
        ]);
        $this->tip->save();
    }
    public function getThirstCount($name)
    {
        if($this->tip->get($name)===null)
        {
            return false;
        }
        else
        {
            return $this->tip->get($name)["ThirstCount"];
        }
    }
    public function setThirstCount($name,$count)
    {
        $b = $this->tip->get($name)["Thirst"];
        $this->tip->set($name,[
            "Thirst"=>$b,
            "ThirstCount"=>$count,
        ]);
        $this->tip->save();
    }
    public function PlayerThirst($name)
    {
        $a = $this->tip->get($name)["ThirstCount"];
        $this->setThirstCount($name,$this->getThirstCount($name)+3);
        if($a >= $this->ThirstCountP)
        {
            $this->setThirst($name,$this->getThirst($name)-1);
            $this->setThirstCount($name,0);
            return true;
        }
    }
    public function OnEat2(PlayerItemConsumeEvent $event)
    {
        if(in_array($event->getPlayer()->getLevel()->getFolderName(),$this->world->get("worlds")))
        {
            $player = $event->getPlayer();
            $name= $player->getName();
            $item = $event->getItem();
            $itemid = $item->getId();
            $damage = $item->getDamage();
            $ezz = $this->getThirst($name);

            switch($itemid)
            {
                case 373:
                    if($damage == 0)
                    {
                        if($ezz >= $this->Thirst)
                        {
                            $player->sendMessage("你现在不渴！不需要补充水份！");
                            $event->setCancelled(true);
                        }
                        else
                        {
                            $this->setThirst($name,$this->getThirst($name)+15);
                        }
                    }
                    break;
                case 260://apple
                    $this->setThirst($name,$this->getThirst($name)+13);
                    break;
                case 349://生鱼
                    $this->setThirst($name,$this->getThirst($name)-13);
                    break;
                case 350://熟鱼
                    $this->setThirst($name,$this->getThirst($name)-17);
                    break;
                case 367://腐肉
                    $this->setThirst($name,$this->getThirst($name)-11);
                    break;
                case 282://蘑菇汤
                    $this->setThirst($name,$this->getThirst($name)+9);
                    break;
                case 297://面包
                    $this->setThirst($name,$this->getThirst($name)-7);
                    break;
                case 319://生猪
                    $this->setThirst($name,$this->getThirst($name)-18);
                    break;
                case 320://熟猪
                    $this->setThirst($name,$this->getThirst($name)-20);
                    break;
                case 365://生鸡
                    $this->setThirst($name,$this->getThirst($name)-17);
                    break;
                case 366://熟鸡
                    $this->setThirst($name,$this->getThirst($name)-15);
                    break;
                case 423://生羊
                    $this->setThirst($name,$this->getThirst($name)-20);
                    break;
                case 424://熟羊
                    $this->setThirst($name,$this->getThirst($name)-19);
                    break;
                case 363://生牛
                    $this->setThirst($name,$this->getThirst($name)-23);
                    break;
                case 364://熟牛
                    $this->setThirst($name,$this->getThirst($name)-21);
                    break;
                case 360://西瓜
                    $this->setThirst($name,$this->getThirst($name)+15);
                    break;
                case 391://萝卜
                    $this->setThirst($name,$this->getThirst($name)+13);
                    break;
                case 392://生土豆
                    $this->setThirst($name,$this->getThirst($name)+13);
                    break;
                case 393://熟土豆
                    $this->setThirst($name,$this->getThirst($name)-6);
                    break;
                case 457://菜根
                    $this->setThirst($name,$this->getThirst($name)+13);
                    break;
                case 459://菜根汤
                    $this->setThirst($name,$this->getThirst($name)+9);
                    break;
                case 357://曲奇
                    $this->setThirst($name,$this->getThirst($name)-7);
                    break;
                case 400://南瓜饼
                    $this->setThirst($name,$this->getThirst($name)-7);
                    break;
                case 411://生兔
                    $this->setThirst($name,$this->getThirst($name)-18);
                    break;
                case 412://熟兔
                    $this->setThirst($name,$this->getThirst($name)-18);
                    break;
                case 413://兔汤
                    $this->setThirst($name,$this->getThirst($name)+7);
                    break;
                case 432://共鸣果
                    $this->setThirst($name,$this->getThirst($name)+12);
                    break;
                case 433://爆裂共鸣果
                    $this->setThirst($name,$this->getThirst($name)+12);
                    break;
            }
        }
    }
    public function OOnHeld(PlayerItemHeldEvent $event)
    {
        if(in_array($event->getPlayer()->getLevel()->getFolderName(),$this->world->get("worlds")))
        {
            $player = $event->getPlayer();
            $name = $player->getName();
            $item = $event->getItem();
            $itemid = $item->getId();
            switch($itemid)
            {
                case 260://apple
                    $this->PlayerStateCheckThirst($name,$player);
                    break;
                case 349://生鱼
                    $this->PlayerStateCheckThirst($name,$player);
                    break;
                case 350://熟鱼
                    $this->PlayerStateCheckThirst($name,$player);
                    break;
                case 367://腐肉
                    $this->PlayerStateCheckThirst($name,$player);
                    break;
                case 282://蘑菇汤
                    $this->PlayerStateCheckThirst($name,$player);
                    break;
                case 297://面包
                    $this->PlayerStateCheckThirst($name,$player);
                    break;
                case 319://生猪
                    $this->PlayerStateCheckThirst($name,$player);
                    break;
                case 320://熟猪
                    $this->PlayerStateCheckThirst($name,$player);
                    break;
                case 365://生鸡
                    $this->PlayerStateCheckThirst($name,$player);
                    break;
                case 366://熟鸡
                    $this->PlayerStateCheckThirst($name,$player);
                    break;
                case 423://生羊
                    $this->PlayerStateCheckThirst($name,$player);
                    break;
                case 424://熟羊
                    $this->PlayerStateCheckThirst($name,$player);
                    break;
                case 363://生牛
                    $this->PlayerStateCheckThirst($name,$player);
                    break;
                case 364://熟牛
                    $this->PlayerStateCheckThirst($name,$player);
                    break;
                case 360://西瓜
                    $this->PlayerStateCheckThirst($name,$player);
                    break;
                case 391://萝卜
                    $this->PlayerStateCheckThirst($name,$player);
                    break;
                case 392://生土豆
                    $this->PlayerStateCheckThirst($name,$player);
                    break;
                case 393://熟土豆
                    $this->PlayerStateCheckThirst($name,$player);
                    break;
                case 457://菜根
                    $this->PlayerStateCheckThirst($name,$player);
                    break;
                case 459://菜根汤
                    $this->PlayerStateCheckThirst($name,$player);
                    break;
                case 357://曲奇
                    $this->PlayerStateCheckThirst($name,$player);
                    break;
                case 400://南瓜饼
                    $this->PlayerStateCheckThirst($name,$player);
                    break;
                case 411://生兔
                    $this->PlayerStateCheckThirst($name,$player);
                    break;
                case 412://熟兔
                    $this->PlayerStateCheckThirst($name,$player);
                    break;
                case 413://兔汤
                    $this->PlayerStateCheckThirst($name,$player);
                    break;
                case 432://共鸣果
                    $this->PlayerStateCheckThirst($name,$player);
                    break;
                case 433://爆裂共鸣果
                    $this->PlayerStateCheckThirst($name,$player);
                    break;
                //case 396://金萝卜
                //case 382://金西瓜
            }
        }
    }
    public function PlayerStateCheckThirst($name,$player)
    {
        if($this->getThirst($name) <= 23)
        {
            $player->sendMessage(C::RED."你现在非常渴,不能再吃这些干燥的东西了！！");
        }
    }
    public function onCommand(CommandSender $s, Command $command, $label, array $args)
    {
        switch($command->getName())
        {
            case "kkz":
                if(!isset($args[0]))
                {
                    $s->sendMessage(C::GRAY .      "§a请输入玩家名");
                    return true;
                }
                if(!isset($args[1]))
                {
                    $s->sendMessage(C::GRAY .      "§a请输入要设置的口渴值");
                    return true;
                }
                if (!is_numeric($args[1]))
                {
                    $s->sendMessage(C::GRAY .      "§a输入的口渴值不为数字,请重新输入");
                    return true;
                }
                else
                {
                    $this->setThirst($args[0],$args[1]);
                    $s->sendMessage(C::GRAY .      "§a已设置玩家§c$args[0]§a的口渴值为§c$args[1]");
                    return true;
                }
            case "kkzaddw":
                if(isset($args[0]))
                {
                    $levels=$this->world->get("worlds");
                    $level=$args[0];
                    if(!$this->getServer()->isLevelGenerated($level))
                    {
                        $s->sendMessage("  §a地图§6{$level}§a不存在！");
                        return true;
                    }
                    else
                    {
                        $levels[]=$level;
                        $this->world->set("worlds",$levels);
                        $this->world->save();
                        $s->sendMessage("  §6口渴值开启在世界§a$level");
                        return true;
                    }
                }
                else
                {
                    $s->sendMessage("  §c未输入要添加的地图名");
                    $s->sendMessage("  §a用法: /kzzaddw [地图名]");
                    return true;
                }
            case "kkzdelw":
                if(isset($args[0]))
                {
                    $levels=$this->world->get("worlds");
                    $level=$args[0];
                    if(in_array($level,$levels))
                    {
                        $inv = array_search($level, $levels);
                        $inv = array_splice($levels, $inv, 1);
                        $this->world->set("worlds",$levels);
                        $this->world->save();
                        $s->sendMessage("  §6口渴值关闭在世界§a$level");
                        return true;
                    }
                    else
                    {
                        $s->sendMessage("  §6配置文件不存在世界§a{$level}§6,请检查后输入");
                        return true;
                    }
                }
                else
                {
                    $s->sendMessage("  §c未输入要删除的地图名");
                    $s->sendMessage("  §a用法: /kzzdelw [地图名]");
                    return true;
                }
        }
    }

}
	
	