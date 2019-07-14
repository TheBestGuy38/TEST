<?php

namespace DaPigGuy\PiggyCustomEnchants;

use DaPigGuy\PiggyCustomEnchants\Blocks\PiggyObsidian;
use DaPigGuy\PiggyCustomEnchants\Commands\CustomEnchantCommand;
use DaPigGuy\PiggyCustomEnchants\CustomEnchants\CustomEnchants;
use DaPigGuy\PiggyCustomEnchants\CustomEnchants\CustomEnchantsIds;
use DaPigGuy\PiggyCustomEnchants\Entities\PiggyFireball;
use DaPigGuy\PiggyCustomEnchants\Entities\PiggyLightning;
use DaPigGuy\PiggyCustomEnchants\Entities\PiggyWitherSkull;
use DaPigGuy\PiggyCustomEnchants\Entities\PigProjectile;
use DaPigGuy\PiggyCustomEnchants\Entities\VolleyArrow;
use DaPigGuy\PiggyCustomEnchants\Tasks\AutoAimTask;
use DaPigGuy\PiggyCustomEnchants\Tasks\CactusTask;
use DaPigGuy\PiggyCustomEnchants\Tasks\ChickenTask;
use DaPigGuy\PiggyCustomEnchants\Tasks\EffectTask;
use DaPigGuy\PiggyCustomEnchants\Tasks\ForcefieldTask;
use DaPigGuy\PiggyCustomEnchants\Tasks\JetpackTask;
use DaPigGuy\PiggyCustomEnchants\Tasks\MeditationTask;
use DaPigGuy\PiggyCustomEnchants\Tasks\ParachuteTask;
use DaPigGuy\PiggyCustomEnchants\Tasks\PoisonousGasTask;
use DaPigGuy\PiggyCustomEnchants\Tasks\ProwlTask;
use DaPigGuy\PiggyCustomEnchants\Tasks\RadarTask;
use DaPigGuy\PiggyCustomEnchants\Tasks\SizeTask;
use DaPigGuy\PiggyCustomEnchants\Tasks\SpiderTask;
use DaPigGuy\PiggyCustomEnchants\Tasks\VacuumTask;
use pocketmine\block\BlockFactory;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
use pocketmine\item\Armor;
use pocketmine\item\Axe;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Hoe;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\Pickaxe;
use pocketmine\item\Shears;
use pocketmine\item\Shovel;
use pocketmine\item\Sword;
use pocketmine\level\Position;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

/**
 * Class Main
 * @package DaPigGuy\PiggyCustomEnchants
 */
class Main extends PluginBase
{
    const MAX_LEVEL = 0;
    const NOT_COMPATIBLE = 1;
    const NOT_COMPATIBLE_WITH_OTHER_ENCHANT = 2;
    const MORE_THAN_ONE = 3;

    const ROMAN_CONVERSION_TABLE = [
        'M' => 1000,
        'CM' => 900,
        'D' => 500,
        'CD' => 400,
        'C' => 100,
        'XC' => 90,
        'L' => 50,
        'XL' => 40,
        'X' => 10,
        'IX' => 9,
        'V' => 5,
        'IV' => 4,
        'I' => 1
    ];

    const COLOR_CONVERSION_TABLE = [
        "BLACK" => TextFormat::BLACK,
        "DARK_BLUE" => TextFormat::DARK_BLUE,
        "DARK_GREEN" => TextFormat::DARK_GREEN,
        "DARK_AQUA" => TextFormat::DARK_AQUA,
        "DARK_RED" => TextFormat::DARK_RED,
        "DARK_PURPLE" => TextFormat::DARK_PURPLE,
        "GOLD" => TextFormat::GOLD,
        "GRAY" => TextFormat::GRAY,
        "DARK_GRAY" => TextFormat::DARK_GRAY,
        "BLUE" => TextFormat::BLUE,
        "GREEN" => TextFormat::GREEN,
        "AQUA" => TextFormat::AQUA,
        "RED" => TextFormat::RED,
        "LIGHT_PURPLE" => TextFormat::LIGHT_PURPLE,
        "YELLOW" => TextFormat::YELLOW,
        "WHITE" => TextFormat::WHITE
    ];

