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
                $profiler->createDefaultActiveGraph();
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
                $profiler->createGraphViz($input, $profiler->getSubGraph($startId), false, $toUngroup);
                //$script  = $this->gv->createScript($profiler->graph);
		$svgHtml = $this->gv->createImageData($profiler->graph);
                $url = "/js/dropdown.js"; //$this->packages->getUrl('js/dropdown.js');
		return new Response(
                    '<!DOCTYPE html><html><head><script src='.$url.' defer ></script></head><body>'.$svgHtml.'</body></html>'
                );
	}

	private function setTree (Profiler $profiler, $input) {
		$this->notesFile = new \SplFileObject('../src/Fixtures/'.$input.'.profile');
        	$profiler->totalGraph->getTree($this->notesFile);
                $profiler->setTreeKey();
                $profiler->setTreeKeyWithEmpty();
                //xdebug_break();
        }

	private function setDefaultGroups (Profiler $profiler, $input) {
                $filenameTotal  = __DIR__ . '/../../input/Graphs/'.$input.'.totgraph'; 
                $filenameActive = __DIR__ . '/../../input/Graphs/'.$input.'.actgraph'; 
                if (false & file_exists ($filenameTotal) && file_exists ($filenameActive) ) {
                    $profiler->restoreGraphFromFile($filenameTotal,  false);
                    $profiler->restoreGraphFromFile($filenameActive, true);
                    return;
                }
                // Of course, it is pointless to modify below if the graphs are stored in files. 
                $this->setTree($profiler, $input);
                $profiler->groupCTwe();
                $profiler->groupTwe();
                $profiler->groupCTDwe();
                //$profiler->groupDOnce("CT19");
                //$profiler->groupDOnce("CT20");
                //$profiler->groupDOnce("CT23");
                //$profiler->groupDOnce("CT369");
                //$profiler->groupDOnce("CT976");
                //$profiler->groupDOnce("CT1133");
                //$profiler->groupDOnce("CT371");
                //$profiler->groupDOnce("CT379");
                //$profiler->groupDOnce("CT381");
                //$profiler->groupDOnce("CT409");
                //$profiler->groupDOnce("CT410");
                //$profiler->groupDOnce("CT562");
                //$profiler->groupDOnce("CT599");
                //$profiler->groupDOnce("CT644");
                //$profiler->groupDOnce("CT652");
                //$profiler->groupDOnce("CT1022");
                //$profiler->groupDOnce("CT1030");
                //$profiler->groupDOnce("CT1359");
                //$profiler->groupDOnce("CT1380");
                //$profiler->groupDOnce("CT1439");
                //$profiler->groupDOnce("CT");
                //$profiler->groupDOnce("CT");
                //$profiler->groupDOnce("CT");
                //$profiler->groupDOnce("CT");
                //$profiler->groupT();
                
                $profiler->createDefaultActiveGraph();

                $profiler->saveGraphInFile($filenameTotal, false); 
                $profiler->saveGraphInFile($filenameActive, true);
        }
}