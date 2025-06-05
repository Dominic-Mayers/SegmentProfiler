<?php

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Profiler;
use Graphp\GraphViz\GraphViz; 

class SegmentProfilerController extends AbstractController {
	
	private GraphViz $gv;
	private \SplFileObject $notesFile;
	
	public function __construct(Profiler $profiler) {
		$this->gv = new GraphViz(); 
		$this->gv->setFormat('svg'); 		
		$this->notesFile = new \SplFileObject('../src/Fixtures/mediawiki.Segment.profile');
		$profiler->getTree($this->notesFile);
		$profiler->setExclusiveTime();
	}
	
	#[Route('/tree')]
	public function showTree(Profiler $profiler): Response {
        $profiler->createGraph();
		$svgHtml = $this->gv->createImageHtml($profiler->graph); 
		return new Response(
            '<html><body>'.$svgHtml.'</body></html>'
        );
	}
		
	#[Route('/drawgraph/{startId}/{toUngroup}', name: 'drawgraph' )]
	public function drawGraph(Profiler $profiler, $startId = null, $toUngroup = ""): Response {
		$this->setDefaultGroups ($profiler);
		if ( !empty($toUngroup) ) {
			$toUngroupArr = explode("_", substr($toUngroup, 0, -1));
			foreach($toUngroupArr as $groupId) {
				$profiler->deactivateGroup($groupId);
			}
		}
		$profiler->setColorCode(); 
        $profiler->createGraph($profiler->getSubGraph($startId), $color = false, $toUngroup);
		$svgHtml = $this->gv->createImageHtml($profiler->graph); 
		return new Response(
            '<html><body>'.$svgHtml.'</body></html>'
        );
	}

	private function setDefaultGroups (Profiler $profiler) {
		$profiler->fullGroupSiblingsPerName();
		$profiler->groupDescendentsPerName();
		$profiler->groupSiblingsPerChildrenName();
		$profiler->fullGroupSiblingsPerName();
	}
	
}