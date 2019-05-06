@push('css')
    <link rel="stylesheet" href="{{ url('https://cdnjs.cloudflare.com/ajax/libs/sceditor/2.1.2/themes/default.min.css') }}">
@endpush

    <form action="{{ route('discussions.posts.store', [$discussion->id, $discussion->slug]) }}" method="post">
        @csrf
        {!! BootForm::textarea('reply', 'Message', old('reply'), ['class' => 'form-control', 'style' => 'width: 100%;']) !!}

        <div class="text-right">
            <button type="submit" class="btn btn-primary">Répondre</button>
        </div>
    </form>

@push('js')
    <script src="{{ url('https://cdnjs.cloudflare.com/ajax/libs/sceditor/2.1.2/sceditor.min.js') }}"></script>
    <script src="{{ url('https://cdnjs.cloudflare.com/ajax/libs/sceditor/2.1.2/formats/bbcode.js') }}"></script>
    <script>
        var textarea = document.getElementById('reply');
        sceditor.create(textarea, {
	        format: 'bbcode',
            emoticonsEnabled: false,
            resizeEnabled: false,
            width: '100%',
	        style: 'https://cdnjs.cloudflare.com/ajax/libs/sceditor/2.1.2/themes/content/default.min.css',
	        toolbar: 'bold,italic,underline,stroke|image,link|maximize,source',
       });
    </script>
@endpush