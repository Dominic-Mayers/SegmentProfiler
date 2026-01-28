<?php
namespace App;
use Graphp\Graph\Graph; 
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\TotalGraph; 
use App\ActiveGraph; 
use App\Traversal; 
use App\VisitorDefaultActiveGraph; 
use App\VisitorT;
use App\VisitorTwe;
use App\VisitorCT;
use App\VisitorCTwe;
use App\VisitorTreeKey;
use App\VisitorTreeWithEmptyKey;

class Profiler {
	
	public function __construct(
                private TotalGraph $totalGraph,
                private ActiveGraph $activeGraph, 
                private Traversal $traversal,
		private UrlGeneratorInterface $urlGenerator,
                private $groupsWithNoInnerNodes = null,
	) {
	}
	
        public function saveGraphInFile ($filename, $active) {
            if (!file_exists($filename)) {
                try {
                    touch($filename); 
                } catch (Exception $ex) {
                    echo "Cannot create $filename"; 
                    exit(); 
                }
            }
            if (!is_writable($filename)) {
                echo "File $filename is not writable.";
                exit();                 
            }
            if ($active) {
                $gr =& $this->activeGraph;
            } else {
                $gr =& $this->totalGraph;                
            }
            $serialGraph = json_encode([$gr->nodes, $gr->arrowsOut]);
            file_put_contents($filename, $serialGraph);
        }
        
        
        public function restoreGraphFromFile ($filename, $active) {
            $serialGraph = file_get_contents($filename); 
            [$nodes, $arrowsOut] = json_decode($serialGraph, true);
            $arrowsIn = [];
            foreach ($arrowsOut as $sourceId => $adjArrowsOut) {                 
                foreach ( $adjArrowsOut as $targetId => $arrow) {
                    $arrowsIn[$targetId][$sourceId] = $arrow;
                }
            }
            if ($active) {
                $gr = & $this->activeGraph; 
            } else {
                $gr = & $this->totalGraph;                
            }
            $gr->nodes     = $nodes;
            $gr->arrowsIn  = $arrowsIn;
            $gr->arrowsOut = $arrowsOut;
        }

        public function groupCT() {
                $visitorCT = new VisitorCT(
                        $this->totalGraph,
                        $this->groupsWithNoInnerNodes); 
		$this->traversal->visitNodes($visitorCT);
	}
        
        public function groupCTwe() {
		// For every non innernode, this only groups its non inner children with a same full name.
		$visitorCTwe = new VisitorCTwe(
                        $this->totalGraph,
                        $this->groupsWithNoInnerNodes);  
		$this->traversal->visitNodes($visitorCTwe);
	}

        public function groupT() {
                $visitorT = new VisitorT(
                        $this->totalGraph,
                        $this->groupsWithNoInnerNodes); 
		$this->traversal->visitNodes( $visitorT);
	}

	public function groupTwe() {
                $visitorTwe = new VisitorTwe(
                        $this->totalGraph,
                        $this->groupsWithNoInnerNodes); 
		$this->traversal->visitNodes($visitorTwe);
	}
       
	public function groupTTD() {
                $visitorTTD = new VisitorTTD(
                        $this->totalGraph,
                        $this->groupsWithNoInnerNodes);
                $this->traversal->visitNodes($visitorTTD);                 
	}        

	public function groupTTweD() {
                $visitorTTweD = new VisitorTTweD(
                        $this->totalGraph,
                        $this->groupsWithNoInnerNodes);
                $this->traversal->visitNodes($visitorTTweD);                 
	}        
        
        public function setTreeKey () {
                $visitorTreeKey = new VisitorTreeKey($this->totalGraph); 
                $this->traversal->visitNodes($visitorTreeKey); 
        }

        public function setTreeKeyWithEmpty () {
                $visitorTreeWithEmptyKey = new VisitorTreeWithEmptyKey($this->totalGraph); 
                $this->traversal->visitNodes($visitorTreeWithEmptyKey); 
        }

        public function optimizedForest () {
                $visitorOptimizedForest = new VisitorOptimizedForest($this->totalGraph); 
                $this->traversal->visitNodes($visitorOptimizedForest); 
        }

        public function optimizedForestWe () {
                $visitorOptimizedForestWe = new VisitorOptimizedForestWe($this->totalGraph); 
                $this->traversal->visitNodes($visitorOptimizedForestWe); 
        }

        public function createDefaultActiveGraph () {
                // To be called once we have the totalGraph, after the groups have been created. 
                $visitorDefaultActiveGraph = new VisitorDefaultActiveGraph(
                        $this->totalGraph, 
                        $this->activeGraph);
                $this->traversal->visitNodes($visitorDefaultActiveGraph);
        }
        
	private function setTree (Profiler $profiler, $input) {
		$this->notesFile = new \SplFileObject(__DIR__. '/Fixtures/'.$input.'.profile');
        	$this->totalGraph->getTree($this->notesFile);
                $profiler->setTreeKeyWithEmpty();
                $profiler->setTreeKey();
                //xdebug_break();
        }

	public function setDefaultGroups (Profiler $profiler, $input) {
                $filenameTotal  = __DIR__ . '/../input/Graphs/'.$input.'.totgraph'; 
                $filenameActive = __DIR__ . '/../input/Graphs/'.$input.'.actgraph'; 
                if (false && file_exists ($filenameTotal) && file_exists ($filenameActive) ) {
                    $profiler->restoreGraphFromFile($filenameTotal,  false);
                    $profiler->restoreGraphFromFile($filenameActive, true);
                    return;
                }
                // Of course, it is pointless to modify below if the graphs are stored in files. 
                $this->setTree($profiler, $input);
                $profiler->createDefaultActiveGraph();
                $profiler->groupCTwe();
                $profiler->createDefaultActiveGraph();
                $profiler->optimizedForestWe();
                $profiler->groupTTweD();
                $profiler->createDefaultActiveGraph();
                //$profiler->groupT();
                //$profiler->groupTwe();
                //$profiler->groupCT();
                

                $profiler->saveGraphInFile($filenameTotal, false); 
                $profiler->saveGraphInFile($filenameActive, true);
        }
}
