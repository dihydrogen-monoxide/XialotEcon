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

namespace DHMO\XialotEcon\Provider;

use InvalidArgumentException;
use pocketmine\utils\UUID;
use UnexpectedValueException;
use const SORT_NUMERIC;
use function array_slice;
use function assert;
use function explode;
use function fclose;
use function feof;
use function fgets;
use function is_float;
use function is_int;
use function mb_strlen;
use function mb_strpos;
use function mb_substr;
use function sprintf;
use function str_split;
use function strlen;
use function strpos;
use function substr;
use function substr_replace;
use function trim;
use function uksort;

class GenericPreparedStatement{
	private const COMMAND_PREFIX = "-- --";
	private const COMMAND_SUFFIX = "#";

	private $name;
	private $variables = [];
	private $query;

	private $positions = [];

	public function __construct(string $name){
		$this->name = $name;
	}

	public function getName() : string{
		return $this->name;
	}

	/**
	 * @return string[][]|mixed[][]
	 */
	public function getVariables() : array{
		return $this->variables;
	}

	public function getQuery() : string{
		return $this->query;
	}

	public function compilePositions() : void{
		uksort($this->variables, function(string $name1, string $name2) : int{
			return strlen($name1) <=> strlen($name2); // shortest name first
		});
		foreach($this->variables as $name => $t){
			for($offset = 0; ($pos = strpos($this->query, $name, $offset)) !== false; $offset = $pos + 1){
				$before = $this->query{$pos - 1};
				if($before === "(" || $before === " " || $before === "\n"){
					$this->positions[$pos] = $name;
				}
			}
		}
		ksort($this->positions, SORT_NUMERIC);
	}


	public function format(array $vars) : string{
		$conversions = [];
		foreach($this->variables as $name => $typeInfo){
			if(!isset($vars[$name])){
				throw new InvalidArgumentException("Missing parameter \"$name\"");
			}
			$conversions[$name] = self::convertParam($vars[$name], $typeInfo);
			unset($vars[$name]);
		}
		$shift = 0;
		$query = $this->query;
		foreach($this->positions as $position => $name){
			$value = $conversions[$name];
			$shift += strlen($value) - strlen($name);
			$query = substr_replace($query, $value, $position + $shift, strlen($name));
		}
		return $query;
	}

	private static function convertParam($value, array $typeInfo) : string{
		switch($typeInfo[0]){
			case "string":
				foreach(str_split("") as $char){
					$value = self::escapeMultiByte($value, $char);
				}
				return "'{$value}'";
			case "int":
			case "integer":
				assert(is_int($value));
				return (string) $value;
			case "float":
				assert(is_float($value));
				return (string) (float) $value;
			case "dec":
			case "decimal":
				return sprintf("%0.{$typeInfo[1]}f", (float) $value);
			case "bool":
			case "boolean":
				return $value ? "1" : "0";
			case "timestamp":
				return "FROM_UNIXTIME(" . sprintf("%0.{$typeInfo[1]}f", (float) $value) . ")";
			case "uuid":
				assert($value instanceof UUID);
				return "'{$value->toString()}'"; // no need to escape
		}
		throw new UnexpectedValueException("Unknown type $typeInfo[0]");
	}

	private static function escapeMultiByte(string $string, string $char, string $encoding = "UTF-8", string $slash = "\\") : string{
		$out = "";
		for($offset = 0;
		    ($pos = mb_strpos($string, $char, $offset, $encoding)) !== false;
		    $offset = $pos + 1){
			$out .= mb_substr($string, $offset, $pos - $offset) . $slash . $char;
		}
		if(mb_strlen($string) !== $offset){
			$out .= mb_substr($string, $offset, null, $encoding);
		}
		return $out;
	}


	public static function fromFile($fh) : array{
		$result = [];

		$currentStatement = null;
		$lineNo = 0;
		while(!feof($fh)){
			++$lineNo;
			$line = trim(fgets($fh));
			if($line === ""){
				continue;
			}
			if(strpos($line, self::COMMAND_PREFIX) === 0 && substr($line, -strlen(self::COMMAND_SUFFIX)) === self::COMMAND_SUFFIX){
				$command = explode(" ", trim(substr($line, strlen(self::COMMAND_PREFIX), -strlen(self::COMMAND_SUFFIX))));
				switch($command[0]){
					case "{":
					case "DefQuery":
						if($currentStatement !== null){
							throw new UnexpectedValueException("Unterminated query on line $lineNo. Is an EndQuery command missing?");
						}
						$currentStatement = new GenericPreparedStatement($command[1]);
						break;
					case "}":
					case "EndQuery":
						if($currentStatement === null){
							throw new UnexpectedValueException("Unmatched EndQuery command on line $lineNo. Is a DefQuery command missing?");
						}
						$currentStatement->compilePositions();
						$result[$currentStatement->name] = $currentStatement;
						$currentStatement = null;
						break;
					case "var":
					case "UsesVar":
						if($currentStatement === null){
							throw new UnexpectedValueException("Unexpected UsesVar command on line $lineNo. Is a DefQuery command missing?");
						}
						if($currentStatement{0} !== ":"){
							throw new UnexpectedValueException("Variable names should start with a colon");
						}
						$currentStatement->variables[$command[1]] = array_slice($command, 2);
						break;
					default:
						throw new UnexpectedValueException("Unknown command {$command[0]}");
				}
			}else{
				if($currentStatement === null){
					throw new UnexpectedValueException("Unexpected text on line $lineNo. Is a DefQuery command missing?");
				}
				$currentStatement->query .= "\n" . $line;
			}
		}
		fclose($fh);
		if($currentStatement !== null){
			throw new UnexpectedValueException("Unexpected end of file. Is an EndQuery command missing?");
		}

		return $result;
	}
}
