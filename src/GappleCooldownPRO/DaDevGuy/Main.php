<?php
declare(strict_types=1);

namespace GappleCooldownPRO\DaDevGuy;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Main extends PluginBase implements Listener
{
    public static $instance = null;

    public function onEnable(): void
    {
        self::$instance = $this;
        $this->cooldown = new Config($this->getDataFolder(). "cooldowns.yml", Config::YAML);
        $this->ecooldown = new Config($this->getDataFolder(). "enchantcooldowns.yml", Config::YAML); 
        $this->getDataFolder();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public static function getInstance() : Main 
    {
        return self::$instance;
    }

    public function convertSeconds($time) 
    {
        if($time >= 60) {
            $mins = $time / 60;
            $minutes = floor($mins);
            $secs = $mins - $minutes;
            $seconds = floor($secs * 60);

            if($minutes >= 60) {
                $hrs = $minutes / 60;
                $hours = floor($hrs);
                $mins = $hrs - $hours;
                $minutes = floor($mins * 60);
                return $hours . "h " . $minutes . "m " . $seconds . "s";
            } else {
                return $minutes . "m " . $seconds . "s";
            }
        } else {
            return ceil($time) . "s";
        }
    }

    public function formatMessage(string $message, $player, $enchanted = false) : string {
        $time = $enchanted ? $this->getCooldownTime($player) : $this->getEnchantedCooldownTime($player);
        $time = $this->convertSeconds($time);
        $message = str_replace("{TIME}", $time, $message);
        $message = str_replace("{NAME}", $player->getName(), $message);
        return $message;
    }

    public function onConsume(PlayerItemConsumeEvent $e){
        $player = $e->getPlayer();
        if($e->getItem()->getId() == 322){
            if($this->hasCooldown($player)){
                $player->sendMessage($this->formatMessage($this->getConfig()->get("has-cooldown-message"), $player));
                $e->cancel();
            }else{
                $this->addCooldown($player);
             }
            }
            if($e->getItem()->getId() == 466){
            if($this->hasEnchantedCooldown($player)){
                $player->sendMessage($this->formatMessage($this->getConfig()->get("has-cooldown-message"), $player));
                $e->cancel();
            }else{
                $this->addEnchantedCooldown($player);
            }
        }
    }

    public function hasCooldown($player) : bool {
        if($this->cooldown->exists($player->getLowerCaseName())){
            if(microtime(true) >= $this->cooldown->get($player->getLowerCaseName())){
                $this->removeCooldown($player);
                return false;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    public function hasEnchantedCooldown($player) : bool {
        if($this->ecooldown->exists($player->getLowerCaseName())){
            if(microtime(true) >= $this->ecooldown->get($player->getLowerCaseName())){
                $this->removeEnchantedCooldown($player);
                return false;
            }else{
                return true;
            }
        }else{
            return false;
        }
    }

    public function removeCooldown(Player $player){
        $this->cooldown->remove($player->getName());
        $this->cooldown->save();
    }

    public function removeEnchantedCooldown(Player $player){
        $this->ecooldown->remove($player->getName());
        $this->ecooldown->save();
    }

    public function getCooldownSeconds(Player $player){
        return $this->cooldown->get($player->getName()) - microtime(true);
    }

    public function getCooldownTime($player){
        return $this->convertSeconds($this->getCooldownSeconds($player));
    }

    public function getEnchantedCooldownSeconds($player){
        return $this->ecooldown->get($player->getLowerCaseName()) - microtime(true);
    }


    public function getEnchantedCooldownTime($player){
        return $this->convertSeconds($this->getEnchantedCooldownSeconds($player));
    }

    public function addCooldown($player){
        $this->cooldown->set($player->getLowerCaseName(), microtime(true) + $this->getConfig()->get("cooldown-seconds"));
        $this->cooldown->save();
    }

    public function addEnchantedCooldown($player){
        $this->ecooldown->set($player->getLowerCaseName(), microtime(true) + $this->getConfig()->get("enchanted-cooldown-seconds"));
        $this->ecooldown->save();
    }
}
