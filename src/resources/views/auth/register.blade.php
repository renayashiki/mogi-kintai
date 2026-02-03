@extends('layouts.guest')

@section('title', '会員登録')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/auth/register.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
@endsection

@section('content')
    <div class="auth-content">
        <h1 class="content-title">会員登録</h1>
        <form method="POST" action="{{ route('register') }}" class="register-form" novalidate>
            @csrf
            <div class="form-group">
                <label for="name" class="form-label">ユーザー名</label>
                <input type="text" id="name" name="name" class="form-input" value="{{ old('name') }}" required
                    autofocus>
                @error('name')
                    <div class="error-message" style="color: red; font-size: 12px; font-weight: bold; margin-top: 3px;">
                        {{ $message }}</div>
                @enderror
            </div>
            <div class="form-group">
                <label for="email" class="form-label">メールアドレス</label>
                <input type="email" id="email" name="email" class="form-input" value="{{ old('email') }}" required>
                @error('email')
                    <div class="error-message" style="color: red; font-size: 12px; font-weight: bold; margin-top: 3px;">
                        {{ $message }}</div>
                @enderror
            </div>
            <div class="form-group">
                <label for="password" class="form-label">パスワード</label>
                <input type="password" id="password" name="password" class="form-input" required>
                @error('password')
                    <div class="error-message" style="color: red; font-size: 12px; font-weight: bold;  margin-top: 3px;">
                        {{ $message }}</div>
                @enderror
            </div>
            <div class="form-group">
                <label for="password_confirmation" class="form-label">確認用パスワード</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-input" required>
                @error('password_confirmation')
                    <div class="error-message" style="color: red; font-size: 12px; font-weight: bold; margin-top: 3px;">
                        {{ $message }}</div>
                @enderror
            </div>
            <button type="submit" class="submit-btn">登録する</button>
        </form>
        <p class="register-link-area">
            <a href="{{ route('login') }}" class="register-link">ログインはこちら</a>
        </p>
    </div>
@endsection
