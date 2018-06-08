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
use RuntimeException;
use function array_map;
use function count;
use function crc32;
use function imagecolorallocate;
use function imagecreatetruecolor;
use function imagefill;
use function imagefilledrectangle;
use function imageline;
use function imagepng;
use function imagettftext;
use function implode;
use function round;
use function uasort;
use const INF;

class InitGraph{
	/** @var InitNode[] */
	private $roots = [];

	/** @var InitNode[] */
	private $namedMap = [];
	/** @var InitNode[] */
	private $childrenMap = [];

	/** @var int */
	public $remaining;
	/** @var callable */
	public $onComplete;

	/**
	 * @param string   $name
	 * @param callable $executor
	 * @param string[] $parents
	 */
	public function add(string $name, callable $executor, array $parents = []) : void{
		$this->addNode(new InitNode($name, $executor, $parents));
	}

	/**
	 * @param string $name
	 * @param string $queryName
	 * @param array  $args
	 * @param string ...$parents
	 */
	public function addGenericQuery(string $name, string $queryName, array $args = [], string ...$parents) : void{
		$this->addNode(new MysqlGenericInitNode($name, $queryName, $args, $parents));
	}

	public function addNode(InitNode $node) : void{
		$this->namedMap[$node->name] = $node;
		$node->graph = $this;

		// add parents
		if(empty($node->parentNames)){
			$this->roots[] = $node;
		}else{
			foreach($node->parentNames as $parent){
				if(isset($this->namedMap[$parent])){
					// parent before, child now
					InitNode::establishEdge($this->namedMap[$parent], $node);
					$this->namedMap[$parent]->children[] = $node;
				}else{
					// parent future, child now
					$this->childrenMap[$parent][] = $node;
				}
			}
		}

		// add children
		if(isset($this->childrenMap[$node->name])){
			foreach($this->childrenMap[$node->name] as $child){
				// parent now, child before
				InitNode::establishEdge($node, $child);
			}
			unset($this->childrenMap[$node->name]);

			// parent now, child after: the children will check for "parent before, child now" when it is added
		}
	}

	public function validateDeps() : void{
		if(!empty($this->childrenMap)){
			throw new LogicException("Not all nodes are rooted: " . implode(", ", array_map(function(array $nodes) : string{
					return implode(", ", array_map(function(InitNode $node) : string{
						return $node->name;
					}, $nodes));
				}, $this->childrenMap)));
		}
	}

	public function execute(callable $onComplete) : void{
		if(!libasynql::isPackaged()){
			$this->validateDeps();
		}
		$this->remaining = count($this->namedMap);
		foreach($this->namedMap as $node){
			$node->remaining = count($node->parents);
		}

		foreach($this->roots as $node){
			$node->execute();
		}
		$this->onComplete = $onComplete;
	}

	public function generateChart() : void{
		if(libasynql::isPackaged()){
			throw new RuntimeException("generateChart only available in debug mode");
		}

		$epoch = INF;
		$completeTime = -INF;

		/** @var InitNode[] $barStack */
		$barStack = [];
		/** @var InitNode[][] $barLists */
		$barLists = [];

		$leftPad = 50;
		$rightPad = 200;
		$topPad = 50;
		$bottomPad = 200;
		$barHeight = 30;
		$secondWidth = 1000;

		uasort($this->namedMap, function(InitNode $node1, InitNode $node2) : int{
			return $node1->startTime <=> $node2->startTime;
		});
		foreach($this->namedMap as $node){
			if($node->startTime === null){
				throw new LogicException("Node $node->name did not get executed");
			}
			if($node->endTime > $completeTime){
				$completeTime = $node->endTime;
			}
			if($node->startTime < $epoch){
				$epoch = $node->startTime;
			}

			for($i = 0, $iMax = count($barLists); $i < $iMax; ++$i){
				if(!isset($barStack[$i]) || $barStack[$i]->endTime <= $node->startTime){
					$barLists[$i][] = $barStack[$i] = $node;
					$node->chartPosition = $i;
					continue 2;
				}
			}
			$barLists[$i][] = $barStack[$i] = $node;
			$node->chartPosition = $i;
		}

		$ttf = Server::getInstance()->getDataPath() . "xialotecon-font.ttf";
		$image = imagecreatetruecolor((int) (($completeTime - $epoch) * $secondWidth) + $leftPad + $rightPad, count($barLists) * $barHeight + $topPad + $bottomPad);
		$foreground = imagecolorallocate($image, 0, 0, 0);
		$background = imagecolorallocate($image, 255, 255, 255);
		$darkMode = 128;
		imagefill($image, 0, 0, $background);

		foreach($barLists as $i => $nodes){
			$ceil = $topPad + $i * $barHeight;
			$floor = $ceil + $barHeight;

			foreach($nodes as $node){
				$left = (int) (($node->startTime - $epoch) * $secondWidth) + $leftPad;
				$right = (int) (($node->endTime - $epoch) * $secondWidth) + $leftPad;

				$crc = crc32($node->name);
				$r = ($crc & 127) + $darkMode;
				$g = (($crc >> 8) & 127) + $darkMode;
				$b = (($crc >> 16) & 127) + $darkMode;

				imagefilledrectangle($image, $left, $ceil, $right, $floor, imagecolorallocate($image, $r, $g, $b));
				imagettftext($image, 12, 0, $left, (int) (($ceil + $floor) / 2) + 6, $foreground, $ttf, $node->name);
			}
		}

		imageline($image, $leftPad, $topPad, $leftPad, $topPad + count($barLists) * $barHeight, $foreground);
		imageline($image, $leftPad, $topPad + (count($barLists) * $barHeight), $leftPad + (int) (($completeTime - $epoch) * $secondWidth), $topPad + count($barLists) * $barHeight, $foreground);
		imagettftext($image, 6, 0, $leftPad + (int) (($completeTime - $epoch) * $secondWidth) - 5, $topPad + count($barLists) * $barHeight + 10, $foreground, $ttf, "ms");

		foreach($this->namedMap as $node){
			imagettftext($image, 6, 0, (int) ($leftPad + ($node->startTime - $epoch) * $secondWidth),
				$topPad + count($barLists) * $barHeight + ($node->chartPosition + 1) * 20, $foreground, $ttf,
				round(($node->startTime - $epoch) * 1000, 3) . " (tick {$node->startTick})");

			imagettftext($image, 6, 0, (int) ($leftPad + ($node->endTime - $epoch) * $secondWidth),
				(int) ($topPad + count($barLists) * $barHeight + ($node->chartPosition + 1.5) * 20), $foreground, $ttf,
				round(($node->endTime - $epoch) * 1000, 3) . " (tick {$node->endTick})");
		}

		imagepng($image, $path = Server::getInstance()->getProperty("xialotecon.debug.init-graph-folder", Server::getInstance()->getDataPath()) . "/init-graph-" . XialotEcon::getInstance()->getInitMode() . ".png");
		XialotEcon::getInstance()->getLogger()->info("Generated init report at $path");
	}
}
