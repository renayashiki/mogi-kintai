@extends('layouts.guest')

@section('title', 'メール認証')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/auth/email-verify.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
@endsection

@section('content')
    <div class="message-container">
        <h1 class="verification-message">登録していただいたメールアドレスに認証メールを送付しました。<br>メール認証を完了してください。</h1>
        <a href="http://localhost:8025" class="verify-link-button" target="_blank">認証はこちらから</a>
        <form method="POST" action="{{ route('verification.resend') }}" class="resend-form">
            @csrf
            <button type="submit" class="resend-link">認証メールを再送する</button>
        </form>
    </div>
@endsection
