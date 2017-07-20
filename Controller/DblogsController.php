<?php

App::uses('DbloggerAppController', 'Dblogger.Controller');

class DblogsController extends DbloggerAppController 
{
	public function admin_index() 
	{
	/**
	 * index method
	 *
	 * Displays all Database Logs
	 */
		$this->Prg->commonProcess();
		
		// include just the user information
		$this->Dblog->recursive = 0;
		$this->paginate['order'] = array('Dblog.created' => 'desc');
		$this->paginate['conditions'] = $this->Dblog->parseCriteria($this->passedArgs);
		$this->set('dblogs', $this->paginate());
	}
	
	
//
	public function admin_view($id = null) 
	{
	/**
	 * view method
	 *
	 * @param string $id
	 * @return void
	 */
		$this->Dblog->id = $id;
		if (!$this->Dblog->exists())
		{
			throw new NotFoundException(__('Invalid Database Log'));
		}
		
		$this->Dblog->recursive = 0;
		$this->set('dblog', $this->Dblog->read(null, $id));
	}
}