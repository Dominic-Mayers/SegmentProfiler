<?php
namespace App;

use Graphp\GraphViz\GraphViz; 
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
use App\VisitorCL;
use App\VisitorCTD;
use App\VisitorCTDwe;
use App\VisitorDOnce;
use App\VisitorTreeKey;
use App\VisitorTreeWithEmptyKey;

class Profiler {
	
	private $cM = [ 
		[ 'sc' => null, 'fl' => "white"  , 'ft' => "black" ],
		[ 'sc' => 'oranges9', 'fl' => '1', 'ft' => "black" ],
		[ 'sc' => 'oranges9', 'fl' => '2', 'ft' => "black" ],
		[ 'sc' => 'oranges9', 'fl' => '3', 'ft' => "black" ],
		[ 'sc' => 'oranges9', 'fl' => '4', 'ft' => "black" ],
		[ 'sc' => 'oranges9', 'fl' => '5', 'ft' => "black" ],
		[ 'sc' => 'oranges9', 'fl' => '6', 'ft' => "black" ],
		[ 'sc' => 'oranges9', 'fl' => '7', 'ft' => "white" ],
		[ 'sc' => 'oranges9', 'fl' => '8', 'ft' => "white" ],
		[ 'sc' => 'oranges9', 'fl' => '9', 'ft' => "white" ]
	]; 

        
	public Graph $graph;
	public GraphViz $graphviz;
	
	public function __construct(
                private TotalGraph $totalGraph,
                private ActiveGraph $activeGraph, 
                private Traversal $traversal,
		private UrlGeneratorInterface $urlGenerator,
                private $groupsWithNoInnerNodes = null,
	) {
		$this->graphviz    = new GraphViz();
	}
	
