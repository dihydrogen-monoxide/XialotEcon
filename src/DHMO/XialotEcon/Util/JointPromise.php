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

use InvalidStateException;

class JointPromise{
	private $callables = [];
	private $results = [];
	private $remaining = 0;
	private $thenCalled = false;

	public static function create() : JointPromise{
		return new JointPromise();
	}

	public static function build(array $tasks, callable $then) : void{
		$promise = new JointPromise();
		foreach($tasks as $key => $value){
			$promise->do($key, $value);
		}
		$promise->then($then);
	}

	public function do(string $key, callable $callable) : JointPromise{
		if($this->thenCalled){
			throw new InvalidStateException("then() has already been called");
		}
		$this->callables[$key] = $callable;
		++$this->remaining;
		return $this;
	}

	public function then(callable $then) : void{
		if($this->thenCalled){
			throw new InvalidStateException("then() has already been called");
		}
		$this->thenCalled = true;
		foreach($this->callables as $key => $c){
			$c(function($result) use ($then, $key){
				$this->results[$key] = $result;
				--$this->remaining;
				if($this->remaining === 0){
					$then($this->results);
				}
			});
		}
	}
}
