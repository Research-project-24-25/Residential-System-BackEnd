<?php

namespace App\Http\Controllers;
use App\Traits\ApiResponse;
use App\Traits\ExceptionHandler;
use App\Traits\Filterable;

abstract class Controller
{
  use ApiResponse, Filterable, ExceptionHandler;   
}
