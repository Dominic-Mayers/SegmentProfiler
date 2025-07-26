<?php
use PhpParser\NodeTraverser;
use PhpParser\PrettyPrinter;


if ( ! isset($argv[1] ) )  {
    echo "No filename provided.".PHP_EOL;
    exit();     
}

if ( ! file_exists($argv[1]) )  {
    echo "File ". $argv[1]. " not found.".PHP_EOL;
    exit(); 
}


$code = file_get_contents($argv[1]);

require_once('initCreateNotes.php');
$visitorUse   = new VisitorUse($stmtast);
$visitorUse->foundUseStatement= false; 
$ast = $parser->parse($code);
$traverserUse = new NodeTraverser();
$traverserUse->addVisitor($visitorUse);
$astUse = $traverserUse->traverse($ast);

if (! $visitorUse->foundUseStatement) {
    exit(); 
}

$traverser = new NodeTraverser();
$visitor   = new Visitor($stmtast);  
$traverser->addVisitor($visitor);
$astFinal = $traverser->traverse($astUse);

if (! $visitor->foundClassMethod) {
    exit(); 
}

$prettyPrinter = new PrettyPrinter\Standard;
$newcode= $prettyPrinter->prettyPrintFile($astFinal);
echo $newcode;
file_put_contents($argv[1].".toprofile", $newcode);
