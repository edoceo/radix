<?php
/**
 * Request Router
 */

namespace Edoceo\Radix\HTTP;

use Edoceo\Radix\HTTP\Route\Node;

class Router
{
	public $root = null;

	// public $force_trailing_slash = true|false|if-has-children;

	function __construct($root0=null)
	{
		if ( ! empty($root0)) {
			$this->root = $root0;
		} else {
			$this->root = new Node();
		}
	}

	/**
	 *
	 */
	function add($path0, $next0, $verb0='GET')
	{
		$path = trim($path0, '/');
		$verb = strtoupper($verb0);

		// Find or Create My Node to Attach To
		$node = $this->root;
		$path_part_list = explode('/', $path);
		foreach ($path_part_list as $part) {
			if (preg_match('/^\{(.+)\}$/', $part, $m)) {
				if (empty($node->paramChild)) {
					$node->paramChild = new Node();
					$node->paramChild->paramName = $m[1];
				}
				$node = $node->paramChild;
			} else {
				if (empty($node->staticChildren[$part])) {
					$node->staticChildren[$part] = new Node();
				}
				$node = $node->staticChildren[$part];
			}
		}

		$node->handlers[$verb] = $next0;

	}

	/**
	 *
	 */
	function handle($REQ)
	{
		$path = $REQ->getPath();
		$verb = $REQ->getVerb();

		$next = $this->resolvePath($path, $verb);
		$func = $this->resolveNext($next);

		if ( ! is_callable($func)) {
			throw new \Exception(sprintf("Server Error [AHR-067]\nUndefined: %s", $next), 500);
		}

		$RES = $func($REQ);

		if (empty($RES)) {
			throw new \Exception(sprintf("Server Error [AHR-073]\nUndefined: %s", $next), 500);
		}

		return $RES;

	}

	function resolveNext($next)
	{
		$RES = null;

		// If is Callable?
		if (is_callable($next)) {
			return $next;
			// $RES = call_user_func($next, $REQ);
			// return $RES;
		}

		if (is_string($next)) {

			if (str_contains($next, '::')) {
				return $next;
				// return $next($REQ);
			}

			if (str_contains($next, ':')) {
				// return $next;
				// return $next($REQ);
				[$c, $m] = explode(':', $next, 2);
				return [new $c(), $m ];
			}

			if (function_exists($next)) {
				return $next;
			}

			if (class_exists($next)) {
				return new $next;
			}

			// // matches plain function name
			// if (preg_match('/^\w+$/', $next)) {
			// 	// Is a function name
			// }

			// // Vendor\Project\Library\Class::factory()
			// if (preg_match('/^\w+\\.+::\w+$/', $next)) {
			// 	// Is class name with static function
			// }

			// // Vendor\Project\Library\Class:factory()
			// if (preg_match('/^\w+\\.+:\w+$/', $next)) {
			// 	// Is class name with instance function
			// }

			// // Vendor\Project\Library\Class
			// if (preg_match('/^\w+\\[^:]+$/', $next)) {
			// 	// Is class name, call magic function __invoke()
			// }

			// if (class_exists($next)) {
			// 	$next = new $next();
			// 	$RES = $next->handle($REQ);
			// 	return $RES;
			// }
			// if (function_exists($next)) {
			// 	// echo "FUNK\n";
			// 	$RES = call_user_func($next, $REQ);
			// 	return $RES;
			// }
		}

	}

	function resolvePath($path, $verb)
	{
		$path = trim($path, '/');
		$path_part_list = explode('/', $path);

		$args = [];
		$node = $this->root;
		foreach ($path_part_list as $part) {

			// First Static Match
			if ( ! empty($node->staticChildren[$part])) {
				$node = $node->staticChildren[$part];
				continue;
			}

			if ( ! empty($node->paramChild)) {
				$node = $node->paramChild;
				// $args[ $node->paramName ] = $part;
				$REQ->setAttribute($node->paramName, $part);
				continue;
			}

			// Then It's Not Found
			http_response_code(404);
			throw new \Exception('Not Found', 404);
		}

		if (empty($node->handlers[$verb])) {
			http_response_code(405);
			throw new \Exception('Method Not Allowed', 405);
		}

		$next = $node->handlers[$verb];

		return $next;

	}

	function save()
	{

	}

}
