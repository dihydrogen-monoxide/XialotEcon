<?php

/**
 * XialotEcon
 *
 * Copyright (C) 2017-2018 dihydrogen-monoxide
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

declare(strict_types=1);

namespace DHMO\XialotEcon\Exceptions;

class ExtensionMissingException extends \Exception{
	/** @var string */
	private $extensionName;

	public static function test(string $extensionName){
		if(!\extension_loaded($extensionName)){
			throw new self($extensionName);
		}
	}

	public function __construct(string $extensionName){
		parent::__construct("The $extensionName extension is missing");
		$this->extensionName = $extensionName;
	}

	/**
	 * @return string
	 */
	public function getExtensionName() : string{
		return $this->extensionName;
	}

	public function getInstallInstructions(bool $windows) : string{
		if($windows){
			$ini = php_ini_loaded_file();
			if(is_file($ini)){
				$file = fopen($ini, "rb");
				while(($line = fgets($file)) !== false){
					if($line{0} === ";" && strpos($line, "extension=") !== false && strpos($line, $this->extensionName) !== false){
						$commentLine = $line;
						break;
					}
				}
				fclose($file);
				if(isset($commentLine)){
					return "Please edit the PHP configuration file at $ini and remove the \";\" before the line \"$commentLine\", then restart the server to enable {$this->extensionName}.";
				}
			}

			return "Please use the PHP binaries from https://bit.ly/PmmpWin, which contain {$this->extensionName} (you need to edit php.ini to enable it).";
		}

		return "Please install PocketMine again using instructions from http://pmmp.readthedocs.io/en/rtfd/installation.html -- The installer there installs the {$this->extensionName} extension by default.";
	}
}
