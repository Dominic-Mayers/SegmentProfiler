<?php
namespace App;
use App\GraphTransformationAPI;
use App\BaseState; 
use App\TreePhase; 
use App\ActiveGraph;

class Backend {
	
	public function __construct(
                private GraphTransformationAPI $graphTransformationAPI, 
                private BaseState $baseState, 
                private TreePhase $treePhase,
                private ActiveGraph $activeGraph, 
	) {
	}
	        
	public function computeGraphStates ($input) {
                $filenameTotal  = __DIR__ . '/../input/Graphs/'.$input.'.totgraph'; 
                $filenameActive = __DIR__ . '/../input/Graphs/'.$input.'.actgraph'; 
                if (false && file_exists ($filenameTotal) && file_exists ($filenameActive) ) {
                    $this->restoreGraphFromFile($filenameTotal,  false);
                    $this->restoreGraphFromFile($filenameActive, true);
                    return;
                }
                // Of course, it is pointless to modify below if the graphs are stored in files. 
                $this->setTree($input);
                //$this->graphTransformationAPI->createDefaultActiveGraph();
                //$this->graphTransformationAPI->contractionOnTwe();
                //$this->graphTransformationAPI->groupCL();
                //$this->graphTransformationAPI->groupCT();
                $this->graphTransformationAPI->groupTwe();
                //$this->graphTransformationAPI->groupCTwe();
                $this->graphTransformationAPI->createDefaultActiveGraph();
                $this->saveGraphInFile($filenameTotal, false); 
                $this->saveGraphInFile($filenameActive, true);
                return [$this->activeGraph->nodes, $this->activeGraph->arrowsOut]; 
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
                $gr = & $this->treePhase;                
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
                $gr =& $this->baseState;                
            }
            $serialGraph = json_encode([$gr->nodes, $gr->arrowsOut]);
            file_put_contents($filename, $serialGraph);
        }

        private function setTree ($input) {
		$notesFile = new \SplFileObject(__DIR__. '/Fixtures/'.$input.'.profile');
        	$this->treePhase->getTree($notesFile);
                $this->graphTransformationAPI->setTreeKeyWithEmpty();
                $this->graphTransformationAPI->setTreeKey();
                //xdebug_break();
        }
}
