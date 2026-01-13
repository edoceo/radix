<?php

namespace Edoceo\Vena\HTTP\Route;

class Node
{
	public array $staticChildren = [];
	public ?Node $paramChild = null;
	public ?string $paramName = null;
	public array $handlers = [];
}
