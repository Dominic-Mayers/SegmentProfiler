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
use App\VisitorCL;
use App\VisitorCTD;
use App\VisitorCTweD;
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
	
	public function __construct(
                private TotalGraph $totalGraph,
                private ActiveGraph $activeGraph, 
                private Traversal $traversal,
		private UrlGeneratorInterface $urlGenerator,
                private $groupsWithNoInnerNodes = null,
	) {
	}
	
        public function setColorCode( $nodes = null ) {
		// To be executed on the active graph or active subgraph. 
		
		$cM = $this->cM;
		$nC = count( $cM );
		$V  = $nodes ?? $this->activeGraph->nodes; 
		$totalTime = 0;
		foreach ( $V as  $nodeId => $node ) {
			$timeExc = $node->attributes['timeExclusive'];
			$sortedTimes[] = $timeExc;
			$totalTime += $timeExc;
		}
                if($totalTime === 0) {
                    foreach ( $V as $nodeId => $node )  {
                            $node->attributes['colorCode'] = (int) $nC / 2; 
                    }
                    return; 
                }
		sort( $sortedTimes );

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
                if ($this->activeGraph->nodes[$startId]->type=== 'TTD' || 
                    $this->activeGraph->nodes[$startId]->type=== 'TTweD')
                {
                    $newStartId = $this->activeGraph->nodes[$startId]->innerNodesId[0];
                    $this->deactivateGroup($startId);
                    $this->setColorCode(); 
                    $startId = $newStartId;                    
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

        public function activeGraphToDot($input = 'input', $graphArr = null , $recolor=false, $toUngroup =  '') {
		$cM = $this->cM; 
		$this->graph = new Graph();
		[$V, $A, $R] = $graphArr ?? [$this->activeGraph->nodes, $this->activeGraph->arrowsOut, $this->totalGraph->rootId];
		if ($recolor) {
			$this->setColorCode($V);
		}  
                $dot = "digraph {" . PHP_EOL;
                foreach ($V as $nodeId => $node) {
                    $cC = $node->attributes['colorCode'];
                    $dot .= "\"$nodeId\" [id=\"$nodeId\" label=\"{$this->getVizLabel($nodeId)}\" ". 
                            "style=\"filled\" fontname=\"Courier-Bold\" shape=\"rect\" ".
                            "URL=\"https://segmentprofiler.org/drawgraph/$input/$nodeId\" target=\"_parent\" ".
                            "colorscheme=\"oranges9\" fillcolor={$cM[$cC]['fl']} fontcolor=\"black\"]". \PHP_EOL;
                }
                foreach ($A as $adj) {
			foreach ($adj as $arrow) {
                             $dot .= "\"{$arrow->sourceId}\" -> \"{$arrow->targetId}\"";
                             if (isset($arrow->calls) && $arrow->calls !== 1) {
                                $dot .= " [label={$arrow->calls}]"; 
                             }
                             $dot .= PHP_EOL; 
                        }
                }
                $dot .= "}" . PHP_EOL;  
                return $dot; 
        }
        
	public function createGraphViz($input = 'input', $graphArr = null , $recolor=false, $toUngroup =  '') {
		$cM = $this->cM; 
		$this->graph = new Graph();
		[$V, $A, $R] = $graphArr ?? [$this->activeGraph->nodes, $this->activeGraph->arrowsOut, $this->totalGraph->rootId];
		if ($recolor) {
			$this->setColorCode($V);
		}
		$gvNodes = [];
                foreach ($V as $nodeId => $node) {
			$gvnode = $gvNodes[$nodeId] = $this->graph->createVertex();
			$gvnode->setAttribute('id', $nodeId );
			$gvnode->setAttribute('graphviz.label', $this->getVizLabel($nodeId)); 
			$gvnode->setAttribute('graphviz.style', 'filled');
			$gvnode->setAttribute('graphviz.fontname', "Courier-Bold"); 
			$gvnode->setAttribute('graphviz.shape', "rect");
			$gvnode->setAttribute('colorscheme', 'orange9');
                        if ( ! empty($A[$nodeId])) {
                                $url = $this->urlGenerator->generate(
                                        'drawgraph', 
                                        ['toUngroup' => $toUngroup, 'startId' => $nodeId, 'input' => $input ],
                                        UrlGeneratorInterface::ABSOLUTE_URL
                                );
                                $gvnode->setAttribute('graphviz.URL', $url );
                                $gvnode->setAttribute('graphviz.target', '_parent');
                        }
			if (isset ($node->attributes['colorCode']) ) {
				$cC = $node-é>attributes['colorCode'];
				$gvnode->setAttribute('graphviz.colorscheme', $cM[$cC]['sc']);
				$gvnode->setAttribute('graphviz.fillcolor'  , $cM[$cC]['fl']);
				$gvnode->setAttribute('graphviz.fontcolor'  , $cM[$cC]['ft']);
			}                    
                }
                foreach ($A as $adj) {
			foreach ($adj as $arrow) {
                                $source = $gvNodes[$arrow->sourceId];
                                $target = $gvNodes[$arrow->targetId];
				$edge = $this->graph->createEdgeDirected($source, $target);
				if (isset($arrow->calls) && $arrow->calls !== 1) {	
					$edge->setAttribute('graphviz.label', $arrow->calls); 
				}
                        }
		}
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

	public function deactivateNode($nodeId) {

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
