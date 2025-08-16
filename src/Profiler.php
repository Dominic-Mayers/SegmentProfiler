<?php
namespace App;

use Graphp\GraphViz\GraphViz; 
use Graphp\Graph\Graph;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use SVG\SVG;

require_once ('Node.php');
require_once ('Arrow.php');

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

        public ActiveGraph $activeGraph;
        public TotalGraph $totalGraph;
	public Graph $graph;
	public GraphViz $graphviz;
	
	public function __construct(
		private UrlGeneratorInterface $urlGenerator,
                private EntityManagerInterface $entityManager,
	) {
		$this->graphviz    = new GraphViz();
                $this->activeGraph = new ActiveGraph(); 
                $this->totalGraph  = new TotalGraph();
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
		$startId ??= $this->totalGraph->rootId; 
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

	public function createGraphViz($input = 'input', $graphArr = null , $recolor=false, $toUngroup =  ''): string {
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
		$script = $this->graphviz->createScript($this->graph);
		return $script; 
	}

	public function groupPerPath() {
		// For every non innernode, this only groups its non inner children with a same full name.
		$visitorP = new VisitorP();
		$traversal = new Traversal($this->totalGraph, $visitorP); 
		$traversal->visitNodes();
	}

	public function groupSiblingsPerPath() {
		// For every non innernode, this only groups its non inner children with a same full name.
		$visitorSP = new VisitorSP();
		$traversal = new Traversal($this->totalGraph, $visitorSP); 
		$traversal->visitNodes();
	}
        
	public function groupSiblingsPerName() {
		// For every non innernode, this only groups its non inner children with a same full name.
		$visitorSN = new VisitorSN();
		$traversal = new Traversal($this->totalGraph, $visitorSN); 
		$traversal->visitNodes();
		return $visitorSN->newNonSingletonSinceLastSet; 
	}

	public function fullGroupSiblingsPerName() {
		while (true) {
			$newNonSingletonSinceLastSet = $this->groupSiblingsPerName();
			if (!$newNonSingletonSinceLastSet) {
                                //echo "No group created".PHP_EOL. PHP_EOL; 
				break;
			}
		}
	}

	public function groupDescendentsPerName() {
		$visitorDN = new VisitorDN();
                $traversal = new Traversal($this->totalGraph, $visitorDN); 
		$traversal->visitNodes();
                //echo PHP_EOL; 
	}

	public function groupSiblingsPerChildrenName() {
		// For every non innernode, this only groups its non inner children with a same full name.
                //echo "Starting SCN".PHP_EOL."-----------".PHP_EOL; 
		$visitorSCN = new VisitorSCN();
                $traversal = new Traversal($this->totalGraph, $visitorSCN);
		$traversal->visitNodes();
                //echo PHP_EOL; 
	}
        
        public function createDefaultActiveGraph () {
                // To be called once we have the totalGraph, after the groups have been created. 
                $visitorDefaultActiveGraph = new VisitorDefaultActiveGraph();
                $visitorDefaultActiveGraph->setActiveGraph($this->activeGraph); 
                $traversal = new Traversal($this->totalGraph, $visitorDefaultActiveGraph);
                $traversal->visitNodes(); 
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
		$label = $node->attributes['label'];
                if (strlen($label) > 130) {
                    $label = substr($label, 0, 130) . "... truncated "; 
                }
		return "   $nodeId: $label$timeTxt";
	}
}