    const PIGGY_ENTITIES = [
        PiggyFireball::class,
        PiggyLightning::class,
        PigProjectile::class,
        VolleyArrow::class,
        PiggyWitherSkull::class
    ];
    /** @var bool */
    public static $lightningFlames = false;
    /** @var bool */
    public static $blazeFlames = false;
    /** @var array */
    public $berserkercd;
    /** @var array */
    public $bountyhuntercd;
    /** @var array */
    public $cloakingcd;
    /** @var array */
    public $endershiftcd;
    /** @var array */
    public $growcd;
    /** @var array */
    public $implantscd;
    /** @var array */
    public $jetpackcd;
    /** @var array */
    public $shrinkcd;
    /** @var array */
    public $vampirecd;
    /** @var array */
    public $growremaining;
    /** @var array */
    public $jetpackDisabled;
    /** @var array */
    public $shrinkremaining;
    /** @var array */
    public $flyremaining;
    /** @var array */
    public $chickenTick;
    /** @var array */
    public $forcefieldParticleTick;
    /** @var array */
    public $gasParticleTick;
    /** @var array */
    public $jetpackChargeTick;
    /** @var array */
    public $meditationTick;
    /** @var array */
    public $blockface;
    /** @var array */
    public $glowing;
    /** @var array */
    public $grew;
    /** @var array */
    public $flying;
    /** @var array */
    public $hallucination;
    /** @var array */
    public $implants;
    /** @var array */
    public $mined;
    /** @var array */
    public $moved;
    /** @var array */
    public $nofall;
    /** @var array */
    public $overload;
    /** @var array */
    public $prowl;
    /** @var array */
    public $using;
    /** @var array */
    public $shrunk;
    /** @var bool */
    public $formsEnabled = false;
    /** @var array */
    public $enchants = [
        //id => ["name", "slot", "trigger", "rarity", maxlevel", "description"]
        CustomEnchantsIds::DEATHBRINGER => ["Deathbringer", "Weapons", "Damage", "Rare", 5, "Increases damage"],
        CustomEnchantsIds::GEARS => ["Gears", "Boots", "Equip", "Uncommon", 5, "Gives speed"],
        CustomEnchantsIds::GLOWING => ["Glowing", "Helmets", "Equip", "Common", 1, "Gives night vision"],
        CustomEnchantsIds::HASTE => ["Haste", "Tools", "Held", "Uncommon", 5, "Gives haste when held"],
        CustomEnchantsIds::LIFESTEAL => ["Lifesteal", "Weapons", "Damage", "Common", 5, "Heals when damaging enemies"],
        CustomEnchantsIds::LIGHTNING => ["Lightning", "Weapons", "Damage", "Rare", 5, "10l% chance to strike enemies with lightning"],
        CustomEnchantsIds::OVERLOAD => ["Overload", "Armor", "Equip", "Mythic", 3, "Gives 1 extra heart per level per armor piece"],
        CustomEnchantsIds::POISON => ["Poison", "Weapons", "Damage", "Uncommon", 5, "Poisons enemies"],
        CustomEnchantsIds::SHIELDED => ["Shielded", "Armor", "Equip", "Rare", 3, "Gives resistance per level per piece of armor"],
        CustomEnchantsIds::SMELTING => ["Smelting", "Tools", "Break", "Uncommon", 1, "Automatically smelts drops when broken"],
        CustomEnchantsIds::SPRINGS => ["Springs", "Boots", "Equip", "Uncommon", 5, "Gives a jump boost"],
        CustomEnchantsIds::WITHER => ["Wither", "Weapons", "Damage", "Uncommon", 5, "Gives enemies wither"],
    ];

    /** @var array */
    public $incompatibilities = [
        CustomEnchantsIds::GROW => [CustomEnchantsIds::SHRINK],
        CustomEnchantsIds::PORKIFIED => [CustomEnchantsIds::BLAZE, CustomEnchantsIds::WITHERSKULL],
        CustomEnchantsIds::VOLLEY => [CustomEnchantsIds::GRAPPLING]
    ];

