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

namespace DHMO\XialotEcon\Player;

use DHMO\XialotEcon\Account\Account;
use DHMO\XialotEcon\XialotEconEvent;
use pocketmine\event\Cancellable;
use pocketmine\Player;

class PlayerAccountDefinitionEvent extends XialotEconEvent implements Cancellable{
	/** @var array */
	private $config;
	/** @var Player */
	private $player;
	/** @var string|null */
	private $type = null;
	/** @var callable|null */
	private $postCreation = null;

	public function __construct(Player $player, array $config){
		parent::__construct();
		$this->player = $player;
		$this->config = $config;
	}

	public function getPlayer() : Player{
		return $this->player;
	}

	public function getConfig() : array{
		return $this->config;
	}

	public function getTypeDef() : string{
		return $this->config["type"] ?? "cash";
	}

	public function getType() : ?string{
		return $this->type;
	}

	public function setType(string $type) : void{
		$this->type = $type;
		$this->setCancelled();
	}

	public function setPostCreation(callable $postCreation) : void{
		$this->postCreation = $postCreation;
	}

	public function onPostCreation(Account $account){
		if($this->postCreation !== null){
			($this->postCreation)($account);
		}
	}
}
