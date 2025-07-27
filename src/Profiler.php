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

        private string $rootId = "00000";
        private bool $changeSinceLastSet = false; 
        public ActiveGraph $activeGraph;
        public TotalGraph $totalGraph;
        private array $doNotDelete  = []; 
        private array $groupsPhase0 = [];
	private array $groupsPhase1 = [];
        private array $grpTraversalKey = []; 
	public Graph $graph;
	public GraphViz $graphviz;
	static $nEnd = 0; 
	
	public function __construct(
		private UrlGeneratorInterface $urlGenerator,
                private EntityManagerInterface $entityManager,
	) {
		$this->graphviz    = new GraphViz();
                $this->activeGraph = new ActiveGraph(); 
                $this->totalGraph  = new TotalGraph();
	}
	
	private static function getGroupId($prefix) {
		static $n = [];
		$n[$prefix] ??= 1;
		return $prefix . str_pad($n[$prefix]++, 5, '0', STR_PAD_LEFT);
	}

	public function getTree(\Iterator $notesFile, ) {
                // Create the root.
		$currentId = $this->rootId;
		$currentNode = $this->totalGraph->nodes[$currentId] = new Node('T');
		$currentNode->attributes['nodeId'] = $currentId;
		$currentNode->attributes['parentId'] = null;
		$currentNode->attributes['startName'] = 'root';
		$currentNode->attributes['label'] = 'root';                
                
                
		foreach ($notesFile as $note) {
			if ( empty (trim ($note))) { continue;}

			$res = $this->processNote($currentId, $currentNode,  $note);
                        
			if ($res === "StopRoot") {
				break;
			}
		}
		$this->processNote($currentId, $currentNode, $this->rootId . ":node:endName=none");
		$this->activeGraph->arrowsOut = $this->totalGraph->arrowsOut;
		$this->activeGraph->arrowsIn = $this->totalGraph->arrowsIn;
		$this->activeGraph->nodes = $this->totalGraph->nodes;
	}

	private function processNote(&$currentId, &$currentNode, $note) {
		//echo "Note: ".trim($note).PHP_EOL;
		$noteArr = explode("=", $note);
		$topKey = trim($noteArr[0]);
		$value = trim($noteArr[1]);
		$topKeyArr = explode(":", $topKey, 2);
		$nodeId = $topKeyArr[0];
		$midKey = $topKeyArr[1];
		$midKeyArr = explode(":", $midKey, 2);
		$type = $midKeyArr[0];
		$key = $midKeyArr[1];

		if ($midKey == "node:startName") {
			$newArrow = new Arrow($currentId, $nodeId);                   
			$this->totalGraph->arrowsOut[$currentId][$nodeId] = $newArrow;
			$this->totalGraph->arrowsIn[$nodeId][$currentId] = $newArrow;
                        
                        // This is the new currentNode !
			$currentNode = new Node('T');
			$currentNode->attributes['parentId'] = $currentId;
			$currentNode->attributes['startName'] = $value;
			$currentNode->attributes['label'] = $value;
			$currentNode->attributes['nodeId'] = $nodeId;
                                                
			//echo "New currentId $nodeId with parentId $currentId.".PHP_EOL;
			$currentId = $nodeId;
			$this->totalGraph->nodes[$currentId] = $currentNode;
		} else {
			while ($currentId !== $nodeId) {
				if (!$currentNode->attributes['parentId']) {
					// Stopped by parent up to root, 
					echo "Error: Could not find segment to stop $nodeId." . PHP_EOL;
					exit();
				}
				echo "Stopping node $currentId by its parent "; 
				$currentNode->attributes['stoppedByParent'] = true;
				$currentNode->attributes['endName'] = 'none';
                                                                
				$currentId = $currentNode->attributes['parentId'];
				$currentNode = $this->totalGraph->nodes[$currentId];
				echo "$currentId, which is now the new currentId. <br>".PHP_EOL;
			}
			$currentNode->attributes[$key] = $value;
			//echo "Set group:$key = $value".PHP_EOL;
			if ($midKey === "node:endName") {
				$newvalue = $currentNode->attributes['label'] .= "_$value";
                                $this->setExclusiveTimeOfNode($currentId); 
				$currentId = $currentNode->attributes['parentId'];
				if (!empty($currentId)) {
					$currentNode = $this->totalGraph->nodes[$currentId];
					//echo "Moving to parent $currentId after endName".PHP_EOL;
				} else {
					//echo "Stopping the root.".PHP_EOL;
					return "StopRoot";
				}
			}
		}
	}
	
        private function setExclusiveTimeOfNode($currentId) {
	    // To be executed on the tree only.
	    $totalTimeChildren = 0;
            $node = $this->totalGraph->nodes[$currentId]; 
	    $adj = $this->totalGraph->arrowsOut[$currentId] ?? []; 
	    foreach ( $adj as $targetId => $arrow ) {
	        $totalTimeChildren += $this->totalGraph->nodes[$targetId]->attributes['timeFct'];
	    }
	    if ( isset( $node->attributes['timeFct'] ) ) {
	        $timeExclusive = $node->attributes['timeFct'] - $totalTimeChildren;
		$node->attributes[ 'timeExclusive' ] = $timeExclusive;
	    } else {
		// Normally, this should only happen for the root.
		$node->attributes['timeFct'] = $totalTimeChildren;
		$node->attributes[ 'timeExclusive' ] = 0; 
	    }
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
        
	public function createGraphViz($input = 'input', $graphArr = null , $color=true, $toUngroup =  ''): string {
		$cM = $this->cM; 
		$this->graph = new Graph();
		[$V, $A, $R] = $graphArr ?? [$this->activeGraph->nodes, $this->activeGraph->arrowsOut, $this->rootId];
		if ($color) {
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
	      
	private function visitNodes($beforeChildren = null, $afterChildren = null, $init = null, $finalize = null) {
		isset($init) && $init();
		$toProcess = [$this->rootId];
		$visited = [];
		while (true) {

			if ($toProcess == []) {
				break;
			}

			$currentId = end($toProcess);
			$currentNode = $this->totalGraph->nodes[$currentId];

			if ($currentNode->groupId) {
				$visited[$currentId] = true;
				array_pop($toProcess);
				continue;
			}

			If (!isset($visited[$currentId]) || !$visited[$currentId]) {
				isset($beforeChildren) && $beforeChildren($currentId);
				$visited[$currentId] = true;
				$adj = $this->getNotInnerArrowsOut($currentId);
				foreach ($adj as $targetId => $arrow) {
					if (
						!isset($visited[$targetId]) || !$visited[$currentId]
					) {
						$toProcess[] = $targetId;
					}
				}
			} else {
				isset($afterChildren) && $afterChildren($currentId);
				array_pop($toProcess);
			}
		}
		isset($finalize) && $finalize();
	}

	private function getNotInnerArrowsOut($nodeId) {
                // This is the same as using the active graph in its original definition, just
                // after the creation of the existing groups, but not after group desactivations.
		$adjNotInner = [];
		$adjAll = $this->totalGraph->arrowsOut[$nodeId] ?? [];
		foreach ($adjAll as $targetId => $arrow) {
			if ( $this->totalGraph->nodes[$targetId]->groupId === null ) {
				$adjNotInner[$targetId] = $arrow;
			}
		}
		return $adjNotInner;
	}

	public function groupDescendentsPerName() {
		$this->visitNodes([$this, 'beforeChildren_dn'], [$this, 'afterChildren_dn'], [$this, 'init_dn']);
		$this->createActiveGroups();
                //echo PHP_EOL; 
	}

        private function init_dn () {
            $this->groupsPhase1 = []; 
            $this->changeSinceLastSet = false; 
            $this->doNotDelete = [];
            //echo "Starting DN".PHP_EOL."-----------".PHP_EOL;
        }

	private function beforeChildren_dn($currentId) {
		$grps0 =& $this->groupsPhase0;
		$currentNode = $this->totalGraph->nodes[$currentId];
		$label = $currentNode->attributes['label'];
		$grps0[$label][] = $currentId;
                if (count ($grps0[$label]) == 2 ) {
                    //echo "Added node $currentId with label $label to a DN or DNX group, after {$grps0[$label][0]}".PHP_EOL;
                }
                if (count ($grps0[$label]) > 2 ) {
                    //echo "Added node $currentId with label $label to a DN or DNX group".PHP_EOL;
                }
                if (
                    $this->totalGraph->nodes[$currentId]->type !== "SN" && 
                    $this->totalGraph->nodes[$currentId]->type !== "DN" &&
                    $this->totalGraph->nodes[$currentId]->type !== "T" )
                {
                    $this->doNotDelete[$label] = true;     
                }
	}

	public function afterChildren_dn($currentId) {
		$grps0 = & $this->groupsPhase0;
		$grps1 = & $this->groupsPhase1;
		$currentNode = $this->totalGraph->nodes[$currentId];
		$label = $currentNode->attributes['label'];
		if (!isset($grps0[$label])) {
			return;
		}

		$firstInnerNodeId = $grps0[$label][0];
		if ($firstInnerNodeId === $currentId) {
			if (isset($grps0[$label][1])) {
                                $type = isset($this->doNotDelete[$label]) && $this->doNotDelete[$label] ? "DNX" : "DN"; 
 				$grps1[] = $groupId = $this->addGroup($grps0[$label], $type, $label); 
                                $this->changeSinceLastSet = true;
                        }
			unset($grps0[$label]);
                        unset($this->doNotDelete[$label]);
		}
	}

	public function groupSiblingsPerName() {
		// For every non innernode, this only groups its non inner children with a same full name.
		$this->visitNodes([$this,  'beforeChildren_sn'], null, [$this, 'init_sn']);
		$this->createActiveGroups(true);
	}

	private function init_sn() {
                $this->changeSinceLastSet = false; 
		$this->groupsPhase1 = [];
                //echo "Starting SN".PHP_EOL."-----------".PHP_EOL;
	}

	private function beforeChildren_sn($currentId) {
		$groups = [];
		$adj = $this->getNotInnerArrowsOut($currentId); 
                $type = "SN"; 
		foreach ($adj as $targetId => $arrow) {
                        if (count($this->activeGraph->arrowsIn[$targetId]) > 1) {continue;}
			$label = $this->totalGraph->nodes[$targetId]->attributes['label'];
                        if (
                            $this->totalGraph->nodes[$targetId]->type !== "SN" && 
                            $this->totalGraph->nodes[$targetId]->type !== "DN" &&
                            $this->totalGraph->nodes[$targetId]->type !== "T" )
                        {
                            $type = "SNX";     
                        }
			$groups[$label][] = $targetId;
                        if (count($groups[$label]) == 2) {
                            //echo "Added node $targetId to a group with label $label, after {$groups[$label][0]}".PHP_EOL;
                        }
                        if (count($groups[$label]) > 2) {
                            //echo "Added node $targetId to a group with label $label".PHP_EOL;
                        }                        
		}
		foreach ($groups as $label => $group) {
                    if (count($group) > 1) {
			$this->groupsPhase1[] = $groupId = $this->addGroup($group, $type, $label);
                        $this->changeSinceLastSet = true;                         
                        //echo "Added new group $groupId".PHP_EOL; 
                    }
		}
	}

	public function fullGroupSiblingsPerName() {
		while (true) {
			$this->groupSiblingsPerName();
			if (!$this->changeSinceLastSet) {
                                //echo "No group created".PHP_EOL. PHP_EOL; 
				break;
			}
		}
	}

	public function groupSiblingsPerChildrenName() {
		// For every non innernode, this only groups its non inner children with a same full name.
                //echo "Starting SCN".PHP_EOL."-----------".PHP_EOL; 
		$this->visitNodes([$this, 'beforeChildren_scn'], null, [$this, 'init_scn']);
		$this->createActiveGroups();
                //echo PHP_EOL; 
	}

	private function init_scn() {
		$this->groupsPhase1 = [];
                $this->changeSinceLastSet = false; 
	}

	private function beforeChildren_scn($currentId) {
		$groups = []; 
		$adj = $this->getNotInnerArrowsOut($currentId);
		foreach ($adj as $targetId => $arrow) {
                        if (count($this->totalGraph->arrowsIn[$targetId]) > 1) {
                            continue;
                        }
			$adjacentNames = $this->getAdjacentNames($targetId);
			if (empty($adjacentNames)) {
				continue;
			}
			$label = "Parents of ". implode('&', array_keys($adjacentNames));
			$groups[$label][] = $targetId;
                        if (count($groups[$label]) == 2) {
                            //echo "Added node $targetId to a group with label $label, after {$groups[$label][0]}".PHP_EOL;
                        }
                        if (count($groups[$label]) > 2) {
                            //echo "Added node $targetId to a group with label $label".PHP_EOL;
                        }                        
		}
		foreach ($groups as $label => $group) {
			if (count($group) > 1) {
				$this->groupsPhase1[] = $groupId = $this->addGroup($group, "SCN", $label);
                                $this->changeSinceLastSet = true;
			}
		}
	}

	private function getAdjacentNames($nodeId) {
		$childrenNames = [];

		$adj = $this->getNotInnerArrowsOut($nodeId);
		foreach ($adj as $targetId => $arrow) {
			if (isset($this->totalGraph->nodes[$targetId]->groupId)) {
				continue;
			}
			$childrenNames[$this->totalGraph->nodes[$targetId]->attributes['label']] ??= 0;
			$childrenNames[$this->totalGraph->nodes[$targetId]->attributes['label']] += $arrow->calls;
		}
		ksort ($childrenNames);
		return $childrenNames;
	}

	public function getSubGraph($startId, $arrows = null) : array {
		$startId ??= $this->rootId; 
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

	private function addGroup($group, $type, $label) {
                if ( count($group) == 1)  {
                        // Singleton are represented by their inner node
                        $groupId = $group[0];
                        if ( $this->totalGraph->nodes[$groupId]->groupId !== null) {
                            echo "Warning: resetting groupId of innernode of singleton $groupId"; 
                            $this->totalGraph->nodes[$groupId]->groupId = null; 
                        }
                        //echo "Taking $groupId as a singleton".PHP_EOL; 
                        return $groupId;  
                }
		$groupId = self::getGroupId($type);
		$this->totalGraph->nodes[$groupId] = new Node($type);
		$this->totalGraph->nodes[$groupId]->attributes['nodeId'] = $groupId;
		$this->totalGraph->nodes[$groupId]->attributes['label'] = $label;

		$this->totalGraph->nodes[$groupId]->attributes['timeFct'] = 0;
		$this->totalGraph->nodes[$groupId]->attributes['timeExclusive'] = 0; 
		foreach ($group as $innernodeId) {
			$this->totalGraph->nodes[$innernodeId]->groupId = $groupId;
			$this->totalGraph->nodes[$groupId]->innerNodesId[] = $innernodeId;
			$this->totalGraph->nodes[$groupId]->attributes['timeFct']       += $this->totalGraph->nodes[$innernodeId]->attributes['timeFct'];
			$this->totalGraph->nodes[$groupId]->attributes['timeExclusive'] += $this->totalGraph->nodes[$innernodeId]->attributes['timeExclusive'];
                }
                //echo "Added group $groupId with label $label".PHP_EOL; 
		return $groupId;
	}

	private function createActiveGroups(bool $permanent = false) {

		foreach ($this->groupsPhase1 as $groupId) {
                        if (count($this->totalGraph->nodes[$groupId]->innerNodesId) === 1  ) {continue;} 
			$this->createGroup($groupId);
		}
		foreach ($this->groupsPhase1 as $groupId) {
                        if (count($this->totalGraph->nodes[$groupId]->innerNodesId) === 1  ) {continue;} 
			$this->activateGroup($groupId, $permanent);
		}
	}

	public function createGroup($groupId) {

		$innerNodesId = $this->totalGraph->nodes[$groupId]->innerNodesId;
		foreach ($innerNodesId as $nodeId) {
			$arrowsOut = $this->totalGraph->arrowsOut[$nodeId] ?? [];
			foreach ($arrowsOut as $targetId => $arrowOut) {
				$this->totalGraph->arrowsOut[$groupId][$targetId] ??= new Arrow($groupId, $targetId, 0);
				$this->totalGraph->arrowsOut[$groupId][$targetId]->calls += $arrowOut->calls;
                                //echo "Adding {$arrowOut->calls} to arrow from $groupId to $targetId because of $nodeId".PHP_EOL; 
				$this->totalGraph->arrowsIn[$targetId][$groupId] = $this->totalGraph->arrowsOut[$groupId][$targetId];
			}
			$arrowsIn = $this->totalGraph->arrowsIn[$nodeId] ?? [];
			foreach ($arrowsIn as $sourceId => $arrowIn) {
				$this->totalGraph->arrowsOut[$sourceId][$groupId] ??= new Arrow($sourceId, $groupId, 0);
				$this->totalGraph->arrowsOut[$sourceId][$groupId]->calls += $arrowIn->calls;
                                //echo "Adding {$arrowIn->calls} to arrow from $sourceId to $groupId because of $nodeId".PHP_EOL; 
				$this->totalGraph->arrowsIn[$groupId][$sourceId] = $this->totalGraph->arrowsOut[$sourceId][$groupId];
			}
		}
	}

	public function activateGroup($groupId, $permanent) {

		foreach ($this->totalGraph->nodes[$groupId]->innerNodesId as $nodeId) {
			$this->deactivateNode($nodeId, $permanent);
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
                                if ($permanent) {
                                    unset($this->totalGraph->arrowsOut[$sourceId][$nodeId]);
                                    unset($this->totalGraph->arrowsIn[$nodeId][$sourceId]);
                                }
			}
			if (empty($this->activeGraph->arrowsOut[$sourceId])) {
				unset($this->activeGraph->arrowsOut[$sourceId]);
			}
   			if (empty($this->activeGraph->arrowsIn[$nodeId])) {
				unset($this->activeGraph->arrowsIn[$nodeId]);
			}
                        if ($permanent) {
			    if (empty($this->totalGraph->arrowsOut[$sourceId])) {
				unset($this->totalGraph->arrowsOut[$sourceId]);
			    }
   			    if (empty($this->totalGraph->arrowsIn[$nodeId])) {
				unset($this->totalGraph->arrowsIn[$nodeId]);
			    }
                        }
		}
		if (isset($this->totalGraph->arrowsOut[$nodeId])) {
			foreach ($this->totalGraph->arrowsOut[$nodeId] as $targetId => $arrow) {
				unset($this->activeGraph->arrowsOut[$nodeId][$targetId]);
                                unset($this->activeGraph->arrowsIn[$targetId][$nodeId]);
                                if ($permanent) {
				    unset($this->totalGraph->arrowsOut[$nodeId][$targetId]);
                                    unset($this->totalGraph->arrowsIn[$targetId][$nodeId]);                                    
                                }
			}
			if (empty($this->activeGraph->arrowsOut[$nodeId])) {
				unset($this->activeGraph->arrowsOut[$nodeId]);
			}
			if (empty($this->activeGraph->arrowsIn[$targetId])) {
				unset($this->activeGraph->arrowsIn[$targetId]);
			}
                        if ($permanent) {                            
			    if (empty($this->totalGraph->arrowsOut[$nodeId])) {
				unset($this->totalGraph->arrowsOut[$nodeId]);
			    }
			    if (empty($this->totalGraph->arrowsIn[$targetId])) {
				unset($this->totalGraph->arrowsIn[$targetId]);
			    }
                        }
		}
		unset($this->activeGraph->nodes[$nodeId]);
                if ($permanent) {
		    unset($this->totalGraph->nodes[$nodeId]);
                }
	}
}
