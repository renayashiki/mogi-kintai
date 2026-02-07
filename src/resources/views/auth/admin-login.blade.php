@extends('layouts.guest')

@section('title', '管理者ログイン')

@section('styles')
    {{-- 一般ユーザー用ログインのCSSを流用 --}}
    <link rel="stylesheet" href="{{ asset('css/auth/login.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
@endsection

@section('content')
    <div class="auth-content">
        <h1 class="content-title">管理者ログイン</h1>
        <div class="login-error-container">
            @error('login_error')
                <p class="error-message">{{ $message }}</p>
            @enderror
        </div>
        {{-- 管理者用のPOSTルートへ送信 --}}
        <form method="POST" action="{{ route('admin.login') }}" class="login-form" novalidate>
            @csrf
            <div class="form-group">
                <label for="email" class="form-label">メールアドレス</label>
                <input type="email" id="email" name="email" class="form-input" value="{{ old('email') }}" required
                    autofocus>
                @error('email')
                    <p class="error-message">{{ $message }}</p>
                @enderror
            </div>
            <div class="form-group">
                <label for="password" class="form-label">パスワード</label>
                <input type="password" id="password" name="password" class="form-input" required>
                @error('password')
                    <p class="error-message">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="submit-btn">ログインする</button>
        </form>
    </div>
@endsection
