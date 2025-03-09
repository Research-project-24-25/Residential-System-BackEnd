<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use App\Traits\ExceptionHandler;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;

class BaseController extends Controller
{
    use AuthorizesRequests, ValidatesRequests, ApiResponse, ExceptionHandler;
}