    public function onEnable()
    {
        if (!$this->isSpoon()) {
            $this->initCustomEnchants();
            $this->saveDefaultConfig();
            if ($this->getConfig()->getNested("forms.enabled")) {
                $this->formsEnabled = true;
            }
            if ($this->getConfig()->getNested("blaze.flames")) {
                self::$blazeFlames = true;
            }
            if ($this->getConfig()->getNested("lightning.flames")) {
                self::$lightningFlames = true;
            }
            $this->jetpackDisabled = $this->getConfig()->getNested("jetpack.disabled") ?? [];
            if (count($this->jetpackDisabled) > 0) {
                $this->getLogger()->info(TextFormat::RED . "Jetpack is currently disabled in the levels " . implode(", ", $this->jetpackDisabled) . ".");
            }
            BlockFactory::registerBlock(new PiggyObsidian(), true);
            foreach (self::PIGGY_ENTITIES as $piggyEntity) {
                Entity::registerEntity($piggyEntity, true);
            }
            if (!ItemFactory::isRegistered(Item::ENCHANTED_BOOK)) { //Check if it isn't already registered by another plugin
                ItemFactory::registerItem(new Item(Item::ENCHANTED_BOOK, 0, "Enchanted Book")); //This is a temporary fix for name being Unknown when given due to no implementation in PMMP. Will remove when implemented in PMMP
            }
            $this->getServer()->getCommandMap()->register("piggycustomenchants", new CustomEnchantCommand("customenchant", $this));
            $this->getScheduler()->scheduleRepeatingTask(new AutoAimTask($this), 1);
            $this->getScheduler()->scheduleRepeatingTask(new CactusTask($this), 10);
            $this->getScheduler()->scheduleRepeatingTask(new ChickenTask($this), 20);
            $this->getScheduler()->scheduleRepeatingTask(new ForcefieldTask($this), 1);
            $this->getScheduler()->scheduleRepeatingTask(new EffectTask($this), 5);
            $this->getScheduler()->scheduleRepeatingTask(new JetpackTask($this), 1);
            $this->getScheduler()->scheduleRepeatingTask(new MeditationTask($this), 20);
            $this->getScheduler()->scheduleRepeatingTask(new ParachuteTask($this), 2);
            $this->getScheduler()->scheduleRepeatingTask(new ProwlTask($this), 1);
            $this->getScheduler()->scheduleRepeatingTask(new RadarTask($this), 1);
            $this->getScheduler()->scheduleRepeatingTask(new SizeTask($this), 20);
            $this->getScheduler()->scheduleRepeatingTask(new SpiderTask($this), 1);
            $this->getScheduler()->scheduleRepeatingTask(new PoisonousGasTask($this), 1);
            $this->getScheduler()->scheduleRepeatingTask(new VacuumTask($this), 1);
            $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        }
    }

    /**
     * Checks if server is using a spoon.
     *
     * @return bool
     */
    public function isSpoon()
    {
        if ($this->getServer()->getName() !== "PocketMine-MP") {
            $this->getLogger()->error("Pig doesn't like spoons. Due to this, the plugin will not function until you are using PMMP.");
            return true;
        }
        if ($this->getDescription()->getAuthors() !== ["DaPigGuy"] || $this->getDescription()->getName() !== "PiggyCustomEnchants") {
            $this->getLogger()->error("You are not using the original version of this plugin (PiggyCustomEnchants) by DaPigGuy/MCPEPIG.");
            return true;
        }
        return false;
    }

    public function initCustomEnchants()
    {
        CustomEnchants::init();
        foreach ($this->enchants as $id => $data) {
            $ce = $this->translateDataToCE($id, $data);
            CustomEnchants::registerEnchantment($ce);
        }
    }

    /**
     * Translates data from strings to int
     *
     * @param $id
     * @param $data
     * @return CustomEnchants
     */
    public function translateDataToCE($id, $data)
    {
        $slot = CustomEnchants::SLOT_NONE;
        switch ($data[1]) {
            case "Global":
                $slot = CustomEnchants::SLOT_ALL;
                break;
            case "Weapons":
                $slot = CustomEnchants::SLOT_SWORD;
                break;
            case "Bow":
                $slot = CustomEnchants::SLOT_BOW;
                break;
            case "Tools":
                $slot = CustomEnchants::SLOT_TOOL;
                break;
            case "Pickaxe":
                $slot = CustomEnchants::SLOT_PICKAXE;
                break;
            case "Axe":
                $slot = CustomEnchants::SLOT_AXE;
                break;
            case "Shovel":
                $slot = CustomEnchants::SLOT_SHOVEL;
                break;
            case "Hoe":
                $slot = CustomEnchants::SLOT_HOE;
                break;
            case "Armor":
                $slot = CustomEnchants::SLOT_ARMOR;
                break;
            case "Helmets":
                $slot = CustomEnchants::SLOT_HEAD;
                break;
            case "Chestplate":
                $slot = CustomEnchants::SLOT_TORSO;
                break;
            case "Leggings":
                $slot = CustomEnchants::SLOT_LEGS;
                break;
            case "Boots":
                $slot = CustomEnchants::SLOT_FEET;
                break;
            case "Compass":
                $slot = 0b10000000000000;
                break;
        }
        $rarity = CustomEnchants::RARITY_COMMON;
        switch ($data[3]) {
            case "Common":
                $rarity = CustomEnchants::RARITY_COMMON;
                break;
            case "Uncommon":
                $rarity = CustomEnchants::RARITY_UNCOMMON;
                break;
            case "Rare":
                $rarity = CustomEnchants::RARITY_RARE;
                break;
            case "Mythic":
                $rarity = CustomEnchants::RARITY_MYTHIC;
                break;
        }
        $ce = new CustomEnchants($id, $data[0], $rarity, $slot, CustomEnchants::SLOT_NONE, $data[4]);
        return $ce;
    }

