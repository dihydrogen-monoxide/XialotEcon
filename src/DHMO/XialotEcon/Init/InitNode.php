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

namespace DHMO\XialotEcon\Init;

use DHMO\XialotEcon\XialotEcon;
use LogicException;
use pocketmine\Server;
use poggit\libasynql\libasynql;
use function count;
use function microtime;
use function var_dump;

class InitNode{
	/** @var string */
	public $name;
	/** @var callable */
	public $executor;
	/** @var string[] */
	public $parentNames;

	/** @var InitGraph */
	public $graph;

	/** @var InitNode[] */
	public $parents = [];
	/** @var int */
	public $remaining;
	/** @var InitNode[] */
	public $children = [];

	/** @var float */
	public $startTime, $endTime;
	/** @var int */
	public $startTick, $endTick;

	public $chartPosition;

	public function __construct(string $name, callable $executor, array $parents){
		$this->name = $name;
		$this->executor = $executor;
		$this->parentNames = $parents;
	}

	public function hasDescendant(InitNode $node) : bool{
		foreach($this->children as $child){
			if($child === $node || $child->hasDescendant($node)){
				return true;
			}
		}
		return false;
	}

	public function getDepth() : int{
		$depth = 0;
		foreach($this->parents as $node){
			if($node->getDepth() >= $depth){
				$depth = $node->getDepth() + 1;
			}
		}
		return $depth;
	}

	protected function debugExecute() : void{
		$this->startTime = microtime(true);
		$this->startTick = Server::getInstance()->getTick();
		XialotEcon::getInstance()->getLogger()->info("Starting $this->name");
	}

	public function execute() : void{
		if(!libasynql::isPackaged()){
			$this->debugExecute();
		}
		($this->executor)([$this, "onComplete"]);
	}

	public function onComplete() : void{
		if(!libasynql::isPackaged()){
			XialotEcon::getInstance()->getLogger()->info("Ending $this->name");
			$this->endTime = microtime(true);
			$this->endTick = Server::getInstance()->getTick();
		}
		--$this->graph->remaining;
		if($this->graph->remaining <= 0){
			if(count($this->children) > 0){
				throw new LogicException("Graph has completed even though certain leaves' parent ({$this->name}) have just completed");
			}
			($this->graph->onComplete)();
		}
		foreach($this->children as $child){
			--$child->remaining;
			if($child->remaining === 0){
				$child->execute();
			}
		}
	}

	public static function establishEdge(InitNode $parent, InitNode $child) : void{
		if($child->hasDescendant($parent)){
			throw new LogicException("Circular dependency between $parent->name and $child->name");
		}
		$parent->children[] = $child;
		$child->parents[] = $parent;
	}
}
