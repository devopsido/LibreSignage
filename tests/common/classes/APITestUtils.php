<?php

namespace classes;

use JsonSchema\Validator;
use JsonSchema\SchemaStorage;
use JsonSchema\Constraints\Factory;

final class APITestUtils {
	public static function json_decode(string $str) {
		/*
		*  Exception handling wrapper for json_decode.
		*/
		$ret = json_decode($str);
		if ($ret === NULL && json_last_error() !== JSON_ERROR_NONE) {
			throw new \Exception('Failed to decode JSON.');
		}
		return $ret;
	}

	public static function json_encode($str): string {
		/*
		*  Exception handling wrapper for json_encode.
		*/
		$ret = json_encode($str);
		if ($ret === FALSE && json_last_error() !== JSON_ERROR_NONE) {
			throw new \Exception('Failed to encode JSON.');
		}
		return $ret;
	}

	public static function read_json_file(string $path) {
		/*
		*  Wrapper for reading and decoding a JSON file in on go.
		*/
		return APITestUtils::json_decode(\file_get_contents($path));
	}

	public static function json_schema_error_string(Validator $validator): string {
		/*
		*  Build an error string from a JsonSchema\Validator object's data.
		*/
		if ($validator->isValid()) { return 'Schema validation OK.'; }

		$ret = "Schema validation failed:\n\n";
		foreach($validator->getErrors() as $e) {
			$ret .= sprintf("%s: %s\n", $e['property'], $e['message']);
		}
		return $ret;
	}

	public static function create_json_validator(
		string $search_path,
		array $files
	): Validator {
		/*
		*  Create a JsonSchema\Validator object. $files is a list of external
		*  schema filenames to add to the Validator. The path $search_path is
		*  searched for these files. Each external schema is added to the validator
		*  as 'file://[filename]' so you can access them with a $ref such as
		*  
		*    { "$ref": "file://[filename]#/definitions/def" }
		*/

		$schema = NULL;
		$storage = new SchemaStorage();

		foreach ($files as $f) {
			$schema = APITestUtils::read_json_file($search_path.'/'.$f);
			$storage->addSchema('file://'.$f, $schema);
		}

		return new Validator(new Factory($storage));
	}
}