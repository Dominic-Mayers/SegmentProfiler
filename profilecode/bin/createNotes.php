<?php
use PhpParser\NodeTraverser;
use PhpParser\PrettyPrinter;
use PhpParser\ParserFactory;

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
$parser = (new ParserFactory())->createForNewestSupportedVersion();
$visitorUse   = new VisitorUse();
$visitorUse->addedUseStatement= false; // That might not be needed.  
$ast = $parser->parse($code);
$traverserUse = new NodeTraverser();
$traverserUse->addVisitor($visitorUse);
$astUse = $traverserUse->traverse($ast);

if (! $visitorUse->addedUseStatement) {
    echo "No Use statement.";
    exit(); 
}

$traverser = new NodeTraverser();
$visitor   = new Visitor();  
$traverser->addVisitor($visitor);
$astFinal = $traverser->traverse($astUse);

if (! $visitor->foundClassMethod) {
    exit(); 
}

$prettyPrinter = new PrettyPrinter\Standard;
$newcode= $prettyPrinter->prettyPrintFile($astFinal);
echo $newcode;
file_put_contents($argv[1].".toprofile", $newcode);
