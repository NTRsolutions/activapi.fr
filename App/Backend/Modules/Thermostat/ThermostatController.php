<?php
namespace App\Backend\Modules\Thermostat;

use \OCFram\BackController;
use \OCFram\HTTPRequest;
use \OCFram\HTTPResponse;
use \OCFram\DateFactory;
use \Entity\Thermostat;
use \Entity\ThermostatLog;
use \Debug\Log;

class ThermostatController extends BackController
{   
  public function executeIndex(HTTPRequest $request)
  {
    $this->page->addVar('title', 'Gestion du Thermostat');
    $manager = $this->managers->getManagerOf('Thermostat');	
    $managerPlanif = $this->managers->getManagerOf('ThermostatPlanif');  
    $thermostats =  $manager->getList();  

    foreach ($thermostats as $thermostat) {

      $name="Aucun";
      if($thermostat->planning() > 0){
        $name = $managerPlanif->getNom($thermostat->planning());
      }

      $thermostat->setPlanningName($name);
    }  
   
    $this->page->addVar('thermostats', $thermostats);
  }  

 public function executeUpdate(HTTPRequest $request) {

    $manager = $this->managers->getManagerOf('Thermostat');
    $thermostatPDO =  $manager->getList(); 
    $thermostat = $thermostatPDO[0];
    $prevEtat=$thermostatPDO[0]->etat();
    $postEtat=$request->postData('etat');

    $hydrate = [
    'id'=>$request->postData('id'),
    'nom'=>$request->postData('nom'),
    'modeid'=>$request->postData('modeid'),
    'sensorid'=>$request->postData('sensorid'),
    'planning'=>$request->postData('planning'),
    'manuel'=>$request->postData('manuel'),
    'consigne'=>$request->postData('consigne'),
    'delta'=>$request->postData('delta'),
    'interne'=>$request->postData('interne'),
    'etat'=>$postEtat
    ];

    foreach ($hydrate as $key => $value) {
      if(is_null($value)){
        $this->page->addVar('thermostat', "Update Error : Value ".$key." is null!");
        return;
      }
    }

    $newThermostat =new Thermostat($hydrate);  

    $thermostatLog =new ThermostatLog([
      'etat'=>$prevEtat,
      'consigne'=>$newThermostat->consigne(),
      'delta'=>$newThermostat->delta()
    ]);

    if($thermostat->hasChanged($newThermostat)){
      if($prevEtat!=$postEtat){
        $thermostatLog->setEtat($prevEtat);
        $manager->addThermostatLog($thermostatLog);     
      }

      $thermostatLog->setEtat($postEtat);
      $manager->addThermostatLog($thermostatLog);
      $result="Success";
    }else{
      $result="No need to log : same thermostats";
    }
    
    $manager->modify($newThermostat);

    $this->page->addVar('thermostat', $result);
  }

  public function executeRefreshLog(){

    $manager = $this->managers->getManagerOf('Thermostat');
    $thermostats =  $manager->getList();

    foreach ($thermostats as $thermostat) {
      $prevEtat=$thermostat->etat();   
      $thermostatLog =new ThermostatLog(['etat'=>$prevEtat]);
      $manager->addThermostatLog($thermostatLog);
    }  
  }

  public function executeLog(HTTPRequest $request){

     $manager = $this->managers->getManagerOf('Thermostat');

    $dateMin='now';
    $dateMax='';
    if($request->getExists("dateMin")){
      $dateMin=$request->getData("dateMin");
    }else{
      $dateMin='now';
    }

    if($request->getExists("dateMax")){
      $dateMax=$request->getData("dateMax");
    }else{
      $dateMax=$dateMin;
    }    
   
    $dateMin = new \DateTime($dateMin,new \DateTimeZone('Europe/Paris'));
    $dateMax = new \DateTime($dateMax,new \DateTimeZone('Europe/Paris')); 

    if($dateMin >$dateMax){
      $dateTemp=$dateMax;
      $dateMax=$dateMin;
      $dateMin=$dateTemp;
    }

    $dateMinFull =$dateMin->format("Y-m-d 00:00:00");
    $dateMaxFull =$dateMax->format("Y-m-d 23:59:59"); 
 
    $logList=$manager->getLogListWithDates($dateMinFull,$dateMaxFull);

    $this->page->addVar('logList',$logList);
  } 

}
