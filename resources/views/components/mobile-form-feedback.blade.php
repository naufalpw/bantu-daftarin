@props(['formKey'])

<input type="hidden" name="_ui_form" value="{{ $formKey }}">
@if(old('_ui_form') === $formKey && $errors->any())
    <div id="feedback-{{ $formKey }}" class="bd-phone-only bd-phone-form-feedback" role="alert" data-phone-form-error>
        <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
