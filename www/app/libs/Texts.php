<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Pieces of text needed in the app
 *
 * @author tom
 */
class Texts extends \Nette\Object{
	
	public $data = Array();
	
	public static function get($category, $key){
		return $this->data[$category][$key];
	}
	
	public static function init(){
		$data['LogMeException'] = Array(
			10	=> 'Unable to recover expired Coinbase token',
			11	=> 'Unable to connect to Coinbase',
			12	=> 'Problem with Coinbase API',
		);
		$data['CoinbaseErrors'] = Array(
			'first_purchase' => 'Please wait until your first bitcoin purchase completes before making additional purchases., (Once your first purchase completes you can make multiple purchases at the same time.)',
		);
	}
	
} Texts::init();