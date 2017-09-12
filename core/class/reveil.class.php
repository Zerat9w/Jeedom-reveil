<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
class reveil extends eqLogic {
	public static function deamon_info() {
		$return = array();
		$return['log'] = 'reveil';
		$return['launchable'] = 'ok';
		$return['state'] = 'ok';
		foreach(eqLogic::byType('reveil') as $reveil){
			if($reveil->getIsEnable() && $reveil->getCmd(null,'isArmed')->execCmd()){
				$cron = cron::byClassAndFunction('reveil', 'pull',array('id' => $reveil->getId()));
				if (!is_object($cron)) 	{	
					$return['state'] = 'nok';
					return $return;
				}
			}
		}
		return $return;
	}
	public static function deamon_start($_debug = false) {
		log::remove('reveil');
		self::deamon_stop();
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') 
			return;
		if ($deamon_info['state'] == 'ok') 
			return;
		foreach(eqLogic::byType('reveil') as $reveil){
			if($reveil->getIsEnable() && $reveil->getCmd(null,'isArmed')->execCmd()){
				$Schedule=$reveil->NextStart();
			}
		}
	}
	public static function deamon_stop() {	
		foreach(eqLogic::byType('reveil') as $reveil){
			$cron = cron::byClassAndFunction('reveil', 'pull',array('id' => $reveil->getId()));
			if (is_object($cron)) 	
				$cron->remove();
			$cron = cron::byClassAndFunction('reveil', 'SimulAubeDemon');
			while(is_object($cron)) {
				$cron->stop();
				$cron->remove();						
				$cron = cron::byClassAndFunction('reveil', 'SimulAubeDemon');
			}
				
		}
	}
	public function postSave() {
		$isArmed=self::AddCommande($this,"Etat activation","isArmed","info","binary",false,'lock');
		$isArmed->event(true);
		$Armed=self::AddCommande($this,"Activer","armed","action","other",true,'lock');
		$Armed->setValue($isArmed->getId());
		$Armed->setConfiguration('state', '1');
		$Armed->setConfiguration('armed', '1');
		$Armed->save();
		$Released=self::AddCommande($this,"Desactiver","released","action","other",true,'lock');
		$Released->setValue($isArmed->getId());
		$Released->save();
		$Released->setConfiguration('state', '0');
		$Released->setConfiguration('armed', '1');
		if($this->getIsEnable() && $this->getCmd(null,'isArmed')->execCmd()){
			$this->NextStart();
		}
		else {
			$cron = cron::byClassAndFunction('reveil', 'pull',array('id' => $this->getId()));
			if (is_object($cron)) 	
				$cron->remove();
		}
	}
	public function postRemove() {
		$cron = cron::byClassAndFunction('reveil', 'pull',array('id' => $this->getId()));
		if (is_object($cron)) 	
			$cron->remove();
		$cron = cron::byClassAndFunction('reveil', 'SimulAubeDemon');
		while(is_object($cron)) {
			$cron->stop();
			$cron->remove();						
			$cron = cron::byClassAndFunction('reveil', 'SimulAubeDemon');
		}
	}
	public function toHtml($_version = 'dashboard') {
		if ($this->getIsEnable() != 1) {
			return '';
		}
		$version = jeedom::versionAlias($_version);
		if ($this->getDisplay('hideOn' . $version) == 1) {
			return '';
		}
		$vcolor = 'cmdColor';
		if ($version == 'mobile') {
			$vcolor = 'mcmdColor';
		}
		$shedule='';
		$cron = cron::byClassAndFunction('reveil', 'pull',array('id' => $this->getId()));
		if (is_object($cron)) 	
			$shedule=$cron->getNextRunDate();
		$replace_eqLogic = array(
			'#id#' => $this->getId(),
			'#background_color#' => $this->getBackgroundColor(jeedom::versionAlias($_version)),
			'#humanname#' => $this->getHumanName(),
			'#name#' => $this->getName(),
			'#height#' => $this->getDisplay('height', 'auto'),
			'#width#' => $this->getDisplay('width', 'auto'),
			'#cmdColor#' => $cmdColor,
			'#shedule#'=> $shedule
		);
		$action = '';
		$cmdColor = ($this->getPrimaryCategory() == '') ? '' : jeedom::getConfiguration('eqLogic:category:' . $this->getPrimaryCategory() . ':' . $vcolor);
		foreach ($this->getCmd() as $cmd) {
			if ($cmd->getIsVisible() == 1) {
				if ($cmd->getDisplay('hideOn' . $version) == 1) 
					continue;
				if ($cmd->getDisplay('forceReturnLineBefore', 0) == 1) 
					$action .= '<br/>';
				$action .= $cmd->toHtml($_version, $cmdColor);
				if ($cmd->getDisplay('forceReturnLineAfter', 0) == 1) 
					$action .= '<br/>';
			}
		}
		$replace_eqLogic['#action#'] = $action;
		return $this->postToHtml($_version, template_replace($replace_eqLogic, getTemplate('core', jeedom::versionAlias($version), 'eqLogic', 'reveil')));
	}
	public static $_widgetPossibility = array('custom' => array(
	        'visibility' => true,
	        'displayName' => false,
	        'optionalParameters' => false,
	));
	public static function AddCommande($eqLogic,$Name,$_logicalId,$Type="info", $SubType='binary',$visible,$Template='') {
		$Commande = $eqLogic->getCmd(null,$_logicalId);
		if (!is_object($Commande))
		{
			$Commande = new reveilCmd();
			$Commande->setId(null);
			$Commande->setName($Name);
			$Commande->setIsVisible($visible);
			$Commande->setLogicalId($_logicalId);
			$Commande->setEqLogic_id($eqLogic->getId());
			$Commande->setType($Type);
			$Commande->setSubType($SubType);
		}
     		$Commande->setTemplate('dashboard',$Template );
		$Commande->setTemplate('mobile', $Template);
		$Commande->save();
		return $Commande;
	}
	public static function pull($_option){
		$reveil=eqLogic::byId($_option['id']);
		if(is_object($reveil)){
			if(!$reveil->getCmd(null,'isArmed')->execCmd())
				exit;
      			//On verifie que l'on a toujours le cron associé
      			$cron = cron::byClassAndFunction('reveil', 'pull',array('id' => $reveil->getId()));
     		 	if (!is_object($cron)) 	{
        			log::add('reveil','debug','Cron manquant on sort');
				exit;
			} else  {
				log::add('reveil','debug','Cron OK on continue');				
			}
			if($reveil->EvaluateCondition()){
				if($reveil->getConfiguration('isHolidays') && $reveil->isHolidays())
					exit;
				foreach($reveil->getConfiguration('Equipements') as $cmd){
					switch($cmd['configuration']['ReveilType']){
						case 'DawnSimulatorEngine';
							log::add('reveil','debug','Lancement daemon simulation d\'aube');
							$cron=$reveil->CreateCron('* * * * *', 'SimulAubeDemon',$cmd);
							$cron->start();
							$cron->run();
						break;
						default:
							log::add('reveil','debug','Exécution de l\'action réveil libre');
							$reveil->ExecuteAction($cmd,'');
						break;
					}
				}
			}
			$reveil->NextStart();
		}
	}
	public function removeSimulAubeDemon($_option){
		$cron = cron::byClassAndFunction('reveil', 'SimulAubeDemon',$_option);
		if(is_object($cron)) {
			log::add('reveil','debug','On termine le daemon de simulation');
			$cron->stop();
			$cron->remove();
		}
	}
	public static function SimulAubeDemon($_option){
		log::add('reveil','debug','Exécution de l\'action réveil simulation d\'aube : '. json_encode($_option));
		$reveil=eqLogic::byId($_option['id']);
		if(is_object($reveil)){
			log::add('reveil','debug','Simulation d\'aube : '.$reveil->getHumanName());
			$time = 0;
			$cmd=cmd::byId($_option['cmd']);
			if(is_object($cmd)){
				while(true){
					$options['slider'] = ceil($reveil->dawnSimulatorEngine(
						$cmd['configuration']['DawnSimulatorEngineType'],
						$time,
						$cmd['configuration']['DawnSimulatorEngineStartValue'], 
						$cmd['configuration']['DawnSimulatorEngineEndValue'], 
						$cmd['configuration']['DawnSimulatorEngineDuration']
					));
					log::add('reveil','debug','Valeur de l\'intensité lumineuse : ' .$options['slider'].'/'.$cmd['configuration']['DawnSimulatorEngineEndValue']." - durée : ".$time."/".$cmd['configuration']['DawnSimulatorEngineDuration']);
					$time++;
					$reveil->ExecuteAction($cmd,$options);

					if($options['slider'] == $cmd['configuration']['DawnSimulatorEngineEndValue'] || ($time - 1) == $cmd['configuration']['DawnSimulatorEngineDuration']){
						log::add('reveil','debug','Fin de la simulation d\'aube');
						$reveil->removeSimulAubeDemon($_option);
						exit;
					}else
						sleep(60);
				}
			}
		}
		
	}
	public function dawnSimulatorEngine($type, $time, $startValue, $endValue, $duration) {
		if($startValue=='')
			$startValue=0;
		if($endValue=='')
			$endValue=100;
		if($duration=='')
			$duration=30;
		switch ($type){
			case 'Linear':
				return $endValue * $time / $duration + $startValue;
			break;
			case 'InQuad':
				$time = $time / $duration;
				return $endValue * pow($time, 2) + $startValue;
			break;
			case 'InOutQuad':
				$time = $time / $duration * 2;
				if ($time < 1)
					return $endValue / 2 * pow($time, 2) + $startValue;
				else
					return -$endValue / 2 * (($time - 1) * ($time - 3) - 1) + $startValue;
			break;
			case 'InOutExpo':
				if ($time == 0)
					return $startValue ;
				if ($time == $duration)
					return $startValue + $endValue;
				$time = $time / $duration * 2;
				if ($time < 1)
					return $endValue / 2 * pow(2, 10 * ($time - 1)) + $startValue - $endValue * 0.0005;
				else{
					$time = $time - 1;
					return $endValue / 2 * 1.0005 * (-pow(2, -10 * $time) + 2) + $startValue;
				}
			break;
			case 'OutInExpo':
				if ($time < $duration / 2)
					return self::equations('OutExpo', $time * 2, $startValue, $endValue / 2, $duration);
				else
					return self::equations('InExpo', ($time * 2) - $duration, $startValue + $endValue / 2, $endValue / 2, $duration);
			break;
			case 'InExpo':
				if($time == 0)
					return $startValue;
				else
					return $endValue * pow(2, 10 * ($time / $duration - 1)) + $startValue - $endValue * 0.001;	
			break;
			case 'OutExpo':
				if($time == $duration)
					return $startValue + $endValue;
				else
					return $endValue * 1.001 * (-pow(2, -10 * $time / $duration) + 1) + $startValue;
			break;
		}
	}
	public function ExecuteAction($cmd,$options='') {
		if (isset($cmd['enable']) && $cmd['enable'] == 0)
			continue;
		try {
			$options = array();
			if (isset($cmd['options'])) 
				$options = $cmd['options'];
			scenarioExpression::createAndExec('action', $cmd['cmd'], $options);
		} catch (Exception $e) {
			log::add('Volets', 'error', __('Erreur lors de l\'éxecution de ', __FILE__) . $action['cmd'] . __('. Détails : ', __FILE__) . $e->getMessage());
		}
		$Commande=cmd::byId(str_replace('#','',$cmd['cmd']));
		if($options=='')
			$options=$cmd['options'];
		if(is_object($Commande)){
			log::add('reveil','debug','Exécution de '.$Commande->getHumanName());
			$Commande->execute($options);
		}		
	}
	public function CreateCron($Schedule, $logicalId, $demon=false) {
		log::add('reveil','debug','Création du cron "'.$logicalId.'" ID = '.$this->getId().' --> '.$Schedule);
		$cron = cron::byClassAndFunction('reveil', $logicalId,array('id' => $this->getId()));
		if (!is_object($cron)) 
			$cron = new cron();
		$cron->setClass('reveil');
		$cron->setFunction($logicalId);
		$options['id']= $this->getId();
		if($demon!= false){
			$options['cmd']= $demon->getId();
			$cron->setDeamon(1);
		}
		$cron->setOption($options);
		$cron->setEnable(1);
		$cron->setSchedule($Schedule);
		$cron->save();
		return $cron;
	}
	public function EvaluateCondition(){
		foreach($this->getConfiguration('Conditions') as $condition){			
			if (isset($condition['enable']) && $condition['enable'] == 0)
				continue;
			$expression = scenarioExpression::setTags($condition['expression']);
			$message = __('Evaluation de la condition : [', __FILE__) . trim($expression) . '] = ';
			$result = evaluate($expression);
			if (is_bool($result)) {
				if ($result) {
					$message .= __('Vrai', __FILE__);
				} else {
					$message .= __('Faux', __FILE__);
				}
			} else {
				$message .= $result;
			}
			log::add('reveil','info',$message);
			if(!$result){
				log::add('reveil','debug','Les conditions ne sont pas remplies');
				return false;
			}
		}
		return true;
	}
	public function NextStart(){
		$ConigSchedule=$this->getConfiguration('Schedule');
		$offset=0;
		if(date('H') > $ConigSchedule["Heure"])
			$offset++;
		if(date('H') == $ConigSchedule["Heure"] && date('i') > $ConigSchedule["Minute"])	
			$offset++;
		for($day=0;$day<7;$day++){
			if($ConigSchedule[date('w')+$day]){
				$offset+=$day;
				$timestamp=mktime ($ConigSchedule["Heure"], $ConigSchedule["Minute"], 0, date("n") , date("j") , date("Y"))+ (3600 * 24) * $offset;
				if($this->getConfiguration('isHolidays') && $this->isHolidays($timestamp))
					continue;
				break;
			}
		}
		$this->CreateCron(date('i H d m w Y',$timestamp), 'pull');
	}
	public function isHolidays($timestamp){
		$dateSearch=mktime(0, 0, 0, date("m",$timestamp), date("d",$timestamp), date("Y",$timestamp));	
		$year = intval(date('Y'));
		$easterDate  = easter_date($year);
		$easterDay   = date('j', $easterDate);
		$easterMonth = date('n', $easterDate);
		$easterYear   = date('Y', $easterDate);

		$holidays = array(
		// Dates fixes
		mktime(0, 0, 0, 1,  1,  $year),  // 1er janvier
		mktime(0, 0, 0, 5,  1,  $year),  // Fête du travail
		mktime(0, 0, 0, 5,  8,  $year),  // Victoire des alliés
		mktime(0, 0, 0, 7,  14, $year),  // Fête nationale
		mktime(0, 0, 0, 8,  15, $year),  // Assomption
		mktime(0, 0, 0, 11, 1,  $year),  // Toussaint
		mktime(0, 0, 0, 11, 11, $year),  // Armistice
		mktime(0, 0, 0, 12, 25, $year),  // Noel

		// Dates variables
		mktime(0, 0, 0, $easterMonth, $easterDay + 1,  $easterYear),
		mktime(0, 0, 0, $easterMonth, $easterDay + 39, $easterYear),
		mktime(0, 0, 0, $easterMonth, $easterDay + 50, $easterYear),
		);
		if(array_search($dateSearch,$holidays) === false){
			log::add('reveil','debug',date("d/m/Y",$dateSearch).' n\'est pas ferié');
			return false;
		}
		log::add('reveil','debug',date("d/m/Y",$dateSearch).' est ferié');
		return true;
	}
}
class reveilCmd extends cmd {
    	public function execute($_options = null) {	
		$Listener=cmd::byId(str_replace('#','',$this->getValue()));
		if (is_object($Listener)) {	
			switch($this->getLogicalId()){
				case 'armed':
					$Listener->event(true);
					$this->getEqLogic()->NextStart();
				break;
				case 'released':
					$Listener->event(false);
					$cron = cron::byClassAndFunction('reveil', 'pull',array('id' => $this->getEqLogic()->getId()));
					if (is_object($cron)) 	
						$cron->remove();
					$cron = cron::byClassAndFunction('reveil', 'SimulAubeDemon');
					while(is_object($cron)) {
						$cron->stop();
						$cron->remove();						
						$cron = cron::byClassAndFunction('reveil', 'SimulAubeDemon');
					}
				break;
			}
			$Listener->setCollectDate(date('Y-m-d H:i:s'));
			$Listener->save();
		}
	}
}
?>