    /**
     * Registers enchantment from id, name, trigger, rarity, and max level
     *
     * @param $id
     * @param $name
     * @param $type
     * @param $trigger
     * @param $rarity
     * @param $maxlevel
     * @param $description
     */
    public function registerEnchantment($id, $name, $type, $trigger, $rarity, $maxlevel, $description = "")
    {
        $data = [$name, $type, $trigger, $rarity, $maxlevel, $description];
        $this->enchants[$id] = $data;
        $ce = $this->translateDataToCE($id, $data);
        CustomEnchants::registerEnchantment($ce);
    }

    /**
     * Unregisters enchantment by id
     *
     * @param $id
     * @return bool
     */
    public function unregisterEnchantment($id)
    {
        if (isset($this->enchants[$id]) && CustomEnchants::getEnchantment($id) !== null) {
            unset($this->enchants[$id]);
            CustomEnchants::unregisterEnchantment($id);
            return true;
        }
        return false;
    }

    /**
     * Add an enchant incompatibility
     *
     * @param int   $id
     * @param array $incompatibilities
     * @return bool
     */
    public function addIncompatibility(int $id, array $incompatibilities)
    {
        if (!isset($this->incompatibilities[$id])) {
            $this->incompatibilities[$id] = $incompatibilities;
            return true;
        }
        return false;
    }

