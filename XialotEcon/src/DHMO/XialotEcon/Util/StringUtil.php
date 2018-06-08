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

namespace DHMO\XialotEcon\Util;

use DHMO\XialotEcon\XialotEcon;
use function fclose;
use function json_decode;
use function mb_strlen;
use function mb_strpos;
use function mb_strtolower;
use function mb_substr;
use function stream_get_contents;
use function strlen;
use const INF;

final class StringUtil{
	public static $TIME_UNITS;

	public static function parseTime(string $time, float $defaultUnit = 1.0) : float{
		$time = mb_strtolower($time);
		if($time === "never"){
			return INF;
		}
		if($time === "immediate"){
			return 0.0;
		}
		$totalLength = 0.0;
		$currentNumber = "";
		/** @noinspection ForeachInvariantsInspection */
		for($i = 0, $iMax = mb_strlen($time); $i < $iMax; ++$i){
			$char = mb_substr($time, $i, 1);
			if($char === "." || (strlen($char) === 1 && '0' <= $char && $char <= '9')){
				$currentNumber .= $char;
				continue;
			}

			foreach(self::$TIME_UNITS as $unitName => $unit){
				if(mb_strpos($time, $unitName, $i) === $i){
					$totalLength += ((float) $currentNumber) * $unit;
					$currentNumber = "";
					$i += mb_strlen($unitName) - 1;
					continue 2; // next word
				}
			}
		}
		if($currentNumber !== ""){
			$totalLength += ((float) $currentNumber) * $defaultUnit;
		}
		return $totalLength;
	}

	public static function init(XialotEcon $plugin) : void{
		$resource = $plugin->getResource("time_units.json");
		self::$TIME_UNITS = json_decode(stream_get_contents($resource), true);
		fclose($resource);
	}
}
