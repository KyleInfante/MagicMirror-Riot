# MagicMirror-Riot #
Web Application built using Riot API to present match history for a given summoner name.  Primarily built for Rasberry Pi's Magic Mirror project.

## How to Set Up ##
* Copy all of the PHP code from the index.php file into your HTML.
* Ensure that the require_once on line 2 points to the riot.php file.
* Edit the file configs.json located at "configs/config.json" with your information.
  * __api-key:__  Go to the [Riot API Site](https://developer.riotgames.com/) and generate your own API key.  Paste that key here
  * __summoner-name:__  The summoner name of who you want to retrieve match data from.
  * __num-matches:__ How many matches to list at once.  Can onle handle 20 matches.
  * __only-ranked:__ true or false; Do you want to only display ranked matches.
  
### Important Notes ###
This application will only pull data from the NA region.  If you want to change that, just change the region values on lines 11,12 and 15.
This application only shows data for ranked or unranked 5v5 matches.  This does not include any special gamemodes, such as URF or One-for-All.
