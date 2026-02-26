<?php
namespace App;
use App\GraphTransformationAPI;
use App\BaseState;
use App\GroupState; 
use App\TreePhase; 
use App\ActiveGraph;

class Backend {
    
        public $fromFile = false;
	
	public function __construct(
                private GraphTransformationAPI $graphTransformationAPI, 
                private BaseState $baseState, 
                private GroupState $groupState, 
                private TreePhase $treePhase,
                private ActiveGraph $activeGraph, 
	) {
	}
	        
	public function computeGraphStates ($input) {
                // We do bnot modify below if the graphs are restored from files. 
                if (false && $this->restoreGraph($input)) {
                    return ['nodes' => $this->activeGraph->nodes, 'adjacency' => $this->activeGraph->arrowsOut];
                }
                $this->setTree($input);
                //$this->graphTransformationAPI->createDefaultActiveGraph();
                //$this->graphTransformationAPI->contractionOnTwe();
                //$this->graphTransformationAPI->groupCL();
                //$this->graphTransformationAPI->groupCT();
                $this->graphTransformationAPI->groupTwe();
                //$this->graphTransformationAPI->groupCTwe();
                $this->graphTransformationAPI->createDefaultActiveGraph();
                $filenameTotal  = __DIR__ . '/../input/Graphs/'.$input.'.totgraph'; 
                $filenameActive = __DIR__ . '/../input/Graphs/'.$input.'.actgraph'; 
                $this->saveGraphInFile($filenameTotal, false); 
                $this->saveGraphInFile($filenameActive, true);
                return ['nodes' => $this->activeGraph->nodes, 'adjacency' => $this->activeGraph->arrowsOut]; 
        }

        public function restoreGraph($input) {
            if ($this->fromFile) {return true;} 
            $filenameTotal  = __DIR__ . '/../input/Graphs/'.$input.'.totgraph'; 
            $filenameActive = __DIR__ . '/../input/Graphs/'.$input.'.actgraph'; 
            if (file_exists ($filenameTotal) && file_exists ($filenameActive) ) {
                    $this->restoreGraphFromFile($filenameTotal,  false);
                    $this->restoreGraphFromFile($filenameActive, true);
                    $this->fromFile = true; 
            }
            return $this->fromFile;             
        }

        private function restoreGraphFromFile ($filename, $active) {
            $serialGraph = file_get_contents($filename); 
            $state = json_decode($serialGraph, true);
            $arrowsIn = [];
            foreach ($state['adjacency'] as $sourceId => $adjArrowsOut) {                 
                foreach ( $adjArrowsOut as $targetId => $arrow) {
                    $arrowsIn[$targetId][$sourceId] = $arrow;
                }
            }
            if ($active) {
                $gr = & $this->activeGraph; 
            } else {
                $gr = & $this->baseState;                
            }
            $gr->nodes     = $state['nodes'];
            $gr->arrowsIn  = $arrowsIn;
            $gr->arrowsOut = $state['adjacency'];
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
            $serialGraph = json_encode(["nodes" => $gr->nodes, "adjacency" => $gr->arrowsOut]);
            file_put_contents($filename, $serialGraph);
        }

        private function setTree ($input) {
		$notesFile = new \SplFileObject(__DIR__. '/Fixtures/'.$input.'.profile');
        	$this->treePhase->getTree($notesFile);
                $this->graphTransformationAPI->setTreeKeyWithEmpty();
                $this->graphTransformationAPI->setTreeKey();
                //xdebug_break();
        }

    public function getGroup($groupId) {
        $group['deleteNodes'] = [$groupId];
        $innerNodesId = $this->baseState->nodes[$groupId]['innerNodesId'];

        $nodes = [];
        foreach ($innerNodesId as $nodeId) {
            $nodes[$nodeId] = $this->baseState->nodes[$nodeId];
        }
        $subGraph['nodes'] =  $nodes;
        
        $adjacency = [];
        foreach ($innerNodesId as $nodeId) {
            $adjacency[$nodeId] = $this->groupState->adjActiveArrowsOut($nodeId);
            foreach ($this->groupState->adjActiveArrowsIn($nodeId) as $sourceId => $arrow) {
                $adjacency[$sourceId][$nodeId] = $arrow; 
            }
        }
        $subGraph['adjacency'] =  $adjacency;
        
        $group['subgraph'] = $subGraph; 
        return $group; 
    }
}