    /**
     * Adds enchantment to item
     *
     * @param Item               $item
     * @param                    $enchants
     * @param                    $levels
     * @param bool               $check
     * @param CommandSender|null $sender
     * @return Item
     */
    public function addEnchantment(Item $item, $enchants, $levels, $check = true, CommandSender $sender = null)
    {
        if (!is_array($enchants)) {
            $enchants = [$enchants];
        }
        if (!is_array($levels)) {
            $levels = [$levels];
        }
        if (count($enchants) > count($levels)) {
            for ($i = 0; $i <= count($enchants) - count($levels); $i++) {
                $levels[] = 1;
            }
        }
        $combined = array_combine($enchants, $levels);
        foreach ($enchants as $enchant) {
            $level = $combined[$enchant];
            if (!$enchant instanceof CustomEnchants) {
                if (is_numeric($enchant)) {
                    $enchant = CustomEnchants::getEnchantment((int)$enchant);
                } else {
                    $enchant = CustomEnchants::getEnchantmentByName($enchant);
                }
            }
            if ($enchant == null) {
                if ($sender !== null) {
                    $sender->sendMessage(TextFormat::RED . "Invalid enchantment.");
                }
                continue;
            }
            $result = $this->canBeEnchanted($item, $enchant, $level);
            if ($result === true || $check !== true) {
                if ($item->getId() == Item::BOOK) {
                    $item = Item::get(Item::ENCHANTED_BOOK, $level);
                }
                $ench = $item->getNamedTagEntry(Item::TAG_ENCH);
                $found = false;
                if (!($ench instanceof ListTag)) {
                    $ench = new ListTag(Item::TAG_ENCH, [], NBT::TAG_Compound);
                } else {
                    foreach ($ench as $k => $entry) {
                        if ($entry->getShort("id") === $enchant->getId()) {
                            $ench->set($k, new CompoundTag("", [
                                new ShortTag("id", $enchant->getId()),
                                new ShortTag("lvl", $level)
                            ]));
                            if ($this->getConfig()->getNested('enchant-position.name')) $item->setCustomName(str_replace($this->getRarityColor($enchant->getRarity()) . $enchant->getName() . " " . $this->getRomanNumber($entry["lvl"]), $this->getRarityColor($enchant->getRarity()) . $enchant->getName() . " " . $this->getRomanNumber($level), $item->getName()));
                            if ($this->getConfig()->getNested('enchant-position.lore')) $item->setLore([str_replace($this->getRarityColor($enchant->getRarity()) . $enchant->getName() . " " . $this->getRomanNumber($entry["lvl"]), $this->getRarityColor($enchant->getRarity()) . $enchant->getName() . " " . $this->getRomanNumber($level), implode("\n", $item->getLore()))]);
                            $found = true;
                            break;
                        }
                    }
                }
                if (!$found) {
                    $ench->push(new CompoundTag("", [
                        new ShortTag("id", $enchant->getId()),
                        new ShortTag("lvl", $level)
                    ]));
                    if ($this->getConfig()->getNested('enchant-position.name')) $item->setCustomName($item->getName() . "\n" . $this->getRarityColor($enchant->getRarity()) . $enchant->getName() . " " . $this->getRomanNumber($level));
                    if ($this->getConfig()->getNested('enchant-position.lore')) $item->setLore([implode("\n", $item->getLore()), $this->getRarityColor($enchant->getRarity()) . $enchant->getName() . " " . $this->getRomanNumber($level)]);
                }
                $item->setNamedTagEntry($ench);
                if ($sender !== null) {
                    $sender->sendMessage(TextFormat::GREEN . "Enchanting succeeded.");
                }
                continue;
            }
            if ($sender !== null) {
                switch ($result) {
                    case self::NOT_COMPATIBLE:
                        $sender->sendMessage(TextFormat::RED . "The item is not compatible with this enchant.");
                        break;
                    case self::NOT_COMPATIBLE_WITH_OTHER_ENCHANT:
                        $sender->sendMessage(TextFormat::RED . "The enchant is not compatible with another enchant.");
                        break;
                    case self::MAX_LEVEL:
                        $sender->sendMessage(TextFormat::RED . "The max level is " . $this->getEnchantMaxLevel($enchant) . ".");
                        break;

                    case self::MORE_THAN_ONE:
                        $sender->sendMessage(TextFormat::RED . "You can only enchant one item at a time.");
                        break;
                }
            }
            continue;
        }
        return $item;
    }

    /**
     * Checks if an item can be enchanted with a specific enchantment and level
     *
     * @param Item $item
     * @param      $enchant
     * @param      $level
     * @return bool|int
     */
    public function canBeEnchanted(Item $item, $enchant, $level)
    {
        if ($enchant instanceof EnchantmentInstance) {
            $enchant = $enchant->getType();
        } elseif ($enchant instanceof CustomEnchants !== true) {
            $this->getLogger()->error("Argument '$enchant' must be an instance EnchantmentInstance or CustomEnchants.");
            return false;
        }
        $type = $this->getEnchantType($enchant);
        if ($this->getEnchantMaxLevel($enchant) < $level) {
            return self::MAX_LEVEL;
        }
        foreach ($this->incompatibilities as $enchantment => $incompatibilities) {
            if ($item->getEnchantment($enchantment) !== null) {
                if (in_array($enchant->getId(), $incompatibilities)) {
                    return self::NOT_COMPATIBLE_WITH_OTHER_ENCHANT;
                }
            } else {
                foreach ($incompatibilities as $incompatibility) {
                    if ($item->getEnchantment($incompatibility) !== null) {
                        if ($enchantment == $enchant->getId() || in_array($enchant->getId(), $incompatibilities)) {
                            return self::NOT_COMPATIBLE_WITH_OTHER_ENCHANT;
                        }
                    }
                }
            }
        }
        if ($item->getCount() > 1) {
            return self::MORE_THAN_ONE;
        }
        if ($item->getId() == Item::BOOK) {
            return true;
        }
        switch ($type) {
            case "Global":
                return true;
            case "Damageable":
                if ($item instanceof Durable) {
                    return true;
                }
                break;
            case "Weapons":
                if ($item instanceof Sword || $item instanceof Axe || $item->getId() == Item::BOW) {
                    return true;
                }
                break;
            case "Bow":
                if ($item->getId() == Item::BOW) {
                    return true;
                }
                break;
            case "Tools":
                if ($item instanceof Pickaxe || $item instanceof Axe || $item instanceof Shovel || $item instanceof Hoe || $item instanceof Shears) {
                    return true;
                }
                break;
            case "Pickaxe":
                if ($item instanceof Pickaxe) {
                    return true;
                }
                break;
            case "Axe":
                if ($item instanceof Axe) {
                    return true;
                }
                break;
            case "Shovel":
                if ($item instanceof Shovel) {
                    return true;
                }
                break;
            case "Hoe":
                if ($item instanceof Hoe) {
                    return true;
                }
                break;
            case "Armor":
                if ($item instanceof Armor) {
                    return true;
                }
                break;
            case "Helmets":
                switch ($item->getId()) {
                    case Item::LEATHER_CAP:
                    case Item::CHAIN_HELMET:
                    case Item::IRON_HELMET:
                    case Item::GOLD_HELMET:
                    case Item::DIAMOND_HELMET:
                        return true;
                }
                break;
            case "Chestplate":
                switch ($item->getId()) {
                    case Item::LEATHER_TUNIC:
                    case Item::CHAIN_CHESTPLATE;
                    case Item::IRON_CHESTPLATE:
                    case Item::GOLD_CHESTPLATE:
                    case Item::DIAMOND_CHESTPLATE:
                    case Item::ELYTRA:
                        return true;
                }
                break;
            case "Leggings":
                switch ($item->getId()) {
                    case Item::LEATHER_PANTS:
                    case Item::CHAIN_LEGGINGS:
                    case Item::IRON_LEGGINGS:
                    case Item::GOLD_LEGGINGS:
                    case Item::DIAMOND_LEGGINGS:
                        return true;
                }
                break;
            case "Boots":
                switch ($item->getId()) {
                    case Item::LEATHER_BOOTS:
                    case Item::CHAIN_BOOTS:
                    case Item::IRON_BOOTS:
                    case Item::GOLD_BOOTS:
                    case Item::DIAMOND_BOOTS:
                        return true;
                }
                break;
            case "Compass":
                if ($item->getId() == Item::COMPASS) {
                    return true;
                }
                break;
        }
        return self::NOT_COMPATIBLE;
    }

