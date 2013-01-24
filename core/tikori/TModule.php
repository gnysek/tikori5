<?php

class TModule {
//	public function registerModule() {
//
//	}

	protected $__initialized = false;

	public function init() {
		$this->__initialized = true;
		return true;
	}

	public function isInitialized() {
		return $this->__initialized;
	}

	public function addObserver($eventName) {
		Core::app()->observer->addObserver($eventName, $this);
	}
}
