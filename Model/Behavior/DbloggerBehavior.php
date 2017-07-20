<?php

class DbloggerBehavior extends ModelBehavior 
{	
	public $Dblog = false;
	
	public $oldData = array();
	
	public $loopModel = false;
	
	public $newItems = array();
	
	public $deletedRecord = false;
	
	public function beforeSave(Model $Model, $options = array())
	{
		if(!$Model->data or !count($Model->data)) return parent::beforeSave($Model, $options);
		
		foreach($Model->data as $model_name => $model_data)
		{
			// don't log the log... endless loop
			if(in_array($model_name, array('Dblog'))) continue;
			
			$this->loopModel = false;
			if($Model->alias == $model_name) $this->loopModel = $Model;
			elseif(isset($Model->{$model_name})) $this->loopModel = $Model->{$model_name};
			
			if(!$this->loopModel) continue;
			
			$primaryKey = $this->loopModel->primaryKey;
			if(isset($model_data[$primaryKey]) and $this->loopModel->_schema)
			{
				$model_schema = array_keys($this->loopModel->_schema);
				$save_schema = array_keys($model_data);
				$fake_columns = array_diff($save_schema, $model_schema);
				$real_columns = array_diff($save_schema, $fake_columns);
				
				$id = $model_data[$primaryKey];
				$this->oldData[$this->loopModel->alias][$id] = $this->loopModel->find('first', array(
					'recursive' => -1,
					'conditions' => array($model_name. '.'. $primaryKey => $id),
					'fields' => $real_columns,
				));
			}
			else
			{
				$this->newItems[$this->loopModel->alias] = $this->loopModel->alias;
			}
		}
		
		return parent::beforeSave($Model, $options);
	}
	
	public function afterSave(Model $Model, $created = false, $options = array())
	{
		if(!$Model->data or !count($Model->data)) return parent::afterSave($Model, $created);
		
		//// for each model, find out what changed
		
		// only mark the actual one as created, if the created field is set
		$this_created = 0;
		
		foreach($Model->data as $model_name => $model_data)
		{
			// don't log the log... endless loop
			if(in_array($model_name, array('Dblog'))) continue;
			
			$this->loopModel = false;
			if($Model->alias == $model_name) $this->loopModel = $Model;
			elseif(isset($Model->{$model_name})) $this->loopModel = $Model->{$model_name};
			
			if(!$this->loopModel) continue;
			
			
			$log_data = array();
			$primaryKey = $this->loopModel->primaryKey;
			if(isset($model_data[$primaryKey]))
			{
				$id = $model_data[$primaryKey];
				
				if($created)
				{
					// track if it's truely new
					$this_created = 0;
					if(isset($this->newItems[$this->loopModel->alias]))
					{
						$this_created = 1;
					}
					
					$readable = array();
					foreach($model_data as $column => $value)
					{
						$readable[] = '"'. $column. '" set to: "'. $value. '"';
					}
					$readable = implode(' ;;; ', $readable);
					
					$log_data = array(
						'Dblog' => array(
							'model' => $this->loopModel->alias,
							'model_id' => $id,
							'changes' => serialize($model_data),
							'readable' => $readable,
							'new' => $this_created,
							'user_id' => AuthComponent::user('id'),
						)
					);
				}
				else
				{
					$db_data = false;
					if(isset($this->oldData[$this->loopModel->alias][$id]))
					{
						$db_data = $this->oldData[$this->loopModel->alias][$id];
					}
					
					if($db_data)
					{
						$db_data = $db_data[$model_name];
						$diff = array_diff($model_data, $db_data);
						
						// remove the created and modified keys
						if(isset($diff['created'])) unset($diff['created']);
						if(isset($diff['modified'])) unset($diff['modified']);
						
						if(count($diff))
						{
							$readable = array();
							foreach($diff as $column => $value)
							{
								$readable[] = '"'. $column. '" changed to: "'. $value. '"';
							}
							$readable = implode(' ;;; ', $readable);
							$log_data = array(
								'Dblog' => array(
									'model' => $model_name,
									'model_id' => $id,
									'changes' => serialize($diff),
									'readable' => $readable,
									'new' => 0,
									'user_id' => AuthComponent::user('id'),
								)
							);
						}
					}
				}
				
				if(count($log_data) and $this->LoadDblog($Model))
				{
					$this->Dblog->create();
					$this->Dblog->data = $log_data;
					$this->Dblog->save($this->Dblog->data);
				}
			}
		}
		
		return parent::afterSave($Model, $created, $options);
	}
	
	public function beforeDelete(Model $Model, $cascade = true)
	{
		if(!isset($Model->{$Model->primaryKey})) return parent::beforeDelete($Model, $cascade);
		
		// track the recursion
		$recursive = $Model->recursive;
		$Model->recursive = 0;
		
		// get the record
		$this->deletedRecord = $Model->read(null, $Model->{$Model->primaryKey});
		
		//reset the recursive
		$Model->recursive = $recursive;
		
		return parent::beforeDelete($Model, $cascade);
	}
	
	public function afterDelete(Model $Model)
	{
		if(!isset($Model->{$Model->primaryKey})) return parent::afterDelete($Model);
		
		$readable = array();
		$model_data = array();
		
		foreach($this->deletedRecord as $model_name => $_model_data)
		{
			if($model_name == $Model->alias)
			{
				$model_data = $_model_data;
				foreach($_model_data as $column => $value)
				{
					$readable[] = '"'. $column. '" : "'. $value. '"';
				}
			}
		}
		
		$readable = implode(' ;;; ', $readable);
		
		$log_data = array(
			'Dblog' => array(
				'model' => $Model->name,
				'model_id' => $Model->{$Model->primaryKey},
				'changes' => serialize($model_data),
				'readable' => $readable,
				'deleted' => 1,
				'user_id' => AuthComponent::user('id'),
			)
		);
		
		if($this->LoadDblog($Model))
		{
			$this->Dblog->create();
			$this->Dblog->data = $log_data;
			$this->Dblog->save($this->Dblog->data);
		}
		
		return parent::afterDelete($Model);
	}
	
	public function LoadDblog(Model $Model)
	{
		if(!$this->Dblog)
		{
			App::uses('Dblog', 'Dblogger.Model');
			if($this->Dblog = new Dblog())
			{
				return true;
			}
		}
		else
		{
			return true;
		}
		return false;
	}
}