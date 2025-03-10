<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use App\Traits\ExceptionHandler;
use App\Traits\Filterable;
use Illuminate\Routing\Controller;

class BaseController extends Controller
{
    use ApiResponse, Filterable, ExceptionHandler;
}
