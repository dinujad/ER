<div class="card mb-4 border-0 shadow-sm">

    @unless(empty($title) && empty($header))
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom-0 pt-4 pb-3 px-4">
            @unless(empty($title))
                <h5 class="mb-0 fw-bold text-dark">{{ $title }}</h5>
            @endunless

            {{-- To add a button on the right add box-tools class to the div in your header --}}
            @unless(empty($header))
                <div>
                    {{ $header }}
                </div>
            @endunless
        </div>
    @endunless

    <div class="card-body px-4 pb-4 pt-2">
        {{ $body }}
    </div>

    @unless(empty($footer))
        <div class="card-footer bg-light px-4 py-3 border-top-0">
            {{ $footer }}
        </div>
    @endunless
</div>
