<?php
namespace App;
use App\GraphTransformation;
use App\TotalGraph; 
use App\ActiveGraph;
use App\VisitorDefaultActiveGraph; 

class Backend {
	
	public function __construct(
                private GraphTransformation $graphTransformation, 
                private TotalGraph $totalGraph,
                private ActiveGraph $activeGraph, 
	) {
	}
	        
	public function setDefaultGroups ($input) {
                $filenameTotal  = __DIR__ . '/../input/Graphs/'.$input.'.totgraph'; 
                $filenameActive = __DIR__ . '/../input/Graphs/'.$input.'.actgraph'; 
                if (false && file_exists ($filenameTotal) && file_exists ($filenameActive) ) {
                    $this->restoreGraphFromFile($filenameTotal,  false);
                    $this->restoreGraphFromFile($filenameActive, true);
                    return;
                }
                // Of course, it is pointless to modify below if the graphs are stored in files. 
                $this->setTree($input);
                $this->graphTransformation->createDefaultActiveGraph();
                $this->graphTransformation->groupCTwe();
                $this->graphTransformation->createDefaultActiveGraph();
                $this->graphTransformation->optimizedForestWe();
                $this->graphTransformation->groupTTweD();
                $this->graphTransformation->createDefaultActiveGraph();
                $this->graphTransformation->groupCL();
                $this->graphTransformation->createDefaultActiveGraph();
                //$this->graphTransformation->groupT();
                //$this->graphTransformation->groupTwe();
                //$this->graphTransformation->groupCT();
                
                $this->saveGraphInFile($filenameTotal, false); 
                $this->saveGraphInFile($filenameActive, true);
        }

        private function restoreGraphFromFile ($filename, $active) {
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

        private function saveGraphInFile ($filename, $active) {
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

        private function setTree ($input) {
		$this->notesFile = new \SplFileObject(__DIR__. '/Fixtures/'.$input.'.profile');
        	$this->totalGraph->getTree($this->notesFile);
                $this->graphTransformation->setTreeKeyWithEmpty();
                $this->graphTransformation->setTreeKey();
                //xdebug_break();
        }
}
