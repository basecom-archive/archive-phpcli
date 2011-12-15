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
 * Is this script called in the right mode? 
 * -> If not stop the script to prevent unexpected results!
 */
PHP_SAPI=='cli' || die('This program works only in CLI-mode!');

/*
 * Check if the multibyte module-functions are available the handle UTF8 in the right way ...
 */
function_exists('mb_internal_encoding') || die("\n\nERROR: PHP module 'mb_string' required\n\n");

/*
 * Set the default internal encoding
 */
mb_internal_encoding('utf-8');

/*
 * Check if the default i/o-streams are allready available.
 * -> If not create them
 */
defined("STDIN")  || define("STDIN",  fopen('php://stdin','r'));		// input-stream (read only)
defined("STDOUT") || define("STDOUT", fopen('php://stdout','a'));	// output-stream (write only)
defined("STDERR") || define("STDERR", fopen('php://stderr','a'));	// error-stream (write only)

/*
 * Stop and flush the output buffer
 */
ob_end_flush();

/**
 * Basic features for comfortable usage of i/o manipulations
 * 
 * @author Simon Schröer
 * @package phpcli
 * @static
 */
class CLI
{
	/**
	 * Read the default input-stream until '$intLength' is reached or the user hits enter
	 * 
	 * @author Simon Schröer
	 * @param Integer $intLength	Amount of signs to read
	 * @return String				User input
	 * @static
	 */
	public static function stdin($intLength)
	{
		return fread(STDIN, (int)$intLength);
	}

	/**
	 * Writes '$strData' to the defaullt output-stream
	 * 
	 * @author Simon Schröer
	 * @param String $strData	Data to write
	 * @static
	 */
	public static function stdout($strData)
	{
		fwrite(STDOUT, $strData);
	}

	/**
	 * Writes '$strError' to the defaullt error-stream
	 * 
	 * @author Simon Schröer
	 * @param String $strError	Error to write
	 * @static
	 */
	public static function stderr($strError)
	{
		fwrite(STDERR, $strError);
	}

	/**
	 * Executes the shell command '$strCommand' and optionally returns the output
	 * 
	 * @author Simon Schröer
	 * @param String $strCommand	Command to execute
	 * @param Boolean $blnReturn	Return the command result (caused output) [default:true]
	 * @return Mixed				Returns true or the command output
	 * @static
	 */
	public static function exec($strCommand, $blnReturn = true)
	{
		if($blnReturn)
		{
			ob_start();
		}

		passthru($strCommand, $intError);

		if($blnReturn)
		{
			$strReturn = trim(ob_get_contents());
			ob_end_clean();
			return (!$intError ? $strReturn : false);
		}
		return true;
	}

	/**
	 * Muted the shell echo, usefull to hide passwords etc. (has no effect if already muted)
	 * 
	 * @author Simon Schröer
	 * @static
	 */
	public static function shellMute()
	{
		self::exec("stty -echo", false);
	}

	/**
	 * Unmute the shell echo (has no effect if already unmuted)
	 * 
	 * @author Simon Schröer
	 * @static
	 */
	public static function shellUnMute()
	{
		self::exec("stty echo", false);
	}

	/**
	 * Wrapper function for 'stdin()' with optionally pre-output and integrated trim for user-inputs
	 *
	 * @author Simon Schröer
	 * @param String $strPreOutput	Optionally data to output before reading the input (usefull for questions etc) [default:false]
	 * @param Integer $intLength	Optionally amount of signs to read [default:2048]
	 * @return String				User input
	 * @static
	 */
	public static function read($strPreOutput = false, $intLength = 2048)
	{
		if($strPreOutput)
		{
			self::write($strPreOutput, false);
		}
		return trim(self::stdin($intLength));
	}

	/**
	 * Read and hide user-input from default input stream
	 * 
	 * @author Simon Schröer
	 * @param String $strPreOutput	Optionally data to output before reading the input (usefull for questions etc) [default:false]
	 * @param Integer $intLength	Optionally amount of signs to read [default:2048]
	 * @return String				User input
	 * @static
	 */
	public static function readHidden($strPreOutput = false, $intLength = 2048)
	{
		if($strPreOutput)
		{
			self::write($strPreOutput, false);
		}
		self::shellMute();
		$mixInput = trim(self::stdin($intLength));
		self::shellUnMute();
		self::write("");
		return $mixInput;
	}

	/**
	 * Wrappter for 'stdout()' with optionally trailing new line
	 *
	 * @author Simon Schröer
	 * @param String $strData		Data to write
	 * @param Boolean $blnNextLine	Append a new line? [default:true]
	 * @static
	 */
	public static function write($strData, $blnNextLine = true)
	{
		self::stdout($strData.($blnNextLine ? "\n" : ""));
	}

