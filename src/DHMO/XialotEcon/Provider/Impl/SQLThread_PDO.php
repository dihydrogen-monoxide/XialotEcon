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

namespace DHMO\XialotEcon\Provider\Impl;

use DHMO\XialotEcon\Provider\Impl\MySQL\mysqli_result;
use DHMO\XialotEcon\Provider\SQLThread;
use PDO;
use RuntimeException;
use Threaded;
use function is_array;

class SQLThread_PDO implements SQLThread{
	private $credentials;

	public $created = false;
	public $connError = null;

	public $running = true;

	/** @var Threaded */
	private $queries;
	/** @var Threaded */
	private $results;

	public function __construct($credentials){
		$this->credentials = serialize($credentials);
		$this->queries = new Threaded;
		$this->results = new Threaded;
	}

	public function run() : void{
		$cred = unserialize($this->credentials, false);

		$port = $cred["port"] ?? 3306;
		try{
			$conn = new PDO("mysql:dbname={$cred["schema"]};host={$cred["host"]};port=$port;charset=utf8", $cred["username"], $cred["password"], [
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_TIMEOUT => 5,
			]);
		}catch(RuntimeException $e){
			$this->connError = (string) $e;
			return;
		}finally{
			$this->created = true;
		}

		while($this->running){
			while(is_array($querySet = $this->queries->shift())){
				[$queryId, $query] = $querySet;
				$stmt = $conn->query($query);
				// TODO handle $stmt

				if($result instanceof mysqli_result){
					$rows = [];
					while(is_array($row = $result->fetch_assoc())){
						$rows[] = $row;
					}
					$result->close();
					$result = $rows;
				}

				$this->results[] = [$queryId, $result, $mysqli->affected_rows, $mysqli->insert_id];
			}

			$this->synchronized(function(SQLThread_mysqli $thread){
				$thread->wait(200000);
			}, $this);
		}

		$mysqli->close();
	}

	public function stopRunning() : void{
		$this->running = false;
	}

	public function addQuery(int $queryId, string $query) : void{
		$this->queries[] = [$queryId, $query];
	}

	public function readResults(array &$callbacks) : void{
		while(is_array($set = $this->results->shift())){
			[$queryId, $result, $affectedRows, $insertId] = $set;
			if(isset($callbacks[$queryId])){
				$callbacks[$queryId]($result, $affectedRows, $insertId);
				unset($callbacks[$queryId]);
			}else{
				throw new RuntimeException("Missing callback for query #$queryId");
			}
		}
	}

	public function connCreated() : bool{
		return $this->created;
	}

	public function hasConnError() : bool{
		return $this->connError !== null;
	}

	public function getConnError() : ?string{
		return $this->connError;
	}
}
