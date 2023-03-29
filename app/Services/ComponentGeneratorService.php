<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Nwidart\Modules\Facades\Module;
use App\Exceptions\ModuleGeneratorException;
use Illuminate\Support\Collection;

class ComponentGeneratorService
{
    /**
     * @var string
     */
    public string $module = '';

    /**
     * @var string
     */
    public string $model = '';

    /**
     * @var string
     */
    public string $table = '';

    /**
     * @var Collection
     */
    public Collection $components;

    /**
     * @var array
     */
    public array $filesToUpdate;

    /**
     * @param string $module
     * @param string $model
     * @param Collection $components
     * @param array $filesToUpdate
     * @return bool
     * @throws ModuleGeneratorException
     */
    public function generate(string $module, string $model, Collection $components, array $filesToUpdate): bool
    {
        $this->initializeProperties($module, $model, $components, $filesToUpdate);

        if(!$this->checkModuleIfExist()) {
            throw new ModuleGeneratorException("ERROR: Module not exist.");
        }

        $this->checkModelIfExist();
        $this->generateComponents();

        return true;
    }

    /**
     * @return bool
     */
    private function checkModuleIfExist(): bool
    {
        return Module::collections()->has($this->module);
    }

    /**
     * @return void
     * @throws ModuleGeneratorException
     */
    private function checkModelIfExist(): void
    {
        $modelPath = 'Modules\\' . $this->module . '\Entities\\' . $this->model;

        if (!class_exists($modelPath)) {
            throw new ModuleGeneratorException("ERROR: Model not exist.");
        }
    }

    /**
     * @param string $module
     * @param string $model
     * @param Collection $components
     * @param array $filesToUpdate
     * @return void
     */
    private function initializeProperties(string $module, string $model, Collection $components, array $filesToUpdate): void
    {
        $this->components = $components;
        $this->filesToUpdate = $filesToUpdate;
        $this->module = $this->pascalCase($module);
        $this->model = $this->pascalCase($model);
        $this->table = $this->snakeCase(Str::plural($this->model));
    }

    /**
     * @param string $keyword
     * @return mixed|void
     */
    private function searchMigrationFile(string $keyword)
    {
        $path = base_path("database/migrations");
        $files = scandir($path);
        foreach($files as $file){
            if(strpos($file, "_create_" .$keyword. "_table.php")){
                return $file;
            }
        }
    }

    /**
     * @param string $path
     * @return void
     */
    private function makeDirectory(string $path): void
    {
        if (!File::exists($path)) {
            File::makeDirectory($path, 0777, true, true);
        }
    }

    /**
     * @param string $string
     * @return array|string|null
     */
    private function pascalCase(string $string): array|string|null
    {
        return preg_replace('/\s+/', '', ucwords($string));
    }

