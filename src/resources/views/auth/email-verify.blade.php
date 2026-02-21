@extends('layouts.guest')

@section('title', 'メール認証')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/auth/email-verify.css') }}">
@endsection

@section('content')
    <div class="message-container">
        <h1 class="visually-hidden">メール認証</h1>
        <p class="verification-message">
            登録していただいたメールアドレスに認証メールを送付しました。<br>
            メール認証を完了してください。
        </p>
        <a href="http://localhost:8025" class="verify-link-button" target="_blank">認証はこちらから</a>
        <form method="POST" action="{{ route('verification.resend') }}" class="resend-form">
            @csrf
            <button type="submit" class="resend-link">認証メールを再送する</button>
        </form>
    </div>
@endsection
