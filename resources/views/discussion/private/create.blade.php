@extends('layouts.app')

@push('css')
    <link rel="stylesheet" href="{{ url('/css/sceditor.css') }}">
@endpush

@section('content')
<div class="container">
    <div class="card">
        <form action="{{ route('private_discussions.store', [$to->id, $to->name]) }}" method="post">
            <div class="card-body">
                <h1 class="h6">Nouveau message privé pour {{ $to->name }}</h1>
                @csrf
                {!! GoogleReCaptchaV3::renderField('create_private_discussion_id', 'create_private_discussion_action') !!}

                {!! BootForm::text('title', 'Sujet') !!}
                {!! BootForm::textarea('body', 'Message', old('body'), ['class' => 'form-control', 'style' => 'width: 100%;']) !!}
            </div>
            <div class="card-footer bg-light">
                <div class="text-right">
                    <button type="submit" class="btn btn-primary">Créer la discussion</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
    <script src="{{ url('https://cdnjs.cloudflare.com/ajax/libs/sceditor/2.1.2/sceditor.min.js') }}"></script>
    <script src="{{ url('https://cdnjs.cloudflare.com/ajax/libs/sceditor/2.1.2/formats/bbcode.js') }}"></script>
    <script>
        var textarea = document.getElementById('body');
        sceditor.create(textarea, {
	        format: 'bbcode',
            plugins: 'undo',
            emoticonsEnabled: false,
            resizeEnabled: false,
            width: '100%',
	        style: "{{ url('/css/sceditor.content.css') }}",
	        toolbar: 'bold,italic,underline,stroke|image,link|maximize,source',
       });
    </script>
@endpush