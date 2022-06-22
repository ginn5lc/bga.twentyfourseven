<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * TwentyFourSeven implementation : © Jim Ginn ginn5j@gmail.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * stats.inc.php
 *
 * TwentyFourSeven game statistics description
 *
 */

/*
    In this file, you are describing game statistics, that will be displayed at the end of the
    game.
    
    !! After modifying this file, you must use "Reload  statistics configuration" in BGA Studio backoffice
    ("Control Panel" / "Manage Game" / "Your Game")
    
    There are 2 types of statistics:
    _ table statistics, that are not associated to a specific player (ie: 1 value for each game).
    _ player statistics, that are associated to each players (ie: 1 value for each player in the game).

    Statistics types can be "int" for integer, "float" for floating point values, and "bool" for boolean
    
    Once you defined your statistics there, you can start using "initStat", "setStat" and "incStat" method
    in your game logic, using statistics names defined below.
    
    !! It is not a good idea to modify this file when a game is running !!

    If your game is already public on BGA, please read the following before any change:
    http://en.doc.boardgamearena.com/Post-release_phase#Changes_that_breaks_the_games_in_progress
    
    Notes:
    * Statistic index is the reference used in setStat/incStat/initStat PHP method
    * Statistic index must contains alphanumerical characters and no space. Example: 'turn_played'
    * Statistics IDs must be >=10
    * Two table statistics can't share the same ID, two player statistics can't share the same ID
    * A table statistic can have the same ID than a player statistics
    * Statistics ID is the reference used by BGA website. If you change the ID, you lost all historical statistic data. Do NOT re-use an ID of a deleted statistic
    * Statistic name is the English description of the statistic as shown to players
    
*/

$stats_type = array(

    // Statistics global to table
    "table" => array(

        "turns_number" => array("id"=> 10,
                    "name" => totranslate("Number of turns"),
                    "type" => "int" ),

    ),
    
    // Statistics existing for each player
    "player" => array(

        "turns_number" => array("id"=> 10,
                    "name" => totranslate("Number of turns"),
                    "type" => "int" ),
    
        "tally_sum_of_7" => array("id"=> 11,
                    "name" => totranslate("Sum of 7 (20 minutes)"),
                    "type" => "int" ),

        "tally_run_of_3" => array("id"=> 13,
                    "name" => totranslate("Run of 3 (30 minutes)"),
                    "type" => "int" ),

        "tally_run_of_4" => array("id"=> 14,
                    "name" => totranslate("Run of 4 (40 minutes)"),
                    "type" => "int" ),

        "tally_sum_of_24" => array("id"=> 12,
                    "name" => totranslate("Sum of 24 (40 minutes)"),
                    "type" => "int" ),

        "tally_run_of_5" => array("id"=> 15,
                    "name" => totranslate("Run of 5 (50 minutes)"),
                    "type" => "int" ),

        "tally_set_of_3" => array("id"=> 17,
                    "name" => totranslate("Set of 3 (50 minutes)"),
                    "type" => "int" ),

        "tally_run_of_6" => array("id"=> 16,
                    "name" => totranslate("Run of 6 (60 minutes)"),
                    "type" => "int" ),

        "tally_set_of_4" => array("id"=> 18,
                    "name" => totranslate("Set of 4 (60 minutes)"),
                    "type" => "int" ),

        "tally_bonus" => array("id"=> 19,
                    "name" => totranslate("Bonus (60 minutes)"),
                    "type" => "int" ),

    )

);
