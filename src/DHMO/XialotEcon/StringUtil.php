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

namespace DHMO\XialotEcon;

use function strlen;
use function strpos;
use function strtolower;
use const INF;

class StringUtil{
	public const TIME_UNITS = [
		"ms" => 0.001,
		"millisecond" => 0.001,
		"milliseconds" => 0.001,
		"s" => 1,
		"sec" => 1,
		"second" => 1,
		"seconds" => 1,
		"m" => 60,
		"min" => 60,
		"minute" => 60,
		"minutes" => 60,
		"h" => 3600,
		"hr" => 3600,
		"hrs" => 3600,
		"hour" => 3600,
		"hours" => 3600,
		"d" => 86400,
		"day" => 86400,
		"days" => 86400,
		"w" => 604800,
		"wk" => 604800,
		"wks" => 604800,
		"week" => 604800,
		"weeks" => 604800,
		"fn" => 1209600,
		"ftn" => 1209600,
		"fortnight" => 1209600,
		"month" => 2592000,
		"months" => 2592000,
		"season" => 7889184,
		"y" => 31556736,
		"yr" => 31556736,
		"yrs" => 31556736,
		"year" => 31556736,
		"years" => 31556736,
		"dec" => 315567360,
		"decade" => 315567360,
		"decades" => 315567360,
		"cent" => 3155673600,
		"century" => 3155673600,
		"centuries" => 3155673600,
		"millennium" => 31556736000,
		"millenniums" => 31556736000,
	];

	public static function parseTime(string $time, float $defaultUnit = 1.0) : float{
		$time = strtolower($time);
		if($time === "never"){
			return INF;
		}
		if($time === "immediate"){
			return 0.0;
		}
		$totalLength = 0.0;
		$currentNumber = "";
		/** @noinspection ForeachInvariantsInspection */
		for($i = 0, $iMax = strlen($time); $i < $iMax; ++$i){
			if($time{$i} === "." || ('0' <= $time{$i} && $time{$i} <= '9')){
				$currentNumber .= $time{$i};
				continue;
			}

			foreach(self::TIME_UNITS as $unitName => $unit){
				if(strpos($time, $unitName) === 0){
					$totalLength += ((float) $currentNumber) * $unit;
					$currentNumber = "";
					$i += strlen($unitName) - 1;
					continue 2; // next word
				}
			}
		}
		if($currentNumber !== ""){
			$totalLength += ((float) $currentNumber) * $defaultUnit;
		}
		return $totalLength;
	}
}
