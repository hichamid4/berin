@props(['title' => 'Nothing was saved. Please correct the following and submit again:'])
@if ($errors->any())
    <div {{ $attributes->merge(['class' => 'alert alert-danger']) }} role="alert">
        <div class="d-flex align-items-start gap-2">
            <i class="bi bi-exclamation-octagon-fill mt-1"></i>
            <div>
                <strong class="d-block mb-1">{{ $title }}</strong>
                <ul class="mb-0 ps-3 small">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif
