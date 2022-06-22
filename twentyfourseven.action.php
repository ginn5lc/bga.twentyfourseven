<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * TwentyFourSeven implementation : © Jim Ginn ginn5j@gmail.com
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 * 
 * twentyfourseven.action.php
 *
 * TwentyFourSeven main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/twentyfourseven/twentyfourseven/myAction.html", ...)
 *
 */
  


  class action_twentyfourseven extends APP_GameAction { 
    // Constructor: please do not modify
    public function __default()
    {
      if( self::isArg( 'notifwindow') ) {
        $this->view = "common_notifwindow";
        $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
      } else {
        $this->view = "twentyfourseven_twentyfourseven";
        self::trace( "Complete reinitialization of board game" );
      }
    } 
  	
    // Play Tile: Player played a tile (tileId) on the board (at x, y)
    public function playTile() {
        self::setAjaxMode();     
        $x = self::getArg( "x", AT_posint, true );
        $y = self::getArg( "y", AT_posint, true );
        $tileId = self::getArg( "tileId", AT_posint, true );
        $result = $this->game->playTile( $x, $y, $tileId );
        self::ajaxResponse();
    }

  }
