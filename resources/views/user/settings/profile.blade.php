@extends('layouts.app')

@section('title')
    Paramètres
@endsection

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-3 col-xl-2 mb-3">
            @include('user.settings._navigation')
        </div>
        <div class="col-lg-7 col-xl-8">
            <form method="POST" action="{{ route('user.settings.profile', $user->name) }}" enctype='multipart/form-data' id="settings">
                @method('put')
                @csrf

                <div class="card mb-3">
                    <div class="card-header">
                        Modification du profil
                    </div>
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col-auto pr-3 d-none d-md-flex">
                                <img src="{{ $user->avatar ? url('storage/avatars/' . $user->avatar) : url('/img/guest.png') }}" class="img-fluid rounded" style="max-height: 60px;">
                            </div>
                            <div class="col">
                                <div class="form-group mb-3 {{ $errors->has('avatar') ? 'is-invalid' : '' }}">
                                    <label for="avatar" class="col-form-label {{ $errors->has('avatar') ? 'text-danger' : '' }}">Photo de profil</label>
                                    <div class="custom-file {{ $errors->has('avatar') ? 'is-invalid' : '' }}">
                                        <input type="file" class="custom-file-input {{ $errors->has('avatar') ? 'is-invalid' : '' }}" id="avatar" name="avatar">
                                        <label class="custom-file-label" for="customFile" data-browse="Choisir un fichier">Aucun fichier choisi</label>
                                        @if ($errors->has('avatar'))
                                            <div class="invalid-feedback">{{ $errors->first('avatar') }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {!! BootForm::text('display_name', 'Nom d\'affichage*', old('display_name', $user->display_name)) !!}

                        @can('update shown_role')
                            {!! BootForm::text('shown_role', 'Classification', old('shown_role', $user->shown_role)) !!}
                        @endcan

                        @can('update achievements')
                            {!! BootForm::select('achievements[]', 'Succès', $achievements, old('achievements[]', $user->achievements->pluck('id')), ['class' => 'select2', 'multiple']) !!}
                        @endcan

                        @can('update roles')
                            {!! BootForm::select('role', 'Rôle', $roles, old('role', $user->roles[0]->id), ['class' => 'select2']) !!}
                        @endcan
                    </div>
                </div>

                <div class="text-right">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    $('#settings').change(function(){
        refreshForm();
    });

    $(document).ready(function(){
        refreshForm();
    });

    function refreshForm(){
        if (!$('#optin_webpush').is(':checked')) {
            $("#webpush_settings").slideUp('fast')
        } else {
            registerServiceWorker()
            $("#webpush_settings").slideDown('fast')
        }
    }

    var _registration = null;

    function registerServiceWorker() {
        return navigator.serviceWorker.register('/js/service-worker.js')
            .then(function (registration) {
                console.log('Service worker successfully registered.');
                _registration = registration;
                registration.update()
                return registration;
            })
            .catch(function (err) {
                console.error('Unable to register service worker.', err);
            });
    }

    function askPermission() {
        return new Promise(function (resolve, reject) {
            const permissionResult = Notification.requestPermission(function (result) {
                resolve(result);
            });
            if (permissionResult) {
                permissionResult.then(resolve, reject);
            }
        })
        .then(function (permissionResult) {
            if (permissionResult !== 'granted') {
                throw new Error('We weren\'t granted permission.');
            } else {
                subscribeUserToPush();
            }
        });
    }

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    function getSWRegistration() {
        var promise = new Promise(function (resolve, reject) {
            if (_registration != null) {
                resolve(_registration);
            } else {
                reject(Error("It broke"));
            }
        });
        return promise;
    }

    function subscribeUserToPush() {
        getSWRegistration()
            .then(function (registration) {
                console.log(registration);
                const subscribeOptions = {
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(
                        "{{ env('VAPID_PUBLIC_KEY') }}"
                    )
                };
                return registration.pushManager.subscribe(subscribeOptions);
            })
            .then(function (pushSubscription) {
                console.log('Received PushSubscription: ', JSON.stringify(pushSubscription));
                sendSubscriptionToBackEnd(pushSubscription);
                return pushSubscription;
            });
    }

    function sendSubscriptionToBackEnd(subscription) {
        return fetch('/api/v0/webpush/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': @json(csrf_token())
                },
                body: JSON.stringify(subscription)
            })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Bad status code from server.');
                }
                $("#enable-webpush").hide();
                $("#test-notification").show();
                return response.json();
            })
            .then(function (responseData) {
                if (!(responseData && responseData.success)) {
                    throw new Error('Bad response from server.');
                }
            });
    }

    function enableNotifications() {
        askPermission();
    }

    function testNotification(){
        _registration.showNotification('J\'suis allé à ma voiture j\'men vais !', {
            body: 'Et puis quand j\'arrive sur le truc pour rentrer sur Colmar..',
            icon: @json(url('/img/webpush/icon.png')),
            badge: @json(url('/img/webpush/badge.png')),
        });
    }
</script>
@endpush