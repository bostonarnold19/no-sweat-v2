<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\StoreComponentGeneratorRequest;
use App\Http\Requests\CreateModuleGeneratorRequest;
use App\Services\ComponentGeneratorService;
use Nwidart\Modules\Facades\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ComponentGeneratorController extends Controller
{
    /**
     * @var ComponentGeneratorService
     */
    private ComponentGeneratorService $componentGeneratorService;

    /**
     * @param ComponentGeneratorService $componentGeneratorService
     */
    public function __construct(ComponentGeneratorService $componentGeneratorService)
    {
        $this->middleware(['auth', 'verified', 'password.confirm']);
        $this->componentGeneratorService = $componentGeneratorService;
    }

    /**
     * @return View|Factory|Application
     */
    public function create(StoreComponentGeneratorRequest $request): View|Factory|Application
    {
        $model = $request->model;
        $module = $request->module;

        return view('pages.component-generator.create', compact('model', 'module'));
    }

    /**
     * @param StoreComponentGeneratorRequest $request
     * @return RedirectResponse
     */
    public function store(StoreComponentGeneratorRequest $request): RedirectResponse
    {
        $sanitized = $request->getSanitized();

        $this->componentGeneratorService->generate(
            $sanitized['module'], 
            $sanitized['model'], 
            $sanitized['components'],
            $sanitized['files_to_update']
        );

        return redirect()->route('module-generator.index')
            ->with('success', 'Component generated successfully.');
    }

}
