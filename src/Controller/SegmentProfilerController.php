<?php

namespace App\Controller;

use App\Profiler;
use Graphp\GraphViz\GraphViz; 
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages; 
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SegmentProfilerController extends AbstractController {
	
	private GraphViz $gv;
	private \SplFileObject $notesFile;
        private Packages $packages;
	
	public function __construct(Packages $packages) {
		$this->gv = new GraphViz(); 
		$this->gv->setFormat('svg');
                $this->packages = $packages;
	}
	
	#[Route('/tree/{input}')]
	public function showTree(Profiler $profiler, $input): Response {
                $this->setTree($profiler, $input); 
                $profiler->setColorCode();
                $profiler->createGraphViz();
                $svgHtml = $this->gv->createImageHtml($profiler->graph); 
                return new Response(
                    '<html><body>'.$svgHtml.'</body></html>'
                );
	}

	#[Route('/drawgraph/{input}/{startId}/{toUngroup}', name: 'drawgraph' )]
	public function drawGraph(Profiler $profiler, $input, $startId = null, $toUngroup = ""): Response {
                set_time_limit(600); 
		$this->setDefaultGroups ($profiler, $input);
		if ( !empty($toUngroup) ) {
			$toUngroupArr = explode("_", substr($toUngroup, 0, -1));
			foreach($toUngroupArr as $groupId) {
				$profiler->deactivateGroup($groupId);
			}
		}
		$profiler->setColorCode(); 
                $script = $profiler->createGraphViz($input, $profiler->getSubGraph($startId), false, $toUngroup);
		$svgHtml = $this->gv->createImageData($profiler->graph);
                $url = "/js/dropdown.js"; //$this->packages->getUrl('js/dropdown.js');
		return new Response(
                    '<!DOCTYPE html><html><head><script src='.$url.' defer ></script></head><body>'.$svgHtml.'</body></html>'
                );
	}

	private function setTree (Profiler $profiler, $input) {
		$this->notesFile = new \SplFileObject('../src/Fixtures/'.$input.'.profile');
        	$profiler->totalGraph->getTree($this->notesFile);
		//$profiler->setExclusiveTime();
        }

	private function setDefaultGroups (Profiler $profiler, $input) {
                $filenameTotal  = '../input/Graphs/'.$input.'.totgraph'; 
                $filenameActive = '../input/Graphs/'.$input.'.actgraph'; 
                if (false && file_exists ($filenameTotal) && file_exists ($filenameActive) ) {
                    $profiler->restoreGraphFromFile($filenameTotal,  false);
                    $profiler->restoreGraphFromFile($filenameActive, true);
                    return;
                }
                $this->setTree($profiler, $input);
                //$profiler->groupPerPath(); 
                $profiler->groupSiblingsPerPath(); 
                $profiler->groupSiblingsPerName();
                $profiler->groupDescendentsPerName();
                $profiler->groupSiblingsPerChildrenName(); 

                $profiler->createDefaultActiveGraph();

                $profiler->saveGraphInFile('../input/Graphs/'.$input.'.totgraph', false); 
                $profiler->saveGraphInFile('../input/Graphs/'.$input.'.actgraph', true);
        }
}