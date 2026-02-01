<?php
namespace App;
use Graphp\Graph\Graph; 
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\TotalGraph; 
use App\ActiveGraph; 
use App\Traversal; 

class UI {
	
	private $cM = [ 
//		[ 'sc' => null, 'fl' => "white"  , 'ft' => "black" ],
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
	) {
	}
	
        public function setColorCode() {
                // The color code of each node is its  $nC quantile weighted per exclusive time.
                // We take the convention that quantiles start at 0.
		$cM = $this->cM; $nC = count( $cM ); $V  = & $this->activeGraph->nodes; 
		$totalTime = 0;
		foreach ( $V as  $nodeId => $node ) {
			$timeExc = $node['attributes']['timeExclusive'];
			$sortedTimes[$nodeId] = $timeExc;
			$totalTime += $timeExc;
		}
                if($totalTime === 0) {
                    // weighted quantiles make no sense when the weights are all zeros. 
                    foreach ( $V as $nodeId => $node ) { $V[$nodeId]['attributes']['colorCode'] = (int) $nC / 2; }
                    return; 
                }
                $partialTime = 0; 
		asort( $sortedTimes );
		foreach ( $sortedTimes as  $nodeId => $currentTime)  {
			$partialTime += $currentTime;
			$pernCtile = (int) ceil(($nC * $partialTime)/ $totalTime) - 1; // Use ceil - 1, because a pernCtile includes its max.
                        $V[$nodeId]['attributes']['colorCode'] =  $pernCtile; 
		}
	}
        
	public function getSubGraph($startId, $arrows = null) : array {
                if (empty($startId)) {
                    return [$this->activeGraph->nodes, $this->activeGraph->arrowsOut, $this->totalGraph->rootId]; 
                }
                if ($this->activeGraph->nodes[$startId]['type']=== 'TTD' || 
                    $this->activeGraph->nodes[$startId]['type']=== 'TTweD')
                {
                    $newStartId = $this->activeGraph->nodes[$startId]['innerNodesId'][0];
                    $this->deactivateGroup($startId);
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

        public function dot2svg ($dotString) {
            $executable = "/usr/bin/dot -Tsvg"; 
            $descriptorspec = array(
                0 => array("pipe", "r"),
                1 => array("pipe", "w"),
                2 => array("pipe", "w")
            );
            $process = proc_open(
                $executable,
                $descriptorspec,
                $pipes
            );
            fwrite($pipes[0], $dotString);
            fclose($pipes[0]);
            $svg = stream_get_contents($pipes[1]); 
            fclose($pipes[1]);
            fclose($pipes[2]);
            return $svg; 
        }

        public function activeGraphToDot($input = 'input', $graphArr = null) {
                // We had a parameter $toUngroup, but it was used to pass the state of the graph in the URLs.
                // We must maintain the state of the graph differently. 
                // This also applies to $input. It is stored in the URLs to pass the state.
		$cM = $this->cM; 
		$this->graph = new Graph();
		[$V, $A, $R] = $graphArr ?? [$this->activeGraph->nodes, $this->activeGraph->arrowsOut, $this->totalGraph->rootId];
                $dot = "digraph {" . PHP_EOL;
                foreach ($V as $nodeId => $node) {
                    $cC = $node['attributes']['colorCode'];
                    $extra = $node['attributes']['extraLabel'] ?? ""; 
                    $dot .= "\"$nodeId\" [id=\"$nodeId\" label=\"{$this->getVizLabel($nodeId)}{$extra}\" ". 
                            "style=\"filled\" fontname=\"Courier-Bold\" shape=\"rect\" ".
                            "URL=\"https://segmentprofiler.org/drawgraph/$input/$nodeId\" target=\"_parent\" ".
                            "colorscheme=\"oranges9\" fillcolor={$cM[$cC]['fl']} fontcolor=\"{$cM[$cC]['ft']}\"]". \PHP_EOL;
                }
                foreach ($A as $adj) {
			foreach ($adj as $arrow) {
                             $dot .= "\"{$arrow['sourceId']}\" -> \"{$arrow['targetId']}\"";
                             if (isset($arrow['calls']) && $arrow['calls'] !== 1) {$dot .= " [label={$arrow['calls']}]";}
                             $dot .= PHP_EOL; 
                        }
                }
                $dot .= "}" . PHP_EOL;  
                return $dot; 
        }
        
	public function activateGroup($groupId, $permanent) {

		foreach ($this->totalGraph->nodes[$groupId]['innerNodesId'] as $nodeId) {
			$this->deactivateNode($nodeId);
                        if ( $permanent ) {
                            $this->removeNode($nodeId);
                        }
		}
		$this->activateNode($groupId);
                if ($permanent) {
                    $this->totalGraph->nodes[$groupId]['innerNodesId'] = [];
                }
                $this->setColorCode(); 
	}

	public function deactivateGroup($groupId) {

		$this->deactivateNode($groupId);
		foreach ($this->totalGraph->nodes[$groupId]['innerNodesId'] as $nodeId) {
			$this->activateNode($nodeId);
		}
                $this->setColorCode(); 
	}

	private function activateNode($nodeId) {

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

	private function deactivateNode($nodeId) {

		if (isset($this->totalGraph->arrowsOut[$nodeId])) {
			foreach ($this->totalGraph->arrowsOut[$nodeId] as $targetId => $arrow) {
				unset($this->activeGraph
                                        ->arrowsOut
                                        [$nodeId]
                                        [$targetId]);
                                unset($this->activeGraph->arrowsIn[$targetId][$nodeId]);
			}
			if (empty($this->activeGraph->arrowsOut[$nodeId])) {
				unset($this->activeGraph->arrowsOut[$nodeId]);
			}
			if (empty($this->activeGraph->arrowsIn[$targetId])) {
				unset($this->activeGraph->arrowsIn[$targetId]);
			}
		}
		if (isset($this->totalGraph->arrowsIn[$nodeId])) {
			foreach ($this->totalGraph->arrowsIn[$nodeId] as $sourceId => $arrow) {
				unset($this->activeGraph
                                        ->arrowsOut
                                        [$sourceId]
                                        [$nodeId]);
                                unset($this->activeGraph->arrowsIn[$nodeId][$sourceId]);
			}
			if (empty($this->activeGraph->arrowsOut[$sourceId])) {
				unset($this->activeGraph->arrowsOut[$sourceId]);
			}
   			if (empty($this->activeGraph->arrowsIn[$nodeId])) {
				unset($this->activeGraph->arrowsIn[$nodeId]);
			}
		}
		unset($this->activeGraph->nodes[$nodeId]);
	}

        private function getVizLabel($nodeId) {
		$node = $this->totalGraph->nodes[$nodeId]; 
		if ( isset($node['attributes']['timeExclusive'] ) ) {
			$excTime = $node['attributes']['timeExclusive'];
			$excTimeInMillisec = number_format($excTime / 1E+6, 3);
			$incTime = $node['attributes']['timeFct'];
			$incTimeInMillisec = number_format($incTime / 1E+6, 3);
			$timeTxt = "($excTimeInMillisec, $incTimeInMillisec)";  
		} else {
			$timeTxt = "";
		}
		$label = $node['attributes']['innerLabel'];
                if (strlen($label) > 130) {
                    $label = substr($label, 0, 130) . "... truncated "; 
                }
		return "   $nodeId: $label$timeTxt";
	}
}
