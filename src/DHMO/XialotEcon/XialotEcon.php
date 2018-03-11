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

use DHMO\XialotEcon\Provider\DataProvider;
use DHMO\XialotEcon\Provider\GenericPreparedStatement;
use DHMO\XialotEcon\Provider\Impl\MySQL\MySQLDataProvider;
use DHMO\XialotEcon\Provider\Impl\SQLiteDataProvider;
use DHMO\XialotEcon\Provider\ProvidedDatum;
use pocketmine\plugin\PluginBase;
use function mkdir;

class XialotEcon extends PluginBase{
	/** @var XialotEcon */
	private static $instance;

	public static function getInstance() : XialotEcon{
		return self::$instance;
	}

	public function onLoad() : void{
		self::$instance = $this;
	}

	/** @var DataProvider */
	private $provider = null;

	public function onEnable() : void{
		//Make the faction config
		@mkdir($this->getDataFolder());
		$this->saveDefaultConfig();

		switch(strtolower($this->getConfig()->getNested("core.provider"))){
			case SQLiteDataProvider::CONFIG_NAME:
				$this->provider = new SQLiteDataProvider($this);
				$this->provider->importStatements(GenericPreparedStatement::fromFile($this->getResource("sqlite3.sql")));
				break;

			case MySQLDataProvider::CONFIG_NAME:
				$this->provider = new MySQLDataProvider($this);
				$this->provider->importStatements(GenericPreparedStatement::fromFile($this->getResource("mysql.sql")));
				break;
		}

		$this->provider->init();

		ProvidedDatum::$EXPIRY_CONFIG = $this->getConfig()->getNested("update-rate");
	}

	public function onDisable() : void{
		if($this->provider !== null){
			$this->provider->cleanup();
		}
	}

	/**
	 * @return DataProvider
	 */
	public function getProvider() : DataProvider{
		return $this->provider;
	}

	public function setProvider(DataProvider $provider){
		if($this->provider !== null){
			$this->provider->cleanup();
		}
		$provider->init();
		$this->provider = $provider;
	}
}
