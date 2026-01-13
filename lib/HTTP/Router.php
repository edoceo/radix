<?php
/**
 * Request Router
 *
 * @see https://github.com/nikic/FastRoute/blob/master/src/RouteParser/Std.php
 */

namespace Edoceo\Vena\HTTP;

use Edoceo\Vena\HTTP\Route\Node;

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

		$path = trim($path, '/');
		$path_part_list = explode('/', $path);

		$args = [];
		$node = $this->root;
		foreach ($path_part_list as $part) {

			// echo "$part<br>\n";

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

		$RES = null;

		// If is Callable?
		if (is_callable($next)) {
			$RES = call_user_func($next, $REQ);
			return $RES;
		} elseif (is_string($next)) {
			if (class_exists($next)) {
				$next = new $next();
				$RES = $next->handle($REQ);
			}
			if (function_exists($next)) {
				// echo "FUNK\n";
				$RES = call_user_func($next, $REQ);
			}
		}

		if (empty($RES)) {
			throw new \Exception(sprintf("Server Error [AHR-117]\nUndefined: %s", $next), 500);
		}

		return $RES;

	}
}
