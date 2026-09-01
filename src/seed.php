<?php
/**
 * Sample catalogue — the historical Citadel references the design prototype
 * shipped with, so a fresh install has something real to look at.
 * Replace it with the live archive; install.php only ever writes it once.
 */

return [
    'ranges' => [
        "CIT" => "Citadel C-Series",
        "RR" => "Regiments of Renown",
        "RT" => "40,000 — Rogue Trader",
        "MM" => "Marauder Miniatures",
        "BB" => "Blood Bowl",
    ],
    'sets' => [
        ["CIT", "C01", "Fighters"],
        ["CIT", "C06", "Dwarfs"],
        ["CIT", "C11", "Orcs"],
        ["CIT", "C12", "Goblins"],
        ["RR", "RR3", "Bugman's Dwarf Rangers"],
        ["RR", "RR4", "Ruglud's Armoured Orcs"],
        ["RR", "RR8", "Grom's Goblin Guard"],
        ["RT", "RT01", "Space Marines"],
        ["RT", "RT101", "Space Orks"],
        ["MM", "MM10", "Dwarfs"],
        ["MM", "MM30", "Chaos Warriors"],
        ["BB", "BB2", "Human Team"],
    ],
    'figures' => [
        "C01" => ["Sword & Shield Fighter", "Two-Handed Swordsman", "Fighter with Axe", "Fighter in Plate", "Man-at-Arms, Advancing", "Champion with Warhammer", "Spearman, Braced", "Fighter with Halberd"],
        "C06" => ["Dwarf with Axe", "Dwarf Crossbowman", "Dwarf Lord", "Dwarf Slayer", "Dwarf Standard Bearer", "Dwarf Musician", "Dwarf with Two-Handed Axe"],
        "C11" => ["Orc with Scimitar", "Orc Archer", "Orc Boss", "Orc Standard Bearer", "Orc with Spear", "Orc Musician", "Orc with Club", "Orc Champion"],
        "C12" => ["Goblin with Spear", "Goblin Archer", "Goblin Netter", "Goblin Boss", "Goblin with Sword", "Goblin Standard"],
        "RR3" => ["Josef Bugman", "Ranger with Crossbow", "Ranger with Axe", "Ranger Standard Bearer", "Ranger Musician", "Ranger with Handgun"],
        "RR4" => ["Ruglud Bonechewer", "Armoured Orc, Crossbow", "Armoured Orc, Loading", "Armoured Orc Standard", "Armoured Orc Musician"],
        "RR8" => ["Grom the Paunch", "Goblin Guard, Spear", "Goblin Guard, Bow", "Goblin Guard Standard", "Niblit the Standard Runner"],
        "RT01" => ["Marine with Bolter", "Marine Sergeant", "Marine with Missile Launcher", "Marine, Kneeling", "Marine Captain", "Marine with Flamer"],
        "RT101" => ["Ork Boy with Bolter", "Ork Nob", "Ork with Heavy Stubber", "Ork Runtherd", "Ork Boy, Charging", "Ork Kommando", "Ork Warboss"],
        "MM10" => ["Dwarf with Hand Weapon", "Dwarf Berserker", "Dwarf Thane", "Dwarf Crossbow", "Dwarf Standard"],
        "MM30" => ["Chaos Warrior, Sword", "Chaos Warrior, Axe", "Chaos Champion", "Chaos Standard Bearer", "Chaos Warrior, Halberd"],
        "BB2" => ["Human Lineman", "Human Thrower", "Human Catcher", "Human Blitzer", "Human Coach", "Human Lineman, Blocking"],
    ],
    'photos' => ["uploads/Scum-1.jpg", "uploads/Extech.jpg", "uploads/Hero.jpg", "uploads/Female_Warrior_Jayne.jpg"],
];
