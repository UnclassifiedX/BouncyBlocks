<?php

namespace Darunia18\BouncyBlocks;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;


class BouncyBlocks extends PluginBase implements Listener{

    private $max;
    private $blocks;

    public $fall;
    public $bounceVelocity;
    public $disabled;

    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveDefaultConfig();

        $config = $this->getConfig();
        $this->max = $config->get("max");
        $this->blocks = $config->get("blocks");
        $this->fall = new \SplObjectStorage();
        $this->bounceVelocity = new \SplObjectStorage();
        $this->disabled = new \SplObjectStorage();
    }

    public function onDisable()
    {
        $this->saveConfig();
    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args) : bool
    {
        switch ($command->getName()) {
            case "bounce":
                if (isset($args[0])) {
                    switch ($args[0]) {

                        case "false":
                            $this->disabled->attach($sender);
                            $sender->sendMessage("You will no longer bounce on blocks");
                            return true;

                        case "true":
                            $this->disabled->detach($sender);
                            $sender->sendMessage("You will now bounce on blocks");
                            return true;

                        default:
                            $sender->sendMessage("Usage: /bounce <true|false>");
                            return true;
                    }
                } else {
                    $sender->sendMessage("Usage: /bounce <true|false>");
                    return true;
                }
                break;
        }
        return false;
    }

    public function onEntityDamage(EntityDamageEvent $event){
    $player = $event->getEntity();
            if($event->getEntity() instanceof Player) {
                if (isset($this->fall[$player]) && $event->getCause()===EntityDamageEvent::CAUSE_FALL ){
                    $event->setCancelled();
                }
       }
   }
    public function onPlayerMove(PlayerMoveEvent $event){
        $player = $event->getPlayer();

        if($player->hasPermission("bouncyblocks.bounce") && !isset($this->disabled[$player])){
            $block = $player->getLevel()->getBlockIdAt(($player->x -1), ($player->y -0.1), ($player->z));

            if($block != 0 && in_array($block, $this->blocks)){

                if(!isset($this->bounceVelocity[$player]) || $this->bounceVelocity[$player] == 0.0){
                    $this->bounceVelocity[$player] = ($player->getMotion()->getY() + 0.2);
                }

                if($this->bounceVelocity[$player] <= $this->max){
                    $this->bounceVelocity[$player] = ($this->bounceVelocity[$player] + 0.2);
                }

                $this->fall->attach($player);
                $motion = new Vector3($player->getMotion()->getX(), $player->getMotion()->getY(), $player->getMotion()->getZ());
                $motion->y = $this->bounceVelocity[$player];
                $player->setMotion($motion);
            }

            if(isset($this->fall[$player])){

                if(!$block == 0 && !in_array($block, $this->blocks)){
                    $this->fall->detach($player);
                    $this->bounceVelocity[$player] = 0.0;
                    $player->setMotion(new Vector3(0.0, 0.0, 0.0));
                }
            }
        }
    }
}
