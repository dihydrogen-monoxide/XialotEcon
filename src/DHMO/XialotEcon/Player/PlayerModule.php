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

use DHMO\XialotEcon\Database\Queries;
use DHMO\XialotEcon\Util\JointPromise;
use DHMO\XialotEcon\XialotEcon;
use DHMO\XialotEcon\XialotEconModule;
use pocketmine\event\Listener;

final class PlayerModule extends XialotEconModule implements Listener{
	public const OWNER_TYPE_PLAYER = "xialotecon.player.player";
	public const ACCOUNT_TYPE_CASH = "xialotecon.player.cash";
	public const ACCOUNT_TYPE_BANK = "xialotecon.player.bank";

	/** @var PlayerModule */
	private static $instance;

	private $config;

	protected static function getName() : string{
		return "player";
	}

	protected static function shouldConstruct(XialotEcon $plugin) : bool{
		return true;
	}

	protected function __construct(XialotEcon $plugin, callable $onComplete){
		self::$instance = $this;
		$this->plugin = $plugin;
		JointPromise::create()
			->do("login.init", function($complete){
				$this->plugin->getConnector()->executeGeneric(Queries::XIALOTECON_PLAYER_LOGIN_INIT, [], $complete);
			})
			// TODO create other tables
			->then($onComplete);
	}

	public function onStartup() : void{
		$this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
		$this->plugin->getServer()->getCommandMap()->registerAll("xialotecon", [
			new PayOnlinePlayerCommand(),
		]);
		$this->config = $this->plugin->getConfig()->get("player");
	}

	public function getConfig() : array{
		return $this->config;
	}

	public function getPlugin() : XialotEcon{
		return $this->plugin;
	}

	public static function getInstance() : PlayerModule{
		return self::$instance;
	}
}
