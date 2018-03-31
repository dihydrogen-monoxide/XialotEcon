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

use function assert;

abstract class XialotEconModule{
	/** @var XialotEcon */
	protected $plugin;

	public final static function init(XialotEcon $plugin, callable $onComplete) : ?XialotEconModule{
		assert(static::class !== XialotEconModule::class);
		if(!static::shouldConstruct($plugin)){
			return null;
		}
		$plugin->getLogger()->info("Asynchronously initializing " . static::getName() . " module...");
		return new static($plugin, $onComplete);
	}

	protected static abstract function getName() : string;

	protected static abstract function shouldConstruct(XialotEcon $plugin) : bool;

	protected abstract function __construct(XialotEcon $plugin, callable $onComplete);

	public function getPlugin() : XialotEcon{
		return $this->plugin;
	}

	public function onStartup() : void{
	}

	public function onShutdown() : void{
	}
}
