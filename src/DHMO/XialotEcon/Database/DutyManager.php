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

namespace DHMO\XialotEcon\Database;

use DHMO\XialotEcon\XialotEcon;
use pocketmine\scheduler\PluginTask;
use poggit\libasynql\result\SqlSelectResult;

class DutyManager extends PluginTask{
	private $plugin;
	private $dutyAcquired = false;

	public function __construct(XialotEcon $plugin){
		parent::__construct($plugin);
		$this->plugin = $plugin;
	}

	public function onRun(int $currentTick) : void{
		if(!$this->dutyAcquired){
			$this->plugin->getConnector()->setLoggingQueries(false);
			$this->plugin->getConnector()->executeSelect(Queries::XIALOTECON_DATA_MODEL_DUTY_ACQUIRE, [
				"serverId" => $this->plugin->getServer()->getServerUniqueId()->toString(),
			], function(SqlSelectResult $result){
				$isOnDuty = (bool) $result->getRows()[0]["result"];
				if(!$this->dutyAcquired && $isOnDuty){
					$this->dutyAcquired = true;
					$this->plugin->getLogger()->info("Acquiring master duty.");
				}elseif($this->dutyAcquired && !$isOnDuty){
					$this->dutyAcquired = false;
					$this->plugin->getLogger()->info("Lost master duty.");
				}
			});
			$this->plugin->getConnector()->setLoggingQueries(true);
			return;
		}

		$this->plugin->getConnector()->setLoggingQueries(false);
		$this->plugin->getConnector()->executeChange(Queries::XIALOTECON_DATA_MODEL_DUTY_MAINTAIN, [
			"serverId" => $this->plugin->getServer()->getServerUniqueId()->toString(),
		], function(int $changes){
			if($changes === 0){
				$this->plugin->getLogger()->error("Unexpected loss of duty lock!");
			}
		});
		$this->plugin->getConnector()->setLoggingQueries(true);

		$this->plugin->getServer()->getPluginManager()->callEvent(new ExecuteDutyEvent());
	}
}
