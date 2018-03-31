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

if(!isset($argv[4])){
	echo "Usage: php " . __FILE__ . " <permissions.xml> <plugin.yml> <src> <Permissions FQN>\n";
	exit(2);
}

$xml = $argv[1];
if(!is_file($xml)){
	echo "$xml: Not a file\n";
	exit(1);
}
$yml = $argv[2];
if(!is_file($yml)){
	echo "$yml: Not a file\n";
	exit(1);
}
$src = $argv[3];
$fqn = explode("\\", $argv[4]);

$parser = xml_parser_create();
xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
if(!xml_parse_into_struct($parser, file_get_contents($xml), $tokens, $index)){
	printf('XML error at line %d column %d',
		xml_get_current_line_number($this->parser),
		xml_get_current_column_number($this->parser));
	exit(1);
}
xml_parser_free($parser);

$permNames = [];

/** @var PermLink|null $node */
$node = null;
/** @var PermLink|null $root */
$root = null;
foreach($tokens as $token){
	switch($token["type"]){
		case "open":
		case "complete":
			$parentName = $node !== null ? ($node->name . ".") : "";
			$perm = new PermLink($parentName . $token["tag"]);
			$permNames[$perm->name] = $perm;
			foreach($token["attributes"] ?? [] as $k => $v){
				$perm->{$k} = $v;
			}
			if($node !== null){
				$node->children[$perm->name] = $perm;
			}else{
				$root = $perm;
			}
			$perm->parent = $node;
			if($token["type"] === "open"){
				$node = $perm;
			}
			if(!empty($token["value"])){
				$perm->description[] = trim(preg_replace('/(^|\n)[ \t]+/', '$1', $token["value"]));
			}
			break;
		case "close":
			$node = $node->parent;
			break;
		case "cdata":
			if(!empty($token["value"])){
				$perm->description[] = trim(preg_replace('/(^|\n)[ \t]+/', '$1', $token["value"]));
			}
			break;
	}
}

$plugin = yaml_parse_file($yml);
$plugin["permissions"] =  [$root->name => $root->toArray()];
yaml_emit_file($yml, $plugin);

$file = $src . "/" . implode("/", $fqn) . ".php";
@mkdir(dirname($file), 0777, true);
echo $file . "\n";
$fh = fopen($file, "wb");
fwrite($fh, "<?php\n\n");
fwrite($fh, <<<LICENSE_HEADER
/**
 * XialotEcon
 *
 * This file is auto-generated from permissions.xml of this project, which is also covered by the license.
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
LICENSE_HEADER
);
fwrite($fh, "\n\ndeclare(strict_types=1);\n\n");
fwrite($fh, "namespace " . implode("\\", array_slice($fqn, 0, -1)) . ";\n\n");
fwrite($fh, "final class " . array_slice($fqn, -1)[0] . "{");
foreach($permNames as $permName => $perm){
	fwrite($fh, "\n\t/**\n");
	if(!empty($perm->description)){
		fwrite($fh, "\t * " . str_replace("\n", "\n\t * ", implode("\n", $perm->description)) . "\n");
		fwrite($fh, "\t *\n");
	}
	fwrite($fh, "\t * Default: {$perm->default}\n");
	fwrite($fh, "\t*/\n");
	$const = strtoupper(str_replace(".", "_", $permName));
	if(strpos($const, "XIALOTECON_") === 0){
		$const = substr($const, strlen("XIALOTECON_"));
	}
	fwrite($fh, "\tpublic const $const = " . json_encode($permName) . ";\n");
}
fwrite($fh, "}\n");
fclose($fh);

class PermLink{
	/** @var string */
	public $name;
	/** @var string[] */
	public $description = [];
	/** @var string */
	public $default = "op";
	/** @var PermLink[] */
	public $children = [];
	/** @var PermLink|null */
	public $parent = null;

	public function __construct(string $name){
		$this->name = $name;
	}

	public function toArray() : array{
		$ret = [];
		$ret["default"] = $this->default === "true" ? true :
			($this->default === "false" ? false : $this->default); // PocketMine parses boolean-type defaults too, so let's make the syntax more consistent
		if(!empty($this->description)){
			$ret["description"] = implode("\n", $this->description);
		}
		if(!empty($this->children)){
			$ret["children"] = array_map(function(PermLink $link){
				return $link->toArray();
			}, $this->children);
		}
		return $ret;
	}
}
