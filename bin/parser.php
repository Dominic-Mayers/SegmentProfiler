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
if (isset($argv[1]) && file_exists($argv[1])) {
    $code = file_get_contents($argv[1]);
} elseif (isset($argv[1])) {
    echo $argv[1]. " not found.".PHP_EOL;
    exit(); 
} else {
    echo "No file provided.".PHP_EOL;
    exit();     
}

$stmtcode = <<<'STATEMENTS'
<?php
$scriptStartId = Note::getId();
Note::$segmentNotes .= $scriptStartId.":node:startName=".explode('::',__METHOD__)[1].PHP_EOL;
Note::$segmentNotes .= $scriptStartId.":node:endName=none".PHP_EOL;
$scriptTimeFct[$scriptStartId] = -hrtime(true);
$scriptTimeFct[$scriptStartId] += hrtime(true);
Note::$segmentNotes .= $scriptStartId . ":group:timeFct={$scriptTimeFct[$scriptStartId]}" . PHP_EOL;
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
		$stmtGetId	= $this->stmtast[0];
		$stmtStartName	= $this->stmtast[1];
		$stmtEndName	= $this->stmtast[2];
		$stmtTimeInit	= $this->stmtast[3];
		$stmtTimeSet	= $this->stmtast[4];
		$stmtTimeFct	= $this->stmtast[5];
		
		if ($node instanceof ClassMethod && is_array($node->stmts)) {
			array_unshift($node->stmts , $stmtTimeInit);  
			array_unshift($node->stmts , $stmtStartName);  
			array_unshift($node->stmts , $stmtGetId);
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
