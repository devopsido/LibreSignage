<?php

/*
*  Queue object definition for easily loading a list
*  list of all the slides in a specific slide queue.
*/

require_once($_SERVER['DOCUMENT_ROOT'].'/common/php/util.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/common/php/slide.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/common/php/config.php');

class Queue {
	private $queue = NULL;
	private $slides = NULL;
	private $path = NULL;

	function __construct(string $queue) {
		$this->queue = $queue;
		$this->slides = array();
		$this->path = LIBRESIGNAGE_ROOT.QUEUES_DIR.
				'/'.$queue.'.json';
	}

	function load() {
		if (!file_exists($this->path)) {
			throw new ArgException(
				"Queue doesn't exist."
			);
		}
		$json = file_lock_and_get($this->path);
		$data = json_decode($json, $assoc=TRUE);
		if (json_last_error() != JSON_ERROR_NONE &&
			$data === NULL) {
			throw new IntException(
				"JSON decoding failed: ".
				json_last_error_msg()
			);
		}

		$this->slides = array();
		foreach ($data['slides'] as $n) {
			$tmp = new Slide();
			if (!$tmp->load($n)) {
				throw new IntException(
					"Slide in queue doesn't exist."
				);
			}
			$this->slides[] = $tmp;
		}
	}

	function write() {
		$ids = array_map(
			function($s) {
				return $s->get_id();
			},
			$this->slides
		);
		$json = json_encode($ids);
		if (json_last_error() != JSON_ERROR_NONE &&
			$json === FALSE) {
			throw new IntException(
				'JSON encoding failed: '.
				json_last_error_msg()
			);
		}
		file_lock_and_put($this->path, $json);
	}

	function add(Slide $slide) {
		$this->slides[] = $slide;
	}

	function remove(Slide $slide) {
		array_filter(
			$this->slides,
			function($s) {
				return $s->get_id() != $slide->get_id();
			}
		);
	}

	function slides() {
		return $this->slides;
	}
}
