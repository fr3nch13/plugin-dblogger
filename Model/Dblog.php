<?php

App::uses('DbloggerAppModel', 'Dblogger.Model');

class Dblog extends DbloggerAppModel 
{

/**
 * belongsTo associations
 *
 * @var array
 */
	public $belongsTo = array(
		'DblogUser' => array(
			'className' => 'User',
			'foreignKey' => 'user_id',
		),
	);
	
	// define the fields that can be searched
	public $searchFields = array(
		'Dblog.model',
		'Dblog.model_id',
		'Dblog.changes',
		'Dblog.readable',
	);
	
	public $Models = false; // place holder to load and store models
	
	public function afterFind($results = array(), $primary = false)
	{
		// attach the other model information
		if($this->recursive == 1)
		{
			// track models
			$Models = array();
			
			foreach($results as $i => $result)
			{
				if(!isset($result['Dblog']['model'])) continue;
				if(!isset($result['Dblog']['model_id'])) continue;
				
				$model = $result['Dblog']['model'];
				$model_id = $result['Dblog']['model_id'];
				
				if(!isset($Models[$model]))
				{
					App::import('Model', $model);
					$Models[$model] = new $model();
				}
				
				$connect = $Models[$model]->find('first', array(
					'recursive' => 0,
					'conditions' => array(
						$Models[$model]->alias. '.'. $Models[$model]->primaryKey => $model_id,
					),
				));
				
				if($connect)
				{
					$results[$i] = array_merge($results[$i], $connect);
				}
			}
		}
		return parent::afterFind($results, $primary);
	}
	
	public function latest($minutes = 5)
	{
	/*
	 * Get a list of the latest changes from $minutes ago
	 */
		if(!$minutes) return false;
		
		$minutes = '-'. $minutes. ' minutes';
		
		// used to see in the afterFind
		$recursive = $this->recursive;
		$this->recursive = 1;
		
		$dblogs = $this->find('all', array(
			'conditions' => array(
				'Dblog.created >' => date('Y-m-d H:i:s', strtotime($minutes)),
			),
		));
		$this->recursive = $recursive;
		
		$this->shellOut(__('Found %s Logs.', count($dblogs)), 'dblog', 'info');
		
		return $dblogs;
	}
	
	public function mapFields($log = false)
	{
	/*
	 * Maps fields to the names, based on the mapp array in the model
	 */
		if(!$log) return false;
		
		$log[$this->alias]['mapped'] = array();
		
		$model = $log[$this->alias]['model'];
		$this->loadModel($model);
		
		$changes = array();
		if($log[$this->alias]['changes'])
		{
			$changes = $log[$this->alias]['changes'];
			$changes = unserialize($changes);
		}
		$mappedFields = array();
		
		if(isset($this->Models[$model]) and isset($this->Models[$model]->mappedFields))
		{
			$mappedFields = $this->Models[$model]->mappedFields;
		}
		
		$_changes = array();
		
		foreach($changes as $field => $value)
		{
			$fieldValue = Inflector::humanize($field);
			if(isset($mappedFields[$field]['name']))
			{
				$fieldValue = $mappedFields[$field]['name'];
			}
			
			// static options mapped
			if(isset($mappedFields[$field]['options']) and isset($mappedFields[$field]['options'][$value]))
			{
				$value = $mappedFields[$field]['options'][$value];
			}
			elseif(isset($mappedFields[$field]['value']))
			{
				$value = Set::classicExtract($log, $mappedFields[$field]['value']);
			}
			$_changes[$fieldValue] = $value;
		}
		
		$log[$this->alias]['mapped_readable'] = false;
		
		$changedTo = 'changed to';
		if($log[$this->alias]['new']) $changedTo = 'set to';
		
		if($log[$this->alias]['mapped'] = $_changes)
		{
			foreach($log[$this->alias]['mapped'] as $k => $v)
			{
				$log[$this->alias]['mapped_readable'][] = '"'. $k. '" '. $changedTo. ': "'. $v. '"';
			}
			$log[$this->alias]['mapped_readable'] = implode("\n", $log[$this->alias]['mapped_readable']);
		}
		
		return $log;
	}
	
	public function loadModel($modelName = false)
	{
	/*
	 * Checks and loads a model
	 */
		if(!isset($this->Models[$modelName]))
		{
			$knownModels = App::objects('Model');
			if(in_array($modelName, $knownModels))
			{
				App::uses($modelName, 'Model');
				$this->Models[$modelName] = new $modelName();
			}
		}
	}
}