<?php
 /**
  *------
  * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
  * TwentyFourSeven implementation : © <Your name here> <Your email address here>
  * 
  * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
  * See http://en.boardgamearena.com/#!doc/Studio for more information.
  * -----
  * 
  * twentyfourseven.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class TwentyFourSeven extends Table
{
	function __construct( )
	{
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        
        self::initGameStateLabels( array( 
            //    "my_first_global_variable" => 10,
            //    "my_second_global_variable" => 11,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
        ) );

	    $this->tiles = self::getNew( "module.common.deck" );
	    $this->tiles->init( "tile" );

	}
	
    protected function getGameName( )
    {
		// Used for translations and stuff. Please do not modify.
        return "twentyfourseven";
    }	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {    
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];
 
        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();
        
        /************ Start the game initialization *****/

        // Init global values with their initial values
        //self::setGameStateInitialValue( 'my_first_global_variable', 0 );
        
        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // Create tiles
        $tiles = array();
        for ($value = 1; $value <= 10; $value ++) {
            $tiles[] = array('type' => 'tile', 'type_arg' => $value, 'nbr' => 4);
        }
        $this->tiles->createCards( $tiles, 'deck' );

        // Shuffle tiles
        $this->tiles->shuffle('deck');

        // Draw tile for center of board
        $center_tile = $this->tiles->pickCardForLocation('deck', 'board');

        // Draw and discard 3 tiles
        $this->tiles->pickCardsForLocation(3, 'deck', 'discard');

        // Draw player hands
        $players = self::loadPlayersBasicInfos();
        $player_count = count($players);
        $hand_size = ($player_count == 2) ? 6 : 5;

        foreach ( $players as $player_id => $player ) {
            $this->tiles->pickCards($hand_size, 'deck', $player_id);
        }

        // Init the board
        $sql = "INSERT INTO board (board_x,board_y,board_value) VALUES ";
        $sql_values = array();
        for( $x=1; $x<=7; $x++ )
        {
            for( $y=1; $y<=7; $y++ )
            {
                $board_value = "NULL";
                if( $x==4 && $y==4 )  // Center space
                    $board_value = $center_tile['type_arg'];
                    
                $sql_values[] = "('$x','$y',$board_value)";
            }
        }
        $sql .= implode( ',', $sql_values );
        self::DbQuery( $sql );

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();
    
        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );
  
        // Tiles in player hand
        $result['hand'] = $this->tiles->getCardsInLocation( 'hand', $current_player_id );
        // Pieces (tiles and time out stones) on the board
        $result['board'] = self::getObjectListFromDB( "SELECT board_x x, board_y y, board_value value
                                                       FROM board
                                                       WHERE board_value IS NOT NULL" );
        // Playable spaces on the board
        $result['spaces'] = self::getPlayableSpaces();

        // TODO: remove
        //$result['43lines'] = self::getLinesAtSpace( 4, 3 );
        //$result['41lines'] = self::getLinesAtSpace( 4, 1 );
        // TODO: remove

        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        // TODO: compute and return the game progression

        return 0;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    // Get the complete board with a double associative array
    function getBoard()
    {
        return self::getDoubleKeyCollectionFromDB( "SELECT board_x x, board_y y, board_value value FROM board", true );
    }

    /*
     * Get the lines at (x,y). When (x,y) does not contain a tile (value > 0), 
     * no lines are returned.
     */
    function getLinesAtSpace( $x, $y ) {
        $lines = array();
        $lines[0] = $this->getHLineAtSpace( $x, $y );
        $lines[1] = $this->getVLineAtSpace( $x, $y );
        $lines[2] = $this->getLDLineAtSpace( $x, $y );
        $lines[3] = $this->getRDLineAtSpace( $x, $y );
        return $lines;
    }

    /*
     * Gets the horizontal line at (x,y). When (x,y) does not contain a tile 
     * (value > 0), no line is returned.
     */
    function getHLineAtSpace( $x, $y ) {
        self::DbQuery( "SET @x := $x, @y := $y" );
        return self::getObjectListFromDB( "SELECT B.board_x x, B.board_y y, B.board_value value
                                            FROM (
                                                SELECT board_x, board_y, board_value, @exp := @exp + 1 exp, CASE WHEN board_value > 0 THEN @act := @act + 1 ELSE @act END act
                                                FROM board b join (SELECT @exp := 0, @act := 0) c
                                                WHERE board_y = @y
                                                ORDER BY board_x, board_y
                                            ) B 
                                            JOIN (
                                                SELECT COUNT(*) tep FROM board WHERE board_x <= @x AND board_y = @y
                                            ) E 
                                            JOIN (
                                                SELECT COUNT(*) tap FROM board WHERE board_x <= @x AND board_y = @y AND board_value > 0
                                            ) A
                                            WHERE 
                                                B.board_value > 0 AND -- Space has a tile
                                                E.tep > 0 AND A.tap > 0 AND -- Tile expected and actual positions exist
                                                (B.exp - E.tep) = (B.act - A.tap) -- Relative expected position == Actual expected position
                                            ORDER BY B.board_x, B.board_y " );
    }

    /*
     * Gets the vertical line at (x,y). When (x,y) does not contain a tile 
     * (value > 0), no line is returned.
     */
    function getVLineAtSpace( $x, $y ) {
        self::DbQuery( "SET @x := $x, @y := $y" );
        return self::getObjectListFromDB( "SELECT B.board_x, B.board_y, B.board_value
                                            FROM (
                                                SELECT board_x, board_y, board_value, @exp := @exp + 1 exp, CASE WHEN board_value > 0 THEN @act := @act + 1 ELSE @act END act
                                                FROM board b join (SELECT @exp := 0, @act := 0) c
                                                WHERE board_x = @x
                                                ORDER BY board_x, board_y
                                            ) B 
                                            JOIN (
                                                SELECT COUNT(*) tep FROM board WHERE board_x = @x AND board_y <= @y
                                            ) E 
                                            JOIN (
                                                SELECT COUNT(*) tap FROM board WHERE board_x = @x AND board_y <= @y AND board_value > 0
                                            ) A
                                            WHERE 
                                                B.board_value > 0 AND -- Space has a tile
                                                E.tep > 0 AND A.tap > 0 AND -- Tile expected and actual positions exist
                                                (B.exp - E.tep) = (B.act - A.tap) -- Relative expected position == Actual expected position
                                            ORDER BY B.board_x, B.board_y " );
    }
    
    /*
     * Gets the left diagonal (NW->SE) line at (x,y). When (x,y) does not 
     * contain a tile (value > 0), no line is returned.
     */
    function getLDLineAtSpace( $x, $y ) {
        self::DbQuery( "SET @x := $x, @y := $y" );
        return self::getObjectListFromDB( "SELECT B.board_x, B.board_y, B.board_value
                                            FROM (
                                                SELECT board_x, board_y, board_value, @exp := @exp + 1 exp, CASE WHEN board_value > 0 THEN @act := @act + 1 ELSE @act END act
                                                FROM board b join (SELECT @exp := 0, @act := 0) c
                                                WHERE board_x = (@x - @y) + board_y AND board_y = (@y - @x) + board_x
                                                ORDER BY board_x, board_y
                                            ) B 
                                            JOIN (
                                                SELECT COUNT(*) tep FROM board WHERE board_x = (@x - @y) + board_y AND board_y = (@y - @x) + board_x AND board_x <= @x AND board_y <= @y
                                            ) E 
                                            JOIN (
                                                SELECT COUNT(*) tap FROM board WHERE board_x = (@x - @y) + board_y AND board_y = (@y - @x) + board_x AND board_x <= @x AND board_y <= @y AND board_value > 0
                                            ) A
                                            WHERE 
                                                B.board_value > 0 AND -- Space has a tile
                                                E.tep > 0 AND A.tap > 0 AND -- Tile expected and actual positions exist
                                                (B.exp - E.tep) = (B.act - A.tap) -- Relative expected position == Actual expected position
                                            ORDER BY B.board_x, B.board_y " );
    }
    
    /*
     * Gets the right diagonal (SW->NE) line at (x,y). When (x,y) does not 
     * contain a tile (value > 0), no line is returned.
     */
    function getRDLineAtSpace( $x, $y ) {
        self::DbQuery( "SET @x := $x, @y := $y") ;
        return self::getObjectListFromDB( "SELECT B.board_x, B.board_y, B.board_value
                                            FROM (
                                                SELECT board_x, board_y, board_value, @exp := @exp + 1 exp, CASE WHEN board_value > 0 THEN @act := @act + 1 ELSE @act END act
                                                FROM board b JOIN (SELECT @exp := 0, @act := 0) c
                                                WHERE board_x = (@x + @y) - board_y AND board_y = (@x + @y) - board_x
                                                ORDER BY board_x, board_y
                                            ) B 
                                            JOIN (
                                                SELECT COUNT(*) tep FROM board WHERE board_x = (@x + @y) - board_y AND board_y = (@x + @y) - board_x AND board_x <= @x AND board_y >= @y
                                            ) E 
                                            JOIN (
                                                SELECT COUNT(*) tap FROM board WHERE board_x = (@x + @y) - board_y AND board_y = (@x + @y) - board_x AND board_x <= @x AND board_y >= @y AND board_value > 0
                                            ) A
                                            WHERE 
                                                B.board_value > 0 AND -- Space has a tile
                                                E.tep > 0 AND A.tap > 0 AND -- Tile expected and actual positions exist
                                                (B.exp - E.tep) = (B.act - A.tap) -- Relative expected position == Actual expected position
                                            ORDER BY B.board_x, B.board_y " );
    }
    
    /*
     * Get the list of empty spaces adjacent to tiles
     */
    function getPlayableSpaces()
    {
        return self::getObjectListFromDB( "SELECT E.board_x x, E.board_y y, E.board_value value 
                                            FROM board E 
                                            JOIN board A ON 
                                                E.board_value IS NULL AND 
                                                A.board_value IS NOT NULL AND A.board_value > 0 AND 
                                                A.board_x BETWEEN (E.board_x - 1) AND (E.board_x + 1) AND
                                                A.board_y BETWEEN (E.board_y - 1) AND (E.board_y + 1)" );
    }

    /*
     * Get the list of playable tiles for a player
     */
    function getPlayableTiles( $player_id )
    {
        $result = array();

        // Find the largest tile that can be played on the board
        // Return the list of tiles in the player's hand less than or equal 
        // to the largest tile that can be played

        return $result;
    }

    /*
     * Determine whether any player has a playable tile
     */
    function doesPlayableTileExist() {
        // Get smallest tile held by players
        // Get largest tile that can be played on the board
        //TODO: GET TILE VALUES!!
        $playerTileValue = 1;
        $boardTileValue = 1;
        if ($playerTileValue <= $boardTileValue) {
            return true;
        } else {
            return false;
        }
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in twentyfourseven.action.php)
    */

    /*
     * Play the given tile on the given space (x,y)
     */
    function playTile( $x, $y, $tileId )
    {
        /*
         * Check that this player is active and that this action is possible 
         * at this moment.
         */
        self::checkAction( 'playTile' );

        $board = self::getBoard();
        $player_id = self::getActivePlayerId();
        
        /*
         * Check if the tile is a valid play. The play is valid if:
         * - The space is empty (null).
         * - The tile is in the active player's hand.
         * - The sum of any line passing through the space sums to 24 or less
         * after playing the tile.
         */
        //TODO
        if( TRUE )
        {
            // This move is possible!

            /*
             * Update the board at (x,y) with the value of the tile 
             */
            //TODO

            /*
             * Change the location of the tile from the player's hand to 
             * the board.
             */
            //TODO

            /*
             * Mark any playable spaces with time out stones (value = 0) that 
             * are no longer playable (placing any tile on the space would 
             * result in a line through the space adding up to more than 24).
             */
            //TODO

            /*
             * Score the space. Get all the lines passing through the space 
             * (x,y) and tally the score.
             */
            //TODO

            /*
             * Update the player score. Add the total scored from playing the 
             * tile to the player's score.
             */
            //TODO

            /*
             * Update the statistics for the player. Increase the tallies for 
             * the player based on what was scored (runs, sets, 24/7s, etc) 
             * from playing the tile.
             */
            //TODO

            /*
             * Draw a tile and add it to the player's hand.
             */
            //TODO

            /*
             * Notify players of the game progression.
             */
            
            /*
             * Played tile notification
             */
            self::notifyAllPlayers( "playTile", clienttranslate( '${player_name} played a ${value} on row ${x} and column ${y} and scored ${minutes} minutes' ), array(
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'minutes' => count( $minutes ),
                'value' => $value,
                'x' => $x,
                'y' => $y
            ) );

            /*
             * New scores notification
             */
            $newScores = self::getCollectionFromDb( "SELECT player_id, player_score FROM player", true );
            self::notifyAllPlayers( "newScores", "", array(
                "scores" => $newScores
            ) );

            // Go to the next state
            $this->gamestate->nextState( 'playTile' );
        } else
            throw new BgaSystemException( "Impossible move" );
    }


    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    function argPlayerTurn()
    {
        $current_player_id = self::getCurrentPlayerId();

        return array(
            'playableSpaces' => self::getPlayableSpaces(),
            'hand' => $this->tiles->getCardsInLocation( 'hand', $current_player_id )

        );
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    function stNextPlayer() {
        // Active next player
        $player_id = self::activeNextPlayer();

        /*
         * Playable spaces - Empty spaces adjacent to tiles on the board (value > 0)
         * are playable. If none exist, the game is over.
         */
        $playableSpaces = self::getPlayableSpaces();
        if( count( $playableSpaces ) == 0 ) {
            /*
             * The board has no playable spaces. Game over.
             */
            $this->gamestate->nextState( 'endGame' );
            return ;
        }

        /*
         * Playable tiles - Tiles in players hands are playable when every line 
         * through a playable space will add up to 24 or less after playing the
         * tile. If all players hands are empty or none of their tiles can be 
         * played (because they are too large), the game is over.
         */
        if( self::doesPlayableTileExist() ) {
            /*
             * No player has a tile that can be played on the board or all 
             * players are out of tiles. Game over.
             */
            $this->gamestate->nextState( 'endGame' );
            return ;
        }

        /*
         * Playable tiles by active player - Are any of the tiles in the active 
         * player's hand playable? 
         */
        $playableTiles = self::getPlayableTiles( $player_id );
        if( count( $playableTiles ) == 0 ) {
            /*
             * This player can't play. Since we are here, we know there are 
             * playable spaces on the board and at least one player has a tile
             * that can be played. The game is not over but this player cannot 
             * take a turn.
             */
            $this->gamestate->nextState( 'cantPlay' );
        } else {
            /*
             * This player can play. Give them some extra time
             */
            self::giveExtraTime( $player_id );
            $this->gamestate->nextState( 'nextTurn' );
        }

    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, '' );
            
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }
    
///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */
    
    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
        
        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }    
}
