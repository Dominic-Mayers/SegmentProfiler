<?php  
require_once('vendor/autoload.php');  
use PhpParser\Error;  
//use PhpParser\NodeDumper;  
use PhpParser\ParserFactory;  
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
//use PhpParser\Node\Stmt\Function_;
use PhpParser\PrettyPrinter;

$parser = (new ParserFactory())->createForNewestSupportedVersion();  
if (file_exists($argv[1])) {
	$code = file_get_contents($argv[1]);
} else {
    echo $argv[1]. " not found.".PHP_EOL;
    exit(); 
}

$stmtcode = <<<'STATEMENTS'
<?php
global $stringAction;
$scriptStartId = getId();
$stringAction .= $scriptStartId.":node:startName=".explode('::',__METHOD__)[1].PHP_EOL;
$stringAction .= $scriptStartId.":node:endName=none".PHP_EOL;
$scriptTimeFct[$scriptStartId] = -hrtime(true);
$scriptTimeFct[$scriptStartId] += hrtime(true);
$stringAction .= $scriptStartId . ":group:timeFct={$scriptTimeFct[$scriptStartId]}" . PHP_EOL;
STATEMENTS;

try {
	$stmtast = $parser->parse($stmtcode);
	$ast = $parser->parse($code);
} catch (Error $error) {
	echo "Parse error: {$error->getMessage()}\n";
   	return;
}

//$dumper = new NodeDumper;
//echo $dumper->dump($stmtast) . "\n";

class Visitor extends NodeVisitorAbstract {
	
	private $stmt; 
	
	public function __construct($stmtast) {
		$this->stmtast = $stmtast; 
	}
	
	public function leaveNode(Node $node) {
		$stmtGlobal		= $this->stmtast[0];
		$stmtGetId		= $this->stmtast[1];
		$stmtStartName	= $this->stmtast[2];
		$stmtEndName	= $this->stmtast[3];
		$stmtTimeInit	= $this->stmtast[4];
		$stmtTimeSet	= $this->stmtast[5];
		$stmtTimeFct	= $this->stmtast[6];
		
		if ($node instanceof ClassMethod && is_array($node->stmts)) {
			array_unshift($node->stmts , $stmtTimeInit);  
			array_unshift($node->stmts , $stmtStartName);  
			array_unshift($node->stmts , $stmtGetId);
			array_unshift($node->stmts , $stmtGlobal);
			$node->stmts[] = $stmtTimeSet;
			$node->stmts[] = $stmtTimeFct;
			$node->stmts[] = $stmtEndName;
		}
		if ( get_class($node) == "PhpParser\Node\Stmt\Return_" ) {
			return [$stmtTimeSet, $stmtTimeFct, $stmtEndName,$node ];
			//return [$stmtEndName,$node ];
		}
	}
}

$traverser = new NodeTraverser();
$visitor   = new Visitor($stmtast); 
$traverser->addVisitor($visitor);

$ast = $traverser->traverse($ast);

$prettyPrinter = new PrettyPrinter\Standard;
$newcode= $prettyPrinter->prettyPrintFile($ast);
echo $newcode;
file_put_contents($argv[1].".toprofile", $newcode);
