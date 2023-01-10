@extends('mails/main_mail')
@section('content')
    <h1 style="margin:2px 2px 10px 2px; padding:2px 5px; font-size:16px;">@lang('mails/contact_mail.title')</h1>
    <div style="padding:5px 10px;">
        <div>@lang('mails/contact_mail.mail'): @isset($sendData['email']) {{$sendData['email']}} @endisset</div>
        <div>@lang('mails/contact_mail.name'): @isset($sendData['name']) {{$sendData['name']}} @endisset</div>
        <div>@lang('mails/contact_mail.subject'): @isset($sendData['subject']) {{$sendData['subject']}} @endisset</div>
        <div>@lang('mails/contact_mail.send_message'): @isset($sendData['send_message']) {{$sendData['send_message']}} @endisset</div>
    </div>
@endsection