    /**
     * Returns enchantment type
     *
     * @param CustomEnchants $enchant
     * @return string
     */
    public function getEnchantType(CustomEnchants $enchant)
    {
        foreach ($this->enchants as $id => $data) {
            if ($enchant->getId() == $id) {
                return $data[1];
            }
        }
        return "Unknown";
    }

    /**
     * Returns the max level the enchantment can have
     *
     * @param CustomEnchants $enchant
     * @return int
     */
    public function getEnchantMaxLevel(CustomEnchants $enchant)
    {
        foreach ($this->enchants as $id => $data) {
            if ($enchant->getId() == $id) {
                return $data[4];
            }
        }
        return 5;
    }

    /**
     * Returns the color of a rarity
     *
     * @param $rarity
     * @return string
     */
    public function getRarityColor($rarity)
    {
        switch ($rarity) {
            case CustomEnchants::RARITY_COMMON:
                $color = strtoupper($this->getConfig()->getNested("color.common"));
                return $this->translateColorNameToTextFormat($color) == false ? TextFormat::YELLOW : $this->translateColorNameToTextFormat($color);
            case CustomEnchants::RARITY_UNCOMMON:
                $color = strtoupper($this->getConfig()->getNested("color.uncommon"));
                return $this->translateColorNameToTextFormat($color) == false ? TextFormat::BLUE : $this->translateColorNameToTextFormat($color);
            case CustomEnchants::RARITY_RARE:
                $color = strtoupper($this->getConfig()->getNested("color.rare"));
                return $this->translateColorNameToTextFormat($color) == false ? TextFormat::GOLD : $this->translateColorNameToTextFormat($color);
            case CustomEnchants::RARITY_MYTHIC:
                $color = strtoupper($this->getConfig()->getNested("color.mythic"));
                return $this->translateColorNameToTextFormat($color) == false ? TextFormat::LIGHT_PURPLE : $this->translateColorNameToTextFormat($color);
            default:
                return TextFormat::GRAY;
        }
    }

    /**
     * Translates color name to TextFormat constant
     *
     * @param $color
     * @return bool|mixed
     */
    public function translateColorNameToTextFormat($color)
    {
        foreach (self::COLOR_CONVERSION_TABLE as $name => $textformat) {
            if ($color == $name) {
                return $textformat;
            }
        }
        return false;
    }

