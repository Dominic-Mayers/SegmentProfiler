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



/**
 * Main entry: compute nC-ciles (colorCode) for the active graph,
 * using tie-handling (half-weight before) and floating-point safe scale.
 */
public function setColorCode() {
    $nC = count($this->cM);
    $V  = &$this->activeGraph->nodes;

    // Step 1: extract weights (timeExclusive)
    $weights = $this->extractWeights($V);
    $totalWeight = array_sum($weights);

    // Step 2: compute robust scale with phantom weight epsilon
    $scale = $this->computeScale($totalWeight, $nC);

    // Step 3: group nodes by weight to handle ties
    $groups = $this->groupNodesByWeight($weights);

    // Step 4: compute CW_i with half-weight before and assign nC-ciles
    $this->assignNCcilesWithTies($V, $groups, $scale, $nC);
}

/**
 * Extract weights from nodes.
 */
private function extractWeights(array $V): array {
    $weights = [];
    foreach ($V as $nodeId => $node) {
        $weights[$nodeId] = $node['attributes']['timeExclusive'];
    }
    return $weights;
}

/**
 * Compute safe scale using phantom weight epsilon.
 */
private function computeScale(float $totalWeight, int $nC): float {
    $epsilonFP = PHP_FLOAT_EPSILON * $totalWeight;
    $epsilon = max($epsilonFP, 0.01);  // minimal phantom weight
    return $nC / ($totalWeight + $epsilon);
}

/**
 * Group nodes by weight (weight => array of nodeIDs).
 */
private function groupNodesByWeight(array $weights): array {
    $groups = [];
    foreach ($weights as $nodeId => $w) {
        $groups[$w][] = $nodeId;
    }
    ksort($groups, SORT_NUMERIC); // ascending order of weights
    return $groups;
}

/**
 * Compute cumulative weights using half-weight before for ties,
 * then assign nC-ciles (colorCode) to nodes.
 */
private function assignNCcilesWithTies(array &$V, array $groups, float $scale, int $nC): void {
    $cumulative = 0.0;

    foreach ($groups as $weight => $nodeIDs) {
        $nb = count($nodeIDs);
        // CW_i = sum of previous weights + half of this group
        $CW_i = $cumulative + 0.5 * $weight * $nb;

        // Compute nC-cile index
        $index = (int) floor($CW_i * $scale);

        // Assign same index to all nodes in this tied group
        foreach ($nodeIDs as $nodeId) {
            $V[$nodeId]['attributes']['colorCode'] = $index;
        }

        // Update cumulative sum for next group
        $cumulative += $weight * $nb;
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
            $executable = "dot -Tsvg | sed -n '/<svg/,\$p'";

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
            $uniqueId = uniqid();
            $svg = str_replace('id="graph0"', 'id="graph_' . $uniqueId . '"', $svg);

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
                            //"URL=\"https://segmentprofiler.org/drawgraph/$input/$nodeId\" target=\"_parent\" ".
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
			$incTime = $node['attributes']['timeInclusive'];
			$incTimeInMillisec = number_format($incTime / 1E+6, 3);
			$timeTxt = "($excTimeInMillisec, $incTimeInMillisec)";  
		} else {
			$timeTxt = "";
		}
		$label = $node['attributes']['innerLabel'];
                if (strlen($label) > 130) {
                    $label = substr($label, 0, 130) . "... truncated "; 
                }
		return "$nodeId: $label$timeTxt";
	}
}
