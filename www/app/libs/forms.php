<?php

class NewOrderForm extends Nette\Application\UI\Form{
	public function create($presenter){
		$this->addSelect('action', 'Action', Array('BUY' => 'Buy', 'SELL' => 'Sell'))->setRequired();
		$this->addText('amount', 'Amount of Bitcoins')
			->addRule(\Nette\Forms\Form::FLOAT)
			->addRule(\Nette\Forms\Form::RANGE, 'Amount of bitcoins per order must be between 0.001 and 10. If you need to buy more, write me at tom@coinbaseorders.com and I\'ll make it possible.', Array(0.001, 10.001));
		$this->addText('at_price', 'At price per Bitcoin')
			->addRule(\Nette\Forms\Form::FLOAT)
			->addRule(\Nette\Forms\Form::NOT_EQUAL, 'Price per bitcoin must be at least 0.01', 0);
		$this->addSubmit('submit','Place Limit Order');
		$this->onSuccess[] = callback($this, 'success');
	}
	
	public function success($form){
		$values = $this->getValues();
		$this->presenter->context->orders->insert(Array(
			'user_id' => $this->presenter->user->id,
			'action' => $values['action'],
			'amount' => $values['amount'],
			'amount_currency' => 'BTC',
			'at_price' => $values['at_price'],
		));
		$this->presenter->redirect('Homepage:orders', Array('OrdersGrid-filter[status]' => 'ACTIVE'));
	}
}