    /**
     * Returns roman numeral of a number
     *
     * @param $integer
     * @return string
     */
    public function getRomanNumber($integer) //Thank you @Muqsit!
    {
        $romanString = "";
        while ($integer > 0) {
            foreach (self::ROMAN_CONVERSION_TABLE as $rom => $arb) {
                if ($integer >= $arb) {
                    $integer -= $arb;
                    $romanString .= $rom;
                    break;
                }
            }
        }
        return $romanString;
    }

    /**
     * Removes enchantment from item
     *
     * @param Item $item
     * @param      $enchant
     * @param int  $level
     * @return bool|Item
     */
    public function removeEnchantment(Item $item, $enchant, $level = -1)
    {
        if (!$item->hasEnchantments()) {
            return false;
        }
        if ($enchant instanceof EnchantmentInstance) {
            $enchant = $enchant->getType();
        }
        $ench = $item->getNamedTagEntry(Item::TAG_ENCH);
        if (!($ench instanceof ListTag)) {
            return false;
        }
        foreach ($ench as $k => $entry) {
            if ($entry->getShort("id") === $enchant->getId() and ($level === -1 or $entry->getShort("lvl") === $level)) {
                $ench->remove($k);
                if ($this->getConfig()->getNested('enchant-position.name')) $item->setCustomName(str_replace("\n" . $this->getRarityColor($enchant->getRarity()) . $enchant->getName() . " " . $this->getRomanNumber($entry->getShort("lvl")), "", $item->getCustomName()));
                if ($this->getConfig()->getNested('enchant-position.lore')) $item->setLore(str_replace("\n" . $this->getRarityColor($enchant->getRarity()) . $enchant->getName() . " " . $this->getRomanNumber($entry->getShort("lvl")), "", $item->getLore()));
                break;
            }
        }
        $item->setNamedTagEntry($ench);
        return $item;
    }

    /**
     * Returns rarity of enchantment
     *
     * @param CustomEnchants $enchant
     * @return string
     */
    public function getEnchantRarity(CustomEnchants $enchant)
    {
        foreach ($this->enchants as $id => $data) {
            if ($enchant->getId() == $id) {
                return $data[3];
            }
        }
        return "Common";
    }

    /**
     * Returns the description of the enchantment
     *
     * @param CustomEnchants $enchant
     * @return string
     */
    public function getEnchantDescription(CustomEnchants $enchant)
    {
        foreach ($this->enchants as $id => $data) {
            if ($enchant->getId() == $id) {
                return $data[5];
            }
        }
        return "Unknown";
    }

    /**
     * Sorts enchantments by type.
     *
     * @return array
     */
    public function sortEnchants()
    {
        $sorted = [];
        foreach ($this->enchants as $id => $data) {
            $type = $data[1];
            if (!isset($sorted[$type])) {
                $sorted[$type] = [$data[0]];
            } else {
                array_push($sorted[$type], $data[0]);
            }
        }
        return $sorted;
    }

    /**
     * Checks for a certain block under a position
     *
     * @param Position $pos
     * @param          $ids
     * @param          $deep
     * @return bool
     * @internal param $id
     */
    public function checkBlocks(Position $pos, $ids, $deep = 0)
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        if ($deep == 0) {
            $block = $pos->getLevel()->getBlock($pos);
            if (!in_array($block->getId(), $ids)) {
                return false;
            }
        } else {
            for ($i = 0; $deep < 0 ? $i >= $deep : $i <= $deep; $deep < 0 ? $i-- : $i++) {
                $block = $pos->getLevel()->getBlock($pos->subtract(0, $i));
                if (!in_array($block->getId(), $ids)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @param Position    $position
     * @param int         $range
     * @param string      $type
     * @param Player|null $player
     * @return null|Entity
     */
    public function findNearestEntity(Position $position, int $range = 50, string $type = Player::class, Player $player = null)
    {
        assert(is_a($type, Entity::class, true));
        $nearestEntity = null;
        $nearestEntityDistance = $range;
        foreach ($position->getLevel()->getEntities() as $entity) {
            $distance = $position->distance($entity);
            if ($distance <= $range && $distance < $nearestEntityDistance && $entity instanceof $type && $player !== $entity && $entity->isAlive() && $entity->isClosed() !== true && $entity->isFlaggedForDespawn() !== true) {
                $nearestEntity = $entity;
                $nearestEntityDistance = $distance;
            }
        }
        return $nearestEntity;
    }
}
