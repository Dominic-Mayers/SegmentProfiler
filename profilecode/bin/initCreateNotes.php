<?php  
require_once(__DIR__ . '/../../vendor/autoload.php');  
use PhpParser\Error;  
//use PhpParser\NodeDumper;
use PhpParser\Node;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt; 
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeVisitorAbstract;
//use PhpParser\Node\Stmt\Function_;
use PhpParser\ParserFactory;


//$dumper = new NodeDumper;
//echo get_class($stmtast[0]) . "\n";

class VisitorUse extends NodeVisitorAbstract {
    
	private $stmtast; 
        public  $addedUseStatement; 
	
	public function __construct() {
            
                $this->addedUseStatement = false; 
                $parser = (new ParserFactory())->createForNewestSupportedVersion(); 
                $segmentClassDir = realpath(__DIR__ . '/../../profilecode/src/Segment.php'); 
                $stmtcode = <<<STATEMENTS
<?php 
require_once '$segmentClassDir';
use Codeforweb\SegmentNotes\Segment;
STATEMENTS;
                // TODO: There must certainly exist a better way to create these statements. 
                try {
                    $this->stmtast = $parser->parse($stmtcode);
                } catch (Error $error) {
                    echo "Parse error: {$error->getMessage()}\n";
                    return;
                }
	}

        public function enterNode(Node $node) {
                if ($this->addedUseStatement) {
                    return VisitorUse::STOP_TRAVERSAL;
                }
        }

        public function leaveNode(Node $node) {
                $stmtRequire    = $this->stmtast[0];
		$stmtUse        = $this->stmtast[1];
		if ($node instanceof Stmt && 
                    ! $node instanceof Declare_ &&
                    ! $node instanceof Namespace_) {
                        $this->addedUseStatement = true; 
			return [$stmtRequire, $stmtUse, $node];
		}
	}
}

class Visitor extends NodeVisitorAbstract {
	

        private int $inClassMethod = 0;
        public bool $foundClassMethod = false;
        private int $returnId = 0; 
	private $parser;
        
	public function __construct() {
                $this->parser = (new ParserFactory())->createForNewestSupportedVersion(); 
                $stmtcode = <<<'STATEMENTS'
<?php 
$scriptNote = Segment::startSegment(__METHOD__);
Segment::endSegment($scriptNote, 'none');
STATEMENTS;
                // TODO: There must certainly exist a better way to create these statements. 
                try {
                    $this->stmtast = $this->parser->parse($stmtcode);
                } catch (Error $error) {
                    echo "Parse error: {$error->getMessage()}\n";
                    return;
                }
	}

	public function enterNode(Node $node) {
                if ( $node instanceof ClassMethod && is_array($node->stmts)) {
                    $this->inClassMethod++; 
                    $this->returnId = 0; 
                    $this->foundClassMethod = true; 
                }
                elseif ($node instanceof FunctionLike ) { 
                    return Visitor::DONT_TRAVERSE_CHILDREN ;             
                }
        }

	public function leaveNode(Node $node) {
		$stmtInit	= $this->stmtast[0];
		$stmtEnd	= $this->stmtast[1];
		if ($node instanceof ClassMethod && is_array($node->stmts)) {
                        $this->inClassMethod--; 
			array_unshift($node->stmts , $stmtInit);
                        if ( ! end($node->stmts) instanceof PhpParser\Node\Stmt\Return_ ) {
			    $node->stmts[] = $stmtEnd; 
                        }
		} elseif ( get_class($node) == "PhpParser\Node\Stmt\Return_" && 
                           $this->inClassMethod ) {
			return [$this->getReturnEndSegment(++$this->returnId), $node];
		}
	}
        
        private function getReturnEndSegment($n) {
                $ret = "\"ret$n\""; 
                $stmtcode = <<<STATEMENTS
<?php 
Segment::endSegment(\$scriptNote, $ret);
STATEMENTS;
                try {
                    $stmtast = $this->parser->parse($stmtcode);
                } catch (Error $error) {
                    echo "Parse error: {$error->getMessage()}\n";
                    return;
                } 
                return $stmtast[0];                 
        }
}
