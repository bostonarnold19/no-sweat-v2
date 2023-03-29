@extends('layouts.backend')
@section('js')
<!-- jQuery -->
<script src="{{ asset('js/lib/jquery.min.js') }}"></script>
<!-- Page JS Plugins -->
<script src="{{ asset('js/plugins/bootstrap-notify/bootstrap-notify.min.js') }}"></script>
<!-- Page JS Code -->
@include('includes.notifications')
<script type="text/javascript">
$(document).ready(function(){
    $( "#component_generate_button" ).click(function() {
        event.preventDefault();

        var textarea = document.getElementById( 'html_form' );

        const parser = new DOMParser();
        const htmlDoc = parser.parseFromString(textarea.value, 'text/html');

        try {
            let form = htmlDoc.querySelector("form");
            let elements = form.querySelectorAll("[name]");
        }
        catch(err) {
            alert('ERROR: Form field should be html format and should have form tag');
            return;
        }

        let form = htmlDoc.querySelector("form");
        let elements = form.querySelectorAll("[name]");

        let jsonData = {};

        elements.forEach(function(element) {
            jsonData[element.name] = [];
        });

        elements.forEach(function(element) {
            var data = {
                id: null,
                label: null,
                mainLabel: null,
                type: null,
                options: [],
                name: null,
                required: null,
                value: null,
            };

            if (element?.tagName?.toLowerCase() === 'textarea') {
                data.id = element?.id;
                data.type = element?.type;
                data.name = element?.name;
                data.required = element?.required;
                data.value = element?.value;
            }

            if (element?.tagName?.toLowerCase() === 'input') {
                data.id = element?.id;
                data.type = element?.type;
                data.name = element?.name;
                data.required = element?.required;
                data.value = element?.value;

                // CHECKBOX
                if(element?.type === 'hidden') {
                    var previousElement = element?.previousSibling;
                    if (
                        previousElement?.tagName?.toLowerCase() === 'input' &&
                        previousElement.type === 'checkbox'
                    ) {
                        data.type = 'checkbox';
                        var nextElement = element?.nextSibling;
                        if(nextElement?.tagName?.toLowerCase() === 'label') {
                            data.label = nextElement.textContent;
                        }
                    }
                }

                // RADIO
                if(element?.type === 'radio') {
                    var nextElement = element?.nextSibling;
                    if(nextElement?.tagName?.toLowerCase() === 'label') {
                        data.label = nextElement.textContent;
                    }
                    var firstChild = element?.parentNode.parentNode.firstChild;
                    if(firstChild?.tagName?.toLowerCase() === 'label') {
                        data.mainLabel = firstChild.textContent;
                    }
                }

                if(
                    element?.type === 'text' || 
                    element?.type === 'date' || 
                    element?.type === 'email' ||
                    element?.type === 'number' ||
                    element?.type === 'password'
                ) {
                    var nextElement = element?.nextSibling;

                    // TRY TO GET LABEL BY FIELDSET
                    if(nextElement?.tagName?.toLowerCase() === 'fieldset') {
                        var fieldsetChildElement = nextElement.childNodes[0];
                        if(fieldsetChildElement?.tagName?.toLowerCase() === 'legend') {
                            var legendChildElement = fieldsetChildElement.childNodes[0];
                            if(legendChildElement?.tagName?.toLowerCase() === 'span') {
                                data.label = legendChildElement.textContent;

                            }
                        }
                    } else {
                        var parentElement = element?.parentNode;
                        var parentPreviousElement = parentElement.previousSibling;

                        if(parentPreviousElement?.tagName?.toLowerCase() === 'label') {
                            data.label = parentPreviousElement.textContent;
                        }
                    }
                }
            }

            if (element?.tagName?.toLowerCase() === 'select') {
                data.id = element?.id;
                data.type = 'select';
                data.name = element?.name;
                data.required = element?.required;
                data.value = element?.value;

                var parentElement = element?.parentNode;
                var parentPreviousElement = parentElement.previousSibling;

                if(parentPreviousElement?.tagName?.toLowerCase() === 'label') {
                    data.label = parentPreviousElement.textContent;
                }

                for (var i = 0; i < element.length; i++){
                    var option = element.options[i];

                    var dataOption = {
                        label: null,
                        value: null,
                        disabled: null,
                        selected: null,
                    }

                    dataOption.label = option?.label;
                    dataOption.value = option?.value;
                    dataOption.selected = option?.selected;
                    dataOption.disabled = option?.disabled;

                    data.options.push(dataOption);
                }
            }

            jsonData[element.name].push(data);
        });

        $('input[name=components]').val(JSON.stringify(jsonData));

        $("#component-generator").submit();
    });

});
</script>
@endsection
@section('content')
<!-- Hero -->
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-grow-1 fs-3 fw-semibold my-1 my-sm-2">Component Generator</h1>
        </div>
    </div>
</div>
<!-- END Hero -->
<!-- Page Content -->
<div class="content">
    <!-- Your Block -->
    <!-- Striped Table -->
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">Component Generator</h3>
        </div>
        <div class="block-content">
            <div id="echo"></div>
            <form id="component-generator" method="POST" action="{{ route('component-generator.store') }}">
                @csrf
                <input type="hidden" name="model" value="{{ $model }}">
                <input type="hidden" name="module" value="{{ $module }}">
                <input type="hidden" name="components">
                <div class="mb-4">
                    <textarea rows="10" class="form-control form-control-alt" name="html_form" id="html_form" placeholder="Form"></textarea>
                </div>
                <div class="mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="ok" id="is_update_model_class" name="is_update_model_class" checked="checked">
                        <label class="form-check-label" for="is_update_model_class">Update Model Class</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="ok" id="is_update_migration_file" name="is_update_migration_file" checked="checked">
                        <label class="form-check-label" for="is_update_migration_file">Update Migration File</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="ok" id="is_create_includes" name="is_create_includes" checked="checked">
                        <label class="form-check-label" for="is_create_includes">Create Includes (fields html file)</label>
                    </div>
                </div>
                <div class="mb-4">
                    <button type="button" class="btn w-100 btn-hero btn-primary" id="component_generate_button">
                        <i class="fa fa-fw fa-sign-in-alt opacity-50 me-1"></i> Create Component
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- END Striped Table -->
    <!-- END Your Block -->
</div>
<!-- END Page Content -->
@endsection