        public function setColorCode( $nodes = null ) {
		// To be executed on the active graph or active subgraph. 
		
		$cM = $this->cM;
		$V  = $nodes ?? $this->activeGraph->nodes; 

		$totalTime = 0;
		foreach ( $V as  $nodeId => $node ) {
			$timeExc = $node->attributes['timeExclusive'];
			$sortedTimes[] = $timeExc;
			$totalTime += $timeExc;
		}
		sort( $sortedTimes );

		$nC = count( $cM );
		$partialTime = 0;
		$fracTime = $totalTime / $nC;
		// It might not seem, but this runs over all the nodes $i,
		// because for each $k many nodes $i are run over and the max
		// value $nC for $k is only reached when $partialTime is
		// actually $totalTime.
		$k = 1;
		$i = 0; 
		while ( $k <= $nC ) {
			$currentTime = $sortedTimes[$i];
			$partialTime += $currentTime;
			$oldN = $k;
			$newN = ($partialTime + 1) / $fracTime;
			while ( $k <= $newN ) {
				$colorTimeLimits[$k - 1] = $currentTime;
				$k++;
			}
			$i++;
		}
		
		// Integer: Darkness of colors when only a subrange of colors are used.
		// 0          => lightest colors.
		// $k - $oldN => darkest colors.
		$adjust = intdiv($k - $oldN,2);

		foreach ( $V as $nodeId => $node )  {
			$excTime = $node->attributes['timeExclusive'];
			$cC = -1;
			for ( $i = 1; $i <= $nC; $i++ ) {
				if ( $excTime <= $colorTimeLimits[$i] ) {
					$cC = $i - 1 + $adjust;
					break;
				}
			}
			if ( $cC === -1  )  {
				trigger_error( "Color code require time under " . $colorTimeLimits[$nC] . 
					", but time was " . $excTime . " for node " . $nodeId, E_USER_ERROR );
			}

			$node->attributes['colorCode'] = $cC;
		}
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
            $serialGraph = serialize([$gr->nodes, $gr->arrowsOut]);
            file_put_contents($filename, $serialGraph);
        }
        
        
        public function restoreGraphFromFile ($filename, $active) {
            $serialGraph = file_get_contents($filename); 
            [$nodes, $arrowsOut] = unserialize($serialGraph); 
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
        
	public function getSubGraph($startId, $arrows = null) : array {
                if (empty($startId)) {
                    return [$this->activeGraph->nodes, $this->activeGraph->arrowsOut, $this->totalGraph->rootId]; 
                }
		$arrows ??= $this->activeGraph->arrowsOut;
		$subArrows = [];
		$subNodes  = [];
		$toProcess = [$startId];
		$subNodes[$startId] = $this->activeGraph->nodes[$startId];
		$visited = [];
		while (true) {
			if ($toProcess == []) {
				break;
			}

			$currentId = end($toProcess);

			If (!isset($visited[$currentId]) || !$visited[$currentId]) {
				$visited[$currentId] = true;
				$adj = $arrows[$currentId] ?? [];
				foreach ($adj as $targetId => $arrow) {
					$subArrows[$currentId][$targetId] = $arrow;
					if (
						!isset($visited[$targetId]) || !$visited[$currentId]
					) {
						$toProcess[] = $targetId;
						$subNodes[$targetId] = $this->activeGraph->nodes[$targetId];
					}
				}
			} else {
				array_pop($toProcess);
			}
		}
		return [$subNodes, $subArrows, $startId];
	}

	public function createGraphViz($input = 'input', $graphArr = null , $recolor=false, $toUngroup =  '') {
		$cM = $this->cM; 
		$this->graph = new Graph();
		[$V, $A, $R] = $graphArr ?? [$this->activeGraph->nodes, $this->activeGraph->arrowsOut, $this->totalGraph->rootId];
		if ($recolor) {
			$this->setColorCode($V);
		}
		if (empty($A)) { return "";}
		$gvNodes = [];
		foreach ($A as $adj) {
			foreach ($adj as $arrow) {
				if (! isset($gvNodes[$arrow->sourceId]) ) {
					$source = $gvNodes[$arrow->sourceId] = $this->graph->createVertex();
					$source->setAttribute('id', $arrow->sourceId );
					$source->setAttribute('graphviz.label', $this->getVizLabel($arrow->sourceId)); 
					$source->setAttribute('graphviz.style', 'filled');
					$source->setAttribute('graphviz.fontname', "Courier-Bold"); 
					$source->setAttribute('graphviz.shape', "rect");
					$source->setAttribute('colorscheme', 'orange9');
					$url = $this->urlGenerator->generate(
						'drawgraph', 
						['toUngroup' => $toUngroup, 'startId' => $arrow->sourceId, 'input' => $input ],
						UrlGeneratorInterface::ABSOLUTE_URL
					);
					$source->setAttribute('graphviz.URL', $url );
					$source->setAttribute('graphviz.target', '_parent'); 
					if (isset ($V[$arrow->sourceId]->attributes['colorCode']) ) {
						$cC = $V[$arrow->sourceId]->attributes['colorCode'];
						$source->setAttribute('graphviz.colorscheme', $cM[$cC]['sc']);
						$source->setAttribute('graphviz.fillcolor'  , $cM[$cC]['fl']);
						$source->setAttribute('graphviz.fontcolor'  , $cM[$cC]['ft']);
					}
				} else {
					$source = $gvNodes[$arrow->sourceId];
				}
				if (! isset($gvNodes[$arrow->targetId]) ) {
					$target = $gvNodes[$arrow->targetId] = $this->graph->createVertex();					
					$target->setAttribute('id', $arrow->targetId );
					$target->setAttribute('graphviz.label', $this->getVizLabel($arrow->targetId)); 
					$target->setAttribute('graphviz.style', 'filled');
					$target->setAttribute('graphviz.fontname', "Courier-Bold"); 
					$target->setAttribute('graphviz.shape', "rect"); 
					if ( ! empty($A[$arrow->targetId])) {
						$url = $this->urlGenerator->generate(
							'drawgraph',
							['toUngroup' => $toUngroup, 'startId' => $arrow->targetId, 'input' => $input ], 
							UrlGeneratorInterface::ABSOLUTE_URL
						);
						$target->setAttribute('graphviz.URL', $url);
						$target->setAttribute('graphviz.target', '_parent');
					} 
					if (isset ($V[$arrow->targetId]->attributes['colorCode']) ) {
						$cC = $V[$arrow->targetId]->attributes['colorCode'];
						$target->setAttribute('graphviz.colorscheme', $cM[$cC]['sc']);	
						$target->setAttribute('graphviz.fillcolor'  , $cM[$cC]['fl']);
						$target->setAttribute('graphviz.fontcolor'  , $cM[$cC]['ft']);
					}
				} else {
					$target = $gvNodes[$arrow->targetId];
				}
				$edge = $this->graph->createEdgeDirected($source, $target);
				if (isset($arrow->calls) && $arrow->calls !== 1) {	
					$edge->setAttribute('graphviz.label', $arrow->calls); 
				}
                        }
		}
	}

	public function groupT() {
                if (! $this->totalGraph->isTree ) {
                    echo "Group by T is only possible on a tree." . PHP_EOL;
                    exit(); 
                }
                $visitorT = new VisitorT(
                        $this->totalGraph,
                        $this->groupsWithNoInnerNodes); 
		$this->traversal->visitNodes( $visitorT);
	}

	public function groupTwe() {
                if (! $this->totalGraph->isTree ) {
                    echo "Group by Twe is only possible on a tree." . PHP_EOL;
                    exit(); 
                }
                $visitorTwe = new VisitorTwe(
                        $this->totalGraph,
                        $this->groupsWithNoInnerNodes); 
		$this->traversal->visitNodes($visitorTwe);
	}

        public function groupCT() {
                if (! $this->totalGraph->isTree ) {
                    echo "Group by CTwe is only possible on a tree." . PHP_EOL;
                    exit(); 
                }
                $visitorCT = new VisitorCT(
                        $this->totalGraph,
                        $this->groupsWithNoInnerNodes); 
		$this->traversal->visitNodes($visitorCT);
	}
        
        public function groupCTwe() {
                if (! $this->totalGraph->isTree ) {
                    echo "Group by CT is only possible on a tree." . PHP_EOL;
                    exit(); 
                }
		// For every non innernode, this only groups its non inner children with a same full name.
		$visitorCTwe = new VisitorCTwe(
                        $this->totalGraph,
                        $this->groupsWithNoInnerNodes);  
		$this->traversal->visitNodes($visitorCTwe);
	}

        public function groupCL() {
                if (! $this->totalGraph->isTree ) {
                    echo "Group by CL is only possible on a tree." . PHP_EOL;
                    exit(); 
                }
		// For every non innernode, this only groups its non inner children with a same full name.
		$visitorCL = new VisitorCL(
                        $this->totalGraph,
                        $this->groupsWithNoInnerNodes); 
		$this->traversal->visitNodes($visitorCL);
	}
        
	public function groupCTD() {
                $visitorCTD = new VisitorCTD(
                        $this->totalGraph,
                        $this->groupsWithNoInnerNodes);
                $this->traversal->visitNodes($visitorCTD);                 
	}

	public function groupCTDwe() {
                $visitorCTDwe = new VisitorCTDwe(
                        $this->totalGraph,
                        $this->groupsWithNoInnerNodes); 
                $this->traversal->visitNodes($visitorCTDwe);                 
	}

	public function groupDOnce( $nodeId ) {
                if ( empty( $this->totalGraph->nodes[$nodeId]->attributes['treeKey'] )  ) {
                    echo "Group by D is only possible when the treeKey attribute is set." . PHP_EOL;
                    exit(); 
                }
                $visitorDOnce = new VisitorDOnce(
                        $this->totalGraph,
                        $this->groupsWithNoInnerNodes); 
                $this->traversal->visitNodes($visitorDOnce, $nodeId);                 
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

        public function createDefaultActiveGraph () {
                // To be called once we have the totalGraph, after the groups have been created. 
                $visitorDefaultActiveGraph = new VisitorDefaultActiveGraph(
                        $this->totalGraph, 
                        $this->activeGraph);
                $this->traversal->visitNodes($visitorDefaultActiveGraph);
        }
        
        public function findMaxSaved() {
                $visitorFindMaxSaved = new VisitorFindMaxSaved($this->traversal, $this->totalGraph);
                $this->traversal->visitNodes($visitorFindMaxSaved); 
        }
        
	public function activateGroup($groupId, $permanent) {

		foreach ($this->totalGraph->nodes[$groupId]->innerNodesId as $nodeId) {
			$this->deactivateNode($nodeId);
                        if ( $permanent ) {
                            $this->removeNode($nodeId);
                        }
		}
		$this->activateNode($groupId);
                if ($permanent) {
                    $this->totalGraph->nodes[$groupId]->innerNodesId = [];
                }
	}

	public function deactivateGroup($groupId) {

		$this->deactivateNode($groupId);
		foreach ($this->totalGraph->nodes[$groupId]->innerNodesId as $nodeId) {
			$this->activateNode($nodeId);
		}
	}

	public function activateNode($nodeId) {

		$this->activeGraph->nodes[$nodeId] = $this->totalGraph->nodes[$nodeId];
		if (isset($this->totalGraph->arrowsIn[$nodeId])) {
			foreach ($this->totalGraph->arrowsIn[$nodeId] as $sourceId => $arrow) {
				if (isset($this->activeGraph->nodes[$sourceId])) {
					$this->activeGraph->arrowsOut[$sourceId][$nodeId] = $arrow;
					$this->activeGraph->arrowsIn[$nodeId][$sourceId]  = $arrow;
				}
			}
		}

		if (isset($this->totalGraph->arrowsOut[$nodeId])) {
			foreach ($this->totalGraph->arrowsOut[$nodeId] as $targetId => $arrow) {
				if (isset($this->activeGraph->nodes[$targetId])) {
					$this->activeGraph->arrowsOut[$nodeId][$targetId] = $arrow;
                                        $this->activeGraph->arrowsIn[$targetId][$nodeId]  = $arrow;
				}
			}
		}
	}

	public function deactivateNode($nodeId, bool $permanent) {

		if (isset($this->totalGraph->arrowsIn[$nodeId])) {
			foreach ($this->totalGraph->arrowsIn[$nodeId] as $sourceId => $arrow) {
				unset($this->activeGraph->arrowsOut[$sourceId][$nodeId]);
                                unset($this->activeGraph->arrowsIn[$nodeId][$sourceId]);
			}
			if (empty($this->activeGraph->arrowsOut[$sourceId])) {
				unset($this->activeGraph->arrowsOut[$sourceId]);
			}
   			if (empty($this->activeGraph->arrowsIn[$nodeId])) {
				unset($this->activeGraph->arrowsIn[$nodeId]);
			}
		}
		if (isset($this->totalGraph->arrowsOut[$nodeId])) {
			foreach ($this->totalGraph->arrowsOut[$nodeId] as $targetId => $arrow) {
				unset($this->activeGraph->arrowsOut[$nodeId][$targetId]);
                                unset($this->activeGraph->arrowsIn[$targetId][$nodeId]);
			}
			if (empty($this->activeGraph->arrowsOut[$nodeId])) {
				unset($this->activeGraph->arrowsOut[$nodeId]);
			}
			if (empty($this->activeGraph->arrowsIn[$targetId])) {
				unset($this->activeGraph->arrowsIn[$targetId]);
			}
		}
		unset($this->activeGraph->nodes[$nodeId]);
	}

        private function getVizLabel($nodeId) {
		$node = $this->totalGraph->nodes[$nodeId]; 
		if ( isset($node->attributes['timeExclusive'] ) ) {
			$excTime = $node->attributes['timeExclusive'];
			$excTimeInMillisec = number_format($excTime / 1E+6, 3);
			$incTime = $node->attributes['timeFct'];
			$incTimeInMillisec = number_format($incTime / 1E+6, 3);
			$timeTxt = "($excTimeInMillisec, $incTimeInMillisec)";  
		} else {
			$timeTxt = "";
		}
		$label = $node->attributes['innerLabel'];
                if (strlen($label) > 130) {
                    $label = substr($label, 0, 130) . "... truncated "; 
                }
		return "   $nodeId: $label$timeTxt";
	}
}
