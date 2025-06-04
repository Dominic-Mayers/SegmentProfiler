<?php  
require_once('vendor/autoload.php');  
use PhpParser\Error;  
use PhpParser\NodeDumper;  
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
$stringAction .= $sriptStartId.":node:startName=".explode('::',__METHOD__)[1].PHP_EOL;
$stringAction .= $scriptStartId.":node:endName=none".PHP_EOL;
STATEMENTS;

try {
	$stmtast = $parser->parse($stmtcode);
	$ast = $parser->parse($code);
} catch (Error $error) {
	echo "Parse error: {$error->getMessage()}\n";
   	return;
}

$globStmt = $stmtast[0];
$stmt0    = $stmtast[1];
$stmt1    = $stmtast[2];
$stmt2    = $stmtast[3];

//$dumper = new NodeDumper;
//echo $dumper->dump($stmtast) . "\n";


$traverser = new NodeTraverser();
$traverser->addVisitor(new class extends NodeVisitorAbstract {
	public function leaveNode(Node $node) {
		global $globStmt, $stmt0, $stmt1, $stmt2;
		if ($node instanceof ClassMethod && is_array($node->stmts)) {
			array_unshift($node->stmts , $stmt1);  
			array_unshift($node->stmts , $stmt0);
			array_unshift($node->stmts , $globStmt);
			$node->stmts[] = $stmt2; 
		}
		if ( get_class($node) == "PhpParser\Node\Stmt\Return_" ) {
			return [$stmt2,$node ];
		}
	}
});

$ast = $traverser->traverse($ast);

$prettyPrinter = new PrettyPrinter\Standard;
$newcode= $prettyPrinter->prettyPrintFile($ast);
echo $newcode;
file_put_contents($argv[1].".toprofile", $newcode);
