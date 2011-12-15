<?php
/**
 * phpcli - Basic i/o-features for php scripts in CLI mode
 * Copyright (C) 2011 basecom GmbH & Co. KG
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * @version         1.0
 * @copyright       basecom GmbH & Co. KG <http://www.basecom.de>
 * @author          Simon Schröer
 * @license         LGPL 3.0
 *
 * Requirements
 * - php, php-cli   (version >= 5.2)
 * - mb_string      (php module)
 * - wget           (only if web-download functionality required)
 */

/*
 * Check if the new default definition "__dir__" is available, if not create a fallback
 */
defined("__DIR__") || define("__DIR__", substr(__FILE__, 0, strrpos(__FILE__, '/')));

/*
 * Define the internal document-root for absolute pathes
 */
define("DOCROOT", __DIR__.'/');

/*
 * Include the phpcli framework
 */
require DOCROOT.'includes/phpcli.inc.php';

/*
 * clear the screen
 */
CLI::clear();

/*
 * Output the welcome screen
 */
CLI::write("       _                _ _ ");
CLI::write(" _ __ | |__  _ __   ___| (_)");
CLI::write("| '_ \| '_ \| '_ \ / __| | |");
CLI::write("| |_) | | | | |_) | (__| | |");
CLI::write("| .__/|_| |_| .__/ \___|_|_|");
CLI::write("|_|         |_|             \n\n");
CLI::write("This is the \"Hello World\" example script :)");
CLI::read("\n[Press enter to continue]");

/*
 * Write a simple selection Menue
 */
do
{
	CLI::write("\n");
	CLI::write("What would you like to do/see?");
	CLI::write("--------------------------");
	CLI::write("  1. Basic input/output handling");
	CLI::write("  2. Example shell formular");
	CLI::write("  3. Download a file from the web (requires wget!)");
	CLI::write("  4. Execute a custom shell command");
	CLI::write("\n(type \"exit\" to leave this example script)\n");

	$strAction = CLI::read('option-number: ');
	switch($strAction)
	{
		/*
		 * basic input/output handling
		 */
		case '1':
			do
			{
				$strName = CLI::read("Your name: ");
			}
			while(empty($strName));
			CLI::write("So $strName ...");

			do
			{
				$strFood = CLI::confirm("... what kind of food do you like?", array(
					'all'		=> 'ALL THE FOOD',
					'fish'		=> 'just fish',
					'meat'		=> 'just meat',
					'vegetarian'=> 'just the food of my food ;)',
					'nothing'	=> false
				), false);

				CLI::write("");
				if(!$strFood)
				{
					CLI::write("Are you kidding me? You can't eat nothing! Try again ...");
				}
				else if(CLI::confirm($strName.", are you sure that you eat ".$strFood.'?'))
				{
					break;
				}
			}
			while(true);

			CLI::read("\n\nWell done $strName, you mastered the basic input output example!\n".
					      "-> Try a new option to see how phpcli can help you ... (press enter to continue)");
		break;

		/*
		 * example shell formular
		 */
		case '2':
			CLI::read("\nNotice that the entered data in the following form won't be used for any purpose.\n".
					    "-> At the end of this example the entered data will be displayed on screen! (press enter to continue)");

			CLI::write("\nAt first some basic personal data without any extra ...");
			$arrFormData = CLI::readForm(array(
				'Personal data',
				'firstname'	=> 'Firstname',
				'lastname'	=> 'Lastname',
				'address'	=> 'Address',
				'state'		=> 'State'
			));

			CLI::write("\nAdd some select-functionality ... ");
			$arrFormData = $arrFormData + CLI::readForm(array(
				'Some more data',
				'likesflowers'	=> array('checkbox','Did you like flowers?'),
				'pets'			=> array('list',	'Enter the names of your pets (e.g. Elvis, Zeus, ...)',				false),	// optional
				'friends'		=> array('list',	'Enter the names of your friends (at least one; e.g. Henry, ...)',	true),	// at least one
				'parents'		=> array('list',	'Enter the names of your mom and dad (Marry, Peter)',				2),		// exactly 2
//				'example_1'		=> array('list',	'at least 2, max 99',	array(2, 99)),										// at least 2, max 99
//				'example_2'		=> array('list',	'at least 2, no max',	array(2, false)),									// at least 2, no max
//				'example_3'		=> array('list',	'optional, max 99',		array(false, 99)),									// optional, max 99
			));

			CLI::write("\nAdd some hidden-fields for passwords etc ... ");
			$arrFormData = $arrFormData + CLI::readForm(array(
				'Some hidden input values',
				'password'	=> array('password',	'Some password goes here'),
				'firstlove'	=> array('password',	'Whats the name of your first love'),
			));

			CLI::write("\nValidate the entered data with own regex ... ");
			$arrFormData = $arrFormData + CLI::readForm(array(
				'Some hidden input values',
				'email'	=> array('regex', 'E-Mail address', '/^([a-z0-9])(([-a-z0-9._])*([a-z0-9]))*\@([a-z0-9])(([a-z0-9-])*([a-z0-9]))+(\.([a-z0-9])([-a-z0-9_-])?([a-z0-9])+)+$/i'),
			));

			CLI::write("\nHere we go, this is the data you entered:\n\n");
			var_dump($arrFormData);

			CLI::read("\n\nWell done, you mastered the shell formular example!\n".
						  "-> Try a new option to see how phpcli can help you ... (press enter to continue)");
		break;

		/*
		 * download a file from the web
		 */
		case '3':
			
			do
			{
				$strURL = CLI::read("Enter absolute URL: ");
			}
			while(empty($strURL));

			do
			{
				$strTargetDir = CLI::read("Enter local target dir: ");
			}
			while(empty($strTargetDir) || !is_dir($strTargetDir));

			do
			{
				$strTargetFilename = CLI::read("Enter target filename: ");
			}
			while(empty($strTargetFilename));

			if(mb_substr($strTargetDir, -1) != '/')
			{
				$strTargetDir .= '/';
			}

			$strTarget = $strTargetDir.$strTargetFilename;
			CLI::wget($strURL, $strTarget);
			CLI::write('File downloaded an can be found here: '.$strTarget);

			CLI::read("\n\nWell done, you mastered the download example!\n".
						  "-> Try a new option to see how phpcli can help you ... (press enter to continue)");
		break;

		/*
		 * execute a custom shell command
		 */
		case '4':
			CLI::read("\n\nWARNING:\nThis is not a joke, the entered command WILL BE EXECUTED.\n".
					      "-> Please use this example with care! (press enter to continue)");

			CLI::write("");
			do
			{
				$strCMD = CLI::read("cmd: ");
			}
			while(empty($strCMD));

			if(CLI::confirm("Are you sure you want to execute the command?", array('y' => true, 'n' => false), false))
			{
				CLI::write("\nCommand will now be executed ... ", false);
				$strResult = CLI::exec($strCMD, true);
				CLI::write("DONE\n");
				CLI::write("Result of the command:\n".$strResult);
			}
			else
			{
				CLI::write("\nCommand NOT executed.");
			}

			CLI::read("\n\nWell done, you mastered the custom shell command example!\n".
						  "-> Try a new option to see how phpcli can help you ... (press enter to continue)");
		break;

		/*
		 * exit the example script by breaking this 'switch' and the outer 'do-while'
		 */
		case 'exit': break 2;
	}

	/*
	 * clear the screen
	 */
	CLI::clear();
}
while(true);

CLI::write("\n\nbye\n\n");

?>