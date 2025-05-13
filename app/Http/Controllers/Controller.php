<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use App\Traits\ExceptionHandler;
use App\Traits\Filterable;
use App\Traits\SoftDeleteActions;

abstract class Controller
{
  use ApiResponse, Filterable, ExceptionHandler, SoftDeleteActions;
}
