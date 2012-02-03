<?php
/**
 * Structure
 *
 * Structure is a way to manage your RESTful data representations
 *
 * @package		Structure
 * @version		1.0
 * @author		Jaap Rood
 * @license		MIT License
 * @copyright	2011 Jaap Rood
 * @link		http://github.com/JaapRood/fuel-structure
 */

Autoloader::add_core_namespace('Representation');


Autoloader::add_classes(array(
	'Representation\\Representation'			=> __DIR__.'/classes/representation.php',
));


/* End of file bootstrap.php */