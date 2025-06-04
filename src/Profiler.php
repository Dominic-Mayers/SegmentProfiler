<?php
namespace App;

use Graphp\GraphViz\GraphViz; 
use Graphp\Graph\Graph; 

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
	public array $nodes = [];
	public array $arrowsOut = [];
	public array $arrowsIn = [];
	public array $arrowsActive = [];
	public array $nodesActive = [];
	public array $groupsPhase0 = [];
	public array $groupsPhase1 = [];
	private Graph $graph; 
	private GraphViz $graphviz; 
	
	public function __construct() {
		$this->graphviz = new  GraphViz(); 
	}

	private static function getGroupId($prefix) {
		static $n = [];
		$n[$prefix] ??= 1;
		return $prefix . str_pad($n[$prefix]++, 5, '0', STR_PAD_LEFT);
	}

	public function getTree(\Iterator $notesFile) {

		$currentId = $this->rootId;
		$currentNode = $this->nodes[$currentId] = new Node();
		$currentNode->attributes['nodeId'] = $currentId;
		$currentNode->attributes['parentId'] = false;
		$currentNode->attributes['startName'] = 'root';
		$currentNode->attributes['label'] = 'root';

		foreach ($notesFile as $note) {
			if ( empty (trim ($note))) { continue;} 
			$res = $this->processNote($currentId, $currentNode, $note);
			if ($res === "StopRoot") {
				break;
			}
		}
		$this->processNote($currentId, $currentNode, $this->rootId . ":node:endName=none");
		$this->arrowsActive = $this->arrowsOut;
		$this->nodesActive = $this->nodes;
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
			$this->arrowsOut[$currentId][$nodeId] = $newArrow;
			$this->arrowsIn[$nodeId][$currentId] = $newArrow;
			$currentNode = new Node();
			$currentNode->attributes['parentId'] = $currentId;
			$currentNode->attributes['startName'] = $value;
			$currentNode->attributes['label'] = $value;
			$currentNode->attributes['nodeId'] = $nodeId;
			//echo "New currentId $nodeId with parentId $currentId.".PHP_EOL;
			$currentId = $nodeId;
			$this->nodes[$currentId] = $currentNode;
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
				$currentNode = $this->nodes[$currentId];
				//echo "$currentId, which is now the new currentId.".PHP_EOL;
			}
			$currentNode->attributes[$key] = $value;
			//echo "Set group:$key = $value".PHP_EOL;
			if ($midKey === "node:endName") {
				$currentNode->attributes['label'] .= "_$value";
				$currentId = $currentNode->attributes['parentId'];
				if (!empty($currentId)) {
					$currentNode = $this->nodes[$currentId];
					//echo "Moving to parent $currentId after endName".PHP_EOL;
				} else {
					//echo "Stopping the root.".PHP_EOL;
					return "StopRoot";
				}
			}
		}
	}
	
	public function setExclusiveTime() {
		// To be executed on the tree only.
		// When grouping, the exclusive time and the inclusive time are additive.
		$nodes = $this->nodesActive;
		$arrows = $this->arrowsActive;
		foreach ($nodes as $nodeId => $node) {
			$totalTimeChildren = 0;
			$adj = $arrows[$nodeId] ?? []; 
			foreach ( $adj as $targetId => $arrow ) {
				$totalTimeChildren += $nodes[$targetId]->attributes['timeFct'];
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
	}

	public function setColorCode( $nodes = null ) {
		// To be executed on the active graph or active subgraph. 
		
		$cM = $this->cM;
		$V  = $nodes ?? $this->nodesActive; 

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
		// This is only to use the darkest color.
		$adjust = $k - $oldN;

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

	public function createGraph($graphArr = null ): string {
		$cM = $this->cM; 
		$this->graph = new Graph();
		[$V, $A] = $graphArr ?? [$this->nodesActive, $this->arrowsActive];
		$this->setColorCode($V); 
		if (empty($A)) { return "";}
		$gvNodes = [];
		foreach ($A as $adj) {
			foreach ($adj as $arrow) {
				if (! isset($gvNodes[$arrow->sourceId]) ) {
					$source = $gvNodes[$arrow->sourceId] = $this->graph->createVertex();
					$source->setAttribute('id', $arrow->sourceId );
					$source->setAttribute('graphviz.label', $this->getLabel($arrow->sourceId)); 
					$source->setAttribute('graphviz.style', 'filled');
					$source->setAttribute('graphviz.fontname', "Courier-Bold"); 
					$source->setAttribute('graphviz.shape', "rect");
					$source->setAttribute('colorscheme', 'orange9'); 
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
					$target->setAttribute('graphviz.label', $this->getLabel($arrow->targetId)); 
					$target->setAttribute('graphviz.style', 'filled');
					$target->setAttribute('graphviz.fontname', "Courier-Bold"); 
					$target->setAttribute('graphviz.shape', "rect");  
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

	private function getLabel($nodeId) {
		$node = $this->nodes[$nodeId]; 
		if ( isset($node->attributes['timeExclusive'] ) ) {
			$excTime = $node->attributes['timeExclusive'];
			$excTimeInMillisec = number_format($excTime / 1E+6, 1);
			$incTime = $node->attributes['timeFct'];
			$incTimeInMillisec = number_format($incTime / 1E+6, 1);
			$timeTxt = "($excTimeInMillisec, $incTimeInMillisec)";  
		} else {
			$timeTxt = "";
		}
		$grTxt = isset($node->groupId) ? " in " . $node->groupId : "";
		$name = $node->attributes['label']; 
		return "$nodeId: $name$timeTxt$grTxt";
	}
	
	public function createSvgHtml($dot) {
		
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
			$currentNode = $this->nodes[$currentId];

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
		$adjNotInner = [];
		$adjAll = $this->arrowsOut[$nodeId] ?? [];
		foreach ($adjAll as $targetId => $arrow) {
			if (!$this->nodes[$targetId]->groupId) {
				$adjNotInner[$targetId] = $arrow;
			}
		}
		return $adjNotInner;
	}

	public function groupDescendentsPerName() {
		$this->visitNodes([$this, 'beforeChildren_dn'], [$this, 'afterChildren_dn']);
		$this->createActiveGroups();
	}

	private function beforeChildren_dn($currentId) {
		$grps0 =& $this->groupsPhase0;
		$currentNode = $this->nodes[$currentId];
		$label = $currentNode->attributes['label'];
		$grps0[$label][] = $currentId;
	}

	public function afterChildren_dn($currentId) {
		$grps0 = & $this->groupsPhase0;
		$grps1 = & $this->groupsPhase1;
		$currentNode = $this->nodes[$currentId];
		$label = $currentNode->attributes['label'];
		if (!isset($grps0[$label])) {
			return;
		}

		$firstInnerNodeId = $grps0[$label][0];
		if ($firstInnerNodeId === $currentId) {
			isset($grps0[$label][1]) &&
				$grps1[] = $this->addGroup($grps0[$label], "DN", $label);
			unset($grps0[$label]);
		}
	}

	public function groupSiblingsPerName() {
		// For every non innernode, this only groups its non inner children with a same full name.
		$this->visitNodes([$this, 'beforeChildren_sn'], null, [$this, 'init_sn']);
		$this->createActiveGroups();
	}

	private function init_sn() {
		$this->groupsPhase1 = [];
	}

	private function beforeChildren_sn($currentId) {
		$groups = [];
		$adj = $this->getNotInnerArrowsOut($currentId);
		foreach ($adj as $targetId => $arrow) {
			$label = $this->nodes[$targetId]->attributes['label'];
			$groups[$label][] = $targetId;
		}
		foreach ($groups as $label => $group) {
			if (count($group) > 1) {
				$this->groupsPhase1[] = $groupId = $this->addGroup($group, "SN", $label);
			}
		}
	}

	public function fullGroupSiblingsPerName() {
		while (true) {
			$numberNodes = count($this->nodes);
			$this->groupSiblingsPerName();
			$newNumberNodes = count($this->nodes);
			if ($numberNodes == $newNumberNodes) {
				break;
			}
		}
	}

	public function groupSiblingsPerChildrenName() {
		// For every non innernode, this only groups its non inner children with a same full name.
		$this->visitNodes(null, [$this, 'afterChildren_scn'], [$this, 'init_scn']);
		$this->createActiveGroups();
	}

	private function init_scn() {
		$this->groupsPhase1 = [];
	}

	private function afterChildren_scn($currentId) {
		if (isset($this->nodes[$currentId]->groupId)) {
			return;
		}
		$groups = [];
		$adj = $this->getNotInnerArrowsOut($currentId);
		foreach ($adj as $targetId => $arrow) {
			if (isset($this->nodes[$targetId]->groupId)) {
				continue;
			}
			$childrenId = $this->notInnerChildrenNamesId($targetId);
			if (empty($childrenId)) {
				continue;
			}
			$groups[$childrenId][] = $targetId;
		}
		foreach ($groups as $childrenId => $group) {
			if (count($group) > 1) {
				$this->groupsPhase1[] = $groupId = $this->addGroup($group, "SCN");
			}
		}
	}

	private function notInnerChildrenNamesId($nodeId) {
		$childrenNames = [];

		$adj = $this->getNotInnerArrowsOut($nodeId);
		foreach ($adj as $targetId => $arrow) {
			if (isset($this->nodes[$targetId]->groupId)) {
				continue;
			}
			$childrenNames[] = $arrow->calls . "*" . $this->nodes[$targetId]->attributes['label'];
		}
		sort($childrenNames);
		$id = implode('&', $childrenNames);
		return $id;
	}

	public function getSubGraph($startId, $arrows = null) {
		$arrows ??= $this->arrowsActive;
		$subArrows = [];
		$subNodes  = [];
		$toProcess = [$startId];
		$subNodes[$startId] = $this->nodesActive[$startId];
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
						$subNodes[$targetId] = $this->nodesActive[$targetId];
					}
				}
			} else {
				array_pop($toProcess);
			}
		}
		return [$subNodes, $subArrows];
	}

	private function addGroup($group, $prefix, $label = null) {
		$groupId = self::getGroupId($prefix);
		$this->nodes[$groupId] = new Node();
		$this->nodes[$groupId]->attributes['nodeId'] = $groupId;
		$this->nodes[$groupId]->attributes['label'] = $label ?? $groupId;

		$this->nodes[$groupId]->attributes['timeFct'] = 0;
		$this->nodes[$groupId]->attributes['timeExclusive'] = 0; 
		foreach ($group as $nodeId) {
			$this->nodes[$nodeId]->groupId = $groupId;
			$this->nodes[$groupId]->innerNodesId[] = $nodeId;
			$this->nodes[$groupId]->attributes['timeFct']       += $this->nodes[$nodeId]->attributes['timeFct'];
			$this->nodes[$groupId]->attributes['timeExclusive'] += $this->nodes[$nodeId]->attributes['timeExclusive']; 	
		}
		return $groupId;
	}

	private function createActiveGroups() {

		foreach ($this->groupsPhase1 as $groupId) {
			$this->createGroup($groupId);
		}
		foreach ($this->groupsPhase1 as $groupId) {
			$this->activateGroup($groupId);
		}
	}

	public function createGroup($groupId) {

		$innerNodesId = $this->nodes[$groupId]->innerNodesId;
		foreach ($innerNodesId as $nodeId) {
			$this->arrowsOut[$nodeId] ??= [];
			foreach ($this->arrowsOut[$nodeId] as $targetId => $arrowOut) {
				$this->arrowsOut[$groupId][$targetId] ??= new Arrow($groupId, $targetId, 0);
				$this->arrowsOut[$groupId][$targetId]->calls += $arrowOut->calls;
				$this->arrowsIn[$targetId][$groupId] = $this->arrowsOut[$groupId][$targetId];
			}
			$this->arrowsIn[$nodeId] ??= [];
			foreach ($this->arrowsIn[$nodeId] as $sourceId => $arrowIn) {
				$this->arrowsOut[$sourceId][$groupId] ??= new Arrow($sourceId, $groupId, 0);
				$this->arrowsOut[$sourceId][$groupId]->calls += $arrowIn->calls;
				$this->arrowsIn[$groupId][$sourceId] = $this->arrowsOut[$sourceId][$groupId];
			}
		}
	}

	public function activateGroup($groupId) {

		foreach ($this->nodes[$groupId]->innerNodesId as $nodeId) {
			$this->deactivateNode($nodeId);
		}
		$this->activateNode($groupId);
	}

	public function deactivateGroup($groupId) {

		$this->deactivateNode($groupId);
		foreach ($this->nodes[$groupId]->innerNodesId as $nodeId) {
			$this->activateNode($nodeId);
		}
	}

	public function activateNode($nodeId) {

		$this->nodesActive[$nodeId] = $this->nodes[$nodeId];
		if (isset($this->arrowsIn[$nodeId])) {
			foreach ($this->arrowsIn[$nodeId] as $sourceId => $arrow) {
				if (isset($this->nodesActive[$sourceId])) {
					$this->arrowsActive[$sourceId][$nodeId] = $arrow;
				}
			}
		}

		if (isset($this->arrowsOut[$nodeId])) {
			foreach ($this->arrowsOut[$nodeId] as $targetId => $arrow) {
				if (isset($this->nodesActive[$targetId])) {
					$this->arrowsActive[$nodeId][$targetId] = $arrow;
				}
			}
		}
	}

	public function deactivateNode($nodeId) {

		if (isset($this->arrowsIn[$nodeId])) {
			foreach ($this->arrowsIn[$nodeId] as $sourceId => $arrow) {
				unset($this->arrowsActive[$sourceId][$nodeId]);
			}
			if (empty($this->arrowsActive[$sourceId])) {
				unset($this->arrowsActive[$sourceId]);
			}
		}
		if (isset($this->arrowsOut[$nodeId])) {
			foreach ($this->arrowsOut[$nodeId] as $targetId => $arrow) {
				unset($this->arrowsActive[$nodeId][$targetId]);
			}
			if (empty($this->arrowsActive[$nodeId])) {
				unset($this->arrowsActive[$nodeId]);
			}
		}
		unset($this->nodesActive[$nodeId]);
	}
}
