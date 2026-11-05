<?php

namespace Javaabu\FormBuilder\Tests\TestSupport\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Javaabu\FormBuilder\Http\Controllers\FormsController;

class CustomFormsController extends FormsController
{
    public function index(Request $request): JsonResponse
    {
        return new JsonResponse(['custom' => true]);
    }
}
