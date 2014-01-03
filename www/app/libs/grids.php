<?php

use \NiftyGrid\Grid;

abstract class GridBuilder extends Grid{
	protected $database;
	protected $presenter;
	protected $table;
	
    public function __construct($presenter, $database)
    {
        parent::__construct();
        $this->database = $database;
		$this->presenter = $presenter;
    }	
}

// http://addons.nette.org/cs/niftygrid

class OrdersGrid extends GridBuilder{
	
    protected function configure($presenter)
    {
//		$this->presenter = $presenter;
		
		$this->table = $this->database->table('orders');
		$this->table->select('date_created, order_id, status, action, amount, amount_currency, at_price, date_cancel, amount*at_price AS total');
		$this->table->where('user_id', $this->presenter->user->id);
        $this->setDataSource(new \NiftyGrid\DataSource\NDataSource($this->table));
		
		$this->addColumn('status', 'Status', '70px')
				->setSortable(FALSE)
				->setSelectFilter(Array('ACTIVE' => 'Active', 'EXECUTED' => 'Executed', 'CANCELED' => 'Canceled'), 'All');
		$this->addColumn('action', 'Action', '70px')
				->setSortable(FALSE)
				->setCellRenderer(function($row){return 'color:'.($row['action'] == 'BUY' ? '#2A2' : '#A22');});
		$this->addColumn('amount', 'Amount', '80px')
				->setTextEditable()
				->setCellRenderer('text-align:right;')
				->setRenderer(function ($row){ return $row['amount_currency']." ".number_format($row['amount'], 3);});
				
		$this->addColumn('at_price', 'If price per Bitcoin', '200px')
				->setTextEditable()
				->setRenderer(function ($row){ 
					if($row['action'] == 'BUY'){
						return 'drops to $'.number_format($row['at_price'], 2);
					}
					elseif($row['action'] == 'SELL'){
						return 'increases to reach $'.number_format($row['at_price'], 2);
					}
					
					
				});
				
		$this->addColumn('total', 'Total', '80px')
				->setCellRenderer('text-align:right;')
				->setRenderer(function ($row){ return '$'.number_format($row['total'], 2);});
				
//		$this->addColumn('date_cancel', 'Expires', '150px')
//				->setDateEditable();
//				->setRenderer(function ($row){ return $row['date_cancel'] ?: 'Good till canceled';});

		$this->addButton(Grid::ROW_FORM, "Edit")
			->setClass(function($row){ return ($row['status'] == 'ACTIVE') ? "fast-edit" : 'nodisplay';});
			
		$this->setRowFormCallback(function($values) use ($presenter){
			if(!preg_match('/[0-9]\.?[0-9]*/', $values['amount']) || $values['amount'] == 0){
				$presenter->flashMessage('Amount must be a number like 0.50 and more than zero.');
			}
			elseif(!preg_match('/[0-9]\.?[0-9]*/', $values['at_price']) || $values['at_price'] == 0){
				$presenter->flashMessage('Price per bitcoin must be a number like 0.50 and more than zero.');
			}
			else{
				$presenter->context->orders->findById($values['order_id'])->update(Array(
					'date_edited' => new Nette\Database\SqlLiteral('NOW()'),
					'amount' => $values['amount'],
					'at_price' => $values['at_price'],
//					'date_cancel' => $values['date_cancel'] ?: NULL,
				));				
			}
		});
			
		
		$self = $this;
		
		$this->addButton("delete", "Delete")
				->setClass(function($row){ return ($row['status'] == 'ACTIVE') ? "delete" : 'nodisplay';})
				->setLink(function($row) use ($self){return $self->link("DeleteOrder!", $row['order_id']);})
				->setAjax(FALSE);
				

		$this->setDefaultOrder('at_price DESC');
		$this->paginate = False;
		$this->messageNoRecords = 'You don\'t have any orders yet';				
		
    }
	
	public function handleDeleteOrder($orderId){
		$this->presenter->context->orders->cancelById($orderId);
		$this->presenter->redirect('Homepage:orders', Array('OrdersGrid-filter[status]' => 'ACTIVE'));
	}
}

class OrderHistoryGrid extends GridBuilder{
	
    protected function configure($presenter)
    {
		$this->table = $this->database->table('logs');
		$this->table->select('relation_id, subtype, text, important, error, date, user_id');
		$this->table->where(Array('user_id' => $presenter->user->id, 'latest' => True));
        $this->setDataSource(new \NiftyGrid\DataSource\NDataSource($this->table));
		
		$this->addColumn('date', 'Date', '150px');
		$this->addColumn('text', 'Latest update', '630px')
			->setSortable(FALSE)
			->setCellRenderer(function($row){
				$style = '';
				if($row['important']){
					$style .= 'font-weight:bold;';
				}
				if($row['error']){
					$style .= 'font-color:#F00;';
				}
				return $style;
			});
			
		$this->setDefaultOrder('date DESC');
		$this->paginate = False;			
		$this->messageNoRecords = 'No records here yet. Updates will start showing up a few minutes after you add your first order.';
	}	
	
}