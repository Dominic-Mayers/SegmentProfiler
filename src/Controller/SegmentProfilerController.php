<?php

namespace App\Controller;

use App\ActiveGraph; 
use App\Backend;
use App\UI;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SegmentProfilerController extends AbstractController {
    
        #[Route('/expandGroup/{input}/{groupId}', name: 'expandGroup')]
        public function expandGroup(Backend $backend, $input,  $groupId) : JsonResponse {
                $backend->restoreGraph($input); 
                if ( ! $backend->fromFile ) {
                    echo "Error: Graphs $input not found on files." .PHP_EOL; 
                    exit(); 
                }
                $state = $backend->getGroup ($groupId);
                $state['graphId'] = $input; 
                return $this->json($state); 
        }

        #[Route('/show/{input}', name: 'show')]
        public function show(Backend $backend, $input) : Response {
                $state = $this->computeState ($backend, $input);  
                $state['graphId'] = $input; 
                return $this->render('graph/show.html.twig', [
                    'graph_state' => $state,
                ]);
        }

        #[Route('/showtest/{input}/{startId}/{toUngroup}', name: 'showtest')]
        public function showTest(Backend $backend, UI $ui, $input, $startId = null, $toUngroup = "") : Response {
                $svg = $this->computeSvg ($backend, $ui, $input, $toUngroup, $startId);   
                return $this->render('graph/show-test.html.twig', [
                    'svg' => $svg,
                ]);
        }
	
        private function computeState (Backend $backend, $input,) {
                $state = $backend->computeGraphStates ($input);
                $state['graphId'] = $input; 
                return $state; 
        }

        private function computeSvg (Backend $backend, UI $ui, $input, $toUngroup, $startId) {
                set_time_limit(600); 
		$backend->computeGraphStates ($input);
		if ( !empty($toUngroup) ) {
			$toUngroupArr = explode("_", substr($toUngroup, 0, -1));
			foreach($toUngroupArr as $groupId) {
				$this->graphTransformationAPI->deactivateGroup($groupId);
			}
		}
                $ui->setColorCode();
                $subGraph = $ui->getSubGraph($startId); 
                $dotString = $ui->activeGraphToDot($input, $subGraph); 
                $svg = $ui->dot2svg($dotString);
                return $svg; 
        }
}