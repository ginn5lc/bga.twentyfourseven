# 24/7: The Game (BoardGameArena Edition)

Digital implementation of the board game, 24/7: The Game, on BoardGameArena's platform.

## TODO

List of ideas to implement prior to alpha release.

### Javascript
- Add tally to each player board/panel (or add a tally area to the game area - tf7_table)
    - Initial population in game setup from the gamedatas object
    - update using arg in new scores notification
- Highlight combos scored at the end of the turn (when scores are updated, etc)
    - Highlight combos scored in the client
- Add tile play confirmation to the play tile flow (click tile to play, click space to play, confirm/cancel action)
- Highlight space clicked on for tile play (instead of playing the tile)
- Add max tile value that can be played on each playable space
- Mark playable spaces with green check or red X when a tile is selected based on whether it will ‘fit’ in the space
- List combos scored in the turn summary log (along the right hand side under player boards/panel)?
- Standardize all the ids, classes, etc so there is no concern about namespace collisions (i.e., prefix everything with tf7_)

### PHP
- Standardize all the ids, classes, etc so there is no concern about namespace collisions (i.e., prefix everything with tf7_)
- Implement zombie turn

### BGA
- Add docs/rules/etc
- Add required images
- Review pre-release checklist

## DONE

Basic implementation of the game with rules enforcement complete. This is a list of items completed from the todo list.
- Keep an array of combos scored (e.g., {name => “Sum of 24”, spaces => [{x => 1, y => 2, value => 8}, … ]}) so the combos with spaces can be returned to the client and used to highlight on each player’s view.
- Use the array of combos to create the tally
