<?php  
require_once('vendor/autoload.php');  
use PhpParser\Error;  
//use PhpParser\NodeDumper;
use PhpParser\Node;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeFinder;
use PhpParser\NodeVisitorAbstract;
//use PhpParser\Node\Stmt\Function_;
use PhpParser\ParserFactory;

$parser = (new ParserFactory())->createForNewestSupportedVersion(); 

$stmtcode = <<<'STATEMENTS'
<?php 
use Codeforweb\SegmentNotes\Note;
$scriptNote = Note::initNote(__METHOD__);
Note::endNote($scriptNote, 'none');
Note::endNote($scriptNote, 'ret1');
Note::endNote($scriptNote, 'ret2');
Note::endNote($scriptNote, 'ret3');
Note::endNote($scriptNote, 'ret4');
Note::endNote($scriptNote, 'ret5');
Note::endNote($scriptNote, 'ret6');
Note::endNote($scriptNote, 'ret7');
Note::endNote($scriptNote, 'ret8');
Note::endNote($scriptNote, 'ret9');
Note::endNote($scriptNote, 'ret10');
Note::endNote($scriptNote, 'ret11');
Note::endNote($scriptNote, 'ret12');
Note::endNote($scriptNote, 'ret13');
Note::endNote($scriptNote, 'ret14');
Note::endNote($scriptNote, 'ret15');
Note::endNote($scriptNote, 'ret16');
Note::endNote($scriptNote, 'ret17');
Note::endNote($scriptNote, 'ret18');
Note::endNote($scriptNote, 'ret19');
Note::endNote($scriptNote, 'ret20+');
STATEMENTS;

// TODO: There must certainly exist a better way to create these statements. 
try {
	$stmtast = $parser->parse($stmtcode);
} catch (Error $error) {
	echo "Parse error: {$error->getMessage()}\n";
   	return;
}

//$dumper = new NodeDumper;
//echo get_class($stmtast[0]) . "\n";

class VisitorUse extends NodeVisitorAbstract {
    
	private $stmtast; 
        public  $foundUseStatement=false; 
	
	public function __construct($stmtast) {
		$this->stmtast = $stmtast; 
	}

        public function enterNode(Node $node) {
                if ($this->foundUseStatement) {
                    return VisitorUse::STOP_TRAVERSAL;
                }
        }

        public function leaveNode(Node $node) {
		$stmtUse        = $this->stmtast[0];
		if ($node instanceof Use_ && $node->type == 1) {
                        $this->foundUseStatement = true; 
			return [$stmtUse,$node];
		}
	}
}

class Visitor extends NodeVisitorAbstract {
	
	private $stmtast;
        private int $inClassMethod = 0;
        public bool $foundClassMethod = false;
        private int $returnId = 0; 
	
	public function __construct($stmtast) {
		$this->stmtast = $stmtast; 
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
		$stmtInit	= $this->stmtast[1];
		$stmtEnd	= $this->stmtast[2];
		$stmtRetArr     = array_slice($this->stmtast,3);
		if ($node instanceof ClassMethod && is_array($node->stmts)) {
                        $this->inClassMethod--; 
			array_unshift($node->stmts , $stmtInit);
                        if ( ! end($node->stmts) instanceof PhpParser\Node\Stmt\Return_ ) {
			    $node->stmts[] = $stmtEnd; 
                        }
		}
		if ( get_class($node) == "PhpParser\Node\Stmt\Return_" && $this->inClassMethod ) {
                        $retId = min($this->returnId, 19);  
                        $this->returnId++;
			return [$stmtRetArr[$retId],$node];
		}
	}
}
