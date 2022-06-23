/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * TwentyFourSeven implementation : © Jim Ginn ginn5j@gmail.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * lcgtwentyfourseven.js
 *
 * TwentyFourSeven user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock"
],
function (dojo, declare) {
    return declare("bgagame.lcgtwentyfourseven", ebg.core.gamegui, {
        constructor: function(){
            console.log('lcgtwentyfourseven constructor');
              
            // Here, you can init the global variables of your user interface
            // Example:
            // this.myGlobalValue = 0;
            this.tilewidth = 75;
            this.tileheight = 105;
        },
        
        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        
        setup: function( gamedatas )
        {
            console.log( "Starting game setup" );

            //TODO: REMOVE
            console.log( gamedatas );
            
            // Setting up player boards
            for( var player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];
                         
                // TODO: Setting up players boards if needed
            }
            
            // Player hand
            this.playerHand = new ebg.stock();
            this.playerHand.create(this, $('tf7_player_hand'), this.tilewidth, this.tileheight);
            this.playerHand.image_items_per_row = 5;
            this.playerHand.centerItems = true;
            this.playerHand.extraClasses = 'playerTile';
            this.playerHand.setSelectionMode( 1 );

            // Create cards types:
            for (var value = 1; value <= 10; value++) {
                // Build card type id
                this.playerHand.addItemType(value, value, g_gamethemeurl + 'img/tiles.png', (value - 1));
            }

            // Update the player's hand
            for ( const i in gamedatas.hand ) 
            {
                const tile = gamedatas.hand[ i ];
                this.playerHand.addToStockWithId(tile.type_arg, tile.id);
            }

            for( const space of gamedatas.board )
            {
                if( space.value !== null )
                {
                    this.addPieceOnBoard( space.x, space.y, space.value );
                }
            }

            // Listen for click events on the board
            /*
                'this' will not be the 24/7 JS instance when onPlayTile is 
                called so it needs to be captured when the listener is 
                registered and passed to the function so it has access to 
                other properties and functions during it's execution.
            */
            var self = this;
            document.querySelector( '#board' ).addEventListener( 'click', function( event ) { self.onPlayTile( event, self ); } );

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
        },
       

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName );
            //TODO: REMOVE
            console.log( args );
            //TODO: REMOVE
            
            switch( stateName )
            {
                case 'playerTurn':
                    this.onEnterPlayerTurn( args );
                    break;
            
            /* Example:
            
            case 'myGameState':
            
                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );
                
                break;
           */
           
           
            case 'dummmy':
                break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch( stateName )
            {
            /* Example:
            
            case 'myGameState':
            
                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );
                
                break;
           */
           
           
            case 'dummmy':
                break;
            }               
        }, 

        onEnterPlayerTurn: function( args )
        {
            this.updatePlayableSpaces( args.args.playableSpaces );
        },

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );
                      
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
/*               
                 Example:
 
                 case 'myGameState':
                    
                    // Add 3 action buttons in the action status bar:
                    
                    this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' ); 
                    this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' ); 
                    this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' ); 
                    break;
*/
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods

        addPieceOnBoard: function( x, y, value, player )
        {
            if (value > 0) { // Tile
                dojo.place( this.format_block( 'jstpl_tile', {
                    x_y: x+'_'+y,
                    value: value
                } ) , 'pieces' );
                
                if (player !== undefined) {
                    this.placeOnObject( 'tile_'+x+'_'+y, 'overall_player_board_'+player );
                } else {
                    this.placeOnObject( 'tile_'+x+'_'+y, 'board' );
                }
                this.slideToObject( 'tile_'+x+'_'+y, 'space_'+x+'_'+y ).play();
            } else { // Time out stone
                dojo.place( this.format_block( 'jstpl_stone', {
                    x_y: x+'_'+y
                } ) , 'pieces' );
                
                this.placeOnObject( 'stone_'+x+'_'+y, 'board' );
                this.slideToObject( 'stone_'+x+'_'+y, 'space_'+x+'_'+y ).play();
            }
        },

        updatePlayableSpaces: function( playableSpaces )
        {
            if( this.isCurrentPlayerActive() )
            {
                for( const space of playableSpaces )
                {
                    // x,y is a playable space
                    dojo.addClass( 'space_'+space.x+'_'+space.y, 'playableSpace' );
                }

                this.addTooltipToClass( 'playableSpace', '', _('Play a tile here') );
            }
        },

        updatePlayerHand: function( playTile, drawTile )
        {
            // Remove the played tile from the hand
            if( playTile != null ){
                this.playerHand.removeFromStockById( playTile.id );
            }

            // Add the drawn tile to the hand
            if( drawTile != null ){
                this.playerHand.addToStockWithId(drawTile.type_arg, drawTile.id);
            }
        },

        ///////////////////////////////////////////////////
        //// Player's action
        
        /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */
        
        /*
            Handle playing a tile (clicking a playable space).

            Since this will be called from an event handler, we need to pass
            along the game instance when registering the handler.
        */
        onPlayTile: function( event, game )
        {
            // Stop propagation and prevent any default handling of the event
            event.stopPropagation();
            event.preventDefault();

            if( event.target.classList.contains( 'playableSpace' ) )
            {
                // Get the clicked space X and Y
                // Note: space id format is "space_X_Y"
                var coords = event.target.id.split('_');
                var x = coords[1];
                var y = coords[2];

                console.log('Playable space ('+x+','+y+') clicked!');

                if( game.checkAction( 'playTile' ) )    // Check that this action is possible at this moment
                {
                    // Get the selected tiles (should only be 1)
                    var tiles = game.playerHand.getSelectedItems();

                    if( tiles.length == 1 ){
                        console.log('Tile type: ' + tiles[0].type + ', id: ' + tiles[0].id + ' played!');

                        // Exactly 1 tile selected, tell the server to process the played tile
                        game.ajaxcall( "/lcgtwentyfourseven/lcgtwentyfourseven/playTile.html", {
                            lock:true,
                            x:x,
                            y:y,
                            tileId:tiles[0].id
                        }, game, function( result ) {} );
                    }
                    else
                    {
                        console.log('Wrong number of tiles selected - ' + tiles.length + '.');
                    }
                }            
            }

        },        
            
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your lcgtwentyfourseven.game.php file.
        
        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );
            
            dojo.subscribe( 'playTile', this, "notif_playTile" );
            this.notifqueue.setSynchronous( 'playTile', 500 );
            dojo.subscribe( 'newScores', this, "notif_newScores" );
            this.notifqueue.setSynchronous( 'newScores', 500 );
            dojo.subscribe( 'handChange', this, "notif_handChange" );
            this.notifqueue.setSynchronous( 'handChange', 500 );
        },  
        
        /*
         * Handle the play tile notification.
         */
        notif_playTile: function( notif )
        {
            // Clear any playable spaces after a tile has been played
            dojo.query( '.playableSpace' ).removeClass( 'playableSpace' ); 

            // Add the played tile to the board
            this.addPieceOnBoard( notif.args.x, notif.args.y, notif.args.value, notif.args.player_id );

            // Place any time out stones
            for( const space of notif.args.time_out_spaces )
            {
                this.addPieceOnBoard( space.x, space.y, space.value, notif.args.player_id );
            }

            console.log(notif.args);
        },

        /*
         * Handle the new scores notification.
         */
        notif_newScores: function( notif )
        {
            for( const player_id in notif.args.scores )
            {
                var newScore = notif.args.scores[ player_id ];
                this.scoreCtrl[ player_id ].toValue( newScore );
            }
        },
        
        /*
         * Handle the hand change notification.
         */
        notif_handChange: function( notif )
        {
            this.updatePlayerHand( notif.args.playTile, notif.args.drawTile );
        },
        
   });             
});