    /**
     * @param string $string
     * @return string
     */
    private function snakeCase(string $string): string
    {
        return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $string));
    }

    /**
     * @param string $string
     * @return string
     */
    private function kebabCase(string $string): string
    {
        return strtolower(ltrim(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '-$0', $string), '-'));
    }

    /**
     * @param string $string
     * @return string
     */
    private function camelToSpace(string $string): string
    {
        $pieces = preg_split('/(?=[A-Z])/', $string);
        return implode(" ", $pieces);
    }

    /**
     * @param string $fileName
     * @return bool|string
     */
    private function getMigrationFile(string $fileName): bool|string
    {
        return file_get_contents(base_path()."/database/migrations/{$fileName}");
    }

    /**
     * @return bool|string
     */
    private function getResourceForm(): bool|string
    {
        return file_get_contents(base_path()."/Modules/{$this->module}/Resources/views/{$this->kebabCase($this->model)}/form.blade.php");
    }

    /**
     * @return bool|string
     */
    private function getModelFile(): bool|string
    {
        return file_get_contents(base_path()."/Modules/{$this->module}/Entities/{$this->model}.php");
    }

    /**
     * @param string $method
     * @return bool|string
     */
    private function getRequestFile(string $method): bool|string
    {
        return file_get_contents(base_path()."/Modules/{$this->module}/Http/Requests/$this->model/{$method}{$this->model}Request.php");
    }

    /**
     * @param $stub
     * @return bool|string
     */
    private function getIncludesFile($stub): bool|string
    {
        return file_get_contents(base_path()."/stubs/custom-stubs/views/includes/{$stub}.stub");
    }

    /**
     * @return bool|string
     */
    private function getRequestCheckboxSanitize(): bool|string
    {
        return file_get_contents(base_path()."/stubs/custom-stubs/request_checkbox_sanitize.stub");
    }

    /**
     * @param string $field
     * @return void
     */
    private function updateMigration(string $field): void
    {
        $keyword = $this->table;
        $fileName = $this->searchMigrationFile($keyword);
        $file = $this->getMigrationFile($fileName);
        $codeToAppend = "\t\t\t" . "\$table->text('{{FIELD}}')->nullable();" . "\n";

        $codeToAppend = str_replace(
            [
                '{{FIELD}}',
            ],
            [
                $field, //{{FIELD}}
            ],
            $codeToAppend
        );

        $searchKey = "/module-generator/";
        $filePathToUpdate = base_path()."/database/migrations/{$fileName}";
        $this->updateFile($searchKey, $file, $codeToAppend, $filePathToUpdate);
    }

    /**
     * @param string $field
     * @return void
     */
    private function updateModelFillable(string $field): void
    {
        $file = $this->getModelFile();
        $codeToAppend = "\t\t" . "'{{FIELD}}'," . "\n";

        $codeToAppend = str_replace(
            [
                '{{FIELD}}',
            ],
            [
                $field, //{{FIELD}}
            ],
            $codeToAppend
        );

        $searchKey = "/module-generator-fillable/";
        $filePathToUpdate = base_path()."/Modules/{$this->module}/Entities/{$this->model}.php";
        $this->updateFile($searchKey, $file, $codeToAppend, $filePathToUpdate);
    }

    /**
     * @param string $field
     * @return void
     */
    private function updateModelSortable(string $field): void
    {
        $file = $this->getModelFile();
        $codeToAppend = "\t\t" . "'{{FIELD}}'," . "\n";

        $codeToAppend = str_replace(
            [
                '{{FIELD}}',
            ],
            [
                $field, //{{FIELD}}
            ],
            $codeToAppend
        );

        $searchKey = "/module-generator-sortable/";
        $filePathToUpdate = base_path()."/Modules/{$this->module}/Entities/{$this->model}.php";
        $this->updateFile($searchKey, $file, $codeToAppend, $filePathToUpdate);
    }

    /**
     * @return void
     */
    private function generateComponents(): void
    {
        $requestPath = base_path()."/Modules/{$this->module}/Resources/views/{$this->kebabCase($this->model)}/includes";
        $this->makeDirectory($requestPath);

        foreach($this->components as $field => $components) {
            if($this->filesToUpdate['is_update_model_class']) {
                $this->updateModelFillable($field);
                $this->updateModelSortable($field);
            }

            if($this->filesToUpdate['is_update_migration_file']) {
                $this->updateMigration($field);
            }

            if($this->filesToUpdate['is_create_includes']) {
                $this->createIncludes($field, $components);
            }
        }

        foreach(array_reverse($this->components->toArray()) as $field => $components) {
            if($this->filesToUpdate['is_create_includes']) {
                $this->useIncludesToForm($field);
            }
        }
    }

    /**
     * @param $field
     * @return void
     */
    private function useIncludesToForm($field): void
    {
        $file = $this->getResourceForm();

        $codeToAppend = "\t\t" . "@include('{{MODULE_VIEW}}::{{MODEL_VIEW}}.includes.{{FIELD}}')" . "\n";

        $codeToAppend = str_replace(
            [
                '{{MODULE_VIEW}}',
                '{{MODEL_VIEW}}',
                '{{FIELD}}',
            ],
            [
                $this->kebabCase($this->module), //{{MODULE_VIEW}}
                $this->kebabCase($this->model), //{{MODEL_VIEW}}
                $field, //{{FIELD}}
            ],
            $codeToAppend
        );

        $searchKey = "/delete-this-after-generate/";
        $filePathToUpdate = base_path()."/Modules/{$this->module}/Resources/views/{$this->kebabCase($this->model)}/form.blade.php";
        $this->updateFile($searchKey, $file, $codeToAppend, $filePathToUpdate);

    }

    /**
     * @param string $field
     * @param array $components
     * @return void
     */
    private function createIncludes(string $field, array $components): void
    {
        $radioComponents = [];
        foreach($components as $component) {
            if(!in_array($component->type, ['select', 'radio'])) {
                $this->generateGenericComponents($component);
                if($component->type === 'checkbox') {
                    $this->generateRequestCheckboxSanitize('Store', $component);
                    $this->generateRequestCheckboxSanitize('Update', $component);
                }
            } elseif($component->type === 'select') {
                $this->generateSelectComponents($component);
            } elseif($component->type === 'radio') {
                $radioComponents[] = $component;
            }
        }

        if(!empty($radioComponents)) {
            $this->generateRadioComponents($radioComponents);
        }
    }

    /**
     * @param string $searchKey
     * @param bool|string $file
     * @param array|string $codeToAppend
     * @param string $filePathToUpdate
     * @return void
     */
    private function updateFile(string $searchKey, bool|string $file, array|string $codeToAppend, string $filePathToUpdate): void
    {
        preg_match($searchKey, $file, $matches, PREG_OFFSET_CAPTURE);
        $firstLinePosition = strlen($matches[0][0]) + $matches[0][1] + 1;
        $newFile = substr_replace($file, $codeToAppend, $firstLinePosition, 0);
        file_put_contents($filePathToUpdate, $newFile);
    }

    /**
     * @param array $radioComponents
     * @return void
     */
    private function generateRadioComponents(array $radioComponents): void
    {
        $file = $this->getIncludesFile('radio');

        $search = [
            '{{MAIN_LABEL}}',
        ];

        $replace = [
            $radioComponents[0]->mainLabel, //{{MAIN_LABEL}}
        ];

        $newFile = str_replace($search, $replace, $file);

        $allRadioOptionsToInclude = '';
        $optionCounter = 0;
        foreach ($radioComponents as $radioComponent) {
            if( $optionCounter == 0 ) {
                $radioOptionFile = $this->getIncludesFile('radio_option_first');
            } else {
                $radioOptionFile = $this->getIncludesFile('radio_option');
            }

            $search = [
                '{{ID}}',
                '{{NAME}}',
                '{{VALUE}}',
                '{{REQUIRED}}',
                '{{LABEL}}',
                '{{VARIABLE}}',
            ];

            $required = $radioComponents[0]->required ? "true" : "false";

            $replace = [
                $radioComponent->id, //{{ID}}
                $radioComponent->name, //{{NAME}}
                $radioComponent->value, //{{VALUE}}
                $required, //{{REQUIRED}}
                $radioComponent->label, //{{LABEL}}
                lcfirst($this->model), //{{VARIABLE}}
            ];

            $newOptionFile = str_replace($search, $replace, $radioOptionFile);
            $allRadioOptionsToInclude .= "\t\t" . $newOptionFile . "\n";
            $optionCounter++;
        }

        $searchKey = "/radio-option-generator/";
        $filePathToUpdate = base_path() . "/Modules/{$this->module}/Resources/views/{$this->kebabCase($this->model)}/includes/{$radioComponents[0]->name}.blade.php";
        $this->updateFile($searchKey, $newFile, $allRadioOptionsToInclude, $filePathToUpdate);
    }

    /**
     * @param mixed $component
     * @return void
     */
    private function generateSelectComponents(mixed $component): void
    {
        $file = $this->getIncludesFile($component->type);

        $search = [
            '{{ID}}',
            '{{NAME}}',
            '{{VALUE}}',
            '{{REQUIRED}}',
            '{{LABEL}}',
            '{{VARIABLE}}',
        ];

        $required = $component->required ? "true" : "false";

        $replace = [
            trim($component->id), //{{ID}}
            trim($component->name), //{{NAME}}
            trim($component->value), //{{VALUE}}
            $required, //{{REQUIRED}}
            trim($component->label), //{{LABEL}}
            lcfirst($this->model), //{{VARIABLE}}
        ];

        $newFile = str_replace($search, $replace, $file);

        $allOptionsToInclude = '';
        if (is_array($component->options)) {
            $optionCounter = 0;
            foreach ($component->options as $option) {
                if( $optionCounter === count($component->options) - 1) {
                    $optionFile = $this->getIncludesFile('select_option_last');
                } else {
                    $optionFile = $this->getIncludesFile('select_option');
                }

                $searchOption = [
                    '{{OPTION_LABEL}}',
                    '{{OPTION_VALUE}}',
                ];

                $replace = [
                    trim($option->label), //{{OPTION_LABEL}}
                    trim($option->value), //{{OPTION_VALUE}}
                ];

                $newOptionFile = str_replace($searchOption, $replace, $optionFile);
                $allOptionsToInclude .= $newOptionFile . "\n";
                $optionCounter++;
            }
        }

        $searchKey = "/select-option-generator/";
        $filePathToUpdate = base_path() . "/Modules/{$this->module}/Resources/views/{$this->kebabCase($this->model)}/includes/{$component->name}.blade.php";
        $this->updateFile($searchKey, $newFile, $allOptionsToInclude, $filePathToUpdate);
    }

    /**
     * @param mixed $component
     * @return void
     */
    private function generateGenericComponents(mixed $component): void
    {
        $file = $this->getIncludesFile($component->type);

        $search = [
            '{{ID}}',
            '{{NAME}}',
            '{{VALUE}}',
            '{{REQUIRED}}',
            '{{LABEL}}',
            '{{VARIABLE}}',
        ];

        $required = $component->required ? "true" : "false";

        $replace = [
            trim($component->id), //{{ID}}
            trim($component->name), //{{NAME}}
            trim($component->value), //{{VALUE}}
            $required, //{{REQUIRED}}
            trim($component->label), //{{LABEL}}
            lcfirst($this->model), //{{VARIABLE}}
        ];

        $newFile = str_replace($search, $replace, $file);

        file_put_contents(base_path() . "/Modules/{$this->module}/Resources/views/{$this->kebabCase($this->model)}/includes/{$component->name}.blade.php", $newFile);
    }

    /**
     * @param string $method
     * @param mixed $component
     * @return void
     */
    private function generateRequestCheckboxSanitize(string $method, mixed $component): void
    {
        $file = $this->getRequestFile($method);

        $requestCheckboxSanitizeStub = $this->getRequestCheckboxSanitize();

        $searchOption = [
            '{{FIELD}}',
        ];

        $replace = [
            trim($component->name), //{{FIELD}}
        ];

        $codeToAppend = str_replace($searchOption, $replace, $requestCheckboxSanitizeStub);

        $searchKey = "/module-generator/";
        $filePathToUpdate = base_path() . "/Modules/{$this->module}/Http/Requests/{$this->model}/{$method}{$this->model}Request.php";
        $this->updateFile($searchKey, $file, $codeToAppend, $filePathToUpdate);
    }
}
