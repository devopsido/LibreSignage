<?php

namespace libresignage\common\php\exportable\migration;

use libresignage\common\php\exportable\migration\exceptions\MigrationException;
use libresignage\common\php\JSONUtils;
use libresignage\common\php\Util;

/**
* A class representing a transformation index.
*/
final class MigrationIndex {
	/**
	* Construct a new MigrationIndex.
	*/
	public function __construct() {
		$this->index = NULL;
	}

	/**
	* Load the transformation index.
	*
	* @param string $path The filepath of the index file.
	*
	* @return array The transformation index as a associative array.
	*
	* @throws MigrationException If the index file doesn't exist.
	*/
	public function load(string $file) {
		if (!is_file($file)) {
			throw new MigrationException(
				"Migration index missing!"
			);
		}

		$tmp = Util::file_lock_and_get($file);
		
		$index = [];
		foreach (JSONUtils::decode($tmp, $assoc=TRUE) as $data) {
			array_push($index, new MigrationIndexEntry(
				$data['from'],
				$data['to'],
				$data['fqcn'],
				$data['data_fqcn']
			));
		}
		self::sort_index($index);
		
		$this->index = $index;
	}

	/**
	* Write a new migration index file.
	*
	* @param string $file The filepath where the index is written.
	* @param string $dir  The directory path that's scanned for
	*                     migration definitions.
	*
	* @return int The number of migration definition classes exported.
	*/
	public static function write(string $file, string $dir) {
		$index = [];
		$files = Util::scandir_recursive($dir);
		
		foreach ($files as $f) {
			require_once($f);
			$classes = get_declared_classes();
			$class = end($classes);
			array_push($index, [
				'from' => $class::from_version(),
				'to' => $class::to_version(),
				'fqcn' => $class,
				'data_fqcn' => $class::classname()
			]);
		}
		Util::file_lock_and_put($file, JSONUtils::encode($index));

		return count($index);
	}

	/**
	* Get a transformation index entry for a data version for a class.
	*
	* @param string $fqcn The fully-qualified classname.
	* @param string $from The origin version.
	*
	* @return MigrationIndexEntry|NULL The corresponding entry or NULL
	*                                  if not found.
	*/
	public function get(string $fqcn, string $from) {
		foreach ($this->index as $t) {
			if ($t->transforms($fqcn, $from)) {
				return $t;
			}
		}
		return NULL;
	}
	
	/**
	* Sort a transformation index by the keys, ie. version numbers.
	*
	* @return bool TRUE on success or FALSE on failure. 
	*/
	public static function sort_index(array &$index) {
		return uksort($index, function ($a, $b) {
			$a_split = explode('.', $a);
			$b_split = explode('.', $b);
			assert(count($a_split) == count($b_split));
			
			for ($i = 0; $i < count($a_split); $i++) {
				if ($a_split[$i] < $b_split[$i]) {
					return -1;
				} else if ($a_split[$i] > $b_split[$i]) {
					return 1;
				}
			}
			return 0;
		});
	}
}
