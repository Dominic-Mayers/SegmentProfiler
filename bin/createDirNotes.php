<?php
use PhpParser\NodeTraverser;
use PhpParser\PrettyPrinter;

if ( ! isset($argv[1])) {
    echo "No directory name provided.".PHP_EOL;
    exit();     
}

$dir = $argv[1];

if ( ! is_dir($dir)) {
    echo "Directory ". $dir. " not found.".PHP_EOL;
    exit(); 
} 

require_once('initCreateNotes.php');

$iterator = new \DirectoryIterator($dir);

foreach ($iterator as $fileinfo) {
    
    $name = $fileinfo->getFilename();
    $ext = pathinfo($name, PATHINFO_EXTENSION);
    if ( $ext === "orig") {
        $pathphp    = $dir."/".basename($name, '.orig');
        $pathorig   = $dir."/".$name;         
        $pathsource = $pathorig; 
    } elseif ($ext === "php" && !file_exists($dir."/".$name.".orig"))  {
        $pathphp    = $dir."/".$name;
        $pathorig   = $pathphp.".orig"; 
        $pathsource = $pathphp;  
    }
    else {
        continue;
    }
    //echo $filepath. PHP_EOL ;
    //echo $filepathorig. PHP_EOL; 
    $code = file_get_contents($pathsource);
    
    $visitorUse = new VisitorUse($stmtast);
    $visitorUse->foundUseStatement= false; 
    $ast = $parser->parse($code);
    $traverserUse = new NodeTraverser();
    $traverserUse->addVisitor($visitorUse);
    $astUse = $traverserUse->traverse($ast);
    
    if (! $visitorUse->foundUseStatement) {
        echo "No use statement: ".$pathsource. PHP_EOL;  
        continue; 
    }
    
    $traverser = new NodeTraverser();
    $visitor   = new Visitor($stmtast); 
    $traverser->addVisitor($visitor);
    $astFinal = $traverser->traverse($astUse);
    
    if (! $visitor->foundClassMethod) {
        echo "No class method: ".$pathsource. PHP_EOL;  
        continue; 
    }


    $prettyPrinter = new PrettyPrinter\Standard;
    $newcode= $prettyPrinter->prettyPrintFile($astFinal);
    if ( $pathsource === $pathphp) {
        rename ($pathphp, $pathorig); 
    }
    echo "Saving new code into $pathphp". PHP_EOL; 
    file_put_contents($pathphp, $newcode);  
}
