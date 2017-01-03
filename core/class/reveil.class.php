<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
class reveil extends eqLogic {
	public static function deamon_info() {
		$return = array();
		$return['log'] = 'reveil';
		$return['launchable'] = 'ok';
		$return['state'] = 'nok';

		$return['state'] = 'ok';
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
		/*foreach(eqLogic::byType('reveil') as $Volet)
			$Volet->StartDemon();*/
	}
	public static function deamon_stop() {	
		/*$cron = cron::byClassAndFunction('reveil', 'ActionJour');
		if (is_object($cron)) 	
			$cron->remove();
		$cron = cron::byClassAndFunction('reveil', 'ActionNuit');
		if (is_object($cron)) 	
			$cron->remove();
		foreach(eqLogic::byType('reveil') as $Volet){
			$listener = listener::byClassAndFunction('reveil', 'pull', array('reveil_id' => intval($Volet->getId())));
			if (is_object($listener))
				$listener->remove();
		}*/
	}
	public function pull($_option){
		$doWhile = true;
		$time = 0;
		while ($doWhile) {
			if($this->EvaluateCondition()){
				switch($this->getConfiguration('ReveilType')){
					case 'DawnSimulatorEngine';
						$options['slider'] = ceil($this->dawnSimulatorEngine($this->getConfiguration('DawnSimulatorEngineType'),$time, $this->getConfiguration('DawnSimulatorEngineStartValue'), $this->getConfiguration('DawnSimulatorEngineEndValue'), $this->getConfiguration('DawnSimulatorEngineDuration')));
						$this->ExecuteAction($this->getConfiguration('Equipements'),$options);
						sleep(1000);
					break;
				}
			}
		}
	}
	private function dawnSimulatorEngine($type, $time, $startValue, $endValue, $duration) {
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
				if ($time == 0 )
					return $startValue 
				if ($time == $duration)
					return $startValue + $endValue
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
	public function ExecuteAction($Action,$options) {	
		foreach($Action as $cmd){
			$Commande=cmd::byId(str_replace('#','',$cmd['cmd']));
			if(is_object($Commande)){
				log::add('reveil','debug','Execution de '.$Commande->getHumanName());
				$Commande->execute($options);
			}
		}
	}
	public function CalculHeureEvent($HeureStart, $delais) {
		if(strlen($HeureStart)==3)
			$Heure=substr($HeureStart,0,1);
		else
			$Heure=substr($HeureStart,0,2);
		$Minute=substr($HeureStart,-2)+$this->getConfiguration($delais);
		while($Minute>=60){
			$Minute-=60;
			$Heure+=1;
		}
		return mktime($Heure,$Minute);
	}
	public function CreateCron($Schedule, $logicalId) {
		$cron =cron::byClassAndFunction('reveil', $logicalId);
			if (!is_object($cron)) {
				$cron = new cron();
				$cron->setClass('reveil');
				$cron->setFunction($logicalId);
				$cron->setEnable(1);
				$cron->setDeamon(0);
				$cron->setSchedule($Schedule);
				$cron->save();
			}
			else{
				$cron->setSchedule($Schedule);
				$cron->save();
			}
		return $cron;
	}
	public function EvaluateCondition(){
		foreach($this->getConfiguration('Conditions') as $condition){
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
				log::add('reveil','debug','Les conditions ne sont pas remplie');
				return false;
			}
		}
		return true;
	}
}
class reveilCmd extends cmd {
    	public function execute($_options = null) {	
	}
}
class dawnSimulatorEngine{
	$_lastValue = 0;
	$_startValue = 0;
	$_endValue = 100;
	$_duration = 1;
	$_devices 
	$_curve = "inExpo";
	function __construct($startValue=0, $endValue=100, $duration=1, $devices={}, $curve){
		$this->_lastValue = 0;
		$this->_startValue = $startValue;
		$this->_endValue = $endValue;
		$this->_duration = $duration;
		$this->_devices = $devices; 
		$this->_curve = $curve;
	}
	public function _update($value){  
		$this->_lastValue = $value;
		foreach($this->_devices as $device){
		//Mise a jours de la valeur de l'equipement
		}
	}
	public function start(){
		$omputedValue;
		$doWhile = true;
		$time = 0;
		while ($doWhile) {
			$computedValue = ceil(self::equations($this->_curve,$time, $this->_startValue, $this->_endValue, $this->_duration));
			if ($computedValue ~= $this->_lastValue) {
				self::_update(computedValue);
			}
			$time++;
			if ($time > $this->_duration) {
				$doWhile = false;
				if ($this->_lastValue < $this->_endValue)
					self::_update($computedValue);
				else
					sleep(1000);
			}
		}
	}
	public function equations($type, $time, $startValue, $endValue, $duration) {
		switch ($type){
			case 'Linear':
				//linear = function(t, b, c, d)
				return $endValue * $time / $duration + $startValue;
			break;
			case 'InQuad':
				//inQuad = function(t, b, c, d)
				$time = $time / $duration;
				return $endValue * pow($time, 2) + $startValue;
			break;
			case 'InOutQuad':
				//inOutQuad = function(t, b, c, d)
				$time = $time / $duration * 2;
				if ($time < 1)
					return $endValue / 2 * pow($time, 2) + $startValue;
				else
					return -$endValue / 2 * (($time - 1) * ($time - 3) - 1) + $startValue;
			break;
			case 'InOutExpo':
				//inOutExpo = function(t, b, c, d)
				if ($time == 0 )
					return $startValue 
				if ($time == $duration)
					return $startValue + $endValue
				$time = $time / $duration * 2;
				if ($time < 1)
					return $endValue / 2 * pow(2, 10 * ($time - 1)) + $startValue - $endValue * 0.0005;
				else{
					$time = $time - 1;
					return $endValue / 2 * 1.0005 * (-pow(2, -10 * $time) + 2) + $startValue;
				}
			break;
			case 'OutInExpo':
				//outInExpo = function(t, b, c, d)
				if ($time < $duration / 2)
					return self::equations('outExpo', $time * 2, $startValue, $endValue / 2, $duration);
				else
					return self::equations('inExpo', ($time * 2) - $duration, $startValue + $endValue / 2, $endValue / 2, $duration);
			break;
			case 'InExpo':
				//inExpo = function(t, b, c, d)
				if($time == 0)
					return $startValue;
				else
					return $endValue * pow(2, 10 * ($time / $duration - 1)) + $startValue - $endValue * 0.001;	
			break;
			case 'OutExpo':
				//outExpo = function(t, b, c, d)
				if($time == $duration)
					return $startValue + $endValue;
				else
					return $endValue * 1.001 * (-pow(2, -10 * $time / $duration) + 1) + $startValue;
			break;
		}
	}

}
?>