	/**
	 * Prints a question and views all valid answers, optionally marks the first answer as default answer if user hits enter wihtout any other input.
	 *
	 * @author Simon Schröer
	 * @param String $strQuestion			Question to ask
	 * @param Array $arrAnswers				Array with answers (key = answer to print, value = internal value to use) [default:array('y'=>true,'n'=>false)]
	 * @param Boolean $blnFirstAsDefault	Mark the first answer as the default one? (converts answer-key to uppercase as usual in linux) [default:true]
	 * @param Mixed							The value of the selected answer-key
	 * @static
	 */
	public static function confirm($strQuestion, $arrAnswers = array('y' => true, 'n' => false), $blnFirstAsDefault = true)
	{
		$arrAnswersParsed = array();
		foreach($arrAnswers as $strAnswer => $mixValue)
		{
			$strAnswer = mb_strtolower($strAnswer);
		    $arrAnswersParsed[] = $strAnswer;
		    $arrAnswers[$strAnswer] = $mixValue;
		}

		if($blnFirstAsDefault)
		{
		    $arrAnswersParsed[0] = mb_strtoupper($arrAnswersParsed[0]);
		}

		$strQuestion .= " [".implode('/', $arrAnswersParsed)."]: ";
		$mixReturnValue = "";
		do
		{
			$strInput = mb_strtolower(self::read($strQuestion));
			if($strInput === "" && $blnFirstAsDefault)
			{
				$strInput = mb_strtolower(array_shift($arrAnswersParsed));
			}
			if(isset($arrAnswers[$strInput]))
			{
				$mixReturnValue = $arrAnswers[$strInput];
				break;
			}
		}
		while(true);

		return $mixReturnValue;
	}

	/**
	 * Deletes a single file or a directory from the filesystem
	 * 
	 * @author Simon Schröer
	 * @param String $strPath			Path of the file or directory (absolute path recommended)
	 * @param Boolean $blnRecursive		Delete recursive (-R, only usefull at directories)
	 * @static
	 */
	public static function fs_delete($strPath, $blnRecursive = true)
	{
		if($strPath == '/' || $strPath == '')
		{
			throw new Exception('CLI::fs_delete(): insecure usage');
		}
		self::exec("rm ".($blnRecursive ? "-R " : "").$strPath, false);
	}

	/**
	 * Downloads an URL
	 *
	 * @author Simon Schröer
	 * @param String $strUrl			URL to download
	 * @param String $strTargetName		Targetname for download
	 * @static
	 */
	public static function wget($strUrl, $strTargetName) 
	{
		self::exec("wget --quiet --output-document=".str_replace(array('', '$'), array('\\ ', '\\$'), $strTargetName)." ".$strUrl, false);
	}

	/**
	 * Clears the terminal (only on UNIX, Linux and Mac)
	 *
	 * @author Simon Schröer
	 * @static
	 */
	public static function clear()
	{
		self::exec('clear', false);
	}

	/**
	 * Prints and reads multiple, structured shell inputs
	 * 
	 * @author Simon Schröer
	 * @param Array $arrFormConfig	Formular structure (see documentation for structure)
	 * @return Array				User-inputs
	 * @static
	 */
	public static function readForm($arrFormConfig)
	{
		$arrValues = array();
		foreach($arrFormConfig as $strField => $strOutput)
		{
			do
			{
				/*
				 * Headline?
				 */
				if(is_numeric($strField))
				{
					/*
					 * Print headline and 'underline' it
					 */
					CLI::write("\n".$strOutput."\n".str_repeat('=', mb_strlen($strOutput)));
				}

				/*
				 * Field
				 */
				else
				{
					/*
					 * Special output types defined?
					 */
					if(is_array($strOutput))
					{
						switch($strOutput[0])
						{
							/*
							 * Default y/n confirmation
							 */
							case 'checkbox':
								$arrValues[$strField] = self::confirm($strOutput[1]);
							break;

							/*
							 * Password / hidden field
							 */
							case 'password':
								$arrValues[$strField] = CLI::readHidden($strOutput[1].': ');
							break;

							/*
							 * Comma seperated value list
							 */
							case 'list':
								do
								{
									$strInput = CLI::read($strOutput[1].': ');
									if($strInput === '')
									{
										/*
										 * No values for this field
										 */
										$arrValues[$strField] = false;
									}
									else
									{
										/*
										 * Convert to array by splitting by comma
										 */
										$arrTemp = explode(',', $strInput);
										$arrValues[$strField] = array();
										foreach($arrTemp as $mixValue)
										{
											$mixValue = trim($mixValue);
											$mixValue==='' || $arrValues[$strField][] = $mixValue;
										}
									}

									if(is_numeric($strOutput[2]))
									{
										$strOutput[2] = array($strOutput[2], $strOutput[2]);
									}

									if($strOutput[2] === true)
									{
										if($strInput !== '')
										{
											break;
										}
									}
									else if($strOutput[2] === false)
									{
										break;
									}
									else if(is_array($strOutput[2]))
									{
										$intCountValues = count($arrValues[$strField]);
										list($intMin, $intMax) = $strOutput[2];

										if($intMax === true)
										{
											$intMax = $intMin;
										}

										if($intMin === $intMax)
										{
											if(is_bool($intMin) || $intMin == $intCountValues)
											{
												break;
											}
										}
										else if(is_numeric($intMin) && is_numeric($intMax))
										{
											if($intCountValues >= $intMin && $intCountValues <= $intMax)
											{
												break;
											}
										}
										else if(is_numeric($intMin) && $intMax === false)
										{
											if($intCountValues >= $intMin)
											{
												break;
											}
										}
										else if($intMin === false && is_numeric($intMax))
										{
											if($intCountValues <= $intMax)
											{
												break;
											}
										}
									}								
								}
								while(true);
							break;

							/*
							 * Check the input against the given regular expression
							 */
							case 'regex':
								do
								{
									$arrValues[$strField] = CLI::read($strOutput[1].': ');
								}
								while(!preg_match($strOutput[2], $arrValues[$strField]));
							break;
						}
					}
					else
					{
						/*
						 * Default
						 */
						$arrValues[$strField] = CLI::read($strOutput.': ');
					}
				}
			}
			while($arrValues[$strField] === ''); // as long as we have no valid input
		}

		/*
		 * Return the collected input data
		 */
		return $arrValues;
	}
}

?>