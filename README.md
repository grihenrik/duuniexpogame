# duuniexpogame
This is a part of the Duuniexpo website, what we did was create a small javascriptbased program 
that was provided by a wp site and uses the WP-api to talk to the server.
The game is made in a simple Who wants to be a millionaire style with a question and four plausible answers 
that should become progressively harder as the game goes forward.

The solution is based on Vanilla Js, Wordpress and has a webworker that that wraps the game logic, 
all to ensure that the player should be able to play the game on a limited mobile network.

Some the plugin is not stand-alone but uses another plugin for the admin side of setting up the questions.
The main purpose of the written plugin is to create the api-backend.